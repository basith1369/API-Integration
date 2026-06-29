<?php
define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_NAME',    'apex_intern');
define('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("DB Connection Error: " . $e->getMessage());
    die(renderDbError("Unable to connect to the database. Please try again later."));
}

function renderDbError(string $msg): string {
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title>
    <style>body{font-family:Segoe UI,sans-serif;background:#f0f4f3;display:flex;align-items:center;justify-content:center;min-height:100vh;}
    .box{background:#fff;border-radius:12px;padding:2.5rem;max-width:460px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,.08);}
    h2{color:#c0392b;}p{color:#555;}a{color:#3aafa9;}</style></head>
    <body><div class="box"><h2>⚠️ Something went wrong</h2>
    <p>'.htmlspecialchars($msg).'</p>
    <p style="margin-top:1rem"><a href="javascript:history.back()">← Go back</a></p>
    </div></body></html>';
}

// ── Role helper functions (Task 5) ────────────────────────────────────────────
function isAdmin(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}
function requireAdmin(): void {
    if (!isAdmin()) {
        header("Location: dashboard.php?error=unauthorized");
        exit();
    }
}
function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}
?>
