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

    public function userLogout(): void
    {
        SessionKeeper::startSession();
        SessionKeeper::clearSession();
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

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'logout') {
        SessionKeeper::startSession();
        SessionKeeper::clearSession();
        header('Location: ../ViewCabinet/login_view.html');
        exit;
    }

    if ($action !== 'login' && $action !== 'register') {
        http_response_code(400);
        exit('Ongeldige actie.');
    }

    $username = $_POST['username'] ?? null;
    $password = $_POST['password'] ?? null;

    if (!is_string($username) || !is_string($password)) {
        http_response_code(400);
        exit('Vul een geldige gebruikersnaam en wachtwoord in.');
    }

    require_once __DIR__ . '/../ModelCabinet/DbConnect.php';
    $database = new DBConnect();
    $userStorage = new UserStorage($database->getConnection());
    $authController = new AuthController($userStorage);

    if ($action === 'login' && $authController->login($username, $password)) {
        header('Location: ../ViewCabinet/cabinet_view.html');
        exit;
    }

    if ($action === 'register' && $authController->registerUser($username, $password)) {
        header('Location: ../ViewCabinet/login_view.html');
        exit;
    }

    http_response_code(400);
    $message = $action === 'register'
        ? 'Registreren mislukt. Controleer de invoer of kies een andere gebruikersnaam.'
        : 'Inloggen mislukt. Controleer je gebruikersnaam en wachtwoord.';
    $backLink = $action === 'register' ? 'register_view.html' : 'login_view.html';

    echo '<p role="alert">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<a href="../ViewCabinet/' . $backLink . '">Terug naar het formulier</a>';
}
