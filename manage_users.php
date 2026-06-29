<?php
session_start();
require_once 'db.php';
requireLogin();
requireAdmin(); // Task 5: only admins can manage users

$deleted     = isset($_GET['deleted']) && $_GET['deleted'] === '1';
$deletedName = isset($_GET['name'])    ? trim($_GET['name'])  : '';
$updated     = isset($_GET['updated']) && $_GET['updated']    === '1';
$deleteError = isset($_GET['error'])   && $_GET['error']      === 'delete_failed';
$search      = isset($_GET['search'])  ? trim($_GET['search']) : '';
$perPage     = 5;
$currentPage = isset($_GET['page'])    ? max(1,(int)$_GET['page']) : 1;
$offset      = ($currentPage - 1) * $perPage;

$users = []; $totalUsers = 0; $dbError = ''; $totalPages = 1;

try {
    $like = "%{$search}%";
    $where = $search !== '' ? "WHERE name LIKE :like1 OR email LIKE :like2" : "";

    $cStmt = $search !== ''
        ? $pdo->prepare("SELECT COUNT(*) FROM users $where")
        : $pdo->query("SELECT COUNT(*) FROM users");
    if ($search !== '') $cStmt->execute([':like1'=>$like,':like2'=>$like]);
    $totalUsers = (int)$cStmt->fetchColumn();
    $totalPages = max(1,(int)ceil($totalUsers/$perPage));
    if ($currentPage > $totalPages) { $currentPage=$totalPages; $offset=($currentPage-1)*$perPage; }

    $stmt = $pdo->prepare("SELECT id,name,email,phone,address,role,created_at FROM users $where ORDER BY id DESC LIMIT :lim OFFSET :off");
    if ($search !== '') { $stmt->bindValue(':like1',$like,PDO::PARAM_STR); $stmt->bindValue(':like2',$like,PDO::PARAM_STR); }
    $stmt->bindValue(':lim',$perPage,PDO::PARAM_INT);
    $stmt->bindValue(':off',$offset,PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Manage Users Error: ".$e->getMessage());
    $dbError = "Could not load users due to a server error. Please try again.";
}

$currentUserId = (int)$_SESSION['user_id'];
function pageUrl($p,$s){$q=['page'=>$p];if($s!=='')$q['search']=$s;return 'manage_users.php?'.http_build_query($q);}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users — ApexPlanet</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">
    <h1>Apex<span>Planet</span></h1>
    <nav class="nav-right" aria-label="Main navigation">
        <span class="user-pill">👤 <?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="dashboard.php" class="btn btn-outline">Dashboard</a>
        <a href="logout.php"    class="btn btn-accent">Logout</a>
    </nav>
</header>

<div class="container">

    <?php if ($dbError): ?>
    <div class="alert alert-error" id="flashDbErr" role="alert">
        <span>⚠️ <?= htmlspecialchars($dbError) ?></span>
        <button class="alert-close" onclick="this.parentElement.remove()">✕</button>
    </div>
    <?php endif; ?>
    <?php if ($deleteError): ?>
    <div class="alert alert-error" id="flashDelErr" role="alert">
        <span>⚠️ Could not delete user. Please try again.</span>
        <button class="alert-close" onclick="this.parentElement.remove()">✕</button>
    </div>
    <?php endif; ?>
    <?php if ($deleted): ?>
    <div class="alert alert-success" id="flashDeleted" role="status">
        <span>✅ User<?= $deletedName ? ' "'.htmlspecialchars($deletedName).'"' : '' ?> deleted successfully.</span>
        <button class="alert-close" onclick="this.parentElement.remove()">✕</button>
    </div>
    <?php endif; ?>
    <?php if ($updated): ?>
    <div class="alert alert-success" id="flashUpdated" role="status">
        <span>✅ User updated successfully.</span>
        <button class="alert-close" onclick="this.parentElement.remove()">✕</button>
    </div>
    <?php endif; ?>

    <div class="page-head">
        <div>
            <h2 class="section-title">Manage Users</h2>
            <p class="section-sub">View, search, edit or delete registered users</p>
        </div>
    </div>

    <div class="toolbar">
        <form class="search-form" method="GET" action="manage_users.php" role="search" aria-label="Search users">
            <label for="search" class="sr-only">Search users</label>
            <input type="search" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or email…" aria-label="Search">
            <button type="submit" class="btn btn-primary">🔍 Search</button>
        </form>
        <div style="display:flex;align-items:center;gap:0.8rem;">
            <?php if ($search !== ''): ?>
                <a href="manage_users.php" class="search-clear" aria-label="Clear search">✕ Clear</a>
            <?php endif; ?>
            <span class="badge badge-primary"><?= $totalUsers ?> user<?= $totalUsers !== 1 ? 's' : '' ?><?= $search !== '' ? ' found' : '' ?></span>
        </div>
    </div>

    <div class="table-wrap" role="region" aria-label="Users table">
        <table aria-label="Registered users">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Address</th>
                    <th scope="col">Role</th>
                    <th scope="col">Joined</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr><td colspan="7">
                    <div class="empty-state">
                        <div class="es-icon">🔍</div>
                        <?php if ($search !== ''): ?>
                            <div class="es-title">No records found for "<?= htmlspecialchars($search) ?>"</div>
                            <p><a href="manage_users.php">Clear search and view all users</a></p>
                        <?php else: ?>
                            <div class="es-title">No users registered yet.</div>
                        <?php endif; ?>
                    </div>
                </td></tr>
                <?php else: foreach ($users as $u):
                    $isYou = ((int)$u['id'] === $currentUserId); ?>
                <tr class="<?= $isYou ? 'is-you' : '' ?>">
                    <td><span class="badge badge-light">#<?= htmlspecialchars($u['id']) ?></span></td>
                    <td>
                        <?= htmlspecialchars($u['name']) ?>
                        <?php if ($isYou): ?><span class="badge badge-you" style="font-size:0.7rem;margin-left:6px;">YOU</span><?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= $u['phone']   ? htmlspecialchars($u['phone'])   : '<span class="td-muted">—</span>' ?></td>
                    <td><?= $u['address'] ? htmlspecialchars($u['address']) : '<span class="td-muted">—</span>' ?></td>
                    <td>
                            <?php if($u['role']==='admin'): ?>
                                <span class="badge badge-you" style="color:#333;">👑 Admin</span>
                            <?php else: ?>
                                <span class="badge badge-light">👤 User</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime($u['created_at']))) ?></td>
                    <td>
                        <div class="actions">
                            <a href="edit_user.php?id=<?= (int)$u['id'] ?>" class="btn btn-info" aria-label="Edit <?= htmlspecialchars($u['name']) ?>">✏️ Edit</a>
                            <a href="delete_user.php?id=<?= (int)$u['id'] ?>" class="btn btn-danger"
                               aria-label="Delete <?= htmlspecialchars($u['name']) ?>"
                               onclick="return confirm('Delete user &quot;<?= htmlspecialchars(addslashes($u['name'])) ?>&quot;?<?= $isYou ? ' This is YOUR account — you will be logged out.' : '' ?> This cannot be undone.');">
                               🗑️ Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="Pagination">
        <?php if ($currentPage > 1): ?>
            <a href="<?= pageUrl($currentPage-1,$search) ?>" aria-label="Previous page">‹ Prev</a>
        <?php else: ?>
            <span class="pg-disabled" aria-disabled="true">‹ Prev</span>
        <?php endif; ?>
        <?php for ($p=1; $p<=$totalPages; $p++): ?>
            <?php if ($p===$currentPage): ?>
                <span class="pg-active" aria-current="page"><?= $p ?></span>
            <?php else: ?>
                <a href="<?= pageUrl($p,$search) ?>" aria-label="Page <?= $p ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($currentPage < $totalPages): ?>
            <a href="<?= pageUrl($currentPage+1,$search) ?>" aria-label="Next page">Next ›</a>
        <?php else: ?>
            <span class="pg-disabled" aria-disabled="true">Next ›</span>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.alert').forEach(function(el){
    setTimeout(function(){el.classList.add('fade-out');setTimeout(function(){el.remove();},450);},4000);
});
</script>
</body>
</html>
