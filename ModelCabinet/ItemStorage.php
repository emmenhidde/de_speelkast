<?php

require_once __DIR__ . '/BoardGame.php';
require_once __DIR__ . '/ExpansionSet.php';

interface ItemInterface
{
    public function findAll(): array;

    public function findAllExtensions(): array;

    public function findSelectedGameIds(string $username, bool $extensions = false): array;

    public function saveSelectedGameIds(string $username, array $gameIds, array $extensionIds): void;

    public function add(BoardGame $item): int;

    public function addExtension(ExpansionSet $item): int;
}

class ItemStorage implements ItemInterface
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
public function addExtension(ExpansionSet $item): int
{
    $statement = $this->pdo->prepare(
        'SELECT id FROM gamesitems WHERE id = ?'
    );
    $statement->execute([$item->baseGame]);

    if ($statement->fetchColumn() === false) {
        throw new InvalidArgumentException('Kies een bestaand hoofdspel.');
    }

    $statement = $this->pdo->prepare(
        'INSERT INTO gamesextensions
         (base_game, extension_name, extend_photo_path,
          min_max_extend_players, extend_is_boardgame, 
          extend_board_size, owner_extension)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->execute([
        $item->baseGame,
        $item->title,
        $item->photoPath,
        json_encode($item->minMaxPlayers, JSON_THROW_ON_ERROR),
        (int) $item->isBoardGame,
        $item->boardFormat === null
            ? null
            : json_encode($item->boardFormat, JSON_THROW_ON_ERROR),
        $item->owner
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
            "SELECT $column FROM gamesclaimed WHERE account_username = :username"
        );
        $statement->execute(['username' => $username]);
        $selectedGames = $statement->fetchColumn();

        if ($selectedGames === false || $selectedGames === null) {
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
                    throw new InvalidArgumentException('De selectie bevat een ongeldig ID.');
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
                throw new InvalidArgumentException('De selectie bevat een onbekend spel of uitbreiding.');
            }
        }

        // Beide lijsten worden pas opgeslagen nadat alle controles zijn geslaagd.
        $statement = $this->pdo->prepare(
            'INSERT INTO gamesclaimed
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
    public function add(BoardGame $item): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO gamesitems
            (category, title, photo_path, min_max_players,
            is_board_game, board_format, owner_main)
            VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $item->category,
            $item->title,
            $item->photoPath,
            json_encode($item->minMaxPlayers, JSON_THROW_ON_ERROR),
            (int) $item->isBoardGame,
            $item->boardFormat === null
                ? null
                : json_encode($item->boardFormat, JSON_THROW_ON_ERROR),
            $item->owner
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}

?>