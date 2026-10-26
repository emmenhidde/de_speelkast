<?php

class DBConnect
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = new PDO(
            'mysql:host=localhost;dbname=despeelkastdb;charset=utf8mb4',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}

?>