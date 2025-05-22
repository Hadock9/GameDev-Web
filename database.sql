-- Видаляємо старі таблиці, якщо вони існують
DROP TABLE IF EXISTS game_moves;
DROP TABLE IF EXISTS active_players;
DROP TABLE IF EXISTS games;
DROP TABLE IF EXISTS players;

-- Створення таблиці гравців
CREATE TABLE players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Створення таблиці ігор
CREATE TABLE games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status ENUM('waiting', 'in_progress', 'completed') DEFAULT 'waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL
);

-- Створення таблиці активних гравців (з прив'язкою до гри)
CREATE TABLE active_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    player_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    UNIQUE KEY unique_player_game (game_id, player_id)
);

-- Створення таблиці ходів (з прив'язкою до гри)
CREATE TABLE game_moves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    player_id INT NOT NULL,
    kronus INT NOT NULL,
    lyrion INT NOT NULL,
    mystara INT NOT NULL,
    eclipsia INT NOT NULL,
    fiora INT NOT NULL,
    score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    UNIQUE KEY unique_player_move (game_id, player_id)
);

-- Створення індексів для оптимізації запитів
CREATE INDEX idx_games_status ON games(status);
CREATE INDEX idx_active_players_game ON active_players(game_id);
CREATE INDEX idx_game_moves_game ON game_moves(game_id);
CREATE INDEX idx_game_moves_player ON game_moves(player_id); 