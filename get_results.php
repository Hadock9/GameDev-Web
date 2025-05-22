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
    // Отримуємо ID гри з GET параметрів
    $game_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : 0;
    
    if ($game_id <= 0) {
        throw new Exception('Невалідний ID гри');
    }

    // Перевіряємо чи гра існує і завершена
    $stmt = $pdo->prepare("SELECT status FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        throw new Exception('Гра не знайдена');
    }

    if ($game['status'] !== 'completed') {
        throw new Exception('Гра ще не завершена');
    }

    // Отримуємо ходи всіх гравців
    $stmt = $pdo->prepare("
        SELECT 
            gm.*,
            p.username,
            (
                gm.kronus * 5 + 
                gm.lyrion * 4 + 
                gm.mystara * 3 + 
                gm.eclipsia * 2 + 
                gm.fiora
            ) as score
        FROM game_moves gm
        JOIN players p ON gm.player_id = p.id
        WHERE gm.game_id = ?
        ORDER BY score DESC
    ");
    $stmt->execute([$game_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
} catch (Exception $e) {
    error_log("Error in get_results.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 