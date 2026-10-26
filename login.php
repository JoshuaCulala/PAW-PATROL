<?php
require_once __DIR__ . '/backend/auth.php';

if (!defined('PAW_PATROL_ENTRY_PAGE') && !empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = trim((string) ($_GET['error'] ?? ''));
$activeForm = trim((string) ($_GET['form'] ?? 'login'));
if (!in_array($activeForm, ['login', 'register'], true)) { $activeForm = 'login'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign in | PAW PATROL</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>body{font-family:'Inter',sans-serif}</style>
</head>
<body class="min-h-screen bg-[#fffdf8] text-zinc-950">
  <main class="mx-auto grid min-h-screen max-w-6xl items-center gap-10 px-5 py-10 md:grid-cols-2 md:px-8">
    <section class="order-2 md:order-1">
      <a href="login.php" class="inline-flex items-center gap-2 font-extrabold tracking-tight">
        <span class="grid h-10 w-10 place-items-center rounded-full bg-black text-lg text-white">🐾</span>
        PAW PATROL
      </a>
      <div class="mt-10 max-w-lg">
        <span class="rounded-full bg-yellow-100 px-3 py-1 text-[11px] font-bold tracking-widest">● COMMUNITY RESCUE</span>
        <h1 class="mt-6 text-5xl font-extrabold leading-[0.98] tracking-tight md:text-6xl">A little help<br>can change a life.</h1>
        <p class="mt-5 max-w-md text-sm leading-6 text-zinc-500">Join your community to report stray animals, follow rescues, and help every pet find a safe home.</p>
      </div>
      <div class="mt-9 flex items-center gap-3 rounded-2xl border border-black/5 bg-white p-4 shadow-sm">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-yellow-100 text-xl">💛</span>
        <div><p class="text-sm font-bold">Every report matters</p><p class="mt-1 text-xs text-zinc-500">Create an account to get started.</p></div>
      </div>
    </section>

    <section class="order-1 mx-auto w-full max-w-md rounded-[28px] border border-black/5 bg-white p-6 shadow-sm md:order-2 md:p-8">
      <h2 class="text-2xl font-extrabold tracking-tight">Welcome to the pack</h2>
      <p class="mt-1 text-sm text-zinc-500">Sign in or create your account to continue.</p>

      <?php if ($error !== ''): ?>
        <p role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= escape($error) ?></p>
      <?php endif; ?>

      <div class="mt-6 grid grid-cols-2 rounded-full bg-zinc-100 p-1">
        <button id="loginTab" type="button" onclick="showForm('login')" class="rounded-full <?= $activeForm === 'login' ? 'bg-black py-2.5 text-sm font-bold text-white' : 'py-2.5 text-sm font-bold text-zinc-500' ?>">Sign in</button>
        <button id="registerTab" type="button" onclick="showForm('register')" class="rounded-full <?= $activeForm === 'register' ? 'bg-black py-2.5 text-sm font-bold text-white' : 'py-2.5 text-sm font-bold text-zinc-500' ?>">Register</button>
      </div>

      <form id="loginForm" action="backend/login-reg.php" method="post" class="mt-6 space-y-4<?= $activeForm === 'register' ? ' hidden' : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
        <input type="hidden" name="action" value="login">
        <label class="block text-xs font-bold text-zinc-600">EMAIL ADDRESS
          <input name="email" type="email" autocomplete="email" maxlength="254" required class="mt-2 w-full rounded-xl border px-4 py-3 text-sm font-normal outline-none focus:border-black" placeholder="you@example.com">
        </label>
        <label class="block text-xs font-bold text-zinc-600">PASSWORD
          <input name="password" type="password" autocomplete="current-password" required class="mt-2 w-full rounded-xl border px-4 py-3 text-sm font-normal outline-none focus:border-black" placeholder="Your password">
        </label>
        <button type="submit" class="w-full rounded-full bg-black py-3.5 text-sm font-bold text-white hover:bg-zinc-800">Sign in →</button>
      </form>

      <form id="registerForm" action="backend/login-reg.php" method="post" class="mt-6<?= $activeForm === 'register' ? ' space-y-4' : ' hidden space-y-4' ?>">
        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
        <input type="hidden" name="action" value="register">
        <label class="block text-xs font-bold text-zinc-600">YOUR NAME
          <input name="name" type="text" autocomplete="name" maxlength="100" required class="mt-2 w-full rounded-xl border px-4 py-3 text-sm font-normal outline-none focus:border-black" placeholder="Your name">
        </label>
        <label class="block text-xs font-bold text-zinc-600">EMAIL ADDRESS
          <input name="email" type="email" autocomplete="email" maxlength="254" required class="mt-2 w-full rounded-xl border px-4 py-3 text-sm font-normal outline-none focus:border-black" placeholder="you@example.com">
        </label>
        <label class="block text-xs font-bold text-zinc-600">PASSWORD <span class="font-normal text-zinc-400">(8 characters minimum)</span>
          <input id="reg_password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required class="mt-2 w-full rounded-xl border px-4 py-3 text-sm font-normal outline-none focus:border-black" placeholder="Create a password">
        </label>
        <label class="block text-xs font-bold text-zinc-600">CONFIRM PASSWORD
          <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required class="mt-2 w-full rounded-xl border px-4 py-3 text-sm font-normal outline-none focus:border-black" placeholder="Enter it again">
        </label>
        <p id="pwMatchError" class="hidden text-xs text-red-600 -mt-2">Passwords do not match.</p>
        <button type="submit" class="w-full rounded-full bg-black py-3.5 text-sm font-bold text-white hover:bg-zinc-800">Create account →</button>
      </form>
      <p class="mt-5 text-center text-[11px] leading-5 text-zinc-400">Your account helps keep rescue reports connected to the community.</p>
    </section>
  </main>
  <script>
    function showForm(form) {
      const login = form === 'login';
      document.getElementById('loginForm').classList.toggle('hidden', !login);
      document.getElementById('registerForm').classList.toggle('hidden', login);
      document.getElementById('loginTab').className = login
        ? 'rounded-full bg-black py-2.5 text-sm font-bold text-white'
        : 'rounded-full py-2.5 text-sm font-bold text-zinc-500';
      document.getElementById('registerTab').className = login
        ? 'rounded-full py-2.5 text-sm font-bold text-zinc-500'
        : 'rounded-full bg-black py-2.5 text-sm font-bold text-white';
    }
    // Issue 1: Password-match client-side validation
    document.getElementById('registerForm').addEventListener('submit', function(e) {
      var pw = document.getElementById('reg_password').value;
      var cpw = document.getElementById('confirm_password').value;
      var err = document.getElementById('pwMatchError');
      if (pw !== cpw) {
        e.preventDefault();
        err.classList.remove('hidden');
        document.getElementById('confirm_password').focus();
      } else {
        err.classList.add('hidden');
      }
    });
  </script>
</body>
</html>
