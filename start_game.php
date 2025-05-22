<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

try {
    // Отримуємо ID гравця з GET параметрів
    $player_id = isset($_GET['player_id']) ? intval($_GET['player_id']) : 0;
    
    if ($player_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Невалідний ID гравця'
        ]);
        exit();
    }

    // Знаходимо гру гравця
    $stmt = $pdo->prepare("
        SELECT g.id, g.status, g.created_at, COUNT(ap.id) as players_count
        FROM games g
        JOIN active_players ap ON g.id = ap.game_id
        WHERE ap.player_id = ?
        GROUP BY g.id
    ");
    $stmt->execute([$player_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        echo json_encode([
            'success' => false,
            'message' => 'Гра не знайдена'
        ]);
        exit();
    }

    // Якщо є 2 гравці і гра в статусі очікування, оновлюємо статус
    if ($game['players_count'] >= 2 && $game['status'] === 'waiting') {
        $stmt = $pdo->prepare("
            UPDATE games 
            SET status = 'in_progress', started_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$game['id']]);
        $game['status'] = 'in_progress';
    }

    echo json_encode([
        'success' => true,
        'current_players' => $game['players_count'],
        'game_status' => $game['status'],
        'start_time' => strtotime($game['created_at'])
    ]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Помилка при отриманні статусу гри'
    ]);
}
?> 