<?php
// Ambil konfigurasi dari Environment Variable Vercel, jika kosong pakai default XAMPP
$host   = getenv('DB_HOST') ?: '127.0.0.1';
$port   = getenv('DB_PORT') ?: '3306';
$db     = getenv('DB_NAME') ?: 'db_auth_pusat';
$user   = getenv('DB_USER') ?: 'root';
$pass   = getenv('DB_PASS') ?: '';
$useSsl = getenv('DB_SSL') === 'true';

define('JWT_SECRET', getenv('JWT_SECRET') ?: 'kunci_rahasia_sso_18_web_2026');

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

if ($useSsl && file_exists('/etc/ssl/certs/ca-certificates.crt')) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
}

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function create_jwt($payload, $secret) {
    $header = base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload['exp'] = time() + 7200; // Berlaku 2 jam
    $payload_encoded = base64url_encode(json_encode($payload));
    $signature = hash_hmac('sha256', "$header.$payload_encoded", $secret, true);
    return "$header.$payload_encoded." . base64url_encode($signature);
}

function verify_jwt($token, $secret) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;

    [$header, $payload, $signature] = $parts;
    $expected_sig = base64url_encode(hash_hmac('sha256', "$header.$payload", $secret, true));

    if (!hash_equals($expected_sig, $signature)) return false;

    $data = json_decode(base64url_decode($payload), true);
    if (!$data || (isset($data['exp']) && $data['exp'] < time())) return false;

    return $data;
}

function get_sso_user() {
    global $pdo;
    if (!isset($_COOKIE['sso_portal_token'])) return null;
    $payload = verify_jwt($_COOKIE['sso_portal_token'], JWT_SECRET);
    if (!is_array($payload) || !isset($payload['id'])) return null;

    // Ambil data terbaru dari MySQL agar perubahan role/akun langsung aktif
    $stmt = $pdo->prepare("SELECT id, username, nama_lengkap AS nama, role FROM users WHERE id = ?");
    $stmt->execute([$payload['id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function is_admin($user) {
    return is_array($user) && (($user['role'] ?? 'user') === 'admin');
}

function normalize_url($url) {
    $url = trim($url);
    if ($url === '') return '';

    if (!preg_match('#^https?://#i', $url)) {
        if (preg_match('#^(localhost|127\.0\.0\.1|192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)#i', $url)) {
            $url = 'http://' . $url;
        } else {
            $url = 'https://' . $url;
        }
    }
    return $url;
}
?>