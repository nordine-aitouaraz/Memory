<?php
declare(strict_types=1);

namespace App;

use App\Storage\Db;

class Leaderboard
{
    private int $limit;

    public function __construct(int $limit = 10)
    {
        $this->limit = $limit;
    }

    public function add(string $name, int $moves, int $seconds, int $pairs): void
    {
        $pdo = Db::get();
        $stmt = $pdo->prepare("INSERT INTO leaderboard (name, moves, seconds, pairs, date) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $moves, $seconds, $pairs]);
    }

    public function top(int $pairs): array
    {
        $pdo = Db::get();
        // Sous-requête pour ne garder que le meilleur score (moves, puis seconds) par joueur
        $sql = "
            SELECT l1.name, l1.moves, l1.seconds, l1.pairs, l1.date
            FROM leaderboard l1
            INNER JOIN (
                SELECT name, MIN(moves) as min_moves
                FROM leaderboard
                WHERE pairs = ?
                GROUP BY name
            ) l2 ON l1.name = l2.name AND l1.moves = l2.min_moves
            INNER JOIN (
                SELECT name, moves, MIN(seconds) as min_seconds
                FROM leaderboard
                WHERE pairs = ?
                GROUP BY name, moves
            ) l3 ON l1.name = l3.name AND l1.moves = l3.moves AND l1.seconds = l3.min_seconds
            INNER JOIN (
                SELECT name, moves, seconds, MIN(id) as min_id
                FROM leaderboard
                WHERE pairs = ?
                GROUP BY name, moves, seconds
            ) l4 ON l1.name = l4.name AND l1.moves = l4.moves AND l1.seconds = l4.seconds AND l1.id = l4.min_id
            WHERE l1.pairs = ?
            ORDER BY l1.moves ASC, l1.seconds ASC
            LIMIT ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $pairs, \PDO::PARAM_INT);
        $stmt->bindValue(2, $pairs, \PDO::PARAM_INT);
        $stmt->bindValue(3, $pairs, \PDO::PARAM_INT);
        $stmt->bindValue(4, $pairs, \PDO::PARAM_INT);
        $stmt->bindValue(5, $this->limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
