CREATE TABLE users (
                       id INTEGER PRIMARY KEY AUTOINCREMENT,
                       username TEXT UNIQUE NOT NULL,
                       password TEXT NOT NULL,
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE favorites (
                           id INTEGER PRIMARY KEY AUTOINCREMENT,
                           user_id INTEGER NOT NULL,
                           article_id TEXT NOT NULL,
                           article_title TEXT NOT NULL,
                           article_url TEXT NOT NULL,
                           created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                           FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
                           UNIQUE(user_id, article_id)
);

CREATE TABLE rate_limits (
                             id INTEGER PRIMARY KEY AUTOINCREMENT,
                             ip_address TEXT NOT NULL,
                             token TEXT NOT NULL,
                             request_count INTEGER DEFAULT 0,
                             last_request TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
