<?php
/**
 * Migration Script: Add Visitor Tracking Tables
 * This adds the missing site_visits, external_searches, and compound_cache tables
 * Access: add_visitor_tracking.php?key=Admin@1234
 */
require_once __DIR__ . '/config/config.php';

// Simple authentication
if (!isset($_GET['key']) || $_GET['key'] !== ADMIN_PASSWORD) {
    die('Access denied. Use ?key=YOUR_ADMIN_PASSWORD');
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Add Visitor Tracking - Migration</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container py-5'>
    <div class='card shadow'>
        <div class='card-header bg-primary text-white'>
            <h2 class='mb-0'>🔧 Database Migration: Add Visitor Tracking</h2>
        </div>
        <div class='card-body'>";

try {
    $db = Database::getInstance()->getConnection();
    $success = true;
    $errors = [];
    
    echo "<div class='alert alert-info'><strong>Starting migration...</strong></div>";
    
    // SQL for PostgreSQL
    $sql = "
    -- 1. Site visits tracking table
    CREATE TABLE IF NOT EXISTS site_visits (
        id SERIAL PRIMARY KEY,
        user_id INTEGER DEFAULT NULL,
        ip_address VARCHAR(45) NOT NULL,
        page_url VARCHAR(500) DEFAULT NULL,
        user_agent VARCHAR(300) DEFAULT NULL,
        session_id VARCHAR(64) DEFAULT NULL,
        visited_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );
    
    CREATE INDEX IF NOT EXISTS idx_visits_date ON site_visits(visited_at);
    CREATE INDEX IF NOT EXISTS idx_visits_user ON site_visits(user_id);
    CREATE INDEX IF NOT EXISTS idx_visits_session ON site_visits(session_id);
    
    -- 2. External searches tracking
    CREATE TABLE IF NOT EXISTS external_searches (
        id SERIAL PRIMARY KEY,
        user_id INTEGER DEFAULT NULL,
        query VARCHAR(200) NOT NULL,
        source VARCHAR(50) NOT NULL,
        results_count INTEGER DEFAULT 0,
        searched_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );
    
    CREATE INDEX IF NOT EXISTS idx_searches_date ON external_searches(searched_at);
    
    -- 3. Compound cache for external API results
    CREATE TABLE IF NOT EXISTS compound_cache (
        id SERIAL PRIMARY KEY,
        compound_id INTEGER NOT NULL,
        source VARCHAR(50) NOT NULL,
        cache_key VARCHAR(200) NOT NULL,
        cache_data JSONB DEFAULT NULL,
        expires_at TIMESTAMP DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(compound_id, source, cache_key)
    );
    ";
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $executed = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || substr($statement, 0, 2) === '--') continue;
        
        try {
            $db->exec($statement);
            $executed++;
            
            // Get table/index name for display
            if (preg_match('/CREATE TABLE.*?(\w+)\s*\(/i', $statement, $m)) {
                echo "<div class='alert alert-success'>✅ Table <strong>{$m[1]}</strong> created/verified</div>";
            } elseif (preg_match('/CREATE INDEX.*?(\w+)\s+ON/i', $statement, $m)) {
                echo "<div class='alert alert-success'>✅ Index <strong>{$m[1]}</strong> created/verified</div>";
            }
        } catch (PDOException $e) {
            $errors[] = $e->getMessage();
            echo "<div class='alert alert-warning'>⚠️ " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    
    echo "<div class='alert alert-success mt-3'><strong>✅ Migration completed!</strong><br>Executed {$executed} statements.</div>";
    
    // Test if tables exist
    echo "<h5 class='mt-4'>Verification:</h5>";
    $tables = ['site_visits', 'external_searches', 'compound_cache'];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_name = '$table'");
        $exists = $stmt->fetchColumn() > 0;
        
        if ($exists) {
            // Count rows
            $countStmt = $db->query("SELECT COUNT(*) FROM $table");
            $count = $countStmt->fetchColumn();
            echo "<div class='alert alert-success'>✅ Table <strong>$table</strong> exists (contains $count rows)</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Table <strong>$table</strong> does NOT exist</div>";
            $success = false;
        }
    }
    
    if ($success) {
        echo "<div class='alert alert-primary mt-4'>";
        echo "<h5>🎉 All Done!</h5>";
        echo "<p>Visitor tracking is now fully configured. The system will automatically track:</p>";
        echo "<ul>";
        echo "<li><strong>Site Visits</strong> - Every page view (deduplicated by 30 min)</li>";
        echo "<li><strong>External Searches</strong> - PubChem, NCBI, ChEBI searches</li>";
        echo "<li><strong>API Cache</strong> - Cached external API results</li>";
        echo "</ul>";
        echo "<p><a href='" . BASE_URL . "check_visits.php?key=" . ADMIN_PASSWORD . "' class='btn btn-info'>Run Diagnostic</a> ";
        echo "<a href='" . BASE_URL . "views/admin/dashboard.php' class='btn btn-primary'>View Dashboard</a></p>";
        echo "</div>";
        
        // Delete this script for security
        echo "<div class='alert alert-warning mt-3'>";
        echo "<strong>⚠️ Security Notice:</strong> For security, delete this file after migration:<br>";
        echo "<code>delete: add_visitor_tracking.php</code>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h5>❌ Migration Failed</h5>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "        </div>
    </div>
</div>
</body>
</html>";
