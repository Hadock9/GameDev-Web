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

if (!isset($data['game_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Game ID is required']);
    exit;
}

$game_id = $data['game_id'];

try {
    // Отримуємо інформацію про гру
    $stmt = $pdo->prepare("
        SELECT g.*, COUNT(ap.id) as player_count
        FROM games g
        LEFT JOIN active_players ap ON g.id = ap.game_id
        WHERE g.id = ?
        GROUP BY g.id
    ");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        http_response_code(404);
        echo json_encode(['error' => 'Game not found']);
        exit;
    }

    // Отримуємо список гравців
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.username,
            ap.joined_at,
            CASE 
                WHEN ap.joined_at = (SELECT MIN(joined_at) FROM active_players WHERE game_id = ?) THEN 1
                ELSE 0
            END as is_host
        FROM active_players ap
        JOIN players p ON ap.player_id = p.id
        WHERE ap.game_id = ?
        ORDER BY ap.joined_at ASC
    ");
    $stmt->execute([$game_id, $game_id]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'game' => [
            'id' => $game['id'],
            'status' => $game['status'],
            'player_count' => $game['player_count']
        ],
        'players' => $players
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to get players',
        'details' => $e->getMessage()
    ]);
}
?> 