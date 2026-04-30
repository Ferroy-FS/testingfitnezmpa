<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PDO;

/**
 * ══════════════════════════════════════════════════════════════
 *  SINGLETON — Creational Pattern
 * ══════════════════════════════════════════════════════════════
 *
 * Memastikan hanya ada SATU instance koneksi database.
 * Laravel sudah menerapkan Singleton untuk DB connection di
 * service container, tapi ini menunjukkan pattern secara eksplisit.
 *
 * Manfaat:
 * - Hemat resource (satu koneksi, bukan banyak)
 * - Global access point yang terkontrol
 * - Thread-safe di PHP (single-request lifecycle)
 *
 * Di Laravel, Singleton didaftarkan via Service Provider.
 * ══════════════════════════════════════════════════════════════
 */
class DatabaseConnection
{
    private static ?DatabaseConnection $instance = null;
    private PDO $pdo;

    /**
     * Private constructor — mencegah instansiasi dari luar
     */
    private function __construct()
    {
        $host = config('database.connections.pgsql.host', '127.0.0.1');
        $port = config('database.connections.pgsql.port', '5432');
        $db   = config('database.connections.pgsql.database', 'fitnez');
        $user = config('database.connections.pgsql.username', 'postgres');
        $pass = config('database.connections.pgsql.password', '0000');

        $this->pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    /**
     * Mencegah cloning
     */
    private function __clone() {}

    /**
     * Global access point — selalu mengembalikan instance yang sama
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
