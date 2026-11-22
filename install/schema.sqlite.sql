-- Schéma SQLite (tests en local uniquement)
CREATE TABLE IF NOT EXISTS settings (skey TEXT PRIMARY KEY, svalue TEXT);

CREATE TABLE IF NOT EXISTS admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL, last_login_at TEXT, created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS login_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, ip TEXT NOT NULL, attempted_at TEXT NOT NULL);
CREATE INDEX IF NOT EXISTS idx_login_ip ON login_attempts (ip, attempted_at);

CREATE TABLE IF NOT EXISTS projects (
    id INTEGER PRIMARY KEY AUTOINCREMENT, slug TEXT NOT NULL UNIQUE, title TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'upcoming', summary TEXT, description TEXT, location TEXT,
    goal_amount REAL, raised_amount REAL, start_date TEXT, end_date TEXT, cover_image TEXT,
    is_featured INTEGER NOT NULL DEFAULT 0, is_published INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL, updated_at TEXT
);

CREATE TABLE IF NOT EXISTS project_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    path TEXT NOT NULL, caption TEXT, sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS testimonials (
    id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, role TEXT, quote TEXT NOT NULL, photo TEXT,
    is_published INTEGER NOT NULL DEFAULT 1, sort_order INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL, updated_at TEXT
);

CREATE TABLE IF NOT EXISTS donors (
    id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, donor_type TEXT NOT NULL DEFAULT 'individual',
    country TEXT, logo TEXT, website TEXT, amount REAL, show_amount INTEGER NOT NULL DEFAULT 0, mask_name INTEGER NOT NULL DEFAULT 0,
    project_id INTEGER REFERENCES projects(id) ON DELETE SET NULL, message TEXT, donated_on TEXT,
    is_published INTEGER NOT NULL DEFAULT 1, sort_order INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL, updated_at TEXT
);

CREATE TABLE IF NOT EXISTS donation_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, icon TEXT NOT NULL DEFAULT 'heart',
    description TEXT, details TEXT, is_published INTEGER NOT NULL DEFAULT 1, sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL, updated_at TEXT
);

CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT, kind TEXT NOT NULL DEFAULT 'contact', name TEXT NOT NULL, email TEXT NOT NULL,
    phone TEXT, country TEXT, subject TEXT, project_id INTEGER REFERENCES projects(id) ON DELETE SET NULL,
    amount REAL, method TEXT, body TEXT, ip TEXT, is_read INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_messages_read ON messages (is_read, created_at);

-- Statistiques de fréquentation anonymes (aucune adresse IP ni aucun cookie).
CREATE TABLE IF NOT EXISTS page_views (
    id INTEGER PRIMARY KEY AUTOINCREMENT, day TEXT NOT NULL, path TEXT NOT NULL, lang TEXT NOT NULL,
    device TEXT NOT NULL, referrer TEXT NOT NULL DEFAULT '', views INTEGER NOT NULL DEFAULT 0,
    UNIQUE (day, path, lang, device, referrer)
);
CREATE INDEX IF NOT EXISTS idx_page_views_day ON page_views (day);

-- Avancement des travaux : étapes datées d'un projet, chacune avec ses photos légendées
CREATE TABLE IF NOT EXISTS project_updates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    update_date TEXT NOT NULL, title TEXT NOT NULL, title_en TEXT, body TEXT, body_en TEXT,
    progress INTEGER NOT NULL DEFAULT 0, is_published INTEGER NOT NULL DEFAULT 1, created_at TEXT NOT NULL, updated_at TEXT
);
CREATE INDEX IF NOT EXISTS idx_updates_project ON project_updates (project_id, update_date);
CREATE TABLE IF NOT EXISTS update_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    update_id INTEGER NOT NULL REFERENCES project_updates(id) ON DELETE CASCADE,
    path TEXT NOT NULL, caption TEXT, caption_en TEXT, sort_order INTEGER NOT NULL DEFAULT 0
);
