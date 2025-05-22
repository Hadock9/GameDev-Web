<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

// Отримуємо дані з POST запиту
$data = json_decode(file_get_contents('php://input'), true);

// Перевіряємо наявність всіх необхідних полів
$required_fields = ['player_id', 'game_id', 'kronus', 'lyrion', 'mystara', 'eclipsia', 'fiora'];
foreach ($required_fields as $field) {
    if (!isset($data[$field])) {
        echo json_encode([
            'success' => false,
            'message' => "Відсутнє поле: $field"
        ]);
        exit();
    }
}

try {
    $pdo->beginTransaction();

    // Перевіряємо чи гра активна
    $stmt = $pdo->prepare("SELECT status FROM games WHERE id = ?");
    $stmt->execute([$data['game_id']]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game || $game['status'] !== 'in_progress') {
        throw new Exception('Гра не активна');
    }

    // Перевіряємо чи гравець є учасником цієї гри
    $stmt = $pdo->prepare("
        SELECT 1 FROM active_players 
        WHERE game_id = ? AND player_id = ?
    ");
    $stmt->execute([$data['game_id'], $data['player_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Гравець не є учасником цієї гри');
    }

    // Перевіряємо чи гравець вже зробив хід
    $stmt = $pdo->prepare("
        SELECT 1 FROM game_moves 
        WHERE game_id = ? AND player_id = ?
    ");
    $stmt->execute([$data['game_id'], $data['player_id']]);
    if ($stmt->fetch()) {
        throw new Exception('Гравець вже зробив хід');
    }

    // Перевіряємо суму дронів
    $total_drones = $data['kronus'] + $data['lyrion'] + $data['mystara'] + $data['eclipsia'] + $data['fiora'];
    if ($total_drones !== 1000) {
        throw new Exception('Загальна кількість дронів повинна бути 1000');
    }

    // Перевіряємо порядок дронів
    if ($data['kronus'] < $data['lyrion'] || 
        $data['lyrion'] < $data['mystara'] || 
        $data['mystara'] < $data['eclipsia'] || 
        $data['eclipsia'] < $data['fiora']) {
        throw new Exception('Неправильний порядок дронів');
    }

    // Додаємо хід
    $stmt = $pdo->prepare("
        INSERT INTO game_moves 
        (game_id, player_id, kronus, lyrion, mystara, eclipsia, fiora) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['game_id'],
        $data['player_id'],
        $data['kronus'],
        $data['lyrion'],
        $data['mystara'],
        $data['eclipsia'],
        $data['fiora']
    ]);

    // Перевіряємо чи всі гравці зробили ходи
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT ap.player_id) as total_players,
               COUNT(DISTINCT gm.player_id) as players_moved
        FROM active_players ap
        LEFT JOIN game_moves gm ON ap.game_id = gm.game_id AND ap.player_id = gm.player_id
        WHERE ap.game_id = ?
    ");
    $stmt->execute([$data['game_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Якщо всі гравці зробили ходи, завершуємо гру
    if ($result['total_players'] === $result['players_moved']) {
        $stmt = $pdo->prepare("
            UPDATE games 
            SET status = 'completed', completed_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$data['game_id']]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Хід успішно збережено',
        'game_id' => $data['game_id']
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error in submit_move.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 