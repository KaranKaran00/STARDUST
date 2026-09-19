-- Stardust: Startup Pitchdeck Reviewer Platform
-- Semester 5 Project — schema.sql
-- Import with: mysql -u root -p < schema.sql

DROP DATABASE IF EXISTS stardust_db;
CREATE DATABASE stardust_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stardust_db;

-- ---------------------------------------------------------------
-- Founders (startup side)
-- ---------------------------------------------------------------
CREATE TABLE founders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Investors (admin / reviewer side)
-- ---------------------------------------------------------------
CREATE TABLE investors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    firm_name VARCHAR(150) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Investor portfolio preferences, used for the admin filter panel
-- and for the Python match-score script.
CREATE TABLE investor_preferences (
    investor_id INT PRIMARY KEY,
    industries VARCHAR(255) DEFAULT NULL,       -- comma separated
    funding_stages VARCHAR(255) DEFAULT NULL,   -- comma separated
    business_models VARCHAR(255) DEFAULT NULL,  -- comma separated
    ticket_size_min INT DEFAULT 0,
    ticket_size_max INT DEFAULT 0,
    notes TEXT,
    FOREIGN KEY (investor_id) REFERENCES investors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Pitches submitted by founders
-- ---------------------------------------------------------------
CREATE TABLE pitches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    founder_id INT NOT NULL,
    startup_name VARCHAR(150) NOT NULL,
    tagline VARCHAR(200) DEFAULT NULL,
    industry VARCHAR(80) NOT NULL,
    funding_stage VARCHAR(60) NOT NULL,
    business_model VARCHAR(60) NOT NULL,
    funding_ask INT NOT NULL DEFAULT 0,
    team_size INT DEFAULT 1,
    website VARCHAR(200) DEFAULT NULL,
    description TEXT NOT NULL,
    problem_statement TEXT,
    traction TEXT,
    deck_filename VARCHAR(255) DEFAULT NULL,
    deck_text_excerpt TEXT,              -- filled by python/analyze_deck.py
    deck_keywords VARCHAR(500) DEFAULT NULL,  -- filled by python/analyze_deck.py
    status ENUM('pending','in_review','shortlisted','rejected') NOT NULL DEFAULT 'pending',
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (founder_id) REFERENCES founders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Internal investor notes on a pitch (admin flexibility)
-- ---------------------------------------------------------------
CREATE TABLE investor_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pitch_id INT NOT NULL,
    investor_id INT NOT NULL,
    note TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pitch_id) REFERENCES pitches(id) ON DELETE CASCADE,
    FOREIGN KEY (investor_id) REFERENCES investors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Meetings requested/scheduled by investors with founders
-- ---------------------------------------------------------------
CREATE TABLE meetings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pitch_id INT NOT NULL,
    investor_id INT NOT NULL,
    founder_id INT NOT NULL,
    proposed_datetime DATETIME NOT NULL,
    message TEXT,
    status ENUM('pending','confirmed','declined') NOT NULL DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pitch_id) REFERENCES pitches(id) ON DELETE CASCADE,
    FOREIGN KEY (investor_id) REFERENCES investors(id) ON DELETE CASCADE,
    FOREIGN KEY (founder_id) REFERENCES founders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Seed data: one demo investor so the admin panel is testable
-- immediately. Password is "investor123" (bcrypt hash below).
-- ---------------------------------------------------------------
INSERT INTO investors (full_name, email, password_hash, firm_name) VALUES
('Meera Anand', 'investor@stardust.demo', '$2y$10$oPCRduRJYM9Lmaq.F4HVrOPASKaQe2hWPFtOdzUs84GCzikXub2S.', 'Northwind Capital');

INSERT INTO investor_preferences (investor_id, industries, funding_stages, business_models, ticket_size_min, ticket_size_max, notes) VALUES
(1, 'SaaS,Fintech,HealthTech', 'Seed,Series A', 'Subscription,Marketplace', 500000, 5000000, 'Prefers founder-led teams with early revenue traction.');
