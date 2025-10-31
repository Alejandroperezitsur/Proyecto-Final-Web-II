<?php
require_once __DIR__ . '/../../config/db.php';

abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($page = 1, $limit = 10, $where = '') {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM {$this->table} " . $where . " LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function count($where = '') {
        $sql = "SELECT COUNT(*) FROM {$this->table} " . $where;
        return $this->db->query($sql)->fetchColumn();
    }

    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function create($data) {
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ':' . $field;
        }, $fields);

        $sql = "INSERT INTO {$this->table} (" . 
               implode(', ', $fields) . 
               ") VALUES (" . 
               implode(', ', $placeholders) . 
               ")";

        $stmt = $this->db->prepare($sql);
        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        return $stmt->execute();
    }

    public function update($id, $data) {
        $fields = array_map(function($field) {
            return $field . ' = :' . $field;
        }, array_keys($data));

        $sql = "UPDATE {$this->table} SET " . 
               implode(', ', $fields) . 
               " WHERE {$this->primaryKey} = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        return $stmt->execute();
    }

    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        return $stmt->execute();
    }

    protected function sanitize($data) {
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $clean[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } else {
                $clean[$key] = $value;
            }
        }
        return $clean;
    }
}