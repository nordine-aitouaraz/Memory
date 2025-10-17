<?php
declare(strict_types=1);

namespace App;

use App\Storage\Db;

class Player
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function addScore(int $score, int $pairs, int $moves, int $seconds): void
    {
        $pdo = Db::get();
        $stmt = $pdo->prepare("INSERT INTO player_scores (player_name, moves, seconds, pairs, date) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$this->name, $moves, $seconds, $pairs]);
    }

    /**
     * Récupère tous les scores du joueur
     * @return array<int,array{date:string,pairs:int,moves:int,seconds:int}>
     */
    public function getScores(): array
    {
        $pdo = Db::get();
        $stmt = $pdo->prepare("SELECT date, pairs, moves, seconds FROM player_scores WHERE player_name = ? ORDER BY date DESC");
        $stmt->execute([$this->name]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function bestScore(): ?int
    {
        $pdo = Db::get();
        $stmt = $pdo->prepare("SELECT MIN(moves) as best FROM player_scores WHERE player_name = ?");
        $stmt->execute([$this->name]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row && $row['best'] !== null ? (int)$row['best'] : null;
    }
}
