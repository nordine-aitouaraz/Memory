<?php
declare(strict_types=1);

namespace App\Storage;

use App\Leaderboard;
use App\Player;

class JsonStore
{
    private string $leaderboardDir;
    private string $playersDir;

    public function __construct(string $dataDir)
    {
        $this->leaderboardDir = rtrim($dataDir, '/\\') . DIRECTORY_SEPARATOR . 'leaderboards';
        $this->playersDir = rtrim($dataDir, '/\\') . DIRECTORY_SEPARATOR . 'players';
        if (!is_dir($this->leaderboardDir)) {
            @mkdir($this->leaderboardDir, 0777, true);
        }
        if (!is_dir($this->playersDir)) {
            @mkdir($this->playersDir, 0777, true);
        }
    }

    /**
     * Charge le classement pour un nombre de paires donné.
     */
    public function loadLeaderboard(int $pairs): \App\Leaderboard
    {
        $lb = new \App\Leaderboard();
        $file = $this->leaderboardDir . DIRECTORY_SEPARATOR . $pairs . '.json';
        if (!is_file($file)) return $lb;
        $data = json_decode((string)@file_get_contents($file), true);
        if (!is_array($data)) return $lb;
        foreach ($data as $e) {
            if (isset($e['name'],$e['moves'],$e['seconds'],$e['pairs'])) {
                $lb->add((string)$e['name'], (int)$e['moves'], (int)$e['seconds'], (int)$e['pairs']);
            }
        }
        return $lb;
    }

    /**
     * Sauvegarde le classement pour un nombre de paires donné.
     */
    public function saveLeaderboard(\App\Leaderboard $lb, int $pairs): void
    {
        $file = $this->leaderboardDir . DIRECTORY_SEPARATOR . $pairs . '.json';
        $entries = $lb->top();
        @file_put_contents($file, json_encode($entries, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    }

    public function loadPlayer(string $name): Player
    {
        $file = $this->playersDir . DIRECTORY_SEPARATOR . $this->sanitize($name) . '.json';
        $player = new Player($name);
        if (is_file($file)) {
            $data = json_decode((string)@file_get_contents($file), true);
            if (is_array($data) && isset($data['scores']) && is_array($data['scores'])) {
                $player->scores = $data['scores'];
            }
        }
        return $player;
    }

    public function savePlayer(Player $player): void
    {
        $file = $this->playersDir . DIRECTORY_SEPARATOR . $this->sanitize($player->name) . '.json';
        $data = [
            'name' => $player->name,
            'scores' => $player->scores,
        ];
        @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    }

    private function sanitize(string $name): string
    {
        return preg_replace('/[^a-z0-9_-]+/i', '_', $name) ?? 'player';
    }
}
