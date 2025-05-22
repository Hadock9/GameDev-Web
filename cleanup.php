<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

try {
    $pdo->beginTransaction();

    // Видаляємо всі активні з'єднання
    $stmt = $pdo->prepare("DELETE FROM active_players");
    $stmt->execute();
    $disconnectedPlayers = $stmt->rowCount();

    // Видаляємо всі активні ігри
    $stmt = $pdo->prepare("DELETE FROM games WHERE status IN ('waiting', 'in_progress')");
    $stmt->execute();
    $deletedGames = $stmt->rowCount();

    // Очищаємо ходи
    $stmt = $pdo->prepare("DELETE FROM game_moves WHERE game_id NOT IN (SELECT id FROM games)");
    $stmt->execute();
    $deletedMoves = $stmt->rowCount();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Очищення завершено',
        'details' => [
            'disconnected_players' => $disconnectedPlayers,
            'deleted_games' => $deletedGames,
            'deleted_moves' => $deletedMoves
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 