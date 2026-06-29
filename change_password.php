<?php
session_start();
require_once 'db.php';
requireLogin();

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current)) $errors['current'] = "Current password is required.";
    if (strlen($new) < 6) $errors['new']    = "New password must be at least 6 characters.";
    if ($new !== $confirm) $errors['confirm']= "Passwords do not match.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $user = $stmt->fetch();

            if (!password_verify($current, $user['password'])) {
                $errors['current'] = "Current password is incorrect.";
            } else {
                $hashed = password_hash($new, PASSWORD_BCRYPT);
                $upd = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $upd->execute([':password' => $hashed, ':id' => $_SESSION['user_id']]);
                $success = "Password changed successfully!";
            }
        } catch (PDOException $e) {
            error_log("Change Password Error: " . $e->getMessage());
            $errors['db'] = "Failed to change password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password — ApexPlanet</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="site-header">
    <h1>Apex<span>Planet</span></h1>
    <nav class="nav-right">
        <span class="user-pill">👤 <?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="profile.php"   class="btn btn-outline">My Profile</a>
        <a href="dashboard.php" class="btn btn-outline">Dashboard</a>
        <a href="logout.php"    class="btn btn-accent">Logout</a>
    </nav>
</header>
<div class="container-sm">
    <a href="profile.php" class="back-link">← Back to Profile</a>
    <div class="card">
        <h2 class="section-title" style="margin-bottom:0.3rem;">🔒 Change Password</h2>
        <p class="section-sub" style="margin-bottom:1.5rem;">Choose a strong password of at least 6 characters.</p>

        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?> <button class="alert-close" onclick="this.parentElement.remove()">✕</button></div>
        <?php endif; ?>
        <?php if (isset($errors['db'])): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($errors['db']) ?> <button class="alert-close" onclick="this.parentElement.remove()">✕</button></div>
        <?php endif; ?>

        <form method="POST" action="change_password.php" id="cpForm" novalidate>
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password"
                       placeholder="🔒  Your current password"
                       class="<?= isset($errors['current'])?'is-invalid':'' ?>"
                       required autocomplete="current-password">
                <span class="field-error <?= isset($errors['current'])?'visible':'' ?>" id="currentErr"><?= $errors['current']??'' ?></span>
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password"
                       placeholder="🔒  Min. 6 characters"
                       class="<?= isset($errors['new'])?'is-invalid':'' ?>"
                       required minlength="6" autocomplete="new-password">
                <span class="field-error <?= isset($errors['new'])?'visible':'' ?>" id="newErr"><?= $errors['new']??'' ?></span>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password"
                       placeholder="🔒  Repeat new password"
                       class="<?= isset($errors['confirm'])?'is-invalid':'' ?>"
                       required autocomplete="new-password">
                <span class="field-error <?= isset($errors['confirm'])?'visible':'' ?>" id="confirmErr"><?= $errors['confirm']??'' ?></span>
                <p class="hint" id="matchHint" aria-live="polite"></p>
            </div>
            <div class="btn-row">
                <a href="profile.php" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">Change Password</button>
            </div>
        </form>
    </div>
</div>
<script>
document.querySelectorAll('.alert').forEach(function(el){
    setTimeout(function(){el.classList.add('fade-out');setTimeout(function(){el.remove();},450);},4000);
});
const newP=document.getElementById('new_password'),confP=document.getElementById('confirm_password'),hint=document.getElementById('matchHint');
confP.addEventListener('input',()=>{
    if(!confP.value){hint.textContent='';return;}
    if(confP.value===newP.value){hint.textContent='✅ Passwords match';hint.style.color='#1a7a5e';}
    else{hint.textContent='❌ Passwords do not match';hint.style.color='#e74c3c';}
});
</script>
</body>
</html>
