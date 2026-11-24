<?php
require 'config.php';

$currentUser = get_sso_user();
if (!$currentUser) {
    header("Location: login.php");
    exit;
}

$isAdmin = is_admin($currentUser);

// Aksi kelola website hanya diproses jika user adalah ADMIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_website') {
        $nama_web   = trim($_POST['nama_web']);
        $pemilik    = trim($_POST['pemilik']);
        $url_tujuan = normalize_url($_POST['url_tujuan']);
        $deskripsi  = trim($_POST['deskripsi']);

        if ($nama_web && $pemilik && $url_tujuan) {
            $stmt = $pdo->prepare("INSERT INTO websites (nama_web, pemilik, url_tujuan, deskripsi) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nama_web, $pemilik, $url_tujuan, $deskripsi]);
            header("Location: index.php?msg=added");
            exit;
        }
    } elseif ($action === 'edit_website') {
        $id         = (int) $_POST['id'];
        $nama_web   = trim($_POST['nama_web']);
        $pemilik    = trim($_POST['pemilik']);
        $url_tujuan = normalize_url($_POST['url_tujuan']);
        $deskripsi  = trim($_POST['deskripsi']);

        if ($id > 0 && $nama_web && $pemilik && $url_tujuan) {
            $stmt = $pdo->prepare("UPDATE websites SET nama_web = ?, pemilik = ?, url_tujuan = ?, deskripsi = ? WHERE id = ?");
            $stmt->execute([$nama_web, $pemilik, $url_tujuan, $deskripsi, $id]);
            header("Location: index.php?msg=updated");
            exit;
        }
    } elseif ($action === 'delete_website') {
        $id = (int) $_POST['id'];
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM websites WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: index.php?msg=deleted");
            exit;
        }
    }
}

$token = create_jwt([
    'id'       => $currentUser['id'],
    'username' => $currentUser['username'],
    'nama'     => $currentUser['nama'],
    'role'     => $currentUser['role']
], JWT_SECRET);

$websites = $pdo->query("SELECT * FROM websites ORDER BY id ASC")->fetchAll();

$gradients = [
    'from-indigo-500 to-blue-600',
    'from-emerald-500 to-teal-600',
    'from-violet-500 to-purple-600',
    'from-amber-500 to-orange-600',
    'from-rose-500 to-pink-600',
    'from-cyan-500 to-sky-600',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Hub — 18 Website Terintegrasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">

    <!-- Navbar -->
    <nav class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white font-bold flex items-center justify-center text-sm shadow-sm">
                    SSO
                </div>
                <div>
                    <span class="font-bold text-slate-900 block leading-none">Portal Hub</span>
                    <span class="text-xs text-slate-500">Single Sign-On System</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <?php if ($isAdmin): ?>
                    <a href="admin.php" class="text-sm font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-3.5 py-2 rounded-xl transition">
                        Panel Admin
                    </a>
                <?php endif; ?>

                <div class="hidden sm:flex items-center gap-2.5 bg-slate-100 px-3 py-1.5 rounded-full">
                    <div class="w-7 h-7 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">
                        <?= strtoupper(substr($currentUser['nama'], 0, 1)) ?>
                    </div>
                    <span class="text-sm font-medium text-slate-700"><?= htmlspecialchars($currentUser['nama']) ?></span>
                    <?php if ($isAdmin): ?>
                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-indigo-600 text-white">Admin</span>
                    <?php endif; ?>
                </div>

                <a href="logout.php" class="text-sm font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-3.5 py-2 rounded-xl transition">
                    Keluar
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <?php if (isset($_GET['msg']) && $isAdmin): 
            $messages = [
                'added'   => 'Website baru berhasil ditambahkan ke daftar portal!',
                'updated' => 'Data dan URL website berhasil diperbarui!',
                'deleted' => 'Website berhasil dihapus dari daftar portal.'
            ];
            $alertText = $messages[$_GET['msg']] ?? '';
            if ($alertText):
        ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex justify-between items-center">
                <span><?= $alertText ?></span>
                <a href="index.php" class="font-bold text-lg leading-none">&times;</a>
            </div>
        <?php endif; endif; ?>

        <!-- Header & Search Bar -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Katalog Aplikasi (<?= count($websites) ?>)</h1>
                <p class="text-slate-500 text-sm mt-1">Pratinjau langsung dashboard masing-masing website. Klik untuk masuk otomatis tanpa login ulang.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <input type="text" id="searchInput" placeholder="Cari nama web atau pemilik..."
                    class="w-full sm:w-64 px-4 py-2.5 rounded-xl bg-white border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                
                <?php if ($isAdmin): ?>
                    <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                        class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition cursor-pointer whitespace-nowrap">
                        + Daftarkan Web
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Grid Website dengan Jendela Pratinjau Mini -->
        <div id="webGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($websites as $index => $web): 
                $grad = $gradients[$index % count($gradients)];
                $initials = strtoupper(substr(trim($web['nama_web']), 0, 2));
                $cleanBaseUrl = normalize_url($web['url_tujuan']);
                $separator = (strpos($cleanBaseUrl, '?') !== false) ? '&' : '?';
                $targetUrl = $cleanBaseUrl . $separator . 'token=' . urlencode($token);
                $displayUrl = preg_replace('#^https?://#', '', $cleanBaseUrl);
                $isLocal = preg_match('#^http://(localhost|127\.0\.0\.1|192\.168\.|10\.)#i', $cleanBaseUrl);
            ?>
                <div class="web-card group bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-lg hover:border-indigo-300 transition flex flex-col justify-between"
                     data-search="<?= strtolower(htmlspecialchars($web['nama_web'] . ' ' . $web['pemilik'] . ' ' . $web['deskripsi'])) ?>">
                    
                    <div>
                        <a href="<?= htmlspecialchars($targetUrl) ?>" target="_blank" class="block border-b border-slate-200 bg-slate-100">
                            <div class="px-3 py-2 bg-slate-100 border-b border-slate-200/80 flex items-center gap-2">
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                                </div>
                                <div class="flex-1 bg-white rounded-md px-2.5 py-0.5 text-[11px] text-slate-500 truncate border border-slate-200/70 font-mono">
                                    <?= htmlspecialchars($displayUrl) ?>
                                </div>
                                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded <?= $isLocal ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' ?> shrink-0">
                                    <?= $isLocal ? 'LOCAL' : 'ONLINE' ?>
                                </span>
                            </div>

                            <div class="preview-wrapper relative w-full aspect-video overflow-hidden bg-slate-900">
                                <div class="absolute inset-0 bg-gradient-to-br <?= $grad ?> flex flex-col items-center justify-center text-white/90 p-4">
                                    <span class="text-2xl font-bold tracking-wider"><?= htmlspecialchars($initials) ?></span>
                                    <span class="text-xs opacity-80 mt-1">Klik untuk membuka dashboard</span>
                                </div>

                                <iframe 
                                    src="<?= htmlspecialchars($targetUrl) ?>" 
                                    loading="lazy"
                                    tabindex="-1"
                                    class="preview-frame absolute top-0 left-0 w-[1280px] h-[720px] origin-top-left border-0 pointer-events-none bg-white"
                                    title="Pratinjau <?= htmlspecialchars($web['nama_web']) ?>">
                                </iframe>

                                <div class="absolute inset-0 bg-slate-900/0 group-hover:bg-slate-900/20 transition flex items-center justify-center">
                                    <span class="opacity-0 group-hover:opacity-100 transition bg-slate-900/85 text-white text-xs font-semibold px-3 py-1.5 rounded-full shadow-md">
                                        Klik untuk Membuka ↗
                                    </span>
                                </div>
                            </div>
                        </a>

                        <div class="p-5">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <h2 class="text-lg font-bold text-slate-900 truncate"><?= htmlspecialchars($web['nama_web']) ?></h2>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 shrink-0">
                                    <?= htmlspecialchars($web['pemilik']) ?>
                                </span>
                            </div>
                            <p class="text-sm text-slate-500 line-clamp-2">
                                <?= htmlspecialchars($web['deskripsi'] ?: 'Tidak ada deskripsi tambahan.') ?>
                            </p>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="px-5 pb-5 pt-1 flex items-center gap-2">
                        <a href="<?= htmlspecialchars($targetUrl) ?>" target="_blank"
                           class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-indigo-600 text-white text-sm font-semibold transition">
                            <span>Buka Website</span>
                            <span>&rarr;</span>
                        </a>

                        <?php if ($isAdmin): ?>
                            <button type="button"
                                onclick="openEditModal(this)"
                                data-id="<?= $web['id'] ?>"
                                data-nama="<?= htmlspecialchars($web['nama_web'], ENT_QUOTES) ?>"
                                data-pemilik="<?= htmlspecialchars($web['pemilik'], ENT_QUOTES) ?>"
                                data-url="<?= htmlspecialchars($web['url_tujuan'], ENT_QUOTES) ?>"
                                data-deskripsi="<?= htmlspecialchars($web['deskripsi'] ?? '', ENT_QUOTES) ?>"
                                title="Edit URL / Data Web"
                                class="p-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-indigo-600 transition cursor-pointer">
                                &#9998;
                            </button>

                            <form method="POST" onsubmit="return confirm('Hapus website ini dari daftar portal?');" class="m-0">
                                <input type="hidden" name="action" value="delete_website">
                                <input type="hidden" name="id" value="<?= $web['id'] ?>">
                                <button type="submit" title="Hapus Website"
                                    class="p-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition cursor-pointer">
                                    &#128465;
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <?php if ($isAdmin): ?>
    <!-- Modal Tambah Website (Khusus Admin) -->
    <div id="addModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-slate-900">Daftarkan Website ke Portal</h3>
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
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">URL Tujuan (Lokal atau Domain Deploy)</label>
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

    <!-- Modal Edit Website (Khusus Admin) -->
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
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">URL Tujuan (Lokal atau Domain Deploy)</label>
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
    <?php endif; ?>

    <script>
        function scalePreviews() {
            document.querySelectorAll('.preview-wrapper').forEach(wrapper => {
                const iframe = wrapper.querySelector('.preview-frame');
                if (iframe) {
                    const scale = wrapper.offsetWidth / 1280;
                    iframe.style.transform = `scale(${scale})`;
                }
            });
        }

        window.addEventListener('load', scalePreviews);
        window.addEventListener('resize', scalePreviews);

        document.getElementById('searchInput').addEventListener('input', function(e) {
            const keyword = e.target.value.toLowerCase();
            document.querySelectorAll('.web-card').forEach(card => {
                const text = card.getAttribute('data-search');
                card.style.display = text.includes(keyword) ? 'flex' : 'none';
            });
            scalePreviews();
        });

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