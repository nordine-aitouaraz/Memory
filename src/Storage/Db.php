<?php
namespace App\Storage;

use PDO;

class Db {
    private static $pdo = null;

    public static function get() {
        if (self::$pdo === null) {
            self::$pdo = new PDO(
                'mysql:host=localhost;dbname=nordine-ait-ouaraz_memory;charset=utf8mb4',
                'memory_user2', // utilisateur MySQL (Plesk)
                'nrz92290plesk',     // mot de passe MySQL (Plesk)
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        }
        return self::$pdo;
    }
}
