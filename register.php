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
    $pdo->beginTransaction();

    // Додавання користувача
    $stmt = $pdo->prepare("INSERT INTO players (username) VALUES (?)");
    $stmt->execute([$username]);
    $player_id = $pdo->lastInsertId();

    // Створюємо нову гру
    $stmt = $pdo->prepare("INSERT INTO games () VALUES ()");
    $stmt->execute();
    $game_id = $pdo->lastInsertId();

    // Додаємо гравця до гри
    $stmt = $pdo->prepare("INSERT INTO active_players (game_id, player_id) VALUES (?, ?)");
    $stmt->execute([$game_id, $player_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'player_id' => $player_id,
        'game_id' => $game_id,
        'message' => 'Player registered and game session created successfully'
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'error' => 'Registration failed',
        'details' => $e->getMessage()
    ]);
}
?> 