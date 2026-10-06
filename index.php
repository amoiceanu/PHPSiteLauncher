<?php
declare(strict_types=1);

session_name('php_launcher_session');
session_set_cookie_params([
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
header('Cache-Control: no-store, private');

const LAUNCHER_ADMIN = 'admin';
const LAUNCHER_PASSWORD_HASH = '$2y$12$gQYRqlFjEQfNtRt9JIIGE.bvepcoAswIP8wDQlOD1vhif8fKagmwa';

function redirectToLauncher(): never
{
    header('Location: /index.php', true, 303);
    exit;
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'],
    ]);
    session_destroy();
    redirectToLauncher();
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf'] ?? '');
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!hash_equals($_SESSION['csrf'], $token)) {
        $error = 'Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.';
    } elseif ($username === LAUNCHER_ADMIN && password_verify($password, LAUNCHER_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['launcher_admin'] = true;
        redirectToLauncher();
    } else {
        $error = 'Utilizator sau parolă incorectă.';
    }
}

if (empty($_SESSION['launcher_admin'])):
?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <title>Autentificare · PHP Site Launcher</title>
  <style>
    :root { --ink:#0b1d34; --muted:#40536b; --line:#b8c6d6; --accent:#075f3e; }
    * { box-sizing:border-box; }
    body { min-height:100vh; margin:0; display:grid; place-items:center; padding:24px; color:var(--ink); font:16px/1.5 Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif; background:radial-gradient(circle at 12% 5%,#d9e5ff 0,transparent 30rem),radial-gradient(circle at 90% 95%,#d6f2e4 0,transparent 31rem),#edf3f9; }
    main { width:min(100%,420px); padding:34px; border:1px solid var(--line); border-radius:22px; background:rgba(255,255,255,.97); box-shadow:0 24px 60px rgba(27,57,91,.18); }
    .mark { display:grid; width:46px; height:46px; place-items:center; border-radius:14px; color:#fff; background:var(--accent); font-size:21px; font-weight:900; }
    h1 { margin:20px 0 8px; font-size:29px; letter-spacing:-.045em; }
    p { margin:0; color:var(--muted); }
    form { display:grid; gap:15px; margin-top:25px; }
    label { display:grid; gap:6px; color:#263b54; font-size:13px; font-weight:750; }
    input { width:100%; height:46px; padding:0 12px; border:1px solid #a9bacd; border-radius:10px; color:var(--ink); background:#fff; font:inherit; }
    input:focus { border-color:#075f3e; outline:3px solid rgba(7,95,62,.15); }
    button { height:46px; border:0; border-radius:10px; color:#fff; background:#075f3e; font:inherit; font-weight:800; cursor:pointer; }
    button:hover { background:#03472d; }
    .error { margin-top:18px; padding:10px 12px; border:1px solid #e7a3a3; border-radius:10px; color:#8c1f1f; background:#fff2f2; font-size:13px; }
    .note { margin-top:20px; font-size:12px; }
  </style>
</head>
<body>
  <main>
    <div class="mark">P</div>
    <h1>PHP Site Launcher</h1>
    <p>Autentifică-te pentru a accesa proiectele locale și evaluările lor.</p>
    <?php if ($error !== ''): ?><div class="error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <form method="post" action="/index.php">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
      <label>Utilizator<input name="username" type="text" autocomplete="username" required autofocus></label>
      <label>Parolă<input name="password" type="password" autocomplete="current-password" required></label>
      <button type="submit">Autentificare</button>
    </form>
    <p class="note">Acces local protejat prin sesiune.</p>
  </main>
</body>
</html>
<?php
exit;
endif;

header('Cache-Control: no-store, private');
require __DIR__.'/render.php';
