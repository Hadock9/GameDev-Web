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

if (!isset($data['player_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Player ID is required']);
    exit;
}

$player_id = $data['player_id'];

try {
    $pdo->beginTransaction();

    // Перевіряємо чи гравець вже в якійсь активній грі
    $stmt = $pdo->prepare("
        SELECT g.id as game_id, g.status 
        FROM games g 
        JOIN active_players ap ON g.id = ap.game_id 
        WHERE ap.player_id = ? AND g.status IN ('waiting', 'in_progress')
    ");
    $stmt->execute([$player_id]);
    $existingGame = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingGame) {
        // Гравець вже в грі
        echo json_encode([
            'success' => true,
            'game_id' => $existingGame['game_id'],
            'status' => $existingGame['status'],
            'message' => 'Player is already in a game'
        ]);
        $pdo->commit();
        exit;
    }

    // Шукаємо гру в очікуванні з місцем
    $stmt = $pdo->prepare("
        SELECT g.id, COUNT(ap.id) as player_count
        FROM games g
        LEFT JOIN active_players ap ON g.id = ap.game_id
        WHERE g.status = 'waiting'
        GROUP BY g.id
        HAVING player_count < 4
        ORDER BY g.created_at ASC
        LIMIT 1
    ");
    $stmt->execute();
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        // Створюємо нову гру
        $stmt = $pdo->prepare("INSERT INTO games () VALUES ()");
        $stmt->execute();
        $game_id = $pdo->lastInsertId();
    } else {
        $game_id = $game['id'];
    }

    // Додаємо гравця до гри
    $stmt = $pdo->prepare("INSERT INTO active_players (game_id, player_id) VALUES (?, ?)");
    $stmt->execute([$game_id, $player_id]);

    // Перевіряємо чи гра заповнена
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as player_count 
        FROM active_players 
        WHERE game_id = ?
    ");
    $stmt->execute([$game_id]);
    $playerCount = $stmt->fetch(PDO::FETCH_ASSOC)['player_count'];

    $isGameFull = $playerCount >= 4;

    if ($isGameFull) {
        // Оновлюємо статус гри на 'in_progress'
        $stmt = $pdo->prepare("UPDATE games SET status = 'in_progress', started_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$game_id]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'game_id' => $game_id,
        'is_game_full' => $isGameFull,
        'status' => $isGameFull ? 'in_progress' : 'waiting',
        'message' => $isGameFull ? 'Game is full, starting now' : 'Successfully joined the game'
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to find or create game',
        'details' => $e->getMessage()
    ]);
}
?> 