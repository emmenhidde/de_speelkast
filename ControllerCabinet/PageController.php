<?php

require_once __DIR__ . '/ItemController.php';

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
        ItemController $itemController,
        string $message
    ): void {
        $items = $itemController->findAll();
        $selectedIDs = $itemController->findSelectedGameIds($username);

        $selectedItems = array_values(array_filter(
            $items,
            static fn(array $item): bool =>
                in_array((int) $item['id'], $selectedIDs, true)
        ));

        // Koppelt iedere toegestane kastpagina aan een HTML-bestand.
        $viewName = match ($page) {
            'select-games' => 'select_games.html',
            'my-selection' => 'my_selection.html',
            'add-game' => 'add_game.html',
        };

        // Vult de markeringen van de gekozen subpagina.
        $values = match ($page) {
            'select-games' => [
                '{{message}}' => $this->escape($message),
                '{{items}}' => $itemController->renderItems(
                    $items,
                    $selectedIDs
                ),
            ],

            'my-selection' => [
                '{{items}}' => $itemController->renderItems(
                    $selectedItems,
                    $selectedIDs,
                    false
                ),
            ],

            'add-game' => [
                '{{game_form_message}}' => $this->escape($message),

                '{{game_title}}' => $this->escapeFormValue('title'),
                '{{game_type}}' => $this->escapeFormValue('type'),
                '{{min_players}}' => $this->escapeFormValue('min_players'),
                '{{max_players}}' => $this->escapeFormValue('max_players'),
                '{{board_width}}' => $this->escapeFormValue('board_width'),
                '{{board_height}}' => $this->escapeFormValue('board_height'),
                '{{extension_game}}' => $this->escapeFormValue('extension_game'),

                '{{expansion_min_players}}' =>
                    $this->escapeFormValue('expansion_min_players'),

                '{{expansion_max_players}}' =>
                    $this->escapeFormValue('expansion_max_players'),

                // Herstelt de schakelaars na een formulierfout
                '{{board_checked}}' =>
                    ($_POST['is_board_game'] ?? '') === '1'
                        ? ' checked'
                        : '',

                '{{expansion_checked}}' =>
                    ($_POST['has_expansion'] ?? '') === '1'
                        ? ' checked'
                        : '',

                // Verbergt en blokkeert velden die niet van toepassing zijn
                '{{board_disabled}}' =>
                    ($_POST['is_board_game'] ?? '') === '1'
                        ? ''
                        : ' hidden disabled',

                '{{expansion_disabled}}' =>
                    ($_POST['has_expansion'] ?? '') === '1'
                        ? ''
                        : ' hidden disabled',

                // Geeft de uploadstatus door aan de view
                '{{image_uploaded}}' =>
                    isset($_SESSION['pending_game_image']) ? '1' : '0',

                '{{photo_status}}' =>
                    isset($_SESSION['pending_game_image'])
                        ? 'Doosfoto geüpload.'
                        : 'Nog geen doosfoto geüpload.',
            ],
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
            throw new RuntimeException(
                'De pagina kon niet worden geladen.'
            );
        }

        return strtr($template, $values);
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