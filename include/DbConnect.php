<?php

class DbConnect {
 
    public $conn;
 
    function __construct() {        
    }

    // abre la conexion a la base de datos
    function connect() {
        try {
            $this->conn = new PDO('mysql:host=' .DB_HOST.';dbname='.DB_NAME.';port='.DB_PORT.';charset=utf8', DB_USERNAME, DB_PASSWORD);

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

            // retorna la conexion
            return $this->conn;

        } catch(PDOException $ex) {
            echo 'An error occured connecting to the database! Details: ' . $ex->getMessage();
            exit;
        }
    }
}
?>