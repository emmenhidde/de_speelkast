<?php

require __DIR__ . '/SessionKeeper.php';

class LogoutController
{
    public function logout(): void
    {
        SessionKeeper::startSession();
        SessionKeeper::clearSession();

        header('Location: ../ViewCabinet/login_view.html');
        exit;
    }
}

$controller = new LogoutController();
$controller->logout();