<?php
require 'config.php';

if (get_sso_user()) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $cek = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $cek->execute([$username]);

    if ($cek->fetch()) {
        $error = "Username '$username' sudah digunakan, pilih yang lain.";
    } else {
        // Jika tabel users masih kosong (misal baru deploy ke Vercel), akun pertama otomatis jadi admin
        $totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $role = ($totalUsers === 0) ? 'admin' : 'user';

        $stmt = $pdo->prepare("INSERT INTO users (nama_lengkap, username, password, role) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$nama, $username, $password, $role])) {
            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — Portal SSO</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white/95 backdrop-blur rounded-2xl shadow-2xl p-8 border border-slate-200">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 text-white font-bold text-xl shadow-lg shadow-indigo-500/30 mb-4">
                SSO
            </div>
            <h1 class="text-2xl font-bold text-slate-800">Buat Akun Baru</h1>
            <p class="text-sm text-slate-500 mt-1">Daftarkan satu akun untuk seluruh aplikasi</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" required placeholder="Contoh: Arsal Arash"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Username</label>
                <input type="text" name="username" required placeholder="Tanpa spasi, misal: arsal"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required placeholder="Minimal 6 karakter"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
            </div>
            <button type="submit"
                class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl shadow-md shadow-indigo-600/20 transition cursor-pointer">
                Daftar Akun
            </button>
        </form>

        <p class="text-center text-sm text-slate-600 mt-6">
            Sudah punya akun? <a href="login.php" class="text-indigo-600 font-semibold hover:underline">Masuk di sini</a>
        </p>
    </div>
</body>
</html>