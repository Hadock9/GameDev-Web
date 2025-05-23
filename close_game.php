<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

// Перевіряємо метод запиту
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Отримуємо дані з запиту
$data = json_decode(file_get_contents('php://input'), true);
$game_id = $data['game_id'] ?? null;

if (!$game_id) {
    echo json_encode(['success' => false, 'error' => 'Game ID is required']);
    exit;
}

try {
    // Перевіряємо, чи гра існує
    $stmt = $pdo->prepare("SELECT id, status FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        echo json_encode(['success' => false, 'error' => 'Game not found']);
        exit;
    }

    // Закриваємо гру, якщо вона не завершена
    if ($game['status'] !== 'completed') {
        $stmt = $pdo->prepare("UPDATE games SET status = 'completed', completed_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$game_id]);
        echo json_encode(['success' => true, 'message' => 'Game session closed successfully']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Game session was already closed']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 