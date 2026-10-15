<?php

class ItemStorage
{
    // Bewaart één PDO-verbinding voor alle spel- en selectiequeries
    public function __construct(private PDO $pdo)
    {
    }

    // Haalt alles op uit de DB, gesorteerd op id
    public function findAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT * FROM gamesitems ORDER BY id'
        );

        return $statement->fetchAll();
    }

    // Leest en valideert de JSON-lijst met spel-ID's van één account
    public function findSelectedGameIds(string $username): array
    {
        $statement = $this->pdo->prepare(
            'SELECT claimed_games FROM gamesClaimed WHERE account_username = :username'
        );
        $statement->execute(['username' => $username]);
        $selectedGames = $statement->fetchColumn();

        if ($selectedGames === false) {
            return [];
        }

        $gameIds = json_decode($selectedGames, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($gameIds)) {
            throw new UnexpectedValueException('De opgeslagen spelkeuze is ongeldig.');
        }

        foreach ($gameIds as $gameId) {
            if (!is_int($gameId) || $gameId < 1) {
                throw new UnexpectedValueException('De opgeslagen spelkeuze bevat een ongeldig ID.');
            }
        }

        return array_values(array_unique($gameIds));
    }

    // Controleert spel-ID's en vervangt daarna de opgeslagen accountselectie
    public function saveSelectedGameIds(string $username, array $gameIds): void
    {
        $gameIds = array_values(array_unique($gameIds));
        foreach ($gameIds as $gameId) {
            if (!is_int($gameId) || $gameId < 1) {
                throw new InvalidArgumentException('De selectie bevat een ongeldig spel-ID.');
            }
        }

        if ($gameIds !== []) {
            $placeholders = implode(',', array_fill(0, count($gameIds), '?'));
            $statement = $this->pdo->prepare(
                "SELECT id FROM gamesitems WHERE id IN ($placeholders)"
            );
            $statement->execute($gameIds);
            $existingIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));

            if (count($existingIds) !== count($gameIds)) {
                throw new InvalidArgumentException('De selectie bevat een onbekend spel.');
            }
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO gamesClaimed (account_username, claimed_games) '
            . 'VALUES (:username, :game_ids) '
            . 'ON DUPLICATE KEY UPDATE claimed_games = VALUES(claimed_games)'
        );
        $statement->execute([
            'username' => $username,
            'game_ids' => json_encode($gameIds, JSON_THROW_ON_ERROR),
        ]);
    }

    // Voegt een gevalideerd spelrecord toe en geeft het nieuwe database-ID terug
    public function add(array $item): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO gamesitems '
            . '(type, title, photo_path, min_max_players, is_board_game, board_format, '
            . 'extension_game, min_max_expand_players) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $item['type'],
            $item['title'],
            $item['photo_path'],
            $item['min_max_players'],
            $item['is_board_game'],
            $item['board_format'],
            $item['extension_game'],
            $item['min_max_expand_players']
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}