<?php
require_once __DIR__ . '/backend/auth.php';
requireLogin();
require_once __DIR__ . '/backend/config.php';

$error = trim((string) ($_GET['error'] ?? ''));
$success = isset($_GET['success']);

function iniSizeInBytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }

    $unit = strtolower($value[strlen($value) - 1]);
    $bytes = (float) $value;
    if ($unit === 'g') {
        $bytes *= 1024;
    }
    if ($unit === 'g' || $unit === 'm') {
        $bytes *= 1024;
    }
    if ($unit === 'g' || $unit === 'm' || $unit === 'k') {
        $bytes *= 1024;
    }

    return (int) $bytes;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    $postMaxSize = iniSizeInBytes((string) ini_get('post_max_size'));
    $requestTooLarge = $contentLength > 0
        && $postMaxSize > 0
        && $contentLength > $postMaxSize
        && $_POST === []
        && $_FILES === [];

    if ($requestTooLarge) {
        $error = 'The report and picture exceed the server request limit. Choose a smaller picture.';
    } elseif (!verifyCsrfToken()) {
        $error = 'Your session expired. Please submit the report again.';
    } else {
        $location = trim((string) ($_POST['location'] ?? ''));
        $type = (string) ($_POST['type'] ?? '');
        $condition = (string) ($_POST['condition'] ?? '');
        $urgency = (string) ($_POST['urgency'] ?? '');
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $allowedTypes = ['Dog', 'Cat', 'Other'];
        $allowedConditions = ['Healthy', 'Injured', 'Needs Food'];
        $allowedUrgencies = ['High', 'Medium', 'Low'];
        $photo = $_FILES['photo'] ?? null;
        $allowedMimeTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if ($location === '' || mb_strlen($location) > 150
            || !in_array($type, $allowedTypes, true)
            || !in_array($condition, $allowedConditions, true)
            || !in_array($urgency, $allowedUrgencies, true)
            || mb_strlen($notes) > 5000) {
            $error = 'Check the report details and try again.';
        } elseif (!is_array($photo) || !isset($photo['error']) || $photo['error'] !== UPLOAD_ERR_OK) {
            $uploadError = is_array($photo) ? ($photo['error'] ?? null) : null;
            $error = match ($uploadError) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The picture exceeds the server upload limit.',
                UPLOAD_ERR_PARTIAL => 'The picture upload was interrupted. Please try again.',
                UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION =>
                    'The server could not receive the picture. Please try again later.',
                default => 'Please choose a picture to upload.',
            };
        } elseif ($photo['size'] > 5 * 1024 * 1024) {
            $error = 'The picture must be 5 MB or smaller.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($photo['tmp_name']);
            $imageInfo = @getimagesize($photo['tmp_name']);

            if (!isset($allowedMimeTypes[$mimeType]) || $imageInfo === false) {
                $error = 'Upload a valid JPG, PNG, or WebP picture.';
            } else {
                $imageData = file_get_contents($photo['tmp_name']);
                if ($imageData === false || $imageData === '') {
                    error_log('Unable to read uploaded report image.');
                    http_response_code(500);
                    exit('Unable to save the picture. Please try again.');
                }

                try {
                    $photoData = null;
                    $statement = $conn->prepare(
                        'INSERT INTO reports (user_id, photo, photo_data, photo_mime, location, animal_type, condition_status, urgency, notes)
                         VALUES (?, \'\', ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $statement->bind_param(
                        'ibssssss',
                        $_SESSION['user_id'],
                        $photoData,
                        $mimeType,
                        $location,
                        $type,
                        $condition,
                        $urgency,
                        $notes
                    );
                    $statement->send_long_data(1, $imageData);
                    $statement->execute();
                    header('Location: report.php?success=1');
                    exit;
                } catch (mysqli_sql_exception $exception) {
                    error_log('Report creation failed: ' . $exception->getMessage());
                    http_response_code(500);
                    exit('Unable to save your report. Please try again later.');
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Report a stray | PAW PATROL</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700;800&display=swap" rel="stylesheet">
  <style>body{font-family:'Inter',sans-serif}</style>
</head>
<body class="min-h-screen bg-[#fffdf8] p-6">
<nav class="mx-auto flex max-w-2xl items-center justify-between"><a href="dashboard.php" class="rounded-full border bg-white px-4 py-2 text-xs font-bold">← Home</a><span class="text-xs font-bold tracking-widest text-zinc-400">REPORT FORM</span></nav>
<form id="reportForm" action="report.php" method="post" enctype="multipart/form-data" class="mx-auto mt-8 max-w-2xl rounded-[20px] border border-black/5 bg-white p-8 shadow-sm">
  <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
  <h1 class="text-2xl font-extrabold">Report a sighting</h1>
  <p class="mt-1 text-xs text-zinc-500">Please upload a clear photo of the stray.</p>
  <?php if ($error !== ''): ?><p role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= escape($error) ?></p><?php endif; ?>
  <?php if ($success): ?><p role="status" class="mt-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">Report saved. Thank you for helping!</p><?php endif; ?>
  <div class="mt-6">
    <label for="photo" class="text-[11px] font-bold tracking-widest text-zinc-500">UPLOAD PICTURE * <span class="font-normal tracking-normal">(JPG, PNG, or WebP; up to 5 MB)</span></label>
    <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" required class="mt-2 block w-full rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-sm">
  </div>
  <div class="mt-6 grid gap-4 md:grid-cols-2">
    <label class="text-[11px] font-bold tracking-widest text-zinc-500">LOCATION
      <input name="location" maxlength="150" required class="mt-1 w-full rounded-xl border px-4 py-3 text-sm font-normal" placeholder="e.g., CE Building">
    </label>
    <label class="text-[11px] font-bold tracking-widest text-zinc-500">TYPE
      <select name="type" class="mt-1 w-full rounded-xl border px-4 py-3 text-sm font-normal"><option value="Dog">Dog 🐶</option><option value="Cat">Cat 🐱</option><option value="Other">Other</option></select>
    </label>
    <label class="text-[11px] font-bold tracking-widest text-zinc-500">CONDITION
      <select name="condition" class="mt-1 w-full rounded-xl border px-4 py-3 text-sm font-normal"><option>Healthy</option><option>Injured</option><option>Needs Food</option></select>
    </label>
    <label class="text-[11px] font-bold tracking-widest text-zinc-500">URGENCY
      <select name="urgency" class="mt-1 w-full rounded-xl border px-4 py-3 text-sm font-normal"><option>High</option><option>Medium</option><option>Low</option></select>
    </label>
  </div>
  <label class="mt-4 block text-[11px] font-bold tracking-widest text-zinc-500">NOTES
    <textarea name="notes" maxlength="5000" class="mt-1 w-full rounded-xl border p-4 text-sm font-normal" rows="3" placeholder="Describe the situation..."></textarea>
  </label>
  <button type="submit" class="mt-6 w-full rounded-full bg-black py-3.5 text-sm font-bold text-white">Submit report →</button>
</form>
</body>
</html>
