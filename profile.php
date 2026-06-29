<?php
session_start();
require_once 'db.php';
requireLogin();

$errors  = [];
$success = '';
$userId  = (int)$_SESSION['user_id'];

// ── Handle profile picture upload ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'avatar') {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['avatar'];
        $allowed  = ['image/jpeg','image/png','image/gif','image/webp'];
        $maxSize  = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowed)) {
            $errors['avatar'] = "Only JPG, PNG, GIF and WebP images are allowed.";
        } elseif ($file['size'] > $maxSize) {
            $errors['avatar'] = "Image must be under 2MB.";
        } else {
            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
            $dest     = 'uploads/' . $filename;

            if (!is_dir('uploads')) mkdir('uploads', 0755, true);

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                try {
                    // Delete old avatar file if exists
                    if ($_SESSION['user_avatar'] && file_exists('uploads/' . $_SESSION['user_avatar'])) {
                        @unlink('uploads/' . $_SESSION['user_avatar']);
                    }
                    $upd = $pdo->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
                    $upd->execute([':avatar' => $filename, ':id' => $userId]);
                    $_SESSION['user_avatar'] = $filename;
                    $success = "Profile picture updated successfully!";
                } catch (PDOException $e) {
                    error_log("Avatar Error: " . $e->getMessage());
                    $errors['avatar'] = "Failed to save avatar. Please try again.";
                }
            } else {
                $errors['avatar'] = "Failed to upload image. Please try again.";
            }
        }
    } else {
        $errors['avatar'] = "Please select an image file.";
    }
}

// ── Handle profile details update ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'profile') {
    $name    = trim(htmlspecialchars($_POST['name']    ?? '', ENT_QUOTES));
    $phone   = trim(htmlspecialchars($_POST['phone']   ?? '', ENT_QUOTES));
    $address = trim(htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES));

    if (empty($name) || strlen($name) < 2)
        $errors['name'] = "Name must be at least 2 characters.";
    if (!empty($phone) && !preg_match('/^[0-9+\-\s]{7,15}$/', $phone))
        $errors['phone'] = "Phone must be 7–15 digits.";

    if (empty($errors)) {
        try {
            $upd = $pdo->prepare("UPDATE users SET name=:name, phone=:phone, address=:address WHERE id=:id");
            $upd->execute([':name'=>$name,':phone'=>$phone?:null,':address'=>$address?:null,':id'=>$userId]);
            $_SESSION['user_name'] = $name;
            $success = "Profile updated successfully!";
        } catch (PDOException $e) {
            error_log("Profile Update Error: " . $e->getMessage());
            $errors['db'] = "Update failed. Please try again.";
        }
    }
}

// ── Fetch current user data ───────────────────────────────────────────────────
try {
    $stmt = $pdo->prepare("SELECT id,name,email,phone,address,role,avatar,created_at FROM users WHERE id=:id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $user = null;
}
if (!$user) { header("Location: login.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — ApexPlanet</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-hero {
            background: linear-gradient(135deg, var(--clr-primary), var(--clr-accent));
            border-radius: var(--radius-lg);
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 1.5rem;
            color: #fff;
        }
        .avatar-wrap { position: relative; flex-shrink: 0; }
        .avatar-img {
            width: 100px; height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255,255,255,0.4);
            background: rgba(255,255,255,0.2);
            display: flex; align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            overflow: hidden;
        }
        .avatar-img img { width:100%; height:100%; object-fit:cover; border-radius:50%; }
        .profile-hero h2  { font-size: 1.6rem; margin-bottom: 0.3rem; }
        .profile-hero p   { opacity: 0.85; font-size: 0.9rem; margin-bottom: 0.4rem; }
        .role-badge {
            display: inline-block;
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .role-admin { background: #ffd700; color: #333; }
        .role-user  { background: rgba(255,255,255,0.25); color: #fff; }
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        .upload-label {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.2);
            border-radius: 6px;
            font-size: 0.85rem;
            border: 1px solid rgba(255,255,255,0.4);
            margin-top: 0.6rem;
            transition: background 0.2s;
        }
        .upload-label:hover { background: rgba(255,255,255,0.35); }
        .upload-label input { display: none; }
        .info-row { display: flex; justify-content: space-between; padding: 0.7rem 0; border-bottom: 1px solid var(--clr-border); font-size: 0.92rem; }
        .info-row:last-child { border-bottom: none; }
        .info-row .info-label { color: var(--clr-subtext); }
        .info-row .info-value { font-weight: 600; color: var(--clr-text); }
        @media (max-width: 650px) {
            .profile-grid { grid-template-columns: 1fr; }
            .profile-hero { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

<header class="site-header">
    <h1>Apex<span>Planet</span></h1>
    <nav class="nav-right">
        <span class="user-pill">👤 <?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <?php if (isAdmin()): ?>
            <a href="manage_users.php" class="btn btn-outline">Manage Users</a>
        <?php endif; ?>
        <a href="dashboard.php" class="btn btn-outline">Dashboard</a>
        <a href="logout.php"    class="btn btn-accent">Logout</a>
    </nav>
</header>

<div class="container">

    <?php if ($success): ?>
    <div class="alert alert-success" id="flashOk">
        <span>✅ <?= htmlspecialchars($success) ?></span>
        <button class="alert-close" onclick="this.parentElement.remove()">✕</button>
    </div>
    <?php endif; ?>
    <?php if (isset($errors['db'])): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($errors['db']) ?> <button class="alert-close" onclick="this.parentElement.remove()">✕</button></div>
    <?php endif; ?>

    <!-- Profile Hero -->
    <div class="profile-hero">
        <div class="avatar-wrap">
            <div class="avatar-img">
                <?php if ($user['avatar'] && file_exists('uploads/' . $user['avatar'])): ?>
                    <img src="uploads/<?= htmlspecialchars($user['avatar']) ?>" alt="Profile picture">
                <?php else: ?>
                    👤
                <?php endif; ?>
            </div>
        </div>
        <div>
            <h2><?= htmlspecialchars($user['name']) ?></h2>
            <p><?= htmlspecialchars($user['email']) ?></p>
            <span class="role-badge <?= $user['role'] === 'admin' ? 'role-admin' : 'role-user' ?>">
                <?= $user['role'] === 'admin' ? '👑 Admin' : '👤 User' ?>
            </span>

            <!-- Avatar upload form -->
            <form method="POST" action="profile.php" enctype="multipart/form-data" id="avatarForm">
                <input type="hidden" name="action" value="avatar">
                <label class="upload-label">
                    📷 Change Photo
                    <input type="file" name="avatar" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
                </label>
                <?php if (isset($errors['avatar'])): ?>
                    <p style="color:#ffd0d0;font-size:0.8rem;margin-top:4px;">⚠ <?= htmlspecialchars($errors['avatar']) ?></p>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="profile-grid">

        <!-- Edit Profile Form -->
        <div class="card">
            <h3 style="color:var(--clr-primary);margin-bottom:1.2rem;">✏️ Edit Profile</h3>
            <form method="POST" action="profile.php" id="profileForm" novalidate>
                <input type="hidden" name="action" value="profile">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name"
                           value="<?= htmlspecialchars($user['name']) ?>"
                           placeholder="👤  Your full name"
                           class="<?= isset($errors['name']) ? 'is-invalid':'' ?>"
                           required minlength="2" maxlength="100">
                    <span class="field-error <?= isset($errors['name'])?'visible':'' ?>" id="nameErr"><?= $errors['name']??'' ?></span>
                </div>
                <div class="form-group">
                    <label for="email">Email Address <span class="opt">(cannot change)</span></label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background:#f5f5f5;cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number <span class="opt">(optional)</span></label>
                    <input type="text" id="phone" name="phone"
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                           placeholder="📱  +91 98765 43210"
                           class="<?= isset($errors['phone'])?'is-invalid':'' ?>"
                           maxlength="15">
                    <span class="field-error <?= isset($errors['phone'])?'visible':'' ?>" id="phoneErr"><?= $errors['phone']??'' ?></span>
                </div>
                <div class="form-group">
                    <label for="address">Address <span class="opt">(optional)</span></label>
                    <input type="text" id="address" name="address"
                           value="<?= htmlspecialchars($user['address'] ?? '') ?>"
                           placeholder="📍  City, State" maxlength="255">
                </div>
                <button type="submit" class="btn btn-primary btn-full">Save Changes</button>
            </form>
        </div>

        <!-- Account Info -->
        <div class="card">
            <h3 style="color:var(--clr-primary);margin-bottom:1.2rem;">ℹ️ Account Info</h3>
            <div class="info-row">
                <span class="info-label">User ID</span>
                <span class="info-value">#<?= htmlspecialchars($user['id']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Role</span>
                <span class="info-value">
                    <span class="role-badge <?= $user['role']==='admin'?'role-admin':'role-user' ?>" style="color:<?= $user['role']==='admin'?'#333':'var(--clr-primary)' ?>">
                        <?= $user['role'] === 'admin' ? '👑 Admin' : '👤 User' ?>
                    </span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Email</span>
                <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Phone</span>
                <span class="info-value"><?= $user['phone'] ? htmlspecialchars($user['phone']) : '<span style="color:#bbb">Not set</span>' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Address</span>
                <span class="info-value"><?= $user['address'] ? htmlspecialchars($user['address']) : '<span style="color:#bbb">Not set</span>' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Member Since</span>
                <span class="info-value"><?= date('d M Y', strtotime($user['created_at'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Profile Picture</span>
                <span class="info-value"><?= $user['avatar'] ? '✅ Uploaded' : '<span style="color:#bbb">Not set</span>' ?></span>
            </div>
            <a href="change_password.php" class="btn btn-ghost btn-full" style="margin-top:1rem;">🔒 Change Password</a>
        </div>

    </div>
</div>

<script>
document.querySelectorAll('.alert').forEach(function(el){
    setTimeout(function(){el.classList.add('fade-out');setTimeout(function(){el.remove();},450);},4000);
});
function showError(el,id,msg){el.classList.add('is-invalid');const s=document.getElementById(id);s.textContent=msg;s.classList.add('visible');}
function clearError(el,id){el.classList.remove('is-invalid');const s=document.getElementById(id);s.textContent='';s.classList.remove('visible');}
const profileForm=document.getElementById('profileForm');
if(profileForm){
    profileForm.addEventListener('submit',function(e){
        let ok=true;
        const nameEl=document.getElementById('name');
        const phoneEl=document.getElementById('phone');
        if(nameEl.value.trim().length<2){showError(nameEl,'nameErr','Name must be at least 2 characters.');ok=false;}else clearError(nameEl,'nameErr');
        const prx=/^[0-9+\-\s]{7,15}$/;
        if(phoneEl.value&&!prx.test(phoneEl.value)){showError(phoneEl,'phoneErr','Phone must be 7–15 digits.');ok=false;}
        if(!ok)e.preventDefault();
    });
}
</script>
</body>
</html>
