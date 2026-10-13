<?php

class ImageController
{
    public function __construct(
        private string $uploadDir = __DIR__ . '/upload/'
    ) {
    }

    public function uploadImage(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Kies eerst een bestand.');
        }

        if ($file['size'] > 2 * 1024 * 1024) {
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

        $fileName = uniqid('', true) . '.' . $extension;
        $destination = rtrim($this->uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Het uploaden is mislukt.');
        }

        return $fileName;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        $controller = new ImageController();
        $fileName = $controller->uploadImage($_FILES['file'] ?? []);
        echo 'Bestand geüpload: ' . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
    } catch (RuntimeException $exception) {
        http_response_code(400);
        echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}
