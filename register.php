<?php
header('Content-Type: application/json');

require_once 'config.php';

$rawPostData = file_get_contents('php://input');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode($rawPostData, true);

if (!isset($data['username']) || empty(trim($data['username']))) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Username is required',
        'received_raw_data' => $rawPostData,
        'received_decoded_data' => $data
    ]);
    exit;
}

$username = trim($data['username']);

try {
    // Перевірка наявності користувача
    $stmt = $pdo->prepare("SELECT id FROM players WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Username already exists']);
        exit;
    }

    // Додавання користувача
    $stmt = $pdo->prepare("INSERT INTO players (username) VALUES (?)");
    $stmt->execute([$username]);
    $player_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'player_id' => $player_id,
        'message' => 'Player registered successfully'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Registration failed',
        'details' => $e->getMessage()
    ]);
}
?>