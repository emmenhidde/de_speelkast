<?php

require_once __DIR__ . '/../ModelCabinet/ItemStorage.php';

class ItemController
{
    public function __construct(private ItemStorage $itemStorage)
    {
    }

    public function findAll(): array
    {
        return $this->itemStorage->findAll();
    }

    public function findSelectedGameIds(string $username): array
    {
        return $this->itemStorage->findSelectedGameIds($username);
    }

    public function updateSelection(string $username, array $submittedIds): void
    {
        $gameIds = [];
        foreach ($submittedIds as $id) {
            if (!is_string($id) || preg_match('/\A[1-9][0-9]*\z/', $id) !== 1) {
                throw new InvalidArgumentException('De selectie bevat een ongeldig spel-ID.');
            }

            $id = filter_var($id, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                throw new InvalidArgumentException('De selectie bevat een ongeldig spel-ID.');
            }
            $gameIds[] = $id;
        }

        $this->itemStorage->saveSelectedGameIds($username, $gameIds);
    }

    public function renderItems(array $items, array $selectedIDs, bool $showCheckboxes = true): string
    {
        if ($items === []) {
            return '<tr><td colspan="' . ($showCheckboxes ? 3 : 2) . '">Er zijn geen spellen gevonden.</td></tr>';
        }

        $selectedIDs = array_fill_keys($selectedIDs, true);
        $rows = [];
        foreach ($items as $item) {
            $id = (int) $item['id'];
            $title = $this->escape((string) $item['title']);
            $details = [
                'Type: ' . $this->escape((string) $item['type']),
                'Is dit een bordspel: ' . ((int) $item['is_board_game'] === 1 ? 'Ja' : 'Nee'),
            ];

            $players = $this->decodeSelectionList($item['min_max_players']);
            if ($players !== null) {
                $details[] = 'Spelers: ' . $this->formatPlayers($players);
            }

            if ((int) $item['is_board_game'] === 1 && $item['board_format'] !== null) {
                $details[] = 'Bordformaat: ' . $this->formatPair($this->decodeSelectionList($item['board_format']));
            }

            if ($item['extension_game'] !== null && $item['extension_game'] !== '') {
                $details[] = 'Uitbreiding: ' . $this->escape((string) $item['extension_game']);
                if ($item['min_max_expand_players'] !== null) {
                    $expandPlayers = $this->decodeSelectionList($item['min_max_expand_players']);
                    if ($expandPlayers !== null) {
                        $details[] = 'Spelers met uitbreiding: ' . $this->formatPlayers($expandPlayers);
                    }
                }
            }

            $selectionCell = $showCheckboxes
                ? '<td style="border: 1px solid black; border-right: 0; border-radius: 3px 0 0 3px;">'
                    . '<input type="checkbox" style="transform: scale(1.5); transform-origin: left center; border: 1px solid black; border-radius: 3px;" name="game_ids[]" value="' . $id . '"'
                    . ' aria-label="Selecteer ' . $title . '"' . (isset($selectedIDs[$id]) ? ' checked' : '') . '></td>'
                : '';
            $imageCellStyle = $showCheckboxes
                ? 'border-top: 1px solid black; border-bottom: 1px solid black;'
                : 'border: 1px solid black; border-right: 0; border-radius: 3px 0 0 3px;';

            $rows[] = '<tr style="background-color: #e6f6fe;">'
                . $selectionCell
                . '<td style="' . $imageCellStyle . '"><img src="'
                . $this->escape((string) $item['photo_path']) . '" alt="Doos: ' . $title . '" width="100"></td>'
                . '<td style="border: 1px solid black; border-left: 0; border-radius: 0 3px 3px 0;">'
                . '<h3>' . $title . '</h3><ul><li>'
                . implode('</li><li>', $details)
                . '</li></ul></td></tr>';
        }

        return implode('', $rows);
    }

    private function decodeSelectionList(string $json): ?array
    {
        $values = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($values)) {
            throw new UnexpectedValueException('Een opgeslagen spelveld is ongeldig.');
        }

        return $values;
    }

    private function formatPlayers(array $players): string
    {
        return $this->formatLimit($players[0] ?? null) . ' tot ' . $this->formatLimit($players[1] ?? null);
    }

    private function formatPair(array $values): string
    {
        return $this->escape((string) ($values[0] ?? ''))
            . ' x '
            . $this->escape((string) ($values[1] ?? ''));
    }

    private function formatLimit(mixed $value): string
    {
        return $value === null ? 'onbeperkt' : $this->escape((string) $value);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
