-- StableCare MySQL schema (alternativa Laravel migrācijām)
-- Izmanto šo SQL failu VAI php artisan migrate; vienu datubāzi inicializē tikai vienā veidā.
CREATE DATABASE IF NOT EXISTS stablecare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stablecare;

CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(50) NOT NULL UNIQUE,
  email_verified_at TIMESTAMP NULL,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(24) NOT NULL DEFAULT 'supervisor',
  phone VARCHAR(40) NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  remember_token VARCHAR(100) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX users_role_index(role), INDEX users_is_active_index(is_active)
) ENGINE=InnoDB;

CREATE TABLE stables (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  address VARCHAR(150) NULL,
  subscription_type VARCHAR(30) NOT NULL DEFAULT 'free',
  invite_code VARCHAR(64) NOT NULL UNIQUE,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  CONSTRAINT stables_created_by_fk FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE users
  ADD COLUMN first_name VARCHAR(30) NULL AFTER name,
  ADD COLUMN last_name VARCHAR(30) NULL AFTER first_name,
  ADD COLUMN stable_id BIGINT UNSIGNED NULL AFTER id,
  ADD INDEX users_stable_id_index(stable_id),
  ADD CONSTRAINT users_stable_fk FOREIGN KEY (stable_id) REFERENCES stables(id) ON DELETE SET NULL;

CREATE TABLE password_reset_tokens (
  email VARCHAR(255) PRIMARY KEY,
  token VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE sessions (
  id VARCHAR(255) PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  payload LONGTEXT NOT NULL,
  last_activity INT NOT NULL,
  INDEX sessions_user_id_index(user_id), INDEX sessions_last_activity_index(last_activity),
  CONSTRAINT sessions_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE horses (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  stable_id BIGINT UNSIGNED NULL,
  name VARCHAR(30) NOT NULL,
  birth_date DATE NULL,
  breed VARCHAR(50) NULL,
  sex VARCHAR(20) NULL,
  colour VARCHAR(80) NULL,
  chip_number VARCHAR(80) NULL UNIQUE,
  owner_name VARCHAR(100) NULL,
  weight_kg DECIMAL(7,2) NULL,
  status VARCHAR(24) NOT NULL DEFAULT 'active',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  INDEX horses_status_index(status),
  CONSTRAINT horses_created_by_fk FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT horses_stable_fk FOREIGN KEY (stable_id) REFERENCES stables(id) ON DELETE SET NULL,
  INDEX horses_stable_id_index(stable_id)
) ENGINE=InnoDB;

CREATE TABLE inventory_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  stable_id BIGINT UNSIGNED NULL,
  name VARCHAR(140) NOT NULL,
  category VARCHAR(80) NOT NULL DEFAULT 'Barība',
  quantity DECIMAL(12,3) NOT NULL DEFAULT 0,
  unit VARCHAR(20) NOT NULL DEFAULT 'kg',
  minimum_quantity DECIMAL(12,3) NOT NULL DEFAULT 0,
  location VARCHAR(140) NULL,
  expires_at DATE NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  CONSTRAINT inventory_items_created_by_fk FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT inventory_items_stable_fk FOREIGN KEY (stable_id) REFERENCES stables(id) ON DELETE SET NULL,
  UNIQUE KEY inventory_items_stable_name_unique(stable_id, name),
  INDEX inventory_items_stable_id_index(stable_id)
) ENGINE=InnoDB;

CREATE TABLE feedings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  horse_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL DEFAULT 'Barošana',
  inventory_item_id BIGINT UNSIGNED NULL,
  amount DECIMAL(10,3) NOT NULL,
  feed_time TIME NOT NULL DEFAULT '08:00:00',
  recurrence VARCHAR(24) NOT NULL DEFAULT 'daily',
  weekdays JSON NULL,
  month_ranges VARCHAR(100) NULL,
  start_date DATE NOT NULL,
  end_date DATE NULL,
  active BOOLEAN NOT NULL DEFAULT TRUE,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  INDEX feedings_active_index(active),
  CONSTRAINT feedings_horse_fk FOREIGN KEY (horse_id) REFERENCES horses(id) ON DELETE CASCADE,
  CONSTRAINT feedings_inventory_fk FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE SET NULL,
  CONSTRAINT feedings_created_by_fk FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE tasks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  task_type VARCHAR(40) NOT NULL DEFAULT 'other',
  horse_id BIGINT UNSIGNED NULL,
  assigned_to BIGINT UNSIGNED NULL,
  feeding_id BIGINT UNSIGNED NULL UNIQUE,
  start_date DATE NOT NULL,
  task_time TIME NULL,
  priority VARCHAR(20) NOT NULL DEFAULT 'normal',
  recurrence VARCHAR(24) NOT NULL DEFAULT 'once',
  weekdays JSON NULL,
  month_ranges VARCHAR(100) NULL,
  end_date DATE NULL,
  inventory_item_id BIGINT UNSIGNED NULL,
  consumption DECIMAL(10,3) NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  INDEX tasks_task_type_index(task_type), INDEX tasks_start_date_index(start_date), INDEX tasks_priority_index(priority),
  CONSTRAINT tasks_horse_fk FOREIGN KEY (horse_id) REFERENCES horses(id) ON DELETE SET NULL,
  CONSTRAINT tasks_assigned_to_fk FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT tasks_feeding_fk FOREIGN KEY (feeding_id) REFERENCES feedings(id) ON DELETE CASCADE,
  CONSTRAINT tasks_inventory_fk FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE SET NULL,
  CONSTRAINT tasks_created_by_fk FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE task_completions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  task_id BIGINT UNSIGNED NOT NULL,
  occurrence_date DATE NOT NULL,
  completed_by BIGINT UNSIGNED NULL,
  notes VARCHAR(500) NULL,
  completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY task_completions_unique(task_id, occurrence_date), INDEX task_completions_occurrence_date_index(occurrence_date),
  CONSTRAINT task_completions_task_fk FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  CONSTRAINT task_completions_user_fk FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE inventory_movements (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  inventory_item_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  quantity_delta DECIMAL(12,3) NOT NULL,
  reason VARCHAR(100) NOT NULL,
  reference_key VARCHAR(180) NULL UNIQUE,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  INDEX inventory_movements_item_created_index(inventory_item_id, created_at),
  CONSTRAINT inventory_movements_item_fk FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
  CONSTRAINT inventory_movements_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE health_records (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  horse_id BIGINT UNSIGNED NOT NULL,
  veterinarian_id BIGINT UNSIGNED NULL,
  record_type VARCHAR(60) NOT NULL DEFAULT 'Apskate',
  record_date DATE NOT NULL,
  title VARCHAR(180) NOT NULL,
  diagnosis VARCHAR(180) NULL,
  notes TEXT NULL,
  next_date DATE NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  INDEX health_records_record_type_index(record_type), INDEX health_records_record_date_index(record_date), INDEX health_records_next_date_index(next_date),
  CONSTRAINT health_records_horse_fk FOREIGN KEY (horse_id) REFERENCES horses(id) ON DELETE CASCADE,
  CONSTRAINT health_records_vet_fk FOREIGN KEY (veterinarian_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE trainings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  horse_id BIGINT UNSIGNED NOT NULL,
  trainer_id BIGINT UNSIGNED NULL,
  training_date DATE NOT NULL,
  training_time TIME NULL,
  training_type VARCHAR(100) NOT NULL,
  duration_minutes SMALLINT UNSIGNED NULL,
  status VARCHAR(24) NOT NULL DEFAULT 'planned',
  notes TEXT NULL,
  results TEXT NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  INDEX trainings_training_date_index(training_date), INDEX trainings_status_index(status),
  CONSTRAINT trainings_horse_fk FOREIGN KEY (horse_id) REFERENCES horses(id) ON DELETE CASCADE,
  CONSTRAINT trainings_trainer_fk FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE horse_documents (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  horse_id BIGINT UNSIGNED NOT NULL,
  uploaded_by BIGINT UNSIGNED NULL,
  title VARCHAR(150) NOT NULL,
  document_type VARCHAR(50) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NULL,
  file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  INDEX horse_documents_horse_type_index(horse_id, document_type),
  CONSTRAINT horse_documents_horse_fk FOREIGN KEY (horse_id) REFERENCES horses(id) ON DELETE CASCADE,
  CONSTRAINT horse_documents_uploader_fk FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE horse_notes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  horse_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  body VARCHAR(500) NOT NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  INDEX horse_notes_horse_created_index(horse_id, created_at),
  CONSTRAINT horse_notes_horse_fk FOREIGN KEY (horse_id) REFERENCES horses(id) ON DELETE CASCADE,
  CONSTRAINT horse_notes_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Apzināti nav ievietoti demo zirgi vai fiktīvi lietotāji.


-- Laravel migrāciju žurnāls: ļauj vēlāk palaist php artisan migrate un atpazīt augstāk esošo shēmu.
CREATE TABLE IF NOT EXISTS migrations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  migration VARCHAR(255) NOT NULL,
  batch INT NOT NULL
) ENGINE=InnoDB;
INSERT INTO migrations (migration, batch) VALUES
('2026_10_20_000001_create_users_and_auth_tables', 1),
('2026_10_20_000002_create_horses_and_inventory_tables', 1),
('2026_10_20_000003_create_feeding_and_task_tables', 1),
('2026_10_20_000004_create_health_and_training_tables', 1),
('2026_10_20_000005_create_stables_documents_and_notes', 2);
