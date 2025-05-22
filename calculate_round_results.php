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
$round_number = $data['round_number'] ?? null;

if (!$game_id || !$round_number) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

try {
    // Отримуємо всі ходи гравців для цього раунду
    $stmt = $pdo->prepare("
        SELECT pm.*, p.username 
        FROM player_moves pm
        JOIN players p ON pm.player_id = p.id
        WHERE pm.game_id = ? AND pm.round_number = ?
    ");
    $stmt->execute([$game_id, $round_number]);
    $moves = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($moves) < 2) {
        echo json_encode(['success' => false, 'error' => 'Not enough moves for comparison']);
        exit;
    }

    // Масив для зберігання балів
    $scores = array_fill(0, count($moves), 0);
    $results = [];

    // Порівнюємо кожну команду з кожною іншою
    for ($i = 0; $i < count($moves); $i++) {
        for ($j = $i + 1; $j < count($moves); $j++) {
            $team1Score = 0;
            $team2Score = 0;

            // Порівнюємо дрони по кожній планеті
            if ($moves[$i]['kronus'] > $moves[$j]['kronus']) $team1Score++;
            else if ($moves[$i]['kronus'] < $moves[$j]['kronus']) $team2Score++;

            if ($moves[$i]['lyrion'] > $moves[$j]['lyrion']) $team1Score++;
            else if ($moves[$i]['lyrion'] < $moves[$j]['lyrion']) $team2Score++;

            if ($moves[$i]['mystara'] > $moves[$j]['mystara']) $team1Score++;
            else if ($moves[$i]['mystara'] < $moves[$j]['mystara']) $team2Score++;

            if ($moves[$i]['eclipsia'] > $moves[$j]['eclipsia']) $team1Score++;
            else if ($moves[$i]['eclipsia'] < $moves[$j]['eclipsia']) $team2Score++;

            if ($moves[$i]['fiora'] > $moves[$j]['fiora']) $team1Score++;
            else if ($moves[$i]['fiora'] < $moves[$j]['fiora']) $team2Score++;

            // Нараховуємо бали
            if ($team1Score > $team2Score) {
                $scores[$i] += 2;
            } else if ($team1Score < $team2Score) {
                $scores[$j] += 2;
            } else {
                $scores[$i] += 1;
                $scores[$j] += 1;
            }
        }
    }

    // Знаходимо максимальний бал
    $maxScore = max($scores);

    // Зберігаємо результати в базу даних
    $pdo->beginTransaction();

    // Видаляємо попередні результати раунду, якщо вони є
    $stmt = $pdo->prepare("DELETE FROM round_results WHERE game_id = ? AND round_number = ?");
    $stmt->execute([$game_id, $round_number]);

    // Зберігаємо нові результати
    $stmt = $pdo->prepare("
        INSERT INTO round_results (game_id, round_number, player_id, score, is_winner)
        VALUES (?, ?, ?, ?, ?)
    ");

    for ($i = 0; $i < count($moves); $i++) {
        $isWinner = ($scores[$i] == $maxScore);
        $stmt->execute([
            $game_id,
            $round_number,
            $moves[$i]['player_id'],
            $scores[$i],
            $isWinner
        ]);

        // Додаємо результат до масиву для відповіді
        $results[] = [
            'player_id' => $moves[$i]['player_id'],
            'username' => $moves[$i]['username'],
            'score' => $scores[$i],
            'is_winner' => $isWinner,
            'drones' => [
                'kronus' => $moves[$i]['kronus'],
                'lyrion' => $moves[$i]['lyrion'],
                'mystara' => $moves[$i]['mystara'],
                'eclipsia' => $moves[$i]['eclipsia'],
                'fiora' => $moves[$i]['fiora']
            ]
        ];
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'results' => $results,
        'max_score' => $maxScore
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 