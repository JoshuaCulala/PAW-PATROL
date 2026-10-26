<?php
require_once __DIR__ . '/backend/auth.php';
requireLogin();
require_once __DIR__ . '/backend/config.php';

$counts = ['total' => 0, 'pending' => 0, 'rescued' => 0];
$result = $conn->query(
    "SELECT COUNT(*) AS total,
        COALESCE(SUM(status = 'Pending'), 0) AS pending,
        COALESCE(SUM(status = 'Rescued'), 0) AS rescued
     FROM reports"
);
$counts = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PAW PATROL</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700;800&display=swap" rel="stylesheet">
  <style>body{font-family:'Inter',sans-serif}</style>
</head>
<body class="bg-[#fffdf8]">
<nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
  <a href="dashboard.php" class="flex items-center gap-2 text-[15px] font-extrabold"><span class="grid h-8 w-8 place-items-center rounded-full bg-black text-white">🐾</span> PAW PATROL</a>
  <div class="flex items-center gap-2">
    <span class="hidden text-xs text-zinc-500 sm:inline">Hi, <?= escape((string) $_SESSION['user_name']) ?></span>
    <a href="report.php" class="rounded-full bg-black px-4 py-2 text-xs font-bold text-white">Report a stray</a>
    <a href="adoption.php" class="rounded-full border bg-white px-4 py-2 text-xs font-bold">Adoption</a>
    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?><a href="admin.php" class="rounded-full border bg-white px-4 py-2 text-xs font-bold">Admin</a><?php endif; ?>
    <a href="logout.php" class="rounded-full border bg-white px-4 py-2 text-xs font-bold">Sign out</a>
  </div>
</nav>
<main class="mx-auto mt-4 grid max-w-6xl gap-6 px-6 md:grid-cols-12">
  <section class="relative overflow-hidden rounded-[28px] border border-black/5 bg-white p-8 shadow-sm md:col-span-7 md:p-10">
    <div class="absolute right-0 top-0 h-64 w-64 rounded-full bg-yellow-100 opacity-60 blur-2xl"></div>
    <div class="relative">
      <span class="rounded-full bg-yellow-100 px-3 py-1 text-[11px] font-bold tracking-widest">● LIVE RESCUE TRACKER</span>
      <h1 class="mt-5 text-[48px] font-extrabold leading-[0.9] tracking-tight">Help us<br>find them<br>a home.</h1>
      <p class="mt-4 max-w-[320px] text-sm leading-relaxed text-zinc-500">Report stray animals in your community and help rescue them and find them a new home.</p>
      <div class="mt-7 flex gap-3">
        <a href="report.php" class="rounded-full bg-black px-6 py-3 text-sm font-bold text-white">Report now →</a>
        <a href="adoption.php" class="rounded-full bg-[#f5f3ef] px-6 py-3 text-sm font-bold">Meet the pets</a>
      </div>
      <div class="mt-10 flex gap-6 border-t border-gray-100 pt-6">
        <div><p class="text-2xl font-extrabold"><?= (int) $counts['total'] ?></p><p class="text-[10px] font-bold tracking-widest text-zinc-400">TOTAL REPORTS</p></div>
        <div><p class="text-2xl font-extrabold"><?= (int) $counts['pending'] ?></p><p class="text-[10px] font-bold tracking-widest text-zinc-400">PENDING</p></div>
        <div><p class="text-2xl font-extrabold"><?= (int) $counts['rescued'] ?></p><p class="text-[10px] font-bold tracking-widest text-zinc-400">RESCUED</p></div>
      </div>
    </div>
  </section>
  <section class="grid gap-4 md:col-span-5">
    <div class="flex items-center gap-4 rounded-[24px] border border-black/5 bg-[#fde68a] p-5">
      <img src="https://images.unsplash.com/photo-1583512603807-63cc1dc80f98?w=200&amp;q=80" alt="Happy dog" class="h-20 w-20 rounded-2xl object-cover">
      <div><p class="inline-block rounded-full bg-black px-2 py-0.5 text-xs font-bold text-white">FEATURED</p><p class="mt-2 text-sm font-bold">Browny is looking for a home</p><p class="mt-1 text-xs text-zinc-600">Rescued at CE Bldg • Very friendly</p></div>
    </div>
    <div class="grid grid-cols-2 gap-3 rounded-[24px] border border-black/5 bg-white p-5">
      <div class="rounded-2xl bg-[#f9fafb] p-4"><p class="text-xl">🐶</p><p class="mt-2 text-sm font-bold">Dog reports</p><p class="text-[11px] text-zinc-500">Help locate dogs in need</p></div>
      <div class="rounded-2xl bg-[#f9fafb] p-4"><p class="text-xl">🐱</p><p class="mt-2 text-sm font-bold">Cat reports</p><p class="text-[11px] text-zinc-500">Help locate cats in need</p></div>
      <div class="col-span-2 flex items-center justify-between rounded-2xl bg-black p-4 text-white"><div><p class="text-sm font-bold">How to help?</p><p class="mt-1 text-xs text-zinc-400">1. Report 2. Share 3. Adopt</p></div><a href="report.php" aria-label="Create a report" class="grid h-8 w-8 place-items-center rounded-full bg-white font-bold text-black">→</a></div>
    </div>
  </section>
</main>
</body>
</html>
