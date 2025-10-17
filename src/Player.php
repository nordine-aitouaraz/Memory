<?php
declare(strict_types=1);

namespace App;

class Player
{
    public string $name;
    /** @var array<int,array{date:string,score:int,pairs:int,moves:int,seconds:int}> */
    public array $scores = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function addScore(int $score, int $pairs, int $moves, int $seconds): void
    {
        $this->scores[] = [
            'date' => date('c'),
            'score' => $score,
            'pairs' => $pairs,
            'moves' => $moves,
            'seconds' => $seconds,
        ];
    }

    public function bestScore(): ?int
    {
        if (!$this->scores) return null;
        $max = 0;
        foreach ($this->scores as $s) {
            $max = max($max, $s['score']);
        }
        return $max;
    }
}
