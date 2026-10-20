<?php

// Contract voor opslag met een unieke bestandsnaam.
interface StorageInterface
{
    public function saveImage(string $temporaryFile, string $extension): string;
}

class FileStorage implements StorageInterface
{
    public function __construct(
        private string $uploadDir = __DIR__ . '/../upload/'
    ) {
    }

    // Bewaart een gecontroleerde upload onder een unieke naam.
    public function saveImage(string $temporaryFile, string $extension): string
    {
        if (
            !is_dir($this->uploadDir)
            && !mkdir($this->uploadDir, 0775, true)
            && !is_dir($this->uploadDir)
        ) {
            throw new RuntimeException('De uploadmap kon niet worden aangemaakt.');
        }

        $fileName = uniqid('', true) . '.' . $extension;
        $destination = rtrim($this->uploadDir, '/\\') . '/' . $fileName;

        if (!move_uploaded_file($temporaryFile, $destination)) {
            throw new RuntimeException('Het uploaden is mislukt.');
        }

        return $fileName;
    }

    // Herkent de gegenereerde bestandsnamen van tijdelijke doosfoto's.
    public static function isPendingGameImageName(mixed $fileName): bool
    {
        return is_string($fileName)
            && basename($fileName) === $fileName
            && preg_match(
                '/\A[a-f0-9.]+\.(jpg|jpeg|png|gif)\z/i',
                $fileName
            ) === 1;
    }

    // Verwijdert een tijdelijke foto.
    // Een opruimfout wordt gelogd en blokkeert de gebruiker niet.
    public function removeImage(mixed $fileName): void
    {
        if (!self::isPendingGameImageName($fileName)) {
            return;
        }

        $path = rtrim($this->uploadDir, '/\\') . '/' . $fileName;

        if (is_file($path) && !@unlink($path)) {
            error_log(
                'Tijdelijke doosfoto kon niet worden verwijderd: ' . $fileName
            );
        }
    }
}