<?php
require_once(__DIR__ . '/../config/database.php');

class Setting {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAllSettings() {
        $query = "SELECT * FROM settings";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    }

    public function updateSettings($section, $data) {
        try {
            $this->conn->beginTransaction();
            
            foreach ($data as $key => $value) {
                $query = "INSERT INTO settings (setting_key, setting_value) 
                         VALUES (:key, :value) 
                         ON DUPLICATE KEY UPDATE setting_value = :value";
                
                $stmt = $this->conn->prepare($query);
                $stmt->execute([
                    ':key' => $key,
                    ':value' => $value
                ]);
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
} 