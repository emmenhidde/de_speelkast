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
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
            $titles .= '<li>' . $title . '</li>';
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

    //TODO voeg XSLT XSD XML export toe
}

?>