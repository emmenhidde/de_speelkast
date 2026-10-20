# De Speelkast

## Starten
De applicatie gebruikt de MySQL database despeelkastdb en Apache via localhost.
De DB tabellen zijn gamesusers, gamesitems, gamesextensions en gamesclaimed.

## Inloggen
Kan via een bestaand account of via ragistratie kan een account worden aangemaakt.
Gebruikersnamen bestaan uit 4 tot 15 letters.
Gebruikersnamen zijn ook inzichtelijk voor andere gebruikers door het dooseigendom.
Wachtwoorden bestaan uit 6 tot 15 letters en/of cijfers.

## Klassen en opslag
CabinetItem is abstract en implementeert PhotoInterface.
BoardGame en ExpansionSet erven hiervan en worden gebruikt bij de weergave.
FileStorage implementeert StorageInterface en wordt aan ImageController doorgegeven.
UserStorage en ItemStorage ontvangen hun PDO-verbinding via de constructor.
Doosfoto's staan in upload/ onder een unieke bestandsnaam.
Het account dat een item toevoegt, wordt als eigenaar van deze doos opgeslagen.

## Validatie
JavaScript geeft invoerfeedback vóór het verzenden.
PHP controleert de invoer opnieuw, omdat clientvalidatie kan worden omzeild.

## Avondlijst
Exporteer via Mijn selectie een XML-bestand.
Open Avondlijst en laad dat XML-bestand in.
gamesFormatting.xsd controleert de structuur.
eveningList.xsl maakt de HTML-weergave.
De ingeladen XML wordt niet permanent opgeslagen.