<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || empty($data['username'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Username is required']);
    exit;
}

$username = $conn->real_escape_string($data['username']);

// Check if username already exists
$check = $conn->query("SELECT id FROM players WHERE username = '$username'");
if ($check->num_rows > 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Username already exists']);
    exit;
}

// Insert new player
$sql = "INSERT INTO players (username) VALUES ('$username')";
if ($conn->query($sql) === TRUE) {
    $player_id = $conn->insert_id;
    echo json_encode([
        'success' => true,
        'player_id' => $player_id,
        'message' => 'Player registered successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed']);
}

$conn->close();
?> 