<?php

class Database {
    private $pdo;

    public function __construct() {
        $host = '127.0.0.1';
        $db   = 'baseball_web';
        $user = 'root';
        $pass = ''; 
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;port=3306;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function getAll($table) {
        $stmt = $this->pdo->query("SELECT * FROM `$table`");
        return $stmt->fetchAll();
    }

    public function find($table, $key, $value) {
        $stmt = $this->pdo->prepare("SELECT * FROM `$table` WHERE `$key` = ?");
        $stmt->execute([$value]);
        // handle the case where we might need to fetch multiple or one. 
        // JSON find returned the *first* matching item.
        return $stmt->fetch();
    }

    public function insert($table, $data) {
        // Handle optional foreign keys like mId or form empty values
        foreach ($data as $key => &$val) {
            if ($val === '' || (is_string($val) && trim($val) === '')) {
                $val = null;
            }
        }
        
        // Ensure created_at in member has value
        if ($table === 'member' && !isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        $keys = array_keys($data);
        $fields = '`' . implode('`, `', $keys) . '`';
        $placeholders = trim(str_repeat('?,', count($keys)), ',');
        
        $sql = "INSERT INTO `$table` ($fields) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));
        return $this->pdo->lastInsertId();
    }

    public function update($table, $id, $data) {
        $pkName = $this->getIdKey($table);
        $setFields = [];
        foreach (array_keys($data) as $key) {
            $setFields[] = "`$key` = ?";
        }
        $setStr = implode(', ', $setFields);
        
        $sql = "UPDATE `$table` SET $setStr WHERE `$pkName` = ?";
        $stmt = $this->pdo->prepare($sql);
        
        $values = array_values($data);
        $values[] = $id;
        
        return $stmt->execute($values);
    }

    public function delete($table, $id) {
        $pkName = $this->getIdKey($table);
        $stmt = $this->pdo->prepare("DELETE FROM `$table` WHERE `$pkName` = ?");
        return $stmt->execute([$id]);
    }

    private function getIdKey($table) {
        $keys = [
            'member' => 'mId',
            'team' => 'team_Id',
            'player' => 'Player_id',
            'teamhistory' => 'History_Id',
            'recruitmentinfo' => 'Recruit_Id',
            'ob' => 'Ob_id',
            'game' => 'Game_id',
            'gamerecord' => 'record_id',
            'playerrecord' => 'Player_record_Id',
            'ai_analysis' => 'Analysis_Id',
            'form' => 'form_id',
            'video' => 'Video_id',
            'news' => 'news_id'
        ];
        return isset($keys[$table]) ? $keys[$table] : 'id';
    }
}
