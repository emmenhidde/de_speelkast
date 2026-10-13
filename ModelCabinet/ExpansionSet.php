<?php

class ExpansionSet extends CabinetItem
{
    public function __construct(
        public int $id,
        public string $type,
        public string $title,
        public string $photoPath,
        public array $minMaxPlayers,
        public bool $isBoardGame,
        public ?string $extensionGame,
        public array $minMaxExpandPlayers,
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
            '%s (uitbreiding op %s, %d-%d spelers)',
            $this->title,
            $this->extensionGame ?? null,
            $this->minMaxExpandPlayers[0],
            $this->minMaxExpandPlayers[1]
        );
    }

    public function expansionDescription(): string
    {
        return $this->description();
    }
}
