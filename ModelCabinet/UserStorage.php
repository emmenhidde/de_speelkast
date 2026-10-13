<?php

class UserStorage
{
    public function __construct(private PDO $connection
    ) {
    }

    public function findByUsername(string $username): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, username, hashed_password FROM gamesusers WHERE username = :username'
        );
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function createUser(string $username, string $passwordHash): bool
    {
        $statement = $this->connection->prepare(
            'INSERT INTO gamesusers (username, hashed_password) VALUES (:username, :hashed_password)'
        );

        return $statement->execute([
            'username' => $username,
            'hashed_password' => $passwordHash,
        ]);
    }
}
