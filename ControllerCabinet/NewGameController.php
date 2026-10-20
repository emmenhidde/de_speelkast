<?php

require_once __DIR__ . '/../ModelCabinet/ItemStorage.php';
require_once __DIR__ . '/../ModelCabinet/BoardGame.php';

class NewGameController
{
    public function __construct(private ItemStorage $itemStorage)
    {
    }

    public function addGame(array $input, string $photoPath, string $owner): int
    {
        if (trim($owner) === '') {
            throw new InvalidArgumentException('Log opnieuw in om een spel toe te voegen.');
        }

        $itemKind = $input['item_kind'] ?? '';

        if (!in_array($itemKind, ['game', 'extension'], true)) {
            throw new InvalidArgumentException('Kies Hoofdspel of Uitbreiding.');
        }

        $title = $this->validatedText($input, 'title', 255, 'Naam');

        $players = $this->playerRange(
            $input,
            'min_players',
            'max_players',
            'Minimumaantal spelers',
            'Maximumaantal spelers'
        );

        $isBoardGame = $this->isChecked($input, 'is_board_game');

        $boardFormat = $isBoardGame
            ? [
                $this->positiveInteger($input['board_width'] ?? null, 'Bordbreedte'),
                $this->positiveInteger($input['board_height'] ?? null, 'Bordhoogte')
            ]
            : null;

        // Beide tabellen bewaren spelers en bordmaten op dezelfde manier.
        $playersJson = json_encode($players, JSON_THROW_ON_ERROR);
        $boardJson = $boardFormat === null
            ? null
            : json_encode($boardFormat, JSON_THROW_ON_ERROR);

        // Een uitbreiding krijgt een eigen foto en een hoofdspel-ID.
        if ($itemKind === 'extension') {
            return $this->itemStorage->addExtension([
                'base_game' => $this->positiveInteger($input['base_game'] ?? null,'Hoofdspel'),
                'extension_name' => $title,
                'extend_photo_path' => $photoPath,
                'min_max_extend_players' => $playersJson,
                'extend_is_boardgame' => $isBoardGame ? 1 : 0,
                'extend_board_size' => $boardJson,
                'owner_extension' => $owner
            ]);
        }

        // Alleen hoofdspellen krijgen een categorie.
        $category = $input['category'] ?? '';
        if (!in_array($category, BoardGame::CATEGORIES, true)) {
            throw new InvalidArgumentException('Kies een geldige categorie.');
        }

        return $this->itemStorage->add([
            'category' => $category,
            'title' => $title,
            'photo_path' => $photoPath,
            'min_max_players' => $playersJson,
            'is_board_game' => $isBoardGame ? 1 : 0,
            'board_format' => $boardJson,
            'owner_main' => $owner
        ]);
    }

    private function validatedText(array $input, string $key, int $maximumLength, string $label): string
    {
        $value = $input[$key] ?? null;
        if (!is_string($value)) {
            throw new InvalidArgumentException($label . ' is verplicht.');
        }

        $value = trim($value);
        if (
            $value === '' ||
            strlen($value) > $maximumLength ||
            preg_match('/\A[A-Za-z0-9 .,;:_?|-]+\z/', $value) !== 1
        ) {
            throw new InvalidArgumentException($label . ' bevat ongeldige tekens of is te lang.');
        }

        return $value;
    }

    private function positiveInteger(mixed $value, string $label): int
    {
        if (!is_string($value) || preg_match('/\A[1-9][0-9]*\z/', $value) !== 1) {
            throw new InvalidArgumentException($label . ' moet een positief geheel getal zijn.');
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT);
        if ($integer === false || $integer < 1) {
            throw new InvalidArgumentException($label . ' is ongeldig.');
        }

        return $integer;
    }

    private function optionalPositiveInteger(mixed $value, string $label): ?int
    {
        return ($value === '' || $value === null) ? null : $this->positiveInteger($value, $label);
    }

    private function playerRange(
        array $input,
        string $minimumKey,
        string $maximumKey,
        string $minimumLabel,
        string $maximumLabel
    ): array {
        $minimum = $this->positiveInteger($input[$minimumKey] ?? null, $minimumLabel);
        $maximum = $this->optionalPositiveInteger($input[$maximumKey] ?? null, $maximumLabel);

        if ($maximum !== null && $maximum < $minimum) {
            throw new InvalidArgumentException($maximumLabel . ' moet minstens het minimum zijn.');
        }

        return [$minimum, $maximum];
    }

    private function isChecked(array $input, string $key): bool
    {
        $value = $input[$key] ?? '';
        if ($value !== '' && $value !== '1') {
            throw new InvalidArgumentException('Ongeldige waarde voor ' . $key . '.');
        }

        return $value === '1';
    }
}

?>