<?php
// ─── models/Database.php ─────────────────────────────────────
use MongoDB\Client;

class Database {
    private static ?Database $instance = null;
    private \MongoDB\Database $db;

    private function __construct() {
        $client   = new Client(MONGO_URI);
        $this->db = $client->selectDatabase(MONGO_DB);
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getCollection(string $name): \MongoDB\Collection {
        return $this->db->selectCollection($name);
    }

    public function getDB(): \MongoDB\Database {
        return $this->db;
    }
}
