<?php
require_once 'config.php';

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists\n";
} else {
    echo "Error creating database: " . $conn->error . "\n";
}

// Select the database
$conn->select_db(DB_NAME);

// Create players table
$sql = "CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'players' created successfully\n";
} else {
    echo "Error creating table 'players': " . $conn->error . "\n";
}

// Create games table
$sql = "CREATE TABLE IF NOT EXISTS games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    kronus INT NOT NULL DEFAULT 0,
    lyrion INT NOT NULL DEFAULT 0,
    mystara INT NOT NULL DEFAULT 0,
    eclipsia INT NOT NULL DEFAULT 0,
    fiora INT NOT NULL DEFAULT 0,
    score INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES players(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'games' created successfully\n";
} else {
    echo "Error creating table 'games': " . $conn->error . "\n";
}

$conn->close();
?> 