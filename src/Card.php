<?php
declare(strict_types=1);

namespace App;

/**
 * Represents a single card in the Memory game.
 * Each pair shares the same id and label.
 */
class Card
{
    public int $id;       // Pair identifier
    public string $label; // Visible label/content when flipped

    public function __construct(int $id, string $label)
    {
        $this->id = $id;
        $this->label = $label;
    }
}
