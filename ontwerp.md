# Opzet Speelkast

## Model
- DbConnect.php - PDO-verbinding met MySQL
- CabinetItem.php - Abstract kastitem met PhotoInterface en abstracte description()
- BoardGame.php - Subclass voor bordspellen; boardDescription()
- ExpansionSet.php - Subclass voor uitbreidingen; expandDescription()
- UserStorage.php - Gebruikers ophalen uit DB
- ItemStorage.php - Items ophalen en plaatsen in DB

## View
- login_view.html - Loginformulier en meldingen
- register_view.html - Registerpagina
- cabinet_view.html - Alle itemsen fotos. Optioneel items toevoegen en selectie exporten als xml

## Controller
- AuthController.php - Login en logout
- SessionKeeper.php - Sessiegeldigheid en toegangscontrole
- LogoutController.php - Uitloggen en terugkeren naar login_view
- ItemController.php - Kast, toevoegen, PHP-validatie 
- ImageController.php - Valideerd de geldigheid van een Image om toe te voegen

## Extra
- /upload/ - Opslaglocatie van afbeeldingen
- ontwerp.md - Structuur, toelichting van files


## Paginaflow
Login en UserInfo geven toegang tot de kast
Logout beëindigt de sessie
Vanuit de kast kun je toevoegen of exporteren


## Database
- gamesusers: id, username (uniek), hashed_password
- gamesItems: id, type, title, owner_id, photo_path, min_max_players, extension_game, is_board_game, board_format, min_max_expand_players
- owner_id verwijst naar users.id
- Bordspellen gebruiken min_max_players; uitbreidingen gebruiken min_max_expand_players
