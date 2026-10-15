# Opzet Speelkast

## Model
- DbConnect.php - PDO-verbinding met MySQL
- CabinetItem.php - Abstract kastitem met hoofdvulling van een item
- BoardGame.php - Subclass voor bordspellen
- ExpansionSet.php - Subclass voor uitbreidingen
- UserStorage.php - Gebruikers ophalen uit DB
- ItemStorage.php - Items ophalen en plaatsen in DB

## View
- login_view.html - Loginformulier en meldingen
- register_view.html - Registerpagina
- cabinet_view.html - Gedeelde kastlayout met navigatie. PageController vult de gekozen subpagina
- select_games.html - Spelcatalogus met accountselectie
- my_selection.html - Opgeslagen spelkeuze en XML-exportplaceholder
- add_game.html - Formulier voor spelgegevens en doosfoto

## Controller
- AuthController.php - Login en registratie
- SessionKeeper.php - Sessie starten, geldigheid controleren en sessie wissen
- PageController.php - Views kiezen, gegevens verzamelen en pagina's opbouwen
- ItemController.php - Spellen ophalen, accountselectie bijwerken en tabelrijen maken
- ImageController.php - Afbeeldingen controleren en uploaden
- NewGameController.php - Nieuwe spelgegevens valideren en laten opslaan
- LogoutController.php - Session beëindgigen, tijdelijke foto opruimen en terugsturen naar login

## Extra
- indexFile.php - Centraal startpunt voor routes, toegangscontrole en formulieracties
- /upload/ - Opslaglocatie van afbeeldingen
- ontwerp.md - Beschrijving van structuur en functionaliteit


## Paginaflow
indexFile.php toont login als er geen sessie is en de kast als er wel een sessie is
De kast heeft subpagina's voor spellen selecteren, mijn selectie en spel toevoegen
Vanuit login kan een gebruiker naar registratie; na registratie keert de gebruiker terug naar login
Een succesvolle login opent de kast; logout beëindigt de sessie en keert terug naar login


## Database
- gamesusers: id, username (uniek), hashed_password
- gamesitems: id, type, title, photo_path, min_max_players, is_board_game, board_format, extension_game, min_max_expand_players
- gamesClaimed: account_username, claimed_games (JSON-lijst met geselecteerde spel-ID's)
- De kast toont alle spellen; de gamesselectie wordt per gebruikersnaam opgeslagen in gamesClaimed
- Nieuwe spellen worden opgeslagen in gamesitems. optionele bord- en uitbreidingsgegevens zijn NULL als ze niet van toepassing zijn
