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
    
    // Haalt alle uitbreidingen op uit hun eigen tabel
    public function findAllExtensions(): array
    {
        return $this->pdo
            ->query('SELECT * FROM gamesextensions ORDER BY id')
            ->fetchAll();
    }

// Controleert het hoofdspel en slaat daarna de uitbreiding op
public function addExtension(array $item): int
{
    $statement = $this->pdo->prepare(
        'SELECT id FROM gamesitems WHERE id = ?'
    );
    $statement->execute([$item['base_game']]);

    if ($statement->fetchColumn() === false) {
        throw new InvalidArgumentException('Kies een bestaand hoofdspel.');
    }

    $statement = $this->pdo->prepare(
        'INSERT INTO gamesextensions
         (base_game, extension_name, extend_photo_path,
          min_max_extend_players, extend_is_boardgame, extend_board_size)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $statement->execute([
        $item['base_game'],
        $item['extension_name'],
        $item['extend_photo_path'],
        $item['min_max_extend_players'],
        $item['extend_is_boardgame'],
        $item['extend_board_size']
    ]);

    return (int) $this->pdo->lastInsertId();
}


    // Leest de hoofdspelselectie, of met true de uitbreidingsselectie
    public function findSelectedGameIds(
        string $username,
        bool $extensions = false
    ): array {
        // De kolomnaam komt uitsluitend uit deze twee vaste namen.
        $column = $extensions ? 'claimed_extensions' : 'claimed_games';

        $statement = $this->pdo->prepare(
            "SELECT $column FROM gamesClaimed WHERE account_username = :username"
        );
        $statement->execute(['username' => $username]);
        $selectedGames = $statement->fetchColumn();

        if ($selectedGames === false) {
            return [];
        }

        $gameIds = json_decode($selectedGames, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($gameIds)) {
            throw new UnexpectedValueException(
                'De opgeslagen spelkeuze is ongeldig.'
            );
        }

        foreach ($gameIds as $gameId) {
            if (!is_int($gameId) || $gameId < 1) {
                throw new UnexpectedValueException(
                    'De opgeslagen spelkeuze bevat een ongeldig ID.'
                );
            }
        }

        return array_values(array_unique($gameIds));
    }
    // Controleert beide selectielijsten en slaat ze in afzonderlijke kolommen op
    public function saveSelectedGameIds(
        string $username,
        array $gameIds,
        array $extensionIds
    ): void {
        $gameIds = array_values(array_unique($gameIds));
        $extensionIds = array_values(array_unique($extensionIds));

        // Iedere lijst wordt gecontroleerd tegen de bijbehorende tabel.
        foreach ([
            'gamesitems' => $gameIds,
            'gamesextensions' => $extensionIds
        ] as $table => $ids) {
            foreach ($ids as $id) {
                if (!is_int($id) || $id < 1) {
                    throw new InvalidArgumentException(
                        'De selectie bevat een ongeldig ID.'
                    );
                }
            }

            if ($ids === []) {
                continue;
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $statement = $this->pdo->prepare(
                "SELECT COUNT(*) FROM $table WHERE id IN ($placeholders)"
            );
            $statement->execute($ids);

            if ((int) $statement->fetchColumn() !== count($ids)) {
                throw new InvalidArgumentException(
                    'De selectie bevat een onbekend spel of uitbreiding.'
                );
            }
        }

        // Beide lijsten worden pas opgeslagen nadat alle controles zijn geslaagd.
        $statement = $this->pdo->prepare(
            'INSERT INTO gamesClaimed
                (account_username, claimed_games, claimed_extensions)
            VALUES (:username, :game_ids, :extension_ids)
            ON DUPLICATE KEY UPDATE
                claimed_games = VALUES(claimed_games),
                claimed_extensions = VALUES(claimed_extensions)'
        );
        $statement->execute([
            'username' => $username,
            'game_ids' => json_encode($gameIds, JSON_THROW_ON_ERROR),
            'extension_ids' => json_encode($extensionIds, JSON_THROW_ON_ERROR)
        ]);
    }

    // Slaat uitsluitend de gegevens van een hoofdspel op.
    public function add(array $item): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO gamesitems
            (category, title, photo_path, min_max_players,
            is_board_game, board_format)
            VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $item['category'],
            $item['title'],
            $item['photo_path'],
            $item['min_max_players'],
            $item['is_board_game'],
            $item['board_format']
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}