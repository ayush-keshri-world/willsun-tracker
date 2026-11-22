CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  mobile CHAR(10) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'employee') NOT NULL DEFAULT 'employee',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_password_resets_user_created (user_id, created_at),
  CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE parties (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('doctor', 'retail') NOT NULL,
  name VARCHAR(150) NOT NULL,
  designation VARCHAR(100) NOT NULL DEFAULT '',
  clinic_name VARCHAR(150) NOT NULL DEFAULT '',
  area VARCHAR(100) NOT NULL,
  pincode CHAR(6) NOT NULL,
  mobile CHAR(10) NOT NULL,
  visit_day VARCHAR(9) NOT NULL,
  mr_id INT UNSIGNED NOT NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_parties_mr_id (mr_id),
  INDEX idx_parties_visit_day (visit_day),
  CONSTRAINT fk_parties_mr FOREIGN KEY (mr_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE visits (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  party_id INT UNSIGNED NOT NULL,
  mr_id INT UNSIGNED NOT NULL,
  visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  distance_m DECIMAL(10,2) NULL,
  location_tag ENUM('first', 'near', 'far') NOT NULL,
  status ENUM('converted', 'not_converted') NOT NULL,
  remark VARCHAR(500) NOT NULL DEFAULT '',
  photo_path VARCHAR(255) NULL,
  photo_expires_at DATETIME NULL,
  INDEX idx_visits_party_id (party_id),
  INDEX idx_visits_visited_at (visited_at),
  CONSTRAINT fk_visits_party FOREIGN KEY (party_id) REFERENCES parties(id),
  CONSTRAINT fk_visits_mr FOREIGN KEY (mr_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  party_id INT UNSIGNED NOT NULL,
  mr_id INT UNSIGNED NOT NULL,
  order_date DATE NOT NULL,
  product VARCHAR(150) NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  INDEX idx_orders_party_id (party_id),
  CONSTRAINT fk_orders_party FOREIGN KEY (party_id) REFERENCES parties(id),
  CONSTRAINT fk_orders_mr FOREIGN KEY (mr_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create the first administrator after import. Generate the hash locally with:
-- php -r "echo password_hash('choose-a-strong-password', PASSWORD_DEFAULT), PHP_EOL;"
-- INSERT INTO users (name, mobile, password_hash, role) VALUES
-- ('Administrator', '9999999999', 'PASTE_THE_GENERATED_HASH', 'admin');
