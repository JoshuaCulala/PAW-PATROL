<?php
require_once __DIR__ . '/backend/auth.php';
requireLogin();
require_once __DIR__ . '/backend/config.php';

$error = '';
$success = isset($_GET['success']);

try {
    $conn->query(
        "INSERT INTO rescued (slug, report_id, name, animal_type, age, photo, photo_data, photo_mime, location)
         SELECT CONCAT('report-', id), id, CONCAT(animal_type, ' #', id), animal_type,
                'Age not listed', photo, photo_data, photo_mime, location
         FROM reports
         WHERE status = 'Rescued'
         ON DUPLICATE KEY UPDATE
           name = VALUES(name),
           animal_type = VALUES(animal_type),
           photo = VALUES(photo),
           photo_data = VALUES(photo_data),
           photo_mime = VALUES(photo_mime),
           location = VALUES(location)"
    );

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!verifyCsrfToken()) {
            $error = 'Your session expired. Please try again.';
        } elseif (($_POST['action'] ?? '') !== 'adopt') {
            $error = 'Invalid adoption request.';
        } else {
            $petId = filter_input(INPUT_POST, 'rescued_id', FILTER_VALIDATE_INT);
            if (!$petId) {
                $error = 'Invalid pet.';
            } else {
                $userId = (int) $_SESSION['user_id'];
                $statement = $conn->prepare(
                    "UPDATE rescued AS r
                     LEFT JOIN reports AS p ON p.id = r.report_id
                     SET r.status = 'Adopted', r.adopted_by = ?, r.adopted_at = CURRENT_TIMESTAMP
                     WHERE r.id = ? AND r.status = 'Available'
                       AND (r.report_id IS NULL OR p.status = 'Rescued')"
                );
                $statement->bind_param('ii', $userId, $petId);
                $statement->execute();

                if ($statement->affected_rows === 1) {
                    header('Location: adoption.php?success=1');
                    exit;
                }
                $error = 'This pet is no longer available for adoption.';
            }
        }
    }

    $pets = $conn->query(
        "SELECT id, name, animal_type, age, photo,
                COALESCE(OCTET_LENGTH(photo_data), 0) > 0 AS has_image_data, location
         FROM rescued
         WHERE status = 'Available'
         ORDER BY created_at DESC, id DESC"
    )->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $exception) {
    error_log('Unable to load adoptable pets: ' . $exception->getMessage());
    http_response_code(500);
    exit('Unable to update or load adoptable pets. Please try again later.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Adoption - PAW PATROL</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700;800&display=swap" rel="stylesheet">
<style>body{font-family:'Inter',sans-serif}</style>
</head>
<body class="bg-[#fffdf8] min-h-screen p-6">
<nav class="max-w-5xl mx-auto flex justify-between items-center bg-white border rounded-full px-5 py-3 shadow-sm"><a href="dashboard.php" class="font-bold text-sm">🐾 Adoption</a><div class="flex gap-2"><a href="dashboard.php" class="bg-black text-white text-xs px-4 py-2 rounded-full font-bold">Home</a><a href="logout.php" class="border text-xs px-4 py-2 rounded-full font-bold">Sign out</a></div></nav>
<div class="max-w-5xl mx-auto mt-8 grid md:grid-cols-3 gap-4">
  <?php if ($success): ?>
    <p role="status" class="md:col-span-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">Thank you for adopting! The pet is no longer listed as available.</p>
  <?php elseif ($error !== ''): ?>
    <p role="alert" class="md:col-span-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= escape($error) ?></p>
  <?php endif; ?>
  <?php if (!$pets): ?>
    <div class="md:col-span-3 bg-white border rounded-2xl p-12 text-center shadow-sm"><p class="font-bold text-sm">No pets available for adoption right now</p><p class="text-xs text-zinc-500 mt-1">Please check back later.</p></div>
  <?php else: foreach ($pets as $pet): ?>
    <div class="bg-white border rounded-2xl p-2 shadow-sm">
      <img src="<?= $pet['has_image_data'] ? 'image.php?rescued_id=' . (int) $pet['id'] : escape($pet['photo']) ?>" alt="<?= escape($pet['name']) ?>" class="w-full h-48 object-cover rounded-xl">
      <div class="p-4">
        <p class="font-bold text-sm"><?= escape($pet['name']) ?> • <?= escape($pet['age']) ?> <?= $pet['animal_type'] === 'Cat' ? '🐱' : ($pet['animal_type'] === 'Dog' ? '🐶' : '🐾') ?></p>
        <p class="text-xs text-zinc-500 mt-1">Rescued near <?= escape($pet['location']) ?></p>
        <form method="post" class="mt-4" onsubmit="return confirm('Are you sure you want to adopt this pet?')">
          <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
          <input type="hidden" name="action" value="adopt">
          <input type="hidden" name="rescued_id" value="<?= (int) $pet['id'] ?>">
          <button type="submit" class="w-full bg-black text-white py-2.5 rounded-full text-xs font-bold">Adopt <?= escape($pet['name']) ?></button>
        </form>
      </div>
    </div>
  <?php endforeach; endif; ?>
  <div class="bg-yellow-300 border-2 border-black rounded-2xl p-6 shadow-[4px_4px_0px_black]"><p class="text-3xl">💛</p><p class="font-black text-lg mt-4 leading-none">Want to<br>adopt?</p><p class="text-xs font-medium mt-3">Visit OSA Office with 1 valid ID.</p><button class="w-full mt-6 bg-black text-white py-3 rounded-full text-xs font-bold">Contact OSA</button></div>
</div>
</body>
</html>