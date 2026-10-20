<?php

// Beschrijft welke fotomethoden ieder kastitem gebruikt
interface PhotoInterface
{
    public function checkFileName(string $fileName): bool;
    public function showImage(): string;
}

// Implementeert de gedeelde gegevens en fotomethoden.
abstract class CabinetItem implements PhotoInterface
{
    public function __construct(
        public int $id,
        public string $title,
        public string $photoPath,
        public array $minMaxPlayers,
        public bool $isBoardGame,
        public ?array $boardFormat,
        public ?string $owner
    ) {
    }

    abstract public function description(): string;

    public function checkFileName(string $fileName): bool
    {
        return in_array(
            strtolower(pathinfo($fileName, PATHINFO_EXTENSION)),
            ['jpg', 'jpeg', 'png', 'gif'],
            true);
    }

    public function showImage(): string
    {
        return $this->photoPath;
    }

    protected function decodeSelectionList(string $json): array
    {
        $values = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($values)) {
            throw new UnexpectedValueException('Een opgeslagen spelveld is ongeldig.');
        }
        return $values;
    }
}

?>