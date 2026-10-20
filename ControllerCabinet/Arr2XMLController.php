<?php

class Arr2XMLController
{
    // Maakt een HTML-lijst van geselcteerde titels
    public function renderSelectedTitles(array $selectedGames): string
    {
        if ($selectedGames === []) {
            return '<li>Je hebt nog geen spellen geselecteerd.</li>';
        }

        $titles = '';

        foreach ($selectedGames as $game) {
            $title = htmlspecialchars(
                (string) ($game['title'] ?? $game['extension_name']),
                ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');

            $owner = htmlspecialchars(
                (string) ($game['owner_main'] ?? $game['owner_extension'] ?? 'Onbekend'),
                ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');

            $titles .= '<li>' . $title . ' - Eigenaar: ' . $owner . '</li>';
        }

        return $titles;
    }

    // Kiest opgeslagen selectie of volledige spelcatalogus
    private function chooseSelectionOrAll(
        array $selectedGames,
        array $allGames,
        string $choice
    ): array {
        return match ($choice) {
            'selection' => $selectedGames,
            'all' => $allGames,
            default => throw new InvalidArgumentException('Ongeldige exportkeuze.'),
        };
    }

    // Bouwt XML, valideert deze en downloadt XML
    public function exportXML(
        array $selectedGames,
        array $allGames,
        string $choice
    ): void {
        $games = $this->chooseSelectionOrAll(
            $selectedGames,
            $allGames,
            $choice
        );

        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $root = $xml->createElement('games');
        $xml->appendChild($root);

        // Behoudt de bestaande velden, inclusief de eigenaar.
        foreach ($games as $game) {
            $gameElement = $xml->createElement(
                isset($game['base_game']) ? 'extension' : 'game'
            );
            $root->appendChild($gameElement);

            foreach ($game as $field => $value) {
                $fieldElement = $xml->createElement($field);
                $fieldElement->appendChild(
                    $xml->createTextNode((string) $value)
                );
                $gameElement->appendChild($fieldElement);
            }
        }

        // Controleert de XML voordat de download begint.
        $previousErrors = libxml_use_internal_errors(true);

        try {
            if (!$xml->schemaValidate(
                __DIR__ . '/../ModelCabinet/gamesFormatting.xsd'
            )) {
                throw new RuntimeException('De XML voldoet niet aan gamesFormatting.xsd.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }

        // Downloadt uitsluitend XML; de avondlijst verzorgt de XSLT-weergave.
        $output = $xml->saveXML();

        if (!is_string($output)) {
            throw new RuntimeException('Het XML-bestand kon niet worden gemaakt.');
        }

        $fileName = (
            $choice === 'all' ? 'speelkast' : 'mijn-selectie'
        ) . '.xml';

        header('Content-Type: application/xml; charset=UTF-8');
        header(
            'Content-Disposition: attachment; filename="' . $fileName . '"'
        );

        echo $output;
        exit;
    }
}

?>