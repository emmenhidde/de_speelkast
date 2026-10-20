<?php

require_once __DIR__ . '/../ModelCabinet/FileStorage.php';

final class SessionKeeper
{
    private const sessionDuration = 2 * 24 * 60 * 60; // days * hours * minutes * seconds

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.gc_maxlifetime', (string) self::sessionDuration);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (!session_start()) {
            throw new RuntimeException('De sessie kon niet worden gestart.');
        }

        $lastActivity = $_SESSION['last_activity'] ?? null;
        if (array_key_exists('last_activity', $_SESSION) && (
            !is_int($lastActivity) ||
            time() - $lastActivity > self::sessionDuration
        )) {
            (new FileStorage())->removeImage($_SESSION['pending_game_image'] ?? null);
            session_regenerate_id(true);
            $_SESSION = [];
        }

        $_SESSION['last_activity'] = time();
    }

    public static function clearSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'] ?? 'Lax',
        ]);
        session_destroy();
    }
}
?>