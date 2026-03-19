<?php
class JsonDB {
    private $dbPath;

    public function __construct($table) {
        $this->dbPath = __DIR__ . '/../data/' . $table . '.json';
        if (!file_exists($this->dbPath)) {
            file_put_contents($this->dbPath, json_encode([]));
        }
    }

    public function getAll() {
        $data = file_get_contents($this->dbPath);
        return json_decode($data, true) ?: [];
    }

    public function getById($field, $id) {
        $data = $this->getAll();
        foreach ($data as $item) {
            if ($item[$field] == $id) {
                return $item;
            }
        }
        return null;
    }

    public function insert($newData) {
        $data = $this->getAll();
        $data[] = $newData;
        file_put_contents($this->dbPath, json_encode($data, JSON_PRETTY_PRINT));
        return true;
    }

    public function update($field, $id, $updateData) {
        $data = $this->getAll();
        $updated = false;
        foreach ($data as &$item) {
            if ($item[$field] == $id) {
                $item = array_merge($item, $updateData);
                $updated = true;
                break;
            }
        }
        if ($updated) {
            file_put_contents($this->dbPath, json_encode($data, JSON_PRETTY_PRINT));
        }
        return $updated;
    }

    public function delete($field, $id) {
        $data = $this->getAll();
        $filtered = array_filter($data, function($item) use ($field, $id) {
            return $item[$field] != $id;
        });
        if (count($data) !== count($filtered)) {
            file_put_contents($this->dbPath, json_encode(array_values($filtered), JSON_PRETTY_PRINT));
            return true;
        }
        return false;
    }

    public function getLastId($field) {
        $data = $this->getAll();
        if (empty($data)) return 0;
        $ids = array_column($data, $field);
        return max($ids);
    }
}
?>
