<?php

class DBConnect
{
    private PDO $connection;

    public function __construct()
    {
        $config = @parse_ini_file(__DIR__ . '/../configuration/.env',false,INI_SCANNER_RAW);

        if ($config === false) {
            throw new RuntimeException('configuration/.env ontbreekt of is ongeldig.');
        }

        foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'] as $key) {
            if (!isset($config[$key]) || !is_string($config[$key])) {
                throw new RuntimeException('De databaseconfiguratie is onvolledig.');
            }
        }

        $this->connection = new PDO(
            'mysql:host=' . 
            $config['DB_HOST'] . ';dbname=' . 
            $config['DB_NAME'] . ';charset=utf8mb4',
            $config['DB_USER'],
            $config['DB_PASSWORD'],
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