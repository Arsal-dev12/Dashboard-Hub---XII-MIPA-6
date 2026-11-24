<?php
require 'config.php';

if (get_sso_user()) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $token = create_jwt([
            'id'       => $user['id'],
            'username' => $user['username'],
            'nama'     => $user['nama_lengkap'],
            'role'     => $user['role']
        ], JWT_SECRET);

        setcookie('sso_portal_token', $token, [
            'expires'  => time() + 7200,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        header("Location: index.php");
        exit;
    } else {
        $error = "Username atau password tidak sesuai.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Portal SSO 18 Website</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white/95 backdrop-blur rounded-2xl shadow-2xl p-8 border border-slate-200">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 text-white font-bold text-xl shadow-lg shadow-indigo-500/30 mb-4">
                SSO
            </div>
            <h1 class="text-2xl font-bold text-slate-800">Portal Utama</h1>
            <p class="text-sm text-slate-500 mt-1">Satu akun untuk mengakses seluruh 18 website</p>
        </div>

        <?php if (isset($_GET['registered'])): ?>
            <div class="mb-4 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
                Akun berhasil dibuat! Silakan masuk menggunakan akun barumu.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Username</label>
                <input type="text" name="username" required placeholder="Masukkan username"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required placeholder="••••••••"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
            </div>
            <button type="submit"
                class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl shadow-md shadow-indigo-600/20 transition cursor-pointer">
                Masuk ke Portal
            </button>
        </form>

        <p class="text-center text-sm text-slate-600 mt-6">
            Belum punya akun? <a href="register.php" class="text-indigo-600 font-semibold hover:underline">Daftar sekarang</a>
        </p>
    </div>
</body>
</html>