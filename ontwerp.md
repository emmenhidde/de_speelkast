# Opzet Speelkast

## Model
- DbConnect.php - PDO-verbinding met MySQL
- CabinetItem.php - Abstract item met gedeelde gegevens, eigenaar en fotomethode
- BoardGame.php - Subclass voor bordspellen
- ExpansionSet.php - Subclass voor uitbreiding met hoofdspel-ID
- UserStorage.php - Gebruikers ophalen uit DB
- ItemStorage.php - Hoofdspellen, uitbreidingen, eigenaars en accountselecties ophalen en opslaan
- FileStorage.php - StorageInterface en FileStorage, handeling van uploads tijdelijke foto's
- games Formatting.xsd - Controleert de vulling van de XML-export

## View
- login_view.html - Loginformulier en meldingen
- register_view.html - Registerpagina
- cabinet_view.html - Gedeelde kastlayout met navigatie. PageController vult de gekozen subpagina
- select_games.html - Hoofdspellen en uitbreidingen met afzonderlijke selectievakjes
- my_selection.html - Opgeslagen selectie, geselecteerde titels en exportformulier
- add_game.html - Formulier voor een hoofdspel of uitbreiding met doosfoto
- evening_list.html - Formulier voor het inladen van XML
- eveningList.xsl - Bouwt avondlijst gesctructureerd op

## Controller
- AuthController.php - Login en registratie
- SessionKeeper.php - Sessie starten, geldigheid controleren en sessie wissen
- PageController.php - Views kiezen, gegevens verzamelen en pagina's opbouwen
- ItemController.php - Selectielijsten verwerken en hoofdspellen met uitbreidingen groeperen
- ImageController.php - Afbeeldingen controleren en via StorageInterface uploaden
- EveningListController.php - Valideert ingeladen XML en maakt de avondlijst via XSLT
- NewGameController.php - Invoer controleren en een hoofdspel of uitbreiding laten opslaan
- Arr2XMLController.php - Selectie of volledige kast exporteren, XML valideren en XSLT uitvoeren
- LogoutController.php - Session beëindgigen, tijdelijke foto opruimen en terugsturen naar login

## Extra
- indexFile.php - Centraal startpunt voor routes, toegangscontrole en formulieracties
- /upload/ - Opslaglocatie van afbeeldingen
- ontwerp.md - Beschrijving van structuur en functionaliteit


## Paginagebruik
indexFile.php toont login als er geen sessie is en de kast als er wel een sessie is
De kast heeft 4 subpagina's welke door PageController opgebouwd worden
    - Hoofdspellen staan links en bijbehorende uitbreidingen rechts
    Meerdere uitbreidingen van een hoofdspel staan onder elkaar, gekoppeld aan het hoofdspel
    Hoofdspellen en uitbreidingen kunnen onafhankelijk worden geselecteerd
    - Mijn selectie toont losse uitbreidingen zonder hoofdspel onderaan rechts, met een melding dat het hoofdspel mist
    Het exportformulier biedt de selectie of volledige kast als XML of HTML via XSLT
    - Het toevoegformulier begint met Type: Hoofdspel of Uitbreiding
    Een hoofdspel krijgt een categorie. een uitbreiding wordt aan een bestaand hoofdspel gekoppeld.
    Beide typen gebruiken dezelfde invoervelden voor spelers, bordmaten en foto-upload
    De doosfoto wordt eerst geüpload, daarna wordt het item eraan gekoppeld.
    Bij toevoegen wordt de ingelogde accountnaam als eigenaar opgeslagen.
Vanuit login kan een gebruiker naar registratie. Na registratie keert de gebruiker terug naar login
Een succesvolle login opent de kast, logout beëindigt de sessie en keert terug naar login

## Database
- gamesusers: id, username (uniek), hashed_password
- gamesitems: id, category, title, photo_path, min_max_players, is_board_game, board_format, owner_main
- gamesextensions: id, base_game, extension_name, extend_photo_path, min_max_extend_players, extend_is_boardgame, extend_board_size, owner_extension
- gamesclaimed: account_username, claimed_games, claimed_extensions

## mogelijke extras
- Een optie onderaan de selectiepagina teovoegen om alle dozen te selecteren waarvan het ingelogde account de eigenaar is