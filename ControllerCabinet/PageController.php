<?php

require_once __DIR__ . '/ItemController.php';
require_once __DIR__ . '/Arr2XMLController.php';

class PageController
{
    // Kiest de login- of registratieview en vult de melding in.
    public function showAuthPage(string $page, string $message): void
    {
        $viewName = match ($page) {
            'login' => 'login_view.html',
            'register' => 'register_view.html',
        };

        echo $this->renderView($viewName, [
            '{{message}}' => $this->escape($message),
        ]);
    }

    // Haalt spelgegevens op en bouwt de gekozen kastpagina op.
    public function showCabinet(
        string $page,
        string $username,
        ?ItemController $itemController,
        string $message,
        string $eveningList = ''
    ): void {

        if ($page !== 'avondlijst') {
            if ($itemController === null) {
                throw new LogicException('Een itemcontroller is nodig voor deze pagina.');
            }
            $items = $itemController->findAll();
        }

        if (in_array($page, ['select-games', 'my-selection'], true)) {
            $selectedIDs = $itemController->findSelectedGameIds($username);
            $extensions = $itemController->findAllExtensions();
            $selectedExtensionIDs = $itemController->findSelectedExtensionIds($username);
        }

        if ($page === 'my-selection') {
            $selectedExtensions = array_filter(
                $extensions,
                static fn(array $extension): bool =>
                    in_array((int) $extension['id'], $selectedExtensionIDs, true)
            );

            $selectedItems = array_filter(
                $items,
                static fn(array $item): bool =>
                    in_array((int) $item['id'], $selectedIDs, true)
            );
        } 

        // Koppelt iedere toegestane kastpagina aan een HTML-bestand.
        $viewName = match ($page) {
            'select-games' => 'select_games.html',
            'my-selection' => 'my_selection.html',
            'add-game' => 'add_game.html',
            'avondlijst' => 'evening_list.html',
        };

        if ($page === 'add-game') 
            {$baseGameOptions = [];

            foreach ($items as $item) {
                $baseGameOptions[$item['id']] = $item['title']
                    . ' (eigenaar: '
                    . ($item['owner_main'] ?? 'Onbekend')
                    . ')';
            }
        }

        // Vult de markeringen van de gekozen subpagina.
        $values = match ($page) {
            'select-games' => [
                '{{message}}' => $this->escape($message),
                '{{items}}' => $itemController->renderGroupedItems(
                    $items,
                    $extensions,
                    $selectedIDs,
                    $selectedExtensionIDs
                ),
            ],          

            'my-selection' => [
                '{{items}}' => $itemController->renderGroupedItems(
                $items,
                $extensions,
                $selectedIDs,
                $selectedExtensionIDs,
                false
                ),

                '{{selected_titles}}' => (new Arr2XMLController())
                    ->renderSelectedTitles(array_merge($selectedItems, $selectedExtensions)),
            ],

            'avondlijst' => [
                '{{evening_message}}' => $this->escape($message),
                '{{Evening_list}}' => $eveningList,
            ],

            'add-game' => [
                '{{game_form_message}}' => $this->escape($message),
                '{{game_title}}' => $this->escapeFormValue('title'),
                '{{min_players}}' => $this->escapeFormValue('min_players'),
                '{{max_players}}' => $this->escapeFormValue('max_players'),
                '{{board_width}}' => $this->escapeFormValue('board_width'),
                '{{board_height}}' => $this->escapeFormValue('board_height'),

                // Herstelt de gekozen soort na upload of een invoerfout.
                '{{extension_selected}}' =>
                    ($_POST['item_kind'] ?? '') === 'extension'
                        ? ' selected'
                        : '',

                // Bouwt de dropdowns voor categorie en hoofdspel.
                '{{category_options}}' => $this->renderOptions(
                    array_combine(BoardGame::CATEGORIES, BoardGame::CATEGORIES),
                    'category'
                ),

                '{{base_game_options}}' => $this->renderOptions(
                    $baseGameOptions,
                    'base_game'
                ),

                // Behoudt de bestaande bordspelschakelaar.
                '{{board_checked}}' =>
                    ($_POST['is_board_game'] ?? '') === '1'
                        ? ' checked'
                        : '',

                '{{board_disabled}}' =>
                    ($_POST['is_board_game'] ?? '') === '1'
                        ? ''
                        : ' hidden disabled',

                // Beide typen gebruiken dezelfde bestaande foto-upload.
                '{{image_uploaded}}' =>
                    isset($_SESSION['pending_game_image']) ? '1' : '0',

                '{{photo_status}}' =>
                    isset($_SESSION['pending_game_image'])
                        ? 'Doosfoto geüpload.'
                        : 'Nog geen doosfoto geüpload.'
            ]
        };

        // Plaatst de gevulde subpagina in de gedeelde kastlayout
        $content = $this->renderView($viewName, $values);

        echo $this->renderView('cabinet_view.html', [
            '{{username}}' => $this->escape($username),
            '{{content}}' => $content,

            '{{select_games_current}}' =>
                $page === 'select-games' ? 'page' : 'false',

            '{{my_selection_current}}' =>
                $page === 'my-selection' ? 'page' : 'false',

            '{{add_game_current}}' =>
                $page === 'add-game' ? 'page' : 'false',

            '{{evening_list_current}}' =>
            $page === 'avondlijst' ? 'page' : 'false',
        ]);
    }

    // Leest een HTML-view en vervangt bijvoorbeeld {{message}}
    private function renderView(
        string $viewName,
        array $values = []
    ): string {
        $template = file_get_contents(
            __DIR__ . '/../ViewCabinet/' . $viewName
        );

        if ($template === false) {
            throw new RuntimeException('De pagina kon niet worden geladen.');
        }

        return strtr($template, $values);
    }

    // Maakt dropdownopties en selecteert de eerder gekozen waarde
    private function renderOptions(array $options, string $key): string
    {
        $html = '';
        $chosen = $_POST[$key] ?? '';

        foreach ($options as $value => $label) {
            $selected = $chosen === (string) $value ? ' selected' : '';

            $html .= '<option value="'
                . $this->escape((string) $value)
                . '"' . $selected . '>'
                . $this->escape((string) $label)
                . '</option>';
        }

        return $html;
    }

    // Zet een eerder ingevulde formulierwaarde veilig terug in de HTML
    private function escapeFormValue(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? $this->escape($value) : '';
    }

    // Zorgt dat gewone tekst niet als HTML wordt uitgevoerd
    private function escape(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}

?>