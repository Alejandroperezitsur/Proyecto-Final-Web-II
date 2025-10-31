<?php
require_once __DIR__ . '/Model.php';

class SavedView extends Model {
    protected $table = 'saved_views';
    private $allowedFields = ['user_id', 'page_key', 'label', 'data_json'];

    public function __construct() {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable() {
        $sql = "CREATE TABLE IF NOT EXISTS saved_views (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            page_key VARCHAR(100) NOT NULL,
            label VARCHAR(100) NOT NULL,
            data_json TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_idx (user_id),
            KEY page_idx (page_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);
    }

    public function create($data) {
        $data = $this->filterAllowedFields($data);
        return parent::create($data);
    }

    public function getByUserAndPage($userId, $pageKey) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :uid AND page_key = :p ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $userId);
        $stmt->bindValue(':p', $pageKey);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function filterAllowedFields($data) {
        return array_intersect_key($data, array_flip($this->allowedFields));
    }
}