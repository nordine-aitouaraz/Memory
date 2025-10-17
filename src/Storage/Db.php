<?php
// filepath: c:\laragon\www\Memory\src\Storage\Db.php
namespace App\Storage;

use PDO;

class Db {
    private static $pdo = null;

    public static function get() {
        if (self::$pdo === null) {
            self::$pdo = new PDO(
                'mysql:host=localhost;dbname=memory;charset=utf8mb4',
                'root', // utilisateur MySQL
                '',     // mot de passe MySQL
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        }
        return self::$pdo;
    }
}
