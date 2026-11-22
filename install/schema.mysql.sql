-- Schéma MySQL / MariaDB — Site de donation UAZ
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
    skey   VARCHAR(100) NOT NULL PRIMARY KEY,
    svalue TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(150) NOT NULL,
    email         VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    last_login_at DATETIME NULL,
    created_at    DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip           VARCHAR(45) NOT NULL,
    attempted_at DATETIME NOT NULL,
    INDEX idx_login_ip (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projects (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug          VARCHAR(190) NOT NULL UNIQUE,
    title         VARCHAR(255) NOT NULL,
    status        VARCHAR(20)  NOT NULL DEFAULT 'upcoming',
    summary       TEXT NULL,
    description   MEDIUMTEXT NULL,
    location      VARCHAR(255) NULL,
    goal_amount   DECIMAL(15,2) NULL,
    raised_amount DECIMAL(15,2) NULL,
    start_date    DATE NULL,
    end_date      DATE NULL,
    cover_image   VARCHAR(255) NULL,
    is_featured   TINYINT(1) NOT NULL DEFAULT 0,
    is_published  TINYINT(1) NOT NULL DEFAULT 1,
    sort_order    INT NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NULL,
    INDEX idx_projects_status (status, is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_images (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    path       VARCHAR(255) NOT NULL,
    caption    VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_images_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS testimonials (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(150) NOT NULL,
    role         VARCHAR(190) NULL,
    quote        TEXT NOT NULL,
    photo        VARCHAR(255) NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    sort_order   INT NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL,
    updated_at   DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS donors (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(190) NOT NULL,
    donor_type   VARCHAR(20) NOT NULL DEFAULT 'individual',
    country      VARCHAR(100) NULL,
    logo         VARCHAR(255) NULL,
    website      VARCHAR(255) NULL,
    amount       DECIMAL(15,2) NULL,
    show_amount  TINYINT(1) NOT NULL DEFAULT 0,
    mask_name    TINYINT(1) NOT NULL DEFAULT 0,
    project_id   INT UNSIGNED NULL,
    message      TEXT NULL,
    donated_on   DATE NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    sort_order   INT NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL,
    updated_at   DATETIME NULL,
    CONSTRAINT fk_donors_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS donation_methods (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(190) NOT NULL,
    icon         VARCHAR(30) NOT NULL DEFAULT 'heart',
    description  TEXT NULL,
    details      TEXT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    sort_order   INT NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL,
    updated_at   DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kind       VARCHAR(20) NOT NULL DEFAULT 'contact',
    name       VARCHAR(150) NOT NULL,
    email      VARCHAR(190) NOT NULL,
    phone      VARCHAR(50) NULL,
    country    VARCHAR(100) NULL,
    subject    VARCHAR(255) NULL,
    project_id INT UNSIGNED NULL,
    amount     DECIMAL(15,2) NULL,
    method     VARCHAR(190) NULL,
    body       TEXT NULL,
    ip         VARCHAR(45) NULL,
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    INDEX idx_messages_read (is_read, created_at),
    CONSTRAINT fk_messages_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Statistiques de fréquentation anonymes : un compteur par jour / page / langue / appareil / provenance.
-- Aucune adresse IP ni aucun cookie.
CREATE TABLE IF NOT EXISTS page_views (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    day      DATE NOT NULL,
    path     VARCHAR(190) NOT NULL,
    lang     VARCHAR(5) NOT NULL,
    device   VARCHAR(10) NOT NULL,
    referrer VARCHAR(120) NOT NULL DEFAULT '',
    views    INT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_page_views (day, path, lang, device, referrer),
    INDEX idx_page_views_day (day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Avancement des travaux : étapes datées d'un projet, chacune avec ses photos légendées
CREATE TABLE IF NOT EXISTS project_updates (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id  INT UNSIGNED NOT NULL,
    update_date DATE NOT NULL,
    title       VARCHAR(255) NOT NULL,
    title_en    VARCHAR(255) NULL,
    body        TEXT NULL,
    body_en     TEXT NULL,
    progress    INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME NULL,
    INDEX idx_updates_project (project_id, update_date),
    CONSTRAINT fk_updates_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS update_images (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    update_id  INT UNSIGNED NOT NULL,
    path       VARCHAR(255) NOT NULL,
    caption    VARCHAR(255) NULL,
    caption_en VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_update_images FOREIGN KEY (update_id) REFERENCES project_updates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paiement en ligne : engagements de dons réguliers (mensuel, trimestriel, annuel)
CREATE TABLE IF NOT EXISTS donation_plans (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference     VARCHAR(40) NOT NULL UNIQUE,
    token         VARCHAR(64) NOT NULL UNIQUE,
    project_id    INT UNSIGNED NULL,
    donor_name    VARCHAR(150) NOT NULL,
    email         VARCHAR(190) NOT NULL,
    phone         VARCHAR(30) NULL,
    country       VARCHAR(100) NULL,
    amount        DECIMAL(15,2) NOT NULL,
    currency      CHAR(3) NOT NULL,
    provider      VARCHAR(30) NOT NULL,
    frequency     VARCHAR(12) NOT NULL,
    status        VARCHAR(12) NOT NULL DEFAULT 'pending',
    lang          VARCHAR(5) NOT NULL DEFAULT 'en',
    next_due_date DATE NULL,
    last_paid_at  DATETIME NULL,
    payments_count INT NOT NULL DEFAULT 0,
    reminded_for  DATE NULL,
    reminder_count INT NOT NULL DEFAULT 0,
    provider_customer VARCHAR(100) NULL,
    provider_subscription VARCHAR(100) NULL,
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NULL,
    cancelled_at  DATETIME NULL,
    INDEX idx_plans_due (status, next_due_date),
    CONSTRAINT fk_plans_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paiement en ligne : chaque paiement (ponctuel ou échéance d'un engagement)
CREATE TABLE IF NOT EXISTS donations (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference     VARCHAR(40) NOT NULL UNIQUE,
    plan_id       INT UNSIGNED NULL,
    project_id    INT UNSIGNED NULL,
    donor_name    VARCHAR(150) NOT NULL,
    email         VARCHAR(190) NOT NULL,
    phone         VARCHAR(30) NULL,
    country       VARCHAR(100) NULL,
    amount        DECIMAL(15,2) NOT NULL,
    currency      CHAR(3) NOT NULL,
    provider      VARCHAR(30) NOT NULL,
    frequency     VARCHAR(12) NOT NULL DEFAULT 'once',
    status        VARCHAR(12) NOT NULL DEFAULT 'pending',
    provider_ref  VARCHAR(190) NULL,
    provider_data TEXT NULL,
    message       TEXT NULL,
    lang          VARCHAR(5) NOT NULL DEFAULT 'en',
    ip            VARCHAR(45) NULL,
    added_to_project TINYINT(1) NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NULL,
    paid_at       DATETIME NULL,
    INDEX idx_donations_status (status, created_at),
    INDEX idx_donations_provider_ref (provider, provider_ref),
    CONSTRAINT fk_donations_plan FOREIGN KEY (plan_id) REFERENCES donation_plans(id) ON DELETE SET NULL,
    CONSTRAINT fk_donations_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications reçues des prestataires (anti-doublon et traçabilité)
CREATE TABLE IF NOT EXISTS payment_events (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider    VARCHAR(30) NOT NULL,
    event_id    VARCHAR(190) NOT NULL,
    event_type  VARCHAR(100) NULL,
    payload     MEDIUMTEXT NULL,
    created_at  DATETIME NOT NULL,
    UNIQUE KEY uq_payment_events (provider, event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
