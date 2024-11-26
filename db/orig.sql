

-- Users and Recorder Codes:

-- - A user signs up for an account but isn't automatically a recorder. The users only become 
-- recorders if they have a valid recorder code which is inputted when signing up to be a recorder.


-- 1) When a new recorder signs up, they'll provide a recorder code.
-- 2) The code will check the recorder_codes table to validate the code.
-- 3) If valid, a new user is created in the users table with is_recorder set to TRUE.
-- 4) The recorder_codes table is updated to mark the code as used and link it to the new user.

-- Users and Lost Items:
-- 1) When a user reports a lost item, a new entry is created in the lost_items table.
-- 2) The user_id in lost_items links back to the users table, identifying who reported the item.

-- Users and Found Items:
-- 1) Only recorders (users with is_recorder set to TRUE) can add found items.
-- 2) When a recorder adds a found item, a new entry is created in the found_items table.
-- 3) The recorder_id in found_items links back to the users table, identifying which recorder added the item.

-- Lost Items, Found Items, and Matches:

-- 1) When the system attempts to match lost and found items, it creates entries in the matches table.
-- 2) Each match links a lost item (lost_item_id) with a potential found item (found_item_id).
-- 3) The user_id in the matches table refers to the user who reported the lost item.






-- Users Table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    is_recorder BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Recorder Codes Table
CREATE TABLE recorder_codes (
    code_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(255) UNIQUE NOT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Lost Items Table
CREATE TABLE lost_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_type VARCHAR(100) NOT NULL,
    brand VARCHAR(100),
    color VARCHAR(50),
    additional_info TEXT,
    lost_time DATETIME NOT NULL,
    status ENUM('lost', 'found', 'claimed') DEFAULT 'lost',
    user_id INT,
    recorder_id INT,
    image_url VARCHAR(255),
    image_public_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (recorder_id) REFERENCES users(user_id)
);

-- Found Items Table
CREATE TABLE found_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_type VARCHAR(100) NOT NULL,
    brand VARCHAR(100),
    color VARCHAR(50),
    additional_info TEXT,
    found_time DATETIME NOT NULL,
    status ENUM('available', 'claimed') DEFAULT 'available',
    recorder_id INT,
    image_url VARCHAR(255),
    image_public_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recorder_id) REFERENCES users(user_id)
);

-- Item Locations Junction Table
CREATE TABLE item_locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    item_type ENUM('lost', 'found') NOT NULL,
    location VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_item_type (item_type),
    INDEX idx_location (location)
);

-- Matches Table
CREATE TABLE matches (
    match_id INT AUTO_INCREMENT PRIMARY KEY,
    lost_item_id INT,
    found_item_id INT,
    user_id INT,
    match_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (lost_item_id) REFERENCES lost_items(item_id),
    FOREIGN KEY (found_item_id) REFERENCES found_items(item_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- User Submission Cooldown Table
CREATE TABLE submission_cooldowns (
    cooldown_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    last_submission TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Locations Reference Table
CREATE TABLE locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category ENUM('Academic & Research', 'Student Life', 'Student Housing', 'Operations & Administration') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert test recorder codes
INSERT INTO recorder_codes (code, user_id) VALUES 
('test1', NULL),
('test2', NULL),
('test3', NULL);

-- Insert predefined locations
INSERT INTO locations (name, category) VALUES
-- Academic & Research
('Amos Eaton Hall', 'Academic & Research'),
('Carnegie Building', 'Academic & Research'),
('Center for Biotechnology and Interdisciplinary Studies (CBIS)', 'Academic & Research'),
('Cogswell Laboratory', 'Academic & Research'),
('Darrin Communications Center', 'Academic & Research'),
('Empire State Hall', 'Academic & Research'),
('Experimental Media & Performing Arts Center (EMPAC)', 'Academic & Research'),
('Folsom Library', 'Academic & Research'),
('Greene Building', 'Academic & Research'),
('Jonsson Engineering Center (JEC)', 'Academic & Research'),
('Jonsson-Rowland Science Center', 'Academic & Research'),
('Lally Hall', 'Academic & Research'),
('LINAC Facility (Gaerttner Laboratory)', 'Academic & Research'),
('Low Center for Industrial Innovation (CII)', 'Academic & Research'),
('Materials Research Center (MRC)', 'Academic & Research'),
('Pittsburgh Building', 'Academic & Research'),
('Ricketts Building', 'Academic & Research'),
('Russell Sage Laboratory', 'Academic & Research'),
('Walker Laboratory', 'Academic & Research'),
('West Hall', 'Academic & Research'),
('Winslow Building', 'Academic & Research'),

-- Student Life
('87 Gymnasium', 'Student Life'),
('Academy Hall', 'Student Life'),
('Alumni Sports & Recreation Center', 'Student Life'),
('Chapel + Cultural Center', 'Student Life'),
('Commons Dining Hall', 'Student Life'),
('East Campus Athletic Village Arena (ECAV)', 'Student Life'),
('East Campus Athletic Village Stadium', 'Student Life'),
('Houston Field House', 'Student Life'),
('Mueller Center', 'Student Life'),
('Playhouse', 'Student Life'),
('Radio Club W2SZ', 'Student Life'),
('Rensselaer Union', 'Student Life'),
('Robison Swimming Pool', 'Student Life'),
('Russell Sage Dining Hall', 'Student Life'),

-- Student Housing
('Barton Hall', 'Student Housing'),
('Blitman Commons', 'Student Housing'),
('Bray Hall', 'Student Housing'),
('Bryckwyck', 'Student Housing'),
('Burdett Avenue Residence Hall', 'Student Housing'),
('Colonie Apartments', 'Student Housing'),
('Hall Hall', 'Student Housing'),
('Nason Hall', 'Student Housing'),
('North Hall', 'Student Housing'),
('Nugent Hall', 'Student Housing'),
('Polytechnic Residence Commons', 'Student Housing'),
('Quadrangle Complex', 'Student Housing'),
('Rensselaer Apartment Housing Project A', 'Student Housing'),
('Rensselaer Apartment Housing Project B', 'Student Housing'),
('Rousseau Apartments', 'Student Housing'),
('Sharp Hall', 'Student Housing'),
('Stacwyck Apartments', 'Student Housing'),
('Warren Hall', 'Student Housing'),
('Williams Apartments', 'Student Housing'),

-- Operations & Administration
('2021 15th Street', 'Operations & Administration'),
('2144 Burdett Avenue', 'Operations & Administration'),
('41 Ninth Street', 'Operations & Administration'),
('Admissions', 'Operations & Administration'),
('Alumni House (Heffner)', 'Operations & Administration'),
('Blaw-Knox 1 & 2', 'Operations & Administration'),
('Boiler House, 11th Street', 'Operations & Administration'),
('Boiler House, Sage Avenue', 'Operations & Administration'),
('Graduate Education', 'Operations & Administration'),
('Greenhouses and Grounds Barn', 'Operations & Administration'),
('H Building', 'Operations & Administration'),
('J Building', 'Operations & Administration'),
('Patroon Manor', 'Operations & Administration'),
('Public Safety', 'Operations & Administration'),
('Service Building', 'Operations & Administration'),
('Troy Building', 'Operations & Administration'),
('Voorhees Computing Center (VCC)', 'Operations & Administration');

DROP TABLE IF EXISTS item_locations;

CREATE TABLE item_locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    item_type ENUM('lost', 'found') NOT NULL,
    location VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lost_item_loc FOREIGN KEY (item_id)
        REFERENCES lost_items(item_id)
        ON DELETE CASCADE,
    INDEX idx_item_type (item_type),
    INDEX idx_location (location)
);