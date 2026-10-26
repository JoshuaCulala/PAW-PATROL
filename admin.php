<?php
require_once __DIR__ . '/backend/auth.php';
requireAdmin();
require_once __DIR__ . '/backend/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        $transactionStarted = false;
        try {
            if ($action === 'status') {
                $reportId = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
                $status = (string) ($_POST['status'] ?? '');
                if (!$reportId || !in_array($status, ['Pending', 'Rescued'], true)) {
                    $error = 'Invalid report update.';
                } else {
                    $conn->begin_transaction();
                    $transactionStarted = true;

                    $statement = $conn->prepare('UPDATE reports SET status = ? WHERE id = ?');
                    $statement->bind_param('si', $status, $reportId);
                    $statement->execute();

                    if ($status === 'Rescued') {
                        $statement = $conn->prepare(
                            "INSERT INTO rescued (slug, report_id, name, animal_type, age, photo, photo_data, photo_mime, location)
                             SELECT CONCAT('report-', id), id, CONCAT(animal_type, ' #', id), animal_type,
                                    'Age not listed', photo, photo_data, photo_mime, location
                             FROM reports
                             WHERE id = ? AND status = 'Rescued'
                             ON DUPLICATE KEY UPDATE
                               name = VALUES(name),
                               animal_type = VALUES(animal_type),
                               photo = VALUES(photo),
                               photo_data = VALUES(photo_data),
                               photo_mime = VALUES(photo_mime),
                               location = VALUES(location)"
                        );
                        $statement->bind_param('i', $reportId);
                        $statement->execute();
                    } else {
                        // Issue 11: Prevent reverting to Pending if pet has already been adopted
                        $adoptCheck = $conn->prepare("SELECT id FROM rescued WHERE report_id = ? AND status = 'Adopted' LIMIT 1");
                        $adoptCheck->bind_param('i', $reportId);
                        $adoptCheck->execute();
                        if ($adoptCheck->get_result()->num_rows > 0) {
                            $conn->rollback();
                            $transactionStarted = false;
                            $error = 'This animal has already been adopted and cannot be reverted to pending.';
                        } else {
                            $statement = $conn->prepare(
                                "DELETE FROM rescued WHERE report_id = ? AND status = 'Available'"
                            );
                            $statement->bind_param('i', $reportId);
                            $statement->execute();
                        }
                    }

                    $conn->commit();
                    $transactionStarted = false;
                }
            } elseif ($action === 'delete') {
                $reportId = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
                if (!$reportId) {
                    $error = 'Invalid report.';
                } else {
                    $statement = $conn->prepare('SELECT photo FROM reports WHERE id = ?');
                    $statement->bind_param('i', $reportId);
                    $statement->execute();
                    $report = $statement->get_result()->fetch_assoc();

                    $statement = $conn->prepare('DELETE FROM reports WHERE id = ?');
                    $statement->bind_param('i', $reportId);
                    $statement->execute();
                    if ($report) {
                        $imagePath = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . basename($report['photo']);
                        if (is_file($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                }
            } elseif ($action === 'clear') {
                $images = $conn->query('SELECT photo FROM reports')->fetch_all(MYSQLI_ASSOC);
                $conn->query('DELETE FROM reports');
                foreach ($images as $image) {
                    $imagePath = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . basename($image['photo']);
                    if (is_file($imagePath)) {
                        unlink($imagePath);
                    }
                }
            } else {
                $error = 'Unknown report action.';
            }
        } catch (mysqli_sql_exception $exception) {
            if ($transactionStarted) {
                $conn->rollback();
            }
            error_log('Admin report update failed: ' . $exception->getMessage());
            http_response_code(500);
            exit('Unable to update reports. Please try again later.');
        }

        if ($error === '') {
            header('Location: admin.php');
            exit;
        }
    }
}

$counts = $conn->query(
    "SELECT COUNT(*) AS total,
        COALESCE(SUM(status = 'Pending'), 0) AS pending,
        COALESCE(SUM(status = 'Rescued'), 0) AS rescued
     FROM reports"
)->fetch_assoc();
$reports = $conn->query(
    'SELECT id, location, animal_type, condition_status, urgency, notes, status, created_at
     FROM reports ORDER BY created_at DESC'
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin | PAW PATROL</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700;800&display=swap" rel="stylesheet">
  <style>body{font-family:'Inter',sans-serif}</style>
</head>
<body class="min-h-screen bg-[#fffdf8] p-6">
<nav class="mx-auto flex max-w-5xl items-center justify-between rounded-full border bg-white px-5 py-3 shadow-sm">
  <a href="dashboard.php" class="text-sm font-bold">🐾 PAW PATROL <span class="font-normal text-zinc-400">Admin</span></a>
  <div class="flex gap-2">
    <form method="post" onsubmit="return confirm('Permanently delete ALL ' + document.querySelectorAll('article').length + ' reports and their pictures? This cannot be undone.')">
      <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
      <input type="hidden" name="action" value="clear">
      <button class="rounded-full border px-4 py-2 text-xs font-bold">Clear reports</button>
    </form>
    <a href="dashboard.php" class="rounded-full bg-black px-4 py-2 text-xs font-bold text-white">Home</a>
    <a href="logout.php" class="rounded-full border px-4 py-2 text-xs font-bold">Sign out</a>
  </div>
</nav>
<main class="mx-auto mt-8 max-w-5xl">
  <?php if ($error !== ''): ?><p role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= escape($error) ?></p><?php endif; ?>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <section class="rounded-2xl border bg-white p-6 shadow-sm"><p class="text-[11px] font-bold tracking-widest text-zinc-400">PENDING</p><p class="mt-1 text-3xl font-black"><?= (int) $counts['pending'] ?></p><div class="mt-4 h-1.5 rounded-full bg-yellow-300"></div></section>
    <section class="rounded-2xl border bg-white p-6 shadow-sm"><p class="text-[11px] font-bold tracking-widest text-zinc-400">RESCUED</p><p class="mt-1 text-3xl font-black"><?= (int) $counts['rescued'] ?></p><div class="mt-4 h-1.5 rounded-full bg-green-400"></div></section>
    <section class="rounded-2xl bg-black p-6 text-white"><p class="text-[11px] font-bold tracking-widest text-zinc-400">TOTAL</p><p class="mt-1 text-3xl font-black"><?= (int) $counts['total'] ?></p><div class="mt-4 h-1.5 rounded-full bg-yellow-300"></div></section>
  </div>

  <div class="mt-6 grid gap-4 md:grid-cols-2">
    <?php if (!$reports): ?>
      <div class="rounded-2xl border bg-white p-12 text-center md:col-span-2"><p class="text-sm font-bold">No reports yet</p><p class="mt-1 text-xs text-zinc-400">Reports submitted by the community will appear here.</p></div>
    <?php else: foreach ($reports as $report): ?>
      <article class="overflow-hidden rounded-2xl border bg-white shadow-sm">
        <img src="image.php?report_id=<?= (int) $report['id'] ?>" alt="Reported <?= escape(strtolower($report['animal_type'])) ?>" class="h-48 w-full object-cover">
        <div class="p-4">
          <div class="flex items-start justify-between gap-3">
            <p class="text-sm font-bold"><?= escape($report['location']) ?> · <?= escape($report['animal_type']) ?></p>
            <span class="shrink-0 rounded-full px-2 py-1 text-[10px] font-bold <?= $report['status'] === 'Pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' ?>"><?= escape($report['status']) ?></span>
          </div>
          <p class="mt-1 text-xs text-zinc-500"><?= escape($report['condition_status']) ?> · <?= escape($report['urgency']) ?> urgency</p>
          <?php if ($report['notes'] !== ''): ?><p class="mt-2 whitespace-pre-wrap text-xs text-zinc-600"><?= escape($report['notes']) ?></p><?php endif; ?>
          <p class="mt-3 text-[10px] text-zinc-400"><?= escape(date('M j, Y g:i a', strtotime($report['created_at']))) ?></p>
          <div class="mt-3 flex gap-2">
            <form method="post" class="flex-1">
              <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
              <input type="hidden" name="action" value="status">
              <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
              <input type="hidden" name="status" value="<?= $report['status'] === 'Pending' ? 'Rescued' : 'Pending' ?>">
              <button class="w-full rounded-full border border-black bg-yellow-300 py-2 text-xs font-bold"><?= $report['status'] === 'Pending' ? 'Mark as rescued' : 'Mark as pending' ?></button>
            </form>
            <form method="post" onsubmit="return confirm('Delete this report and its picture?')">
              <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
              <button class="rounded-full border px-4 py-2 text-xs font-bold text-red-700">Delete</button>
            </form>
          </div>
        </div>
      </article>
    <?php endforeach; endif; ?>
  </div>
</main>
</body>
</html>
