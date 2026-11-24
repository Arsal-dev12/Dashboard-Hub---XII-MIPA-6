<?php
require 'config.php';

$currentUser = get_sso_user();
if (!$currentUser || !is_admin($currentUser)) {
    header("Location: index.php");
    exit;
}

// Proses aksi Admin (Kelola Website & Kelola User)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Tambah Website
    if ($action === 'add_website') {
        $nama_web   = trim($_POST['nama_web']);
        $pemilik    = trim($_POST['pemilik']);
        $url_tujuan = normalize_url($_POST['url_tujuan']);
        $deskripsi  = trim($_POST['deskripsi']);

        if ($nama_web && $pemilik && $url_tujuan) {
            $stmt = $pdo->prepare("INSERT INTO websites (nama_web, pemilik, url_tujuan, deskripsi) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nama_web, $pemilik, $url_tujuan, $deskripsi]);
            header("Location: admin.php?msg=web_added");
            exit;
        }
    }

    // 2. Edit Website
    if ($action === 'edit_website') {
        $id         = (int) $_POST['id'];
        $nama_web   = trim($_POST['nama_web']);
        $pemilik    = trim($_POST['pemilik']);
        $url_tujuan = normalize_url($_POST['url_tujuan']);
        $deskripsi  = trim($_POST['deskripsi']);

        if ($id > 0 && $nama_web && $pemilik && $url_tujuan) {
            $stmt = $pdo->prepare("UPDATE websites SET nama_web = ?, pemilik = ?, url_tujuan = ?, deskripsi = ? WHERE id = ?");
            $stmt->execute([$nama_web, $pemilik, $url_tujuan, $deskripsi, $id]);
            header("Location: admin.php?msg=web_updated");
            exit;
        }
    }

    // 3. Hapus Website
    if ($action === 'delete_website') {
        $id = (int) $_POST['id'];
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM websites WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: admin.php?msg=web_deleted");
            exit;
        }
    }

    // 4. Ubah Role User (Admin <-> User)
    if ($action === 'toggle_role') {
        $targetId = (int) $_POST['user_id'];
        $newRole  = ($_POST['new_role'] === 'admin') ? 'admin' : 'user';
        // Cegah admin menurunkan role dirinya sendiri
        if ($targetId > 0 && $targetId !== (int) $currentUser['id']) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $targetId]);
            header("Location: admin.php?msg=role_updated");
            exit;
        }
    }

    // 5. Hapus User
    if ($action === 'delete_user') {
        $targetId = (int) $_POST['user_id'];
        // Cegah admin menghapus akunnya sendiri
        if ($targetId > 0 && $targetId !== (int) $currentUser['id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$targetId]);
            header("Location: admin.php?msg=user_deleted");
            exit;
        }
    }
}

$websites = $pdo->query("SELECT * FROM websites ORDER BY id ASC")->fetchAll();
$users    = $pdo->query("SELECT id, username, nama_lengkap, role, created_at FROM users ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin — Kelola Portal SSO</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">

    <!-- Navbar Admin -->
    <nav class="sticky top-0 z-30 bg-slate-900 text-white border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-indigo-500 text-white font-bold flex items-center justify-center text-sm">
                    ADM
                </div>
                <div>
                    <span class="font-bold block leading-none">Panel Admin Portal</span>
                    <span class="text-xs text-slate-400">Manajemen 18 Website & Akun SSO</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="index.php" class="text-sm font-medium text-slate-200 hover:text-white bg-slate-800 hover:bg-slate-700 px-3.5 py-2 rounded-xl transition">
                    &larr; Kembali ke Portal
                </a>
                <a href="logout.php" class="text-sm font-semibold text-rose-400 hover:text-rose-300 bg-rose-950/50 px-3.5 py-2 rounded-xl transition">
                    Keluar
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">

        <?php if (isset($_GET['msg'])): 
            $alerts = [
                'web_added'    => 'Website baru berhasil ditambahkan!',
                'web_updated'  => 'Data website berhasil diperbarui!',
                'web_deleted'  => 'Website berhasil dihapus!',
                'role_updated' => 'Hak akses (role) pengguna berhasil diubah!',
                'user_deleted' => 'Akun pengguna berhasil dihapus!'
            ];
            if (isset($alerts[$_GET['msg']])):
        ?>
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex justify-between items-center">
                <span><?= $alerts[$_GET['msg']] ?></span>
                <a href="admin.php" class="font-bold text-lg leading-none">&times;</a>
            </div>
        <?php endif; endif; ?>

        <!-- Ringkasan Statistik -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-xs font-semibold text-slate-400 uppercase">Total Website Terdaftar</p>
                <p class="text-3xl font-bold text-slate-900 mt-1"><?= count($websites) ?> <span class="text-sm font-normal text-slate-400">/ 18 target</span></p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-xs font-semibold text-slate-400 uppercase">Total Akun SSO</p>
                <p class="text-3xl font-bold text-indigo-600 mt-1"><?= count($users) ?> <span class="text-sm font-normal text-slate-400">pengguna</span></p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase">Login Sebagai</p>
                    <p class="text-lg font-bold text-slate-900 mt-1"><?= htmlspecialchars($currentUser['nama']) ?></p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700">ADMIN</span>
            </div>
        </div>

        <!-- Bagian 1: Manajemen Daftar Website -->
        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Daftar Website Terintegrasi</h2>
                    <p class="text-sm text-slate-500">Tambah, edit URL (localhost maupun domain deploy), atau hapus website anggota.</p>
                </div>
                <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                    class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition cursor-pointer">
                    + Tambah Website Baru
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
                            <th class="py-3.5 px-6">No</th>
                            <th class="py-3.5 px-6">Nama Website</th>
                            <th class="py-3.5 px-6">Pemilik</th>
                            <th class="py-3.5 px-6">URL Tujuan</th>
                            <th class="py-3.5 px-6">Status</th>
                            <th class="py-3.5 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm">
                        <?php foreach ($websites as $i => $web): 
                            $cleanUrl = normalize_url($web['url_tujuan']);
                            $isLocal = preg_match('#^http://(localhost|127\.0\.0\.1|192\.168\.|10\.)#i', $cleanUrl);
                        ?>
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-4 px-6 font-medium text-slate-500"><?= $i + 1 ?></td>
                            <td class="py-4 px-6">
                                <div class="font-bold text-slate-900"><?= htmlspecialchars($web['nama_web']) ?></div>
                                <div class="text-xs text-slate-500 max-w-xs truncate"><?= htmlspecialchars($web['deskripsi']) ?></div>
                            </td>
                            <td class="py-4 px-6 font-medium text-slate-700"><?= htmlspecialchars($web['pemilik']) ?></td>
                            <td class="py-4 px-6 font-mono text-xs text-slate-600 max-w-xs truncate">
                                <a href="<?= htmlspecialchars($cleanUrl) ?>" target="_blank" class="hover:text-indigo-600 underline">
                                    <?= htmlspecialchars($cleanUrl) ?>
                                </a>
                            </td>
                            <td class="py-4 px-6">
                                <span class="text-[11px] font-bold px-2.5 py-1 rounded-full <?= $isLocal ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' ?>">
                                    <?= $isLocal ? 'LOCAL' : 'ONLINE' ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right space-x-2 whitespace-nowrap">
                                <button type="button"
                                    onclick="openEditModal(this)"
                                    data-id="<?= $web['id'] ?>"
                                    data-nama="<?= htmlspecialchars($web['nama_web'], ENT_QUOTES) ?>"
                                    data-pemilik="<?= htmlspecialchars($web['pemilik'], ENT_QUOTES) ?>"
                                    data-url="<?= htmlspecialchars($web['url_tujuan'], ENT_QUOTES) ?>"
                                    data-deskripsi="<?= htmlspecialchars($web['deskripsi'] ?? '', ENT_QUOTES) ?>"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-indigo-100 text-slate-700 hover:text-indigo-700 transition cursor-pointer">
                                    Edit
                                </button>

                                <form method="POST" onsubmit="return confirm('Yakin ingin menghapus website ini?');" class="inline">
                                    <input type="hidden" name="action" value="delete_website">
                                    <input type="hidden" name="id" value="<?= $web['id'] ?>">
                                    <button type="submit"
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-600 transition cursor-pointer">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($websites)): ?>
                            <tr><td colspan="6" class="py-8 text-center text-slate-400">Belum ada website terdaftar.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Bagian 2: Manajemen Akun Pengguna -->
        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Manajemen Pengguna SSO</h2>
                <p class="text-sm text-slate-500">Atur siapa saja yang menjadi Admin atau User biasa di seluruh jaringan website.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
                            <th class="py-3.5 px-6">Nama Lengkap</th>
                            <th class="py-3.5 px-6">Username</th>
                            <th class="py-3.5 px-6">Role</th>
                            <th class="py-3.5 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm">
                        <?php foreach ($users as $u): 
                            $isSelf = ((int) $u['id'] === (int) $currentUser['id']);
                        ?>
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-4 px-6 font-semibold text-slate-900">
                                <?= htmlspecialchars($u['nama_lengkap']) ?>
                                <?php if ($isSelf): ?>
                                    <span class="ml-1.5 text-xs font-normal text-slate-400">(Akun Anda)</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-6 font-mono text-slate-600"><?= htmlspecialchars($u['username']) ?></td>
                            <td class="py-4 px-6">
                                <span class="text-xs font-bold uppercase px-2.5 py-1 rounded-full <?= $u['role'] === 'admin' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600' ?>">
                                    <?= htmlspecialchars($u['role']) ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right space-x-2 whitespace-nowrap">
                                <?php if (!$isSelf): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="toggle_role">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="new_role" value="<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>">
                                        <button type="submit"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-indigo-100 text-slate-700 hover:text-indigo-700 transition cursor-pointer">
                                            <?= $u['role'] === 'admin' ? 'Jadikan User Biasa' : 'Angkat Jadi Admin' ?>
                                        </button>
                                    </form>

                                    <form method="POST" onsubmit="return confirm('Hapus akun pengguna ini?');" class="inline">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-600 transition cursor-pointer">
                                            Hapus
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400 italic">Tidak dapat diubah</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <!-- Modal Tambah Website -->
    <div id="addModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-slate-900">Tambah Website ke Portal</h3>
                <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_website">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Nama Website</label>
                    <input type="text" name="nama_web" required placeholder="Misal: e-Piket SMAN 2 Cikarang Selatan"
                        class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Nama Pemilik</label>
                    <input type="text" name="pemilik" required placeholder="Misal: Arsal"
                        class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">URL Tujuan (Lokal / Domain Deploy)</label>
                    <input type="text" name="url_tujuan" required placeholder="epiket.vercel.app atau localhost/web_teman_03/index.php"
                        class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Deskripsi Singkat</label>
                    <input type="text" name="deskripsi" placeholder="Deskripsi singkat fungsi website"
                        class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm outline-none focus:border-indigo-500">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</button>
                    <button type="submit"
                        class="px-4 py-2 rounded-xl text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Website -->
    <div id="editModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-slate-900">Edit Website Terdaftar</h3>
                <button onclick="document.getElementById('editModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit_website">
                <input type="hidden" name="id" id="edit_id">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Nama Website</label>
                    <input type="text" name="nama_web" id="edit_nama" required
                        class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Nama Pemilik</label>
                    <input type="text" name="pemilik" id="edit_pemilik" required
                        class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">URL Tujuan (Lokal / Domain Deploy)</label>
                    <input type="text" name="url_tujuan" id="edit_url" required
                        class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Deskripsi Singkat</label>
                    <input type="text" name="deskripsi" id="edit_deskripsi"
                        class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm outline-none focus:border-indigo-500">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</button>
                    <button type="submit"
                        class="px-4 py-2 rounded-xl text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(btn) {
            document.getElementById('edit_id').value = btn.dataset.id;
            document.getElementById('edit_nama').value = btn.dataset.nama;
            document.getElementById('edit_pemilik').value = btn.dataset.pemilik;
            document.getElementById('edit_url').value = btn.dataset.url;
            document.getElementById('edit_deskripsi').value = btn.dataset.deskripsi;
            document.getElementById('editModal').classList.remove('hidden');
        }
    </script>
</body>
</html>