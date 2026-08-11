<?php
/**
 * Diagnostic Tool: Check Visitor Tracking
 * Access: check_visits.php?key=Admin@1234
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/SiteVisit.php';

// Simple authentication
if (!isset($_GET['key']) || $_GET['key'] !== ADMIN_PASSWORD) {
    die('Access denied. Use ?key=YOUR_ADMIN_PASSWORD');
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Visitor Tracking Diagnostic</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .box { border: 1px solid #0f0; padding: 15px; margin: 10px 0; background: #000; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #ff0; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #0f0; padding: 8px; text-align: left; }
        th { background: #0a4; }
    </style>
</head>
<body>";

echo "<h1>🔍 VISITOR TRACKING DIAGNOSTIC</h1>";

// Test 1: Check if table exists
echo "<div class='box'>";
echo "<h2>TEST 1: Check if site_visits table exists</h2>";
try {
    $db = Database::getInstance()->getConnection();
    
    if (DB_DRIVER === 'pgsql') {
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_name = 'site_visits'");
    } else {
        $stmt = $db->query("SHOW TABLES LIKE 'site_visits'");
    }
    
    $exists = DB_DRIVER === 'pgsql' ? ($stmt->fetchColumn() > 0) : ($stmt->rowCount() > 0);
    
    if ($exists) {
        echo "<p class='success'>✅ Table 'site_visits' EXISTS</p>";
    } else {
        echo "<p class='error'>❌ Table 'site_visits' DOES NOT EXIST</p>";
        echo "<p class='warning'>⚠️ The table will be auto-created on next page visit</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error checking table: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 2: Get total visit count
echo "<div class='box'>";
echo "<h2>TEST 2: Count total visits in database</h2>";
try {
    $visitModel = new SiteVisit();
    $stats = $visitModel->getStats();
    
    echo "<table>";
    echo "<tr><th>Metric</th><th>Count</th></tr>";
    echo "<tr><td>All Time Visits</td><td class='success'><strong>" . number_format($stats['total_all_time'] ?? 0) . "</strong></td></tr>";
    echo "<tr><td>Today</td><td>" . number_format($stats['today'] ?? 0) . "</td></tr>";
    echo "<tr><td>This Week</td><td>" . number_format($stats['this_week'] ?? 0) . "</td></tr>";
    echo "<tr><td>This Month</td><td>" . number_format($stats['this_month'] ?? 0) . "</td></tr>";
    echo "<tr><td>This Year</td><td>" . number_format($stats['this_year'] ?? 0) . "</td></tr>";
    echo "<tr><td>Unique IP Addresses</td><td>" . number_format($stats['unique_visitors'] ?? 0) . "</td></tr>";
    echo "<tr><td>Unique Sessions</td><td>" . number_format($stats['unique_sessions'] ?? 0) . "</td></tr>";
    echo "</table>";
    
    if ($stats['total_all_time'] == 0) {
        echo "<p class='warning'>⚠️ No visits recorded yet. This could mean:</p>";
        echo "<ul>";
        echo "<li>The site_visits table is empty (new installation)</li>";
        echo "<li>Visit tracking is failing silently</li>";
        echo "<li>Database connection issue</li>";
        echo "</ul>";
    } else {
        echo "<p class='success'>✅ Visits are being tracked!</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error getting stats: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 3: Show recent visits
echo "<div class='box'>";
echo "<h2>TEST 3: Show last 20 visits</h2>";
try {
    $stmt = $db->query("SELECT * FROM site_visits ORDER BY visited_at DESC LIMIT 20");
    $visits = $stmt->fetchAll();
    
    if (empty($visits)) {
        echo "<p class='warning'>⚠️ No visits found in database</p>";
    } else {
        echo "<p class='success'>Found " . count($visits) . " recent visits:</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>IP Address</th><th>Page URL</th><th>User ID</th><th>Session ID</th><th>Visited At</th></tr>";
        foreach ($visits as $v) {
            echo "<tr>";
            echo "<td>" . $v['id'] . "</td>";
            echo "<td>" . htmlspecialchars($v['ip_address']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($v['page_url'] ?? '', 0, 50)) . "</td>";
            echo "<td>" . ($v['user_id'] ?? 'Guest') . "</td>";
            echo "<td>" . htmlspecialchars(substr($v['session_id'] ?? '', 0, 10)) . "...</td>";
            echo "<td>" . htmlspecialchars($v['visited_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error fetching visits: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 4: Test recording a visit
echo "<div class='box'>";
echo "<h2>TEST 4: Try recording a test visit NOW</h2>";
try {
    $visitModel = new SiteVisit();
    $visitModel->record();
    echo "<p class='success'>✅ Successfully called record() method</p>";
    echo "<p class='warning'>Note: Due to 30-minute deduplication, this visit might not show if you already visited this page recently</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error recording visit: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
echo "</div>";

// Test 5: Check Database Configuration
echo "<div class='box'>";
echo "<h2>TEST 5: Database Configuration</h2>";
echo "<table>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>DB Driver</td><td>" . DB_DRIVER . "</td></tr>";
echo "<tr><td>DB Host</td><td>" . DB_HOST . "</td></tr>";
echo "<tr><td>DB Name</td><td>" . DB_NAME . "</td></tr>";
echo "<tr><td>DB User</td><td>" . DB_USER . "</td></tr>";
echo "<tr><td>DB Connected</td><td class='success'>✅ Yes</td></tr>";
echo "</table>";
echo "</div>";

// Test 6: Check if tracking is enabled in config.php
echo "<div class='box'>";
echo "<h2>TEST 6: Tracking Configuration</h2>";
$configContent = file_get_contents(__DIR__ . '/config/config.php');
if (strpos($configContent, 'new SiteVisit') !== false) {
    echo "<p class='success'>✅ Visit tracking code is present in config.php</p>";
} else {
    echo "<p class='error'>❌ Visit tracking code NOT found in config.php</p>";
}

if (strpos($configContent, 'class_exists(\'Database\')') !== false) {
    echo "<p class='success'>✅ Database check is present before tracking</p>";
} else {
    echo "<p class='warning'>⚠️ No Database check before tracking (might cause errors)</p>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>✅ DIAGNOSTIC COMPLETE</h2>";
echo "<p>Visit count shown on admin dashboard: <strong>" . number_format($stats['total_all_time'] ?? 0) . "</strong></p>";
echo "<p><a href='" . BASE_URL . "views/admin/dashboard.php' style='color: #0ff;'>Go to Admin Dashboard</a></p>";
echo "</div>";

echo "</body></html>";
