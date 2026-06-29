<?php
session_start();
require_once 'db.php';
requireLogin();
requireAdmin();

$errors = [];
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
if ($id <= 0) { header("Location: manage_users.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim(htmlspecialchars($_POST['name']    ?? '', ENT_QUOTES));
    $email   = trim($_POST['email']   ?? '');
    $phone   = trim(htmlspecialchars($_POST['phone']   ?? '', ENT_QUOTES));
    $address = trim(htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES));

    if (empty($name)||strlen($name)<2||strlen($name)>100) $errors['name']="Name must be 2–100 characters.";
    elseif (!preg_match('/^[\p{L}\s\'-]+$/u',$name)) $errors['name']="Name may only contain letters, spaces, hyphens and apostrophes.";
    if (empty($email)||!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors['email']="Please enter a valid email address.";
    if (!empty($phone)&&!preg_match('/^[0-9+\-\s]{7,15}$/',$phone)) $errors['phone']="Phone must be 7–15 digits.";

    if (empty($errors)) {
        try {
            $check=$pdo->prepare("SELECT id FROM users WHERE email=:email AND id!=:id");
            $check->execute([':email'=>$email,':id'=>$id]);
            if ($check->fetch()) {
                $errors['email']="This email is already used by another account.";
            } else {
                $upd=$pdo->prepare("UPDATE users SET name=:name,email=:email,phone=:phone,address=:address WHERE id=:id");
                $upd->execute([':name'=>$name,':email'=>$email,':phone'=>$phone?:null,':address'=>$address?:null,':id'=>$id]);
                header("Location: manage_users.php?updated=1"); exit();
            }
        } catch (PDOException $e) {
            error_log("Edit User Error: ".$e->getMessage());
            $errors['db']="Update failed due to a server error. Please try again.";
        }
    }
}

try {
    $stmt=$pdo->prepare("SELECT id,name,email,phone,address FROM users WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    $user=$stmt->fetch();
} catch (PDOException $e) { $user=null; }
if (!$user) { header("Location: manage_users.php"); exit(); }

$name    = $_POST['name']    ?? $user['name'];
$email   = $_POST['email']   ?? $user['email'];
$phone   = $_POST['phone']   ?? $user['phone'];
$address = $_POST['address'] ?? $user['address'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User — ApexPlanet</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">
    <h1>Apex<span>Planet</span></h1>
    <nav class="nav-right">
        <a href="manage_users.php" class="btn btn-outline">Manage Users</a>
        <a href="logout.php"       class="btn btn-accent">Logout</a>
    </nav>
</header>

<div class="container-sm">
    <a href="manage_users.php" class="back-link">← Back to Manage Users</a>
    <div class="card">
        <h2 class="section-title" style="margin-bottom:0.25rem;">Edit User</h2>
        <p class="section-sub" style="margin-bottom:1.5rem;">Updating record for User ID #<?= (int)$user['id'] ?></p>

        <?php if (isset($errors['db'])): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($errors['db']) ?> <button class="alert-close" onclick="this.parentElement.remove()">✕</button></div>
        <?php endif; ?>

        <form method="POST" action="edit_user.php?id=<?= (int)$user['id'] ?>" id="editForm" novalidate>
            <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>"
                       class="<?= isset($errors['name']) ? 'is-invalid':'' ?>"
                       required minlength="2" maxlength="100">
                <span class="field-error <?= isset($errors['name'])?'visible':'' ?>" id="nameErr"><?= $errors['name']??'' ?></span>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>"
                       class="<?= isset($errors['email'])?'is-invalid':'' ?>"
                       required maxlength="150">
                <span class="field-error <?= isset($errors['email'])?'visible':'' ?>" id="emailErr"><?= $errors['email']??'' ?></span>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number <span class="opt">(optional)</span></label>
                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($phone??'') ?>"
                       class="<?= isset($errors['phone'])?'is-invalid':'' ?>"
                       placeholder="+91 98765 43210" maxlength="15">
                <span class="field-error <?= isset($errors['phone'])?'visible':'' ?>" id="phoneErr"><?= $errors['phone']??'' ?></span>
            </div>

            <div class="form-group">
                <label for="address">Address <span class="opt">(optional)</span></label>
                <input type="text" id="address" name="address" value="<?= htmlspecialchars($address??'') ?>"
                       placeholder="City, State" maxlength="255">
            </div>

            <div class="btn-row">
                <a href="manage_users.php" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function showErr(el,id,msg){el.classList.add('is-invalid');el.classList.remove('is-valid');const s=document.getElementById(id);s.textContent=msg;s.classList.add('visible');}
function clearErr(el,id){el.classList.remove('is-invalid');el.classList.add('is-valid');const s=document.getElementById(id);s.textContent='';s.classList.remove('visible');}
const form=document.getElementById('editForm');
const nameEl=document.getElementById('name'),emailEl=document.getElementById('email'),phoneEl=document.getElementById('phone');
emailEl.addEventListener('blur',()=>{const rx=/^[^\s@]+@[^\s@]+\.[^\s@]+$/;rx.test(emailEl.value)?clearErr(emailEl,'emailErr'):showErr(emailEl,'emailErr','Enter a valid email address.');});
phoneEl.addEventListener('blur',()=>{const rx=/^[0-9+\-\s]{7,15}$/;(!phoneEl.value||rx.test(phoneEl.value))?clearErr(phoneEl,'phoneErr'):showErr(phoneEl,'phoneErr','Phone must be 7–15 digits.');});
form.addEventListener('submit',function(e){
    let ok=true;
    nameEl.value.trim().length<2?(showErr(nameEl,'nameErr','Name must be at least 2 characters.'),ok=false):clearErr(nameEl,'nameErr');
    const rx=/^[^\s@]+@[^\s@]+\.[^\s@]+$/;!rx.test(emailEl.value.trim())?(showErr(emailEl,'emailErr','Enter a valid email address.'),ok=false):clearErr(emailEl,'emailErr');
    const prx=/^[0-9+\-\s]{7,15}$/;phoneEl.value&&!prx.test(phoneEl.value)?(showErr(phoneEl,'phoneErr','Phone must be 7–15 digits.'),ok=false):null;
    if(!ok){e.preventDefault();form.querySelector('.is-invalid').focus();}
});
</script>
</body>
</html>
