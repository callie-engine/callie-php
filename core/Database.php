<?php

namespace Callie;

class Database {
    protected $pdo;
    protected static $instance;

    public function __construct() {
        self::$instance = $this;
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function connect() {
        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '3306');
        $db   = Env::get('DB_NAME', 'callie');
        $user = Env::get('DB_USER', 'root');
        $pass = Env::get('DB_PASSWORD', '');
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            // Echo raw JSON error if connection fails, as this is critical
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database Connection Failed: ' . $e->getMessage()]);
            exit;
        }
    }

    public function table($name) {
        return new QueryBuilder($this->pdo, $name);
    }

    // Direct raw query helper
    public function raw($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            // If it's a SELECT, return rows
            if (stripos(trim($sql), 'SELECT') === 0) {
                return $stmt->fetchAll();
            }
            // If INSERT, return ID
            if (stripos(trim($sql), 'INSERT') === 0) {
                return $this->pdo->lastInsertId();
            }
            // Update/Delete return count
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new Exception("Query Failed: " . $e->getMessage());
        }
    }
}

class QueryBuilder {
    protected $pdo;
    protected $table;
    protected $conditions = [];
    protected $params = [];
    protected $selects = '*';
    protected $limit = null;
    protected $offset = null;
    protected $orderBy = null;

    public function __construct($pdo, $table) {
        $this->pdo = $pdo;
        // Basic SQL injection protection for table name
        $this->table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    }

    public function select(...$fields) {
        if (empty($fields)) return $this;
        $cleanFields = array_map(function($f) {
            return preg_replace('/[^a-zA-Z0-9_.*]/', '', $f);
        }, $fields);
        $this->selects = implode(', ', $cleanFields);
        return $this;
    }

    public function where($field, $operator = null, $value = null) {
        // Support where(['id' => 1]) syntax
        if (is_array($field)) {
            foreach ($field as $key => $val) {
                $this->where($key, '=', $val);
            }
            return $this;
        }

        // Support where('id', 1) syntax -> implies '='
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->conditions[] = "$field $operator ?";
        $this->params[] = $value;
        return $this;
    }

    public function orderBy($field, $direction = 'ASC') {
        $cleanField = preg_replace('/[^a-zA-Z0-9_]/', '', $field);
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy = "ORDER BY $cleanField $direction";
        return $this;
    }

    public function limit($count) {
        $this->limit = (int)$count;
        return $this;
    }

    public function offset($count) {
        $this->offset = (int)$count;
        return $this;
    }

    public function paginate($page = 1, $perPage = 10) {
        if ($page < 1) $page = 1;
        $this->limit($perPage);
        $this->offset(($page - 1) * $perPage);
        return $this;
    }

    public function get() {
        $sql = "SELECT {$this->selects} FROM {$this->table}";
        
        if (!empty($this->conditions)) {
            $sql .= " WHERE " . implode(' AND ', $this->conditions);
        }

        if ($this->orderBy) {
            $sql .= " " . $this->orderBy;
        }

        if ($this->limit) {
            $sql .= " LIMIT " . $this->limit;
        }

        if ($this->offset) {
            $sql .= " OFFSET " . $this->offset;
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw new Exception("Query Failed: " . $e->getMessage());
        }
    }

    public function first() {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function count() {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        if (!empty($this->conditions)) {
            $sql .= " WHERE " . implode(' AND ', $this->conditions);
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);
        return $stmt->fetch()['count'];
    }

    public function insert($data) {
        $keys = array_keys($data);
        $values = array_values($data);
        
        // Sanitize columns
        $cleanKeys = array_map(function($k) { 
            return preg_replace('/[^a-zA-Z0-9_]/', '', $k); 
        }, $keys);
        
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $columns = implode(', ', $cleanKeys);

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);
            return $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            throw new Exception("Insert Failed: " . $e->getMessage());
        }
    }

    public function update($data) {
        $sets = [];
        $values = [];

        foreach ($data as $key => $value) {
            $cleanKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            $sets[] = "$cleanKey = ?";
            $values[] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);
        
        if (!empty($this->conditions)) {
            $sql .= " WHERE " . implode(' AND ', $this->conditions);
            $values = array_merge($values, $this->params);
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new Exception("Update Failed: " . $e->getMessage());
        }
    }

    public function delete() {
        $sql = "DELETE FROM {$this->table}";
        
        if (!empty($this->conditions)) {
            $sql .= " WHERE " . implode(' AND ', $this->conditions);
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new Exception("Delete Failed: " . $e->getMessage());
        }
    }
}
