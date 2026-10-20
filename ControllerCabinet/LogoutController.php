<?php

require_once __DIR__ . '/SessionKeeper.php';
require_once __DIR__ . '/../ModelCabinet/FileStorage.php';

class LogoutController
{
    public function logout(): void
    {
        // Staat uitloggen alleen toe via een POST-formulier
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Methode niet toegestaan.');
        }

        // Start de sessie als deze nog niet actief is
        SessionKeeper::startSession();

        // Verwijdert een geüploade foto die nog niet bij een spel hoort
        $this->removePendingGameImage();

        // Wist de sessiegegevens en de sessiecookie
        SessionKeeper::clearSession();

        // Stuurt de gebruiker na het uitloggen naar de loginpagina
        header('Location: indexFile.php?page=login', true, 303);
        exit;
    }

    private function removePendingGameImage(): void
    {
        (new FileStorage())->removeImage($_SESSION['pending_game_image'] ?? null);
        unset($_SESSION['pending_game_image']);
    }
}

?>