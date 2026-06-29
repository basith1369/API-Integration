<?php
session_start();
if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit(); }
require_once 'db.php';

$errors  = [];
$success = "";
$old     = ['name'=>'','email'=>'','phone'=>'','address'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name']    = trim(htmlspecialchars($_POST['name']    ?? '', ENT_QUOTES));
    $old['email']   = trim($_POST['email']   ?? '');
    $old['phone']   = trim(htmlspecialchars($_POST['phone']   ?? '', ENT_QUOTES));
    $old['address'] = trim(htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES));
    $password       = $_POST['password']         ?? '';
    $confirm        = $_POST['confirm_password'] ?? '';

    if (empty($old['name']) || strlen($old['name']) < 2)
        $errors['name'] = "Name must be at least 2 characters.";
    if (empty($old['email']) || !filter_var($old['email'], FILTER_VALIDATE_EMAIL))
        $errors['email'] = "Please enter a valid email address.";
    if (!empty($old['phone']) && !preg_match('/^[0-9+\-\s]{7,15}$/', $old['phone']))
        $errors['phone'] = "Phone must be 7–15 digits.";
    if (strlen($password) < 6)
        $errors['password'] = "Password must be at least 6 characters.";
    if ($password !== $confirm)
        $errors['confirm'] = "Passwords do not match.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $old['email']]);
            if ($stmt->fetch()) {
                $errors['email'] = "This email is already registered. <a href='login.php'>Login instead?</a>";
            } else {
                // First registered user becomes admin automatically
                $countStmt = $pdo->query("SELECT COUNT(*) FROM users");
                $userCount = (int)$countStmt->fetchColumn();
                $role = $userCount === 0 ? 'admin' : 'user';

                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $ins = $pdo->prepare(
                    "INSERT INTO users (name,email,password,phone,address,role)
                     VALUES (:name,:email,:password,:phone,:address,:role)"
                );
                $ins->execute([
                    ':name'     => $old['name'],
                    ':email'    => $old['email'],
                    ':password' => $hashed,
                    ':phone'    => $old['phone']   ?: null,
                    ':address'  => $old['address'] ?: null,
                    ':role'     => $role,
                ]);
                $success = "Account created successfully! You can now <a href='login.php'>login here</a>.";
                $old = ['name'=>'','email'=>'','phone'=>'','address'=>''];
            }
        } catch (PDOException $e) {
            error_log("Register Error: " . $e->getMessage());
            $errors['db'] = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — ApexPlanet</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
<main class="auth-card">
    <div class="logo">
        <h1>Apex<span>Planet</span></h1>
        <p>Software Pvt Ltd — Internship Portal</p>
    </div>
    <h2 class="page-title">Create an Account</h2>

    <?php if (isset($errors['db'])): ?>
        <div class="alert alert-error">⚠️ <?= $errors['db'] ?> <button class="alert-close" onclick="this.parentElement.remove()">✕</button></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= $success ?> <button class="alert-close" onclick="this.parentElement.remove()">✕</button></div>
    <?php endif; ?>

    <form method="POST" action="register.php" id="regForm" novalidate>
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name']) ?>"
                   placeholder="👤  e.g. Ravi Kumar"
                   class="<?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                   minlength="2" maxlength="100" required autocomplete="name">
            <span class="field-error <?= isset($errors['name']) ? 'visible' : '' ?>" id="nameErr"><?= $errors['name'] ?? '' ?></span>
        </div>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email']) ?>"
                   placeholder="✉️  ravi@example.com"
                   class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                   maxlength="150" required autocomplete="email">
            <span class="field-error <?= isset($errors['email']) ? 'visible' : '' ?>" id="emailErr"><?= $errors['email'] ?? '' ?></span>
        </div>
        <div class="form-group">
            <label for="phone">Phone <span class="opt">(optional)</span></label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($old['phone']) ?>"
                   placeholder="📱  +91 98765 43210"
                   class="<?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                   maxlength="15" autocomplete="tel">
            <span class="field-error <?= isset($errors['phone']) ? 'visible' : '' ?>" id="phoneErr"><?= $errors['phone'] ?? '' ?></span>
        </div>
        <div class="form-group">
            <label for="address">Address <span class="opt">(optional)</span></label>
            <input type="text" id="address" name="address" value="<?= htmlspecialchars($old['address']) ?>"
                   placeholder="📍  City, State" maxlength="255" autocomplete="street-address">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="🔒  Min. 6 characters"
                   class="<?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                   minlength="6" maxlength="72" required autocomplete="new-password">
            <span class="field-error <?= isset($errors['password']) ? 'visible' : '' ?>" id="passErr"><?= $errors['password'] ?? '' ?></span>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password"
                   placeholder="🔒  Repeat your password"
                   class="<?= isset($errors['confirm']) ? 'is-invalid' : '' ?>"
                   required autocomplete="new-password">
            <span class="field-error <?= isset($errors['confirm']) ? 'visible' : '' ?>" id="confirmErr"><?= $errors['confirm'] ?? '' ?></span>
            <p class="hint" id="matchHint" aria-live="polite"></p>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Register</button>
    </form>
    <hr class="divider">
    <p class="footer-link">Already have an account? <a href="login.php">Login</a></p>
</main>
<script>
function showError(el,id,msg){el.classList.add('is-invalid');el.classList.remove('is-valid');const s=document.getElementById(id);s.textContent=msg;s.classList.add('visible');}
function clearError(el,id){el.classList.remove('is-invalid');el.classList.add('is-valid');const s=document.getElementById(id);s.textContent='';s.classList.remove('visible');}
const form=document.getElementById('regForm');
const nameEl=document.getElementById('name'),emailEl=document.getElementById('email');
const phoneEl=document.getElementById('phone'),passEl=document.getElementById('password');
const confirmEl=document.getElementById('confirm_password'),matchHint=document.getElementById('matchHint');
confirmEl.addEventListener('input',()=>{
    if(!confirmEl.value){matchHint.textContent='';return;}
    if(confirmEl.value===passEl.value){matchHint.textContent='✅ Passwords match';matchHint.style.color='#1a7a5e';clearError(confirmEl,'confirmErr');}
    else{matchHint.textContent='❌ Passwords do not match';matchHint.style.color='#e74c3c';showError(confirmEl,'confirmErr','Passwords do not match.');}
});
form.addEventListener('submit',function(e){
    let ok=true;
    const erx=/^[^\s@]+@[^\s@]+\.[^\s@]+$/,prx=/^[0-9+\-\s]{7,15}$/;
    nameEl.value.trim().length<2?(showError(nameEl,'nameErr','Name must be at least 2 characters.'),ok=false):clearError(nameEl,'nameErr');
    !erx.test(emailEl.value.trim())?(showError(emailEl,'emailErr','Enter a valid email address.'),ok=false):clearError(emailEl,'emailErr');
    phoneEl.value&&!prx.test(phoneEl.value)?(showError(phoneEl,'phoneErr','Phone must be 7–15 digits.'),ok=false):null;
    passEl.value.length<6?(showError(passEl,'passErr','Password must be at least 6 characters.'),ok=false):clearError(passEl,'passErr');
    confirmEl.value!==passEl.value?(showError(confirmEl,'confirmErr','Passwords do not match.'),ok=false):clearError(confirmEl,'confirmErr');
    if(!ok){e.preventDefault();form.querySelector('.is-invalid').focus();}
});
</script>
</body>
</html>
