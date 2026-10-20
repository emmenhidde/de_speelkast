<?php

require_once __DIR__ . '/../ModelCabinet/FileStorage.php';

class ImageController
{
    public function __construct(private StorageInterface $storage){}

    public function uploadImage(array $file): string
    {  
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new RuntimeException('Het bestand mag maximaal 2 MB zijn.');
        }

        $temporaryFile = $file['tmp_name'] ?? null;
        $originalName = $file['name'] ?? null;

        if (
            $error !== UPLOAD_ERR_OK
            || !is_string($temporaryFile)
            || !is_string($originalName)
            || !is_uploaded_file($temporaryFile)
        ) {
            throw new RuntimeException('Kies eerst een geldig bestand.');
        }

        $size = filesize($temporaryFile);
        if ($size === false || $size < 1 || $size > 2 * 1024 * 1024) {
            throw new RuntimeException('Het bestand mag maximaal 2 MB zijn.');
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        ];

        $imageInfo = @getimagesize($temporaryFile);
        if (
            !isset($allowedTypes[$extension]) ||
            $imageInfo === false ||
            $imageInfo['mime'] !== $allowedTypes[$extension]
        ) {
            throw new RuntimeException('Kies een JPG-, PNG- of GIF-afbeelding.');
        }

        return $this->storage->saveImage($temporaryFile, $extension);
    }
}
?>