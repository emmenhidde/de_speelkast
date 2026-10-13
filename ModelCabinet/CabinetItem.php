<?php

abstract class CabinetItem
{
    public function __construct(
        public int $id,
        public string $type,
        public string $title,
        public string $photoPath,
        public array $minMaxPlayers,
        public bool $isBoardGame,
        public ?string $extensionGame,
    ) {
    }

    abstract public function description(): string;

    public function checkFileName(string $fileName): bool
    {
        return pathinfo($fileName, PATHINFO_EXTENSION) !== '';
    }

    public function showImage(): string
    {
        return $this->photoPath;
    }
}
