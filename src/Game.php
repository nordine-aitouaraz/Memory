<?php
declare(strict_types=1);

namespace App;

/**
 * Core game logic and state (stateless methods + helpers for session state shape).
 */
class Game
{
    public const MIN_PAIRS = 3;
    public const MAX_PAIRS = 12;

    /**
     * Create a shuffled deck of 2*N cards based on N unique labels.
     * Returns array of Card indexed from 0..(2N-1).
     */
    public function createDeck(int $pairs): array
    {
        if ($pairs < self::MIN_PAIRS || $pairs > self::MAX_PAIRS) {
            throw new \InvalidArgumentException("Pairs must be between " . self::MIN_PAIRS . " and " . self::MAX_PAIRS);
        }

        $labels = $this->generateLabels($pairs);
        $deck = [];
        foreach ($labels as $i => $label) {
            // Two cards per pair with same id and label
            $deck[] = new Card($i, $label);
            $deck[] = new Card($i, $label);
        }
        // Shuffle keeping numeric indexes re-ordered
        shuffle($deck);
        return $deck;
    }

    /**
     * Random labels for pairs — simple emojis by default.
     */
    private function generateLabels(int $pairs): array
    {
        $pool = [
            '🍎','🍌','🍇','🍓','🍍','🥝','🍉','🍑','🍒','🥥','🍋','🍐',
            '🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮',
            '⚽','🏀','🏈','⚾','🎾','🏐','🎱','🏓','🏸','🥎','🥏','🏒'
        ];
        shuffle($pool);
        return array_slice($pool, 0, $pairs);
    }

    /**
     * Score calculation: fewer moves is better. Perfect score baseline.
     */
    public function calculateScore(int $moves, int $pairs, int $seconds): int
    {
        $minMoves = $pairs; // perfect: each pair found in 1 discovery move
        $penalty = max(0, $moves - $minMoves) * 5 + intdiv($seconds, 5);
        $score = max(0, 1000 - $penalty);
        return $score;
    }
}
