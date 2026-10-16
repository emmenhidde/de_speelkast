<?php

// Laadt de classes die het centrale startpunt nodig heeft
require_once __DIR__ . '/ControllerCabinet/SessionKeeper.php';
require_once __DIR__ . '/ModelCabinet/DbConnect.php';
require_once __DIR__ . '/ModelCabinet/UserStorage.php';
require_once __DIR__ . '/ModelCabinet/ItemStorage.php';
require_once __DIR__ . '/ControllerCabinet/AuthController.php';
require_once __DIR__ . '/ControllerCabinet/LogoutController.php';
require_once __DIR__ . '/ControllerCabinet/ItemController.php';
require_once __DIR__ . '/ControllerCabinet/NewGameController.php';
require_once __DIR__ . '/ControllerCabinet/ImageController.php';
require_once __DIR__ . '/ControllerCabinet/PageController.php';
require_once __DIR__ . '/ControllerCabinet/Arr2XMLController.php';

// Maakt de controller die de HTML-pagina's gaat opbouwen
$pageController = new PageController();

// Controleert dat een sessiebestandsnaam veilig naar de uploadmap verwijst
function isPendingGameImageName(mixed $fileName): bool
{
    return is_string($fileName)
        && basename($fileName) === $fileName
        && preg_match('/\A[a-f0-9.]+\.(jpg|jpeg|png|gif)\z/i', $fileName) === 1;
}

// Start de sessie voordat de router gebruikersstatus controleert
SessionKeeper::startSession();

// Bepaalt de gevraagde pagina en de standaardroute
$page = $_GET['page'] ?? '';
if (!is_string($page)) {
    http_response_code(404);
    exit('Pagina niet gevonden.');
}

$loggedIn = isset($_SESSION['user_id'], $_SESSION['username'])
    && is_string($_SESSION['username']);

if ($page === '') {
    $page = $loggedIn ? 'select-games' : 'login';
}

// Geeft het uitloggen door aan LogoutController
if ($page === 'logout') {
    $logoutController = new LogoutController();
    $logoutController->logout();
}

// Verwerkt login en registratie of toont de bijbehorende HTML-view
if ($page === 'login' || $page === 'register') {
    if ($loggedIn) {
        header('Location: indexFile.php?page=select-games');
        exit;
    }

    $message = '';

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $action = $_POST['action'] ?? '';
        $username = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;

        if (
            ($page === 'login' && $action !== 'login') ||
            ($page === 'register' && $action !== 'register') ||
            !is_string($username) ||
            !is_string($password)
        ) {
            http_response_code(400);
            $message = 'Vul een geldige gebruikersnaam en wachtwoord in.';
        } else {
            $userStorage = new UserStorage((new DBConnect())->getConnection());
            $authController = new AuthController($userStorage);

            if ($page === 'login' && $authController->login($username, $password)) {
                header('Location: indexFile.php?page=select-games');
                exit;
            }

            if ($page === 'register' && $authController->registerUser($username, $password)) {
                header('Location: indexFile.php?page=login&registered=1');
                exit;
            }

            http_response_code(400);
            $message = $page === 'login'
                ? 'Onjuiste gebruikersnaam of wachtwoord.'
                : 'Registreren mislukt. Controleer de invoer of kies een andere gebruikersnaam.';
        }
    } elseif ($page === 'login' && isset($_GET['registered'])) {
        $message = 'Account aangemaakt. Je kunt nu inloggen.';
    }

    // Laat PageController de login- of registratiepagina tonen
    $pageController->showAuthPage($page, $message);
    exit;
}

// Stuurt de oude kast-URL door naar de eerste cabinet-subpagina
if ($page === 'cabinet') {
    header('Location: indexFile.php?page=select-games');
    exit;
}

$cabinetPages = ['select-games', 'my-selection', 'add-game'];
if (!in_array($page, $cabinetPages, true)) {
    http_response_code(404);
    exit('Pagina niet gevonden.');
}

// Beschermt alle kastsubpagina's tegen toegang zonder geldige gebruikerssessie
if (!$loggedIn) {
    header('Location: indexFile.php?page=login');
    exit;
}

$username = $_SESSION['username'];
$itemStorage = new ItemStorage((new DBConnect())->getConnection());
$itemController = new ItemController($itemStorage);
$newGameController = new NewGameController($itemStorage);

// Leest een melding die na een redirect moet worden getoond
$message = $_SESSION['cabinet_flash'] ?? '';
unset($_SESSION['cabinet_flash']);

// Bereidt de melding voor het formulier voor speltoevoeging voor
$gameFormMessage = $page === 'add-game' ? $message : '';

// Verwerkt upload, speltoevoeging en selectie-updates voor de actieve subpagina
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    // Verwerkt de exportknoppen onderaan de selectiepagina
    if ($page === 'my-selection' && $action === 'export_xml') {
        $items = $itemController->findAll();
        $selectedIds = $itemController->findSelectedGameIds($username);

        $selectedItems = array_values(array_filter(
            $items,
            static fn(array $item): bool =>
                in_array((int) $item['id'], $selectedIds, true)
        ));

        // Uitbreidingen hebben hun eigen opgeslagen selectie
        $extensions = $itemController->findAllExtensions();
        $extensionIds = $itemController->findSelectedExtensionIds($username);

        $selectedExtensions = array_values(array_filter(
            $extensions,
            static fn(array $extension): bool =>
                in_array((int) $extension['id'], $extensionIds, true)
        ));

        $choice = $_POST['export_choice'] ?? '';

    }

    if ($page === 'add-game' && $action === 'upload_game_image') {
        $previousImage = $_SESSION['pending_game_image'] ?? null;
        $imageController = new ImageController(__DIR__ . '/upload/');

        try {
            $fileName = $imageController->uploadImage($_FILES['file'] ?? []);
            if (isPendingGameImageName($previousImage)) {
                $previousPath = __DIR__ . '/upload/' . $previousImage;
                if (is_file($previousPath)) {
                    unlink($previousPath);
                }
            }
            $_SESSION['pending_game_image'] = $fileName;
            $gameFormMessage = 'De doosfoto is geüpload.';
        } catch (RuntimeException $exception) {
            http_response_code(400);
            $gameFormMessage = $exception->getMessage();
        }
    } elseif ($page === 'add-game' && $action === 'add_game') {
        $fileName = $_SESSION['pending_game_image'] ?? null;
        if (!isPendingGameImageName($fileName) || !is_file(__DIR__ . '/upload/' . $fileName)) {
            http_response_code(400);
            $gameFormMessage = 'Upload eerst een geldige doosfoto.';
        } else {
            $applicationPath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            $photoPath = $applicationPath . '/upload/' . $fileName;

            try {
                $newGameController->addGame($_POST, $photoPath);
                unset($_SESSION['pending_game_image']);
                $_SESSION['cabinet_flash'] = 'Het spel is toegevoegd aan de speelkast.';
                header('Location: indexFile.php?page=add-game');
                exit;
            } catch (InvalidArgumentException $exception) {
                http_response_code(400);
                $gameFormMessage = $exception->getMessage();
            }
        }
    } elseif ($page === 'select-games' && $action === 'update_selection') {
        $submittedIds = $_POST['game_ids'] ?? [];
        $submittedExtensionIds = $_POST['extension_ids'] ?? [];

        if (!is_array($submittedIds) || !is_array($submittedExtensionIds)) {
            http_response_code(400);
            exit('Ongeldige spelkeuze.');
        }

        try {
            $itemController->updateSelection($username, $submittedIds, $submittedExtensionIds);
            $_SESSION['cabinet_flash'] = 'Selectie bijgewerkt.';
            header('Location: indexFile.php?page=select-games');
            exit;
        } catch (InvalidArgumentException $exception) {
            http_response_code(400);
            $message = $exception->getMessage();
        }
    } else {
        http_response_code(400);
        exit('Ongeldige actie.');
    }
}

// Toont de kastpagina met de melding die bij deze pagina hoort
$pageController->showCabinet(
    $page,
    $username,
    $itemController,
    $page === 'add-game' ? $gameFormMessage : $message
);

?>