<?php
setcookie('sso_portal_token', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);
header("Location: login.php");
exit;
?>