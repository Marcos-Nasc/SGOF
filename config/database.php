<?php
// /config/database.php

class Database {
    private $host = "localhost";
    private $db_name = "sgof_logistica";
    private $username = "root";
    private $password = ""; // Ajuste para a senha do seu ambiente local/servidor
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Erro de Conexão: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>