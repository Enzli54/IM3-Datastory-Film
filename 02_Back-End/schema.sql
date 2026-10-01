CREATE TABLE IF NOT EXISTS films (
    tmdb_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    release_date DATE NULL,
    release_year SMALLINT UNSIGNED NOT NULL,
    genres VARCHAR(255) NOT NULL,
    popularity DECIMAL(12, 4) NOT NULL DEFAULT 0,
    vote_count INT UNSIGNED NOT NULL DEFAULT 0,
    vote_average DECIMAL(5, 3) NOT NULL DEFAULT 0,
    PRIMARY KEY (tmdb_id),
    INDEX idx_films_release_year (release_year)
) ENGINE = InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
