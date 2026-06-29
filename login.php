<?php
session_start();
if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit(); }
require_once 'db.php';

$errors    = [];
$oldEmail  = '';
$loggedOut = isset($_GET['logout']) && $_GET['logout'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldEmail = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($oldEmail) || !filter_var($oldEmail, FILTER_VALIDATE_EMAIL))
        $errors['email'] = "Please enter a valid email address.";
    if (empty($password))
        $errors['password'] = "Password is required.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id, name, password, role, avatar FROM users WHERE email = :email");
            $stmt->execute([':email' => $oldEmail]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $oldEmail;
                $_SESSION['user_role']  = $user['role'];   // Task 5: store role
                $_SESSION['user_avatar']= $user['avatar']; // Task 5: store avatar
                header("Location: dashboard.php?welcome=1");
                exit();
            } else {
                $errors['credentials'] = "Incorrect email or password. Please try again.";
            }
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            $errors['db'] = "Login failed due to a server error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — ApexPlanet</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
<main class="auth-card">
    <div class="logo">
        <h1>Apex<span>Planet</span></h1>
        <p>Software Pvt Ltd — Internship Portal</p>
    </div>
    <h2 class="page-title">Login to Your Account</h2>

    <?php if ($loggedOut): ?>
        <div class="alert alert-success">✅ You have been logged out successfully. <button class="alert-close" onclick="this.parentElement.remove()">✕</button></div>
    <?php endif; ?>
    <?php if (isset($errors['credentials'])): ?>
        <div class="alert alert-error" role="alert">🔐 <?= htmlspecialchars($errors['credentials']) ?> <button class="alert-close" onclick="this.parentElement.remove()">✕</button></div>
    <?php endif; ?>
    <?php if (isset($errors['db'])): ?>
        <div class="alert alert-error" role="alert">⚠️ <?= htmlspecialchars($errors['db']) ?> <button class="alert-close" onclick="this.parentElement.remove()">✕</button></div>
    <?php endif; ?>

    <form method="POST" action="login.php" id="loginForm" novalidate>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($oldEmail) ?>"
                   placeholder="✉️  ravi@example.com"
                   class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                   required autofocus autocomplete="email">
            <span class="field-error <?= isset($errors['email']) ? 'visible' : '' ?>" id="emailErr"><?= $errors['email'] ?? '' ?></span>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="🔒  Your password"
                   class="<?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                   required autocomplete="current-password">
            <span class="field-error <?= isset($errors['password']) ? 'visible' : '' ?>" id="passErr"><?= $errors['password'] ?? '' ?></span>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Login</button>
    </form>
    <hr class="divider">
    <p class="footer-link">Don't have an account? <a href="register.php">Register</a></p>
</main>
<script>
function showError(el,id,msg){el.classList.add('is-invalid');const s=document.getElementById(id);s.textContent=msg;s.classList.add('visible');}
function clearError(el,id){el.classList.remove('is-invalid');const s=document.getElementById(id);s.textContent='';s.classList.remove('visible');}
const form=document.getElementById('loginForm');
const email=document.getElementById('email'),pass=document.getElementById('password');
form.addEventListener('submit',function(e){
    let ok=true;
    const rx=/^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    !rx.test(email.value.trim())?(showError(email,'emailErr','Enter a valid email address.'),ok=false):clearError(email,'emailErr');
    !pass.value.trim()?(showError(pass,'passErr','Password is required.'),ok=false):clearError(pass,'passErr');
    if(!ok){e.preventDefault();form.querySelector('.is-invalid').focus();}
});
</script>
</body>
</html>
