<?php

class BoardGame extends CabinetItem
{
    public function __construct(
        public int $id,
        public string $type,
        public string $title,
        public string $photoPath,
        public array $minMaxPlayers,
        public bool $isBoardGame,
        public ?string $extensionGame,
        public array $boardFormat,
    ) {
        parent::__construct(
            $id,
            $type,
            $title,
            $photoPath,
            $minMaxPlayers,
            $isBoardGame,
            $extensionGame
        );
    }

    public function description(): string
    {
        return sprintf(
            '%s (%d-%d spelers)',
            $this->title,
            $this->minMaxPlayers[0],
            $this->minMaxPlayers[1]
        );
    }

    public function boardgameDescription(): string
    {
        return $this->description();
    }
}
