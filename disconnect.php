<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $player_id = $data['player_id'] ?? null;
    $game_id = $data['game_id'] ?? null;

    if (!$player_id || !$game_id) {
        throw new Exception('Відсутній player_id або game_id');
    }

    $pdo->beginTransaction();

    // Видаляємо гравця з активних гравців
    $stmt = $pdo->prepare("DELETE FROM active_players WHERE player_id = ? AND game_id = ?");
    $stmt->execute([$player_id, $game_id]);

    // Перевіряємо, чи залишились гравці в грі
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM active_players WHERE game_id = ?");
    $stmt->execute([$game_id]);
    $remainingPlayers = $stmt->fetchColumn();

    // Якщо гра порожня, видаляємо її
    if ($remainingPlayers === 0) {
        $stmt = $pdo->prepare("DELETE FROM games WHERE id = ?");
        $stmt->execute([$game_id]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Гравця відключено']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 