<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$rawPostData = file_get_contents('php://input');
$data = json_decode($rawPostData, true);

if (!isset($data['username']) || !isset($data['game_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Username and game_id are required']);
    exit;
}

$username = trim($data['username']);
$game_id = $data['game_id'];

try {
    $pdo->beginTransaction();

    // Перевірка наявності користувача
    $stmt = $pdo->prepare("SELECT id FROM players WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Username already exists']);
        exit;
    }

    // Перевіряємо чи гра існує і має місце
    $stmt = $pdo->prepare("
        SELECT g.id, g.status, COUNT(ap.id) as player_count
        FROM games g
        LEFT JOIN active_players ap ON g.id = ap.game_id
        WHERE g.id = ? AND g.status = 'waiting'
        GROUP BY g.id
        HAVING player_count < 4
    ");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        http_response_code(400);
        echo json_encode(['error' => 'Game is full or not available']);
        exit;
    }

    // Додавання користувача
    $stmt = $pdo->prepare("INSERT INTO players (username) VALUES (?)");
    $stmt->execute([$username]);
    $player_id = $pdo->lastInsertId();

    // Додаємо гравця до гри
    $stmt = $pdo->prepare("INSERT INTO active_players (game_id, player_id) VALUES (?, ?)");
    $stmt->execute([$game_id, $player_id]);

    // Перевіряємо, чи гра заповнена
    $stmt = $pdo->prepare("SELECT COUNT(*) as player_count FROM active_players WHERE game_id = ?");
    $stmt->execute([$game_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row['player_count'] >= 2) {
        // Оновлюємо статус гри на 'in_progress'
        $stmt = $pdo->prepare("UPDATE games SET status = 'in_progress' WHERE id = ?");
        $stmt->execute([$game_id]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'player_id' => $player_id,
        'game_id' => $game_id,
        'message' => 'Player registered and joined game successfully'
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