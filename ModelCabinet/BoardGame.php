<?php

require_once __DIR__ . '/CabinetItem.php';

class BoardGame extends CabinetItem
{

    public const CATEGORIES = [
        'Kaartspel',
        'Bordspel',
        'Dobbelspel',
        'Rollenspel',
        'Behendigheidsspel'
    ];

    public string $category;

    public function __construct(array $item)
    {
        $this->category = (string) $item['category'];

        parent::__construct(
            (int) $item['id'],
            (string) $item['title'],
            (string) $item['photo_path'],
            $this->decodeSelectionList($item['min_max_players']),
            (int) $item['is_board_game'] === 1,
            $item['board_format'] === null
                ? null
                : $this->decodeSelectionList($item['board_format']),
            $item['owner_main'] ?? null
        );
    }

    public function description(): string
    {
        return 'Categorie: ' . $this->category;
    }

}

?>