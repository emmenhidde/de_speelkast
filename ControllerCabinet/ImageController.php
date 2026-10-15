<?php

class ImageController
{
    public function __construct(
        private string $uploadDir = __DIR__ . '/../upload/'
    ) {
    }

    public function uploadImage(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Kies eerst een bestand.');
        }

        if (!isset($file['size'], $file['name'], $file['tmp_name']) || $file['size'] > 2 * 1024 * 1024) {
            throw new RuntimeException('Het bestand mag maximaal 2 MB zijn.');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        ];

        $imageInfo = getimagesize($file['tmp_name']);
        if (
            !isset($allowedTypes[$extension]) ||
            $imageInfo === false ||
            $imageInfo['mime'] !== $allowedTypes[$extension]
        ) {
            throw new RuntimeException('Kies een JPG-, PNG- of GIF-afbeelding.');
        }

        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0775, true) && !is_dir($this->uploadDir)) {
            throw new RuntimeException('De uploadmap kon niet worden aangemaakt.');
        }

        $fileName = uniqid('', true) . '.' . $extension;
        $destination = rtrim($this->uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Het uploaden is mislukt.');
        }

        return $fileName;
    }
}
