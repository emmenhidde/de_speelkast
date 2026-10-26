<?php

require_once __DIR__ . '/../ModelCabinet/ItemStorage.php';
require_once __DIR__ . '/../ModelCabinet/BoardGame.php';
require_once __DIR__ . '/../ModelCabinet/ExpansionSet.php';

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

    // Haalt alle uitbreidingen op
    public function findAllExtensions(): array
    {
        return $this->itemStorage->findAllExtensions();
    }

    // Pullt ID van geselecteerde uitbreidingen
    public function findSelectedExtensionIds(string $username): array
    {
        return $this->itemStorage->findSelectedGameIds($username, true);
    }

    public function updateSelection(string $username, array $submittedIds, array $submittedExtensionIds): void
    {
        $selections = [];
        foreach ([$submittedIds, $submittedExtensionIds] as $submitted) {
            $gameIds = [];
            foreach ($submitted as $id) {
                if (
                    !is_string($id) ||
                    preg_match('/\A[1-9][0-9]*\z/', $id) !== 1
                ) {
                    throw new InvalidArgumentException('De selectie bevat een ongeldig ID.');
                }

                $id = filter_var($id, FILTER_VALIDATE_INT);

                if ($id === false || $id < 1) {
                    throw new InvalidArgumentException('De selectie bevat een ongeldig ID.');
                }

                $gameIds[] = $id;
            }

            $selections[] = $gameIds;
        }

        $this->itemStorage->saveSelectedGameIds(
            $username,
            $selections[0],
            $selections[1]
        );
    }

    public function renderItems(array $items, array $selectedIDs, bool $showCheckboxes = true, bool $isExtension = false): string
    {
        if ($items === []) {
            return '<tr><td colspan="' . ($showCheckboxes ? 3 : 2) . '">Er zijn geen spellen gevonden.</td></tr>';
        }

        $selectedIDs = array_fill_keys($selectedIDs, true);
        $rows = [];
        foreach ($items as $item) {
            $cabinetItem = $isExtension
                ? new ExpansionSet($item)
                : new BoardGame($item);

            $id = $cabinetItem->id;
            $title = $this->escape($cabinetItem->title);

            $details = [
                $this->escape($cabinetItem->description()),
                'Eigenaar: ' . $this->escape($cabinetItem->owner ?? 'Onbekend'),
                'Spelers: ' . $this->formatPlayers($cabinetItem->minMaxPlayers)
            ];

            if ($cabinetItem->isBoardGame) {
                $details[] = 'Bordformaat: ' . (
                    $cabinetItem->boardFormat === null
                        ? 'Onbekend of variabel'
                        : $this->formatPair($cabinetItem->boardFormat) . ' cm');
            }

            $photoPath = $cabinetItem->showImage();

            $image = $cabinetItem->checkFileName($photoPath)
                ? '<img src="' . $this->escape($photoPath)
                    . '" alt="Doos: ' . $title . '" width="100">'
                : 'Geen geldige doosfoto beschikbaar.';

            // Beide soorten behouden hun eigen selectielijst
            $selectionName = $isExtension
                ? 'extension_ids[]'
                : 'game_ids[]';

            $selectionCell = $showCheckboxes
                ? '<td style="width: 40px;">'
                    . '<input type="checkbox"'
                    . ' style="transform: scale(1.5); transform-origin: left center;"'
                    . ' name="' . $selectionName . '"'
                    . ' value="' . $id . '"'
                    . ' aria-label="Selecteer ' . $title . '"'
                    . (isset($selectedIDs[$id]) ? ' checked' : '')
                    . '></td>'
                : '';

            $rows[] = '<tr>'
                . $selectionCell
                . '<td style="width: 120px;">' . $image . '</td>'
                . '<td><h3>' . $title . '</h3>'
                . '<ul><li>' . implode('</li><li>', $details) . '</li></ul>'
                . '</td></tr>';
        }

        return implode('', $rows);
    }

    // Groepeert itemweergave in linker en rechter kolom en plaatst losse uitbreidingsselecties onderaan
    public function renderGroupedItems(
        array $items,
        array $extensions,
        array $selectedIDs,
        array $selectedExtensionIDs,
        bool $showCheckboxes = true
    ): string {
        $rows = '';
        $orphanRows = '';
        $groups = [];

        // Groepeert zichtbare uitbreidingen op hoofdspelId
        foreach ($extensions as $extension) {
            if (
                $showCheckboxes ||
                in_array((int) $extension['id'], $selectedExtensionIDs, true)
            ) {
                $groups[(int) $extension['base_game']][] = $extension;
            }
        }

        // Deze opmaak geldt voor de omlijnde spelvlakken
        $blockStyle = 'width: 50%; vertical-align: top;'
            . ' border: 1px solid black; background-color: #e6f6fe;';

        foreach ($items as $item) {
            $id = (int) $item['id'];
            $selected = in_array($id, $selectedIDs, true);
            $relatedExtensions = $groups[$id] ?? [];

            // Volledig ongeselecteerde groepen worden overgeslagen
            if (
                !$showCheckboxes &&
                !$selected &&
                $relatedExtensions === []
            ) {
                continue;
            }

            // Bouwt rechterhelft met bijbehorende uitbreidingen
            $extensionBlocks = [];

            foreach ($relatedExtensions as $extension) {
                $extension['base_game_title'] = (string) $item['title'];
                $extensionBlocks[] =
                    '<table style="width: 100%; text-align: left;">'
                    . $this->renderItems(
                        [$extension],
                        $selectedExtensionIDs,
                        $showCheckboxes,
                        true
                    )
                    . '</table>';
            }

            $right = implode('<hr>', $extensionBlocks);

            // Een losse uitbreiding heeft geen geselecteerd hoofdspel
            $orphanExtension = !$showCheckboxes && !$selected;

            $left = $orphanExtension
                ? '<h3>Deze uitbreiding is geselecteerd zonder het hoofdspel: '
                    . $this->escape((string) $item['title'])
                    . '</h3>'
                : '<table style="width: 100%; text-align: left;">'
                    . $this->renderItems(
                        [$item],
                        $selectedIDs,
                        $showCheckboxes
                    )
                    . '</table>';

            $leftStyle = $orphanExtension
                ? 'width: 50%; vertical-align: top; padding: 12px;'
                : $blockStyle;

            $rightStyle = $extensionBlocks === []
                ? 'width: 50%; vertical-align: top;'
                : $blockStyle;

            $row = '<tr>'
                . '<td style="' . $leftStyle . '">' . $left . '</td>'
                . '<td style="' . $rightStyle . '">' . $right . '</td>'
                . '</tr>';

            // Uitbreiding zonder hoofdspel
            if ($orphanExtension) {
                $orphanRows .= $row;
            } else {
                $rows .= $row;
            }
        }

        // Voegt losse uitbreidingsgroepen toe
        return $rows . $orphanRows
            ?: '<tr><td colspan="2">Er zijn geen spellen geselecteerd.</td></tr>';
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
        return $value === null ? 'je kamer vol is' : $this->escape((string) $value);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

?>