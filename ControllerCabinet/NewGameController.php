<?php

require_once __DIR__ . '/../ModelCabinet/ItemStorage.php';

class NewGameController
{
    public function __construct(private ItemStorage $itemStorage)
    {
    }

    public function addGame(array $input, string $photoPath): int
    {
        $title = $this->validatedText($input, 'title', 255, 'Spelnaam');
        $type = $this->validatedText($input, 'type', 100, 'Speltype');
        [$minimumPlayers, $maximumPlayers] = $this->playerRange(
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
                $this->positiveInteger($input['board_height'] ?? null, 'Bordhoogte'),
            ]
            : null;

        $extensionGame = null;
        $expandPlayers = null;
        if ($this->isChecked($input, 'has_expansion')) {
            $extensionGame = $this->validatedText($input, 'extension_game', 255, 'Uitbreidingsnaam');
            $expandPlayers = $this->playerRange(
                $input,
                'expansion_min_players',
                'expansion_max_players',
                'Minimumaantal spelers met uitbreiding',
                'Maximumaantal spelers met uitbreiding'
            );
        }

        return $this->itemStorage->add([
            'type' => $type,
            'title' => $title,
            'photo_path' => $photoPath,
            'min_max_players' => json_encode([$minimumPlayers, $maximumPlayers], JSON_THROW_ON_ERROR),
            'is_board_game' => $isBoardGame ? 1 : 0,
            'board_format' => $boardFormat === null
                ? null
                : json_encode($boardFormat, JSON_THROW_ON_ERROR),
            'extension_game' => $extensionGame,
            'min_max_expand_players' => $expandPlayers === null
                ? null
                : json_encode($expandPlayers, JSON_THROW_ON_ERROR),
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
        return $value === '' ? null : $this->positiveInteger($value, $label);
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
