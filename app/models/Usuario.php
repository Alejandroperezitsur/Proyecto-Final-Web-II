<?php
require_once __DIR__ . '/Model.php';

class Usuario extends Model {
    protected $table = 'usuarios';
    
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function findByMatricula($matricula) {
        $sql = "SELECT * FROM {$this->table} WHERE matricula = :matricula LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':matricula', $matricula);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function updateLastLogin($id) {
        $sql = "UPDATE {$this->table} SET last_login = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        return $stmt->execute();
    }
    
    public function create($data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return parent::create($data);
    }
    
    public function update($id, $data) {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }
        return parent::update($id, $data);
    }

    public function getAllByRole($role, $page = 1, $limit = 10) {
        $page = max(1, (int)$page);
        $limit = max(1, (int)$limit);
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM {$this->table} WHERE rol = :rol LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':rol', $role);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByRole($role) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE rol = :rol";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':rol', $role);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function searchByRole($role, $term, $page = 1, $limit = 10) {
        $page = max(1, (int)$page);
        $limit = max(1, (int)$limit);
        $offset = ($page - 1) * $limit;
        $like = "%$term%";
        $sql = "SELECT * FROM {$this->table}
                WHERE rol = :rol AND (matricula LIKE :term OR email LIKE :term)
                LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':rol', $role);
        $stmt->bindValue(':term', $like);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByRoleSearch($role, $term) {
        $like = "%$term%";
        $sql = "SELECT COUNT(*) FROM {$this->table}
                WHERE rol = :rol AND (matricula LIKE :term OR email LIKE :term)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':rol', $role);
        $stmt->bindValue(':term', $like);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getByRoleAndEstado($role, $estado, $page = 1, $limit = 10) {
        $page = max(1, (int)$page);
        $limit = max(1, (int)$limit);
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM {$this->table} WHERE rol = :rol AND activo = :estado LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':rol', $role);
        $stmt->bindValue(':estado', (int)$estado);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByRoleAndEstado($role, $estado) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE rol = :rol AND activo = :estado";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':rol', $role);
        $stmt->bindValue(':estado', (int)$estado);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function searchByRoleAndEstado($role, $term, $estado, $page = 1, $limit = 10) {
        $page = max(1, (int)$page);
        $limit = max(1, (int)$limit);
        $offset = ($page - 1) * $limit;
        $like = "%$term%";
        $sql = "SELECT * FROM {$this->table}
                WHERE rol = :rol AND activo = :estado AND (matricula LIKE :term OR email LIKE :term)
                LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':rol', $role);
        $stmt->bindValue(':estado', (int)$estado);
        $stmt->bindValue(':term', $like);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByRoleSearchAndEstado($role, $term, $estado) {
        $like = "%$term%";
        $sql = "SELECT COUNT(*) FROM {$this->table}
                WHERE rol = :rol AND activo = :estado AND (matricula LIKE :term OR email LIKE :term)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':rol', $role);
        $stmt->bindValue(':estado', (int)$estado);
        $stmt->bindValue(':term', $like);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}