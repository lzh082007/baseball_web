<?php
class JsonDB {
    private $dataDir;

    public function __construct($dataDir = __DIR__ . '/../data/') {
        $this->dataDir = rtrim($dataDir, '/') . '/';
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }

    private function getFilePath($table) {
        return $this->dataDir . $table . '.json';
    }

    public function getAll($table) {
        $filePath = $this->getFilePath($table);
        if (!file_exists($filePath)) {
            return [];
        }
        $content = file_get_contents($filePath);
        return json_decode($content, true) ?: [];
    }

    public function saveAll($table, $data) {
        $filePath = $this->getFilePath($table);
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return true;
    }

    public function find($table, $key, $value) {
        $items = $this->getAll($table);
        foreach ($items as $item) {
            if (isset($item[$key]) && $item[$key] == $value) {
                return $item;
            }
        }
        return null;
    }

    public function insert($table, $data) {
        $items = $this->getAll($table);
        $idKey = $this->getIdKey($table);
        
        $maxId = 0;
        foreach ($items as $item) {
            if (isset($item[$idKey]) && $item[$idKey] > $maxId) {
                $maxId = $item[$idKey];
            }
        }
        
        $data[$idKey] = $maxId + 1;
        $items[] = $data;
        $this->saveAll($table, $items);
        return $data[$idKey];
    }

    public function update($table, $id, $data) {
        $items = $this->getAll($table);
        $idKey = $this->getIdKey($table);
        $updated = false;
        
        foreach ($items as &$item) {
            if (isset($item[$idKey]) && $item[$idKey] == $id) {
                $item = array_merge($item, $data);
                $item[$idKey] = $id; // Ensure ID remains the same
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            $this->saveAll($table, $items);
        }
        return $updated;
    }

    public function delete($table, $id) {
        $items = $this->getAll($table);
        $idKey = $this->getIdKey($table);
        $newItems = [];
        $deleted = false;
        
        foreach ($items as $item) {
            if (isset($item[$idKey]) && $item[$idKey] == $id) {
                $deleted = true;
                continue;
            }
            $newItems[] = $item;
        }
        
        if ($deleted) {
            $this->saveAll($table, $newItems);
        }
        return $deleted;
    }

    private function getIdKey($table) {
        // Map table to its PK as defined in doc
        $keys = [
            'member' => 'm_id',
            'team' => 'team_id',
            'player' => 'player_id',
            'team_history' => 'history_id',
            'recruitment_info' => 'recruit_id',
            'ob_member' => 'ob_id',
            'game' => 'game_id',
            'game_record' => 'record_id',
            'player_performance' => 'perf_id',
            'ai_analysis' => 'analysis_id',
            'form_application' => 'form_id',
            'video' => 'video_id',
            'news' => 'news_id'
        ];
        return isset($keys[$table]) ? $keys[$table] : 'id';
    }
}
