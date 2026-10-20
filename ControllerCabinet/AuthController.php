<?php

require_once __DIR__ . '/../ModelCabinet/UserStorage.php';
require_once __DIR__ . '/SessionKeeper.php';

class AuthController
{
    public function __construct(
        private UserStorage $userStorage
    ) {
    }

    public function login(string $username, string $password): bool
    {
        if (!$this->validUsername($username) || !$this->validPassword($password)) {
            return false;
        }

        SessionKeeper::startSession();
        $user = $this->userStorage->findByUsername(trim($username));

        if (!$user || !password_verify($password, $user['hashed_password'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        return true;
    }

    public function validUsername(string $username): bool
    {
        return preg_match('/\A[A-Za-z]{4,15}\z/', $username) === 1;
    }

    public function validPassword(string $password): bool
    {
        return preg_match('/\A[A-Za-z0-9]{6,15}\z/', $password) === 1;
    }

    public function registerUser(string $username, string $password): bool
    {
        if (
            !$this->validUsername($username) ||
            !$this->validPassword($password) ||
            $this->userStorage->findByUsername($username) !== null
        ) {
            return false;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        return $this->userStorage->createUser($username, $passwordHash);
    }
}

?>