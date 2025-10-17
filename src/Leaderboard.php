<?php
declare(strict_types=1);

namespace App;

class Leaderboard
{
    /** @var array<int,array{name:string,moves:int,seconds:int,pairs:int,date:string}> */
    private array $entries = [];
    private int $limit;

    public function __construct(int $limit = 10)
    {
        $this->limit = $limit;
    }

    /**
     * Ajoute une entrée au classement (classement par coups, puis temps).
     */
    public function add(string $name, int $moves, int $seconds, int $pairs): void
    {
        $this->entries[] = [
            'name' => $name,
            'moves' => $moves,
            'seconds' => $seconds,
            'pairs' => $pairs,
            'date' => date('c'),
        ];
        // Trier par coups croissants, puis temps croissant
        usort($this->entries, function($a, $b) {
            if ($a['moves'] !== $b['moves']) return $a['moves'] <=> $b['moves'];
            return $a['seconds'] <=> $b['seconds'];
        });
        $this->entries = array_slice($this->entries, 0, $this->limit);
    }

    /** @return array<int,array{name:string,moves:int,seconds:int,pairs:int,date:string}> */
    public function top(): array
    {
        return $this->entries;
    }
}
