<?php

require_once __DIR__ . '/CabinetItem.php';

class ExpansionSet extends CabinetItem
{
    public int $baseGame;

    public function __construct(array $item)
    {
        $this->baseGame = (int) $item['base_game'];

        parent::__construct(
            (int) $item['id'],
            (string) $item['extension_name'],
            (string) $item['extend_photo_path'],
            $this->decodeSelectionList($item['min_max_extend_players']),
            (int) $item['extend_is_boardgame'] === 1,
            $item['extend_board_size'] === null
                ? null
                : $this->decodeSelectionList($item['extend_board_size']),
            $item['owner_extension'] ?? null
        );
    }

    public function description(): string
    {
        return 'Dit is een uitbreidingsset (hoofdspel-ID: ' . $this->baseGame . ')';
    }

}

?>