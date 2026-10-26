<?php
require_once __DIR__ . '/backend/auth.php';
requireLogin();
require_once __DIR__ . '/backend/config.php';

$reportId = filter_input(INPUT_GET, 'report_id', FILTER_VALIDATE_INT);
$rescuedId = filter_input(INPUT_GET, 'rescued_id', FILTER_VALIDATE_INT);
if (($reportId && $rescuedId) || (!$reportId && !$rescuedId)) {
    http_response_code(400);
    exit('Invalid image request.');
}

$table = $reportId ? 'reports' : 'rescued';
$id = $reportId ?: $rescuedId;
$statement = $conn->prepare("SELECT photo_data, photo_mime, photo FROM {$table} WHERE id = ?");
$statement->bind_param('i', $id);
$statement->execute();
$image = $statement->get_result()->fetch_assoc();

if (!$image) {
    http_response_code(404);
    exit('Image not found.');
}

if ($image['photo_data'] !== null && $image['photo_data'] !== '') {
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($image['photo_mime'], $allowedMimeTypes, true)) {
        http_response_code(500);
        exit('Stored image has an invalid content type.');
    }

    header('Content-Type: ' . $image['photo_mime']);
    header('Content-Length: ' . strlen($image['photo_data']));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=3600');
    echo $image['photo_data'];
    exit;
}

$legacyPhoto = (string) $image['photo'];
if (filter_var($legacyPhoto, FILTER_VALIDATE_URL)) {
    if (parse_url($legacyPhoto, PHP_URL_SCHEME) !== 'https') {
        http_response_code(404);
        exit('Image not found.');
    }
    header('Location: ' . $legacyPhoto, true, 302);
    exit;
}

if (preg_match('/\Auploads\/[A-Za-z0-9._-]+\z/', $legacyPhoto) === 1) {
    $legacyPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $legacyPhoto);
    if (is_file($legacyPath)) {
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($legacyPath);
        if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . filesize($legacyPath));
            header('X-Content-Type-Options: nosniff');
            readfile($legacyPath);
            exit;
        }
    }
}

http_response_code(404);
exit('Image not found.');
