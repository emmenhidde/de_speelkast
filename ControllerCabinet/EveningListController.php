<?php

class EveningListController
{
    // Leest de tijdelijke upload en geeft de XSLT-weergave terug.
    public function renderUpload(array $file): string
    {
        $temporaryFile = $file['tmp_name'] ?? '';

        if (
            ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
            !is_string($temporaryFile) ||
            !is_uploaded_file($temporaryFile)
        ) {
            throw new InvalidArgumentException('Kies eerst een XML-bestand.');
        }

        if (filesize($temporaryFile) > 2 * 1024 * 1024) {
            throw new InvalidArgumentException('Het XML-bestand mag maximaal 2 MB zijn.');
        }

        if (!class_exists('XSLTProcessor')) {
            throw new RuntimeException('Schakel extension=xsl in php.ini in en herstart Apache.');
        }

        $previousErrors = libxml_use_internal_errors(true);

        try {$xml = new DOMDocument();

            // Laat de ingeladen XML geen externe bestanden ophalen
            $xml->resolveExternals = false;
            $xml->substituteEntities = false;

            if (!$xml->load($temporaryFile, LIBXML_NONET) ||$xml->doctype !== null
            ) {throw new InvalidArgumentException('Kies een geldig XML-bestand zonder DTD.');
            }

            // Gebruikt hetzelfde schema als de bestaande export
            if (!$xml->schemaValidate(__DIR__ . '/../ModelCabinet/gamesFormatting.xsd')) {
                throw new InvalidArgumentException('De XML past niet bij de Speelkast-export.');
            }

            $applicationPath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])),'/');

            // Voegt leesbare waarden toe voor de XSLT-weergave
            foreach ($xml->documentElement->childNodes as $item) {
                if (!$item instanceof DOMElement) {
                    continue;
                }

                $read = static fn(string $field): string =>
                    trim($item->getElementsByTagName($field)
                        ->item(0)?->textContent ?? '');

                $extension = $item->tagName === 'extension';

                $isBoard = in_array(
                    $read(
                        $extension
                            ? 'extend_is_boardgame'
                            : 'is_board_game'
                    ),
                    ['1', 'true'],
                    true
                );

                // Gebruikt uitsluitend afbeeldingen uit de eigen uploadmap
                $photoName = basename(str_replace(
                    '\\',
                    '/',
                    $read(
                        $extension
                            ? 'extend_photo_path'
                            : 'photo_path'
                    )
                ));

                $hasPhoto = in_array(
                    strtolower(pathinfo($photoName, PATHINFO_EXTENSION)),
                    ['jpg', 'jpeg', 'png', 'gif'],
                    true
                ) && is_file(__DIR__ . '/../upload/' . $photoName);

                $item->setAttribute(
                    'photo_src',
                    $hasPhoto
                        ? $applicationPath . '/upload/'
                            . rawurlencode($photoName)
                        : ''
                );

                $item->setAttribute(
                    'players_text',
                    $this->formatPair(
                        $read(
                            $extension
                                ? 'min_max_extend_players'
                                : 'min_max_players'
                        ),
                        ' tot ',
                        true
                    ) ?: 'Onbekend'
                );

                $board = $isBoard
                    ? $read(
                        $extension
                            ? 'extend_board_size'
                            : 'board_format'
                    )
                    : '';

                $item->setAttribute(
                    'board_game',
                    $isBoard ? 'Ja' : 'Nee'
                );

                $boardText = $this->formatPair($board, ' x ');

                $item->setAttribute(
                    'board_text',
                    $boardText === ''
                        ? 'Onbekend of variabel'
                        : $boardText . ' cm'
                );
            }

            $xsl = new DOMDocument();

            if (!$xsl->load(
                __DIR__ . '/../ViewCabinet/eveningList.xsl',
                LIBXML_NONET
            )) {
                throw new RuntimeException('eveningList.xsl kon niet worden geladen.');
            }

            $processor = new XSLTProcessor();

            // De transformatie gebruikt alleen de al ingeladen documenten
            $processor->setSecurityPrefs(
                XSL_SECPREF_READ_FILE |
                XSL_SECPREF_WRITE_FILE |
                XSL_SECPREF_CREATE_DIRECTORY |
                XSL_SECPREF_READ_NETWORK |
                XSL_SECPREF_WRITE_NETWORK
            );

            if (!$processor->importStylesheet($xsl)) {
                throw new RuntimeException('De XSLT kon niet worden verwerkt.');
            }

            $html = $processor->transformToXML($xml);

            if (!is_string($html)) {
                throw new RuntimeException('De avondlijst kon niet worden weergegeven.');
            }

            return $html;
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('De XML bevat ongeldige spelers- of bordgegevens.');
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
    }

    private function formatPair(
        string $json,
        string $separator,
        bool $players = false
    ): string {
        $values = json_decode($json, true);

        // Een onleesbaar paar krijgt bij de weergave een vervangende tekst.
        if (
            !is_array($values) ||
            !isset($values[0]) ||
            !array_key_exists(1, $values) ||
            !is_numeric($values[0]) ||
            ($values[1] !== null && !is_numeric($values[1])) ||
            (!$players && $values[1] === null)
        ) {
            return '';
        }

        // Een ontbrekende bovengrens bij spelers betekent onbeperkt.
        return $values[0] . $separator . ($values[1] ?? 'onbeperkt');
    }
}