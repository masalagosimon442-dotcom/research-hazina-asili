-- HAZINA ASILI v3.0 — PostgreSQL Schema
-- For Render deployment

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'researcher' CHECK (role IN ('admin','researcher')),
    bio TEXT DEFAULT NULL,
    institution VARCHAR(200) DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    passport_document VARCHAR(255) DEFAULT NULL,
    api_key VARCHAR(64) DEFAULT NULL,
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_expires TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS organisms (
    id SERIAL PRIMARY KEY,
    kingdom VARCHAR(100) NOT NULL,
    phylum VARCHAR(100) NOT NULL,
    class VARCHAR(100) NOT NULL,
    order_name VARCHAR(100) DEFAULT NULL,
    family VARCHAR(100) DEFAULT NULL,
    genus VARCHAR(100) DEFAULT NULL,
    species VARCHAR(100) DEFAULT NULL,
    cell_type VARCHAR(20) DEFAULT NULL CHECK (cell_type IN ('eukaryotic','prokaryotic')),
    habitat VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    structure_image VARCHAR(255) DEFAULT NULL,
    scientific_name VARCHAR(200) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "references" (
    id SERIAL PRIMARY KEY,
    title VARCHAR(400) NOT NULL,
    author VARCHAR(300) NOT NULL,
    year INTEGER NOT NULL,
    citation TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS compounds (
    id SERIAL PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    formula VARCHAR(100) NOT NULL,
    molecular_weight DECIMAL(10,4) NOT NULL,
    description TEXT DEFAULT NULL,
    structure_image VARCHAR(255) DEFAULT NULL,
    organism_id INTEGER DEFAULT NULL,
    created_by INTEGER DEFAULT NULL,
    version INTEGER NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS compound_versions (
    id SERIAL PRIMARY KEY,
    compound_id INTEGER NOT NULL,
    version INTEGER NOT NULL,
    name VARCHAR(200) NOT NULL,
    formula VARCHAR(100) NOT NULL,
    molecular_weight DECIMAL(10,4) NOT NULL,
    description TEXT DEFAULT NULL,
    organism_id INTEGER DEFAULT NULL,
    changed_by INTEGER DEFAULT NULL,
    change_summary VARCHAR(500) DEFAULT NULL,
    old_values JSONB DEFAULT NULL,
    new_values JSONB DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(compound_id, version)
);

CREATE TABLE IF NOT EXISTS compound_reference (
    compound_id INTEGER NOT NULL,
    reference_id INTEGER NOT NULL,
    PRIMARY KEY (compound_id, reference_id)
);

CREATE TABLE IF NOT EXISTS researcher_insights (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    compound_id INTEGER NOT NULL,
    insight_text TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','approved','rejected')),
    admin_comment TEXT DEFAULT NULL,
    reviewed_by INTEGER DEFAULT NULL,
    reviewed_at TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS researcher_recommendations (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    compound_id INTEGER NOT NULL,
    field_to_change VARCHAR(50) NOT NULL CHECK (field_to_change IN ('name','formula','molecular_weight','description')),
    suggested_value TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','approved','rejected')),
    admin_comment TEXT DEFAULT NULL,
    reviewed_by INTEGER DEFAULT NULL,
    reviewed_at TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS approval_comments (
    id SERIAL PRIMARY KEY,
    entity_type VARCHAR(20) NOT NULL CHECK (entity_type IN ('insight','recommendation')),
    entity_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    comment TEXT NOT NULL,
    action VARCHAR(20) NOT NULL DEFAULT 'comment' CHECK (action IN ('comment','approve','reject')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS activity_log (
    id SERIAL PRIMARY KEY,
    user_id INTEGER DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) DEFAULT NULL,
    entity_id INTEGER DEFAULT NULL,
    old_values JSONB DEFAULT NULL,
    new_values JSONB DEFAULT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(300) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'info',
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(400) DEFAULT NULL,
    is_read SMALLINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS error_log (
    id SERIAL PRIMARY KEY,
    level VARCHAR(20) NOT NULL DEFAULT 'notice' CHECK (level IN ('notice','warning','critical')),
    message TEXT NOT NULL,
    file VARCHAR(400) DEFAULT NULL,
    line INTEGER DEFAULT NULL,
    trace TEXT DEFAULT NULL,
    user_id INTEGER DEFAULT NULL,
    url VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS login_attempts (
    id SERIAL PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

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

CREATE TABLE IF NOT EXISTS external_searches (
    id SERIAL PRIMARY KEY,
    user_id INTEGER DEFAULT NULL,
    query VARCHAR(200) NOT NULL,
    source VARCHAR(50) NOT NULL,
    results_count INTEGER DEFAULT 0,
    searched_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_searches_date ON external_searches(searched_at);

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

-- Seed data
INSERT INTO users (name, email, password, role, institution, created_at) VALUES
('System Admin', 'admin@hazina-asili.com', '$2y$12$LN1Rh.LjDDT9TuO5RhGwOeDQhMqx3bRwFMGFhJXXMfYs3MnCKMQWi', 'admin', 'HAZINA ASILI', NOW()),
('Dr. Jane Smith', 'researcher@hazina-asili.com', '$2y$12$LN1Rh.LjDDT9TuO5RhGwOeDQhMqx3bRwFMGFhJXXMfYs3MnCKMQWi', 'researcher', 'University of Dar es Salaam', NOW());

INSERT INTO organisms (kingdom, phylum, class, scientific_name) VALUES
('Plantae', 'Tracheophyta', 'Magnoliopsida', 'Camellia sinensis'),
('Plantae', 'Tracheophyta', 'Magnoliopsida', 'Curcuma longa'),
('Plantae', 'Tracheophyta', 'Magnoliopsida', 'Allium sativum'),
('Plantae', 'Tracheophyta', 'Magnoliopsida', 'Zingiber officinale'),
('Fungi', 'Ascomycota', 'Eurotiomycetes', 'Penicillium chrysogenum'),
('Bacteria', 'Actinobacteria', 'Actinomycetia', 'Streptomyces griseus');

INSERT INTO "references" (title, author, year, citation) VALUES
('Quercetin: A Versatile Flavonoid', 'Boots, A.W., Haenen, G.R., Bast, A.', 2008, 'Boots AW et al. Health effects of quercetin. Eur J Pharmacol. 2008.'),
('Curcumin: Biological and Medicinal Properties', 'Aggarwal, B.B., Harikumar, K.B.', 2009, 'Aggarwal BB et al. Potential therapeutic effects of curcumin. 2009.'),
('Allicin: Chemistry and Biological Properties', 'Borlinghaus, J. et al.', 2014, 'Borlinghaus J et al. Allicin properties. Molecules. 2014.'),
('Penicillin: Discovery and Development', 'Fleming, A.', 1929, 'Fleming A. On the antibacterial action of Penicillium. 1929.');

INSERT INTO compounds (name, formula, molecular_weight, description, organism_id, version) VALUES
('Quercetin', 'C15H10O7', 302.2357, 'A plant flavonoid with antioxidant and anti-inflammatory properties.', 1, 1),
('Curcumin', 'C21H20O6', 368.3799, 'The principal curcuminoid of turmeric with anticancer properties.', 2, 1),
('Allicin', 'C6H10OS2', 162.2700, 'An organosulfur compound from garlic with health benefits.', 3, 1),
('Gingerol', 'C17H26O4', 294.3800, 'Active constituent of ginger with anti-nausea effects.', 4, 1),
('Penicillin G', 'C16H18N2O4S', 334.3900, 'First widely used antibiotic from Penicillium fungi.', 5, 1),
('Streptomycin', 'C21H39N7O12', 581.5700, 'Aminoglycoside antibiotic used to treat tuberculosis.', 6, 1),
('Resveratrol', 'C14H12O3', 228.2440, 'Stilbenoid from red grapes with cardiovascular benefits.', NULL, 1),
('Caffeine', 'C8H10N4O2', 194.1900, 'Purine alkaloid from coffee acting as CNS stimulant.', 1, 1);

INSERT INTO compound_reference (compound_id, reference_id) VALUES
(1, 1), (2, 2), (3, 3), (5, 4);
