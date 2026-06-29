<?php
session_start();
require_once 'db.php';
requireLogin();

if (empty($_SESSION['__regen'])) {
    session_regenerate_id(true);
    $_SESSION['__regen'] = true;
}
$welcome = isset($_GET['welcome']) && $_GET['welcome'] === '1';
$unauthorized = isset($_GET['error']) && $_GET['error'] === 'unauthorized';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — ApexPlanet</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── Weather Widget ── */
        .weather-card {
            background: linear-gradient(135deg, #1565c0, #1e88e5);
            color: #fff;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .weather-card h3 { font-size: 1rem; opacity: 0.85; margin-bottom: 0.8rem; letter-spacing: 0.3px; }
        .weather-main  { display: flex; align-items: center; gap: 1.2rem; flex-wrap: wrap; }
        .weather-icon  { font-size: 3rem; line-height: 1; }
        .weather-temp  { font-size: 2.8rem; font-weight: 700; line-height: 1; }
        .weather-temp span { font-size: 1.2rem; font-weight: 400; }
        .weather-desc  { font-size: 1rem; opacity: 0.9; text-transform: capitalize; margin-top: 0.2rem; }
        .weather-details {
            display: flex; gap: 1.5rem; margin-top: 1rem;
            flex-wrap: wrap; font-size: 0.85rem; opacity: 0.85;
        }
        .weather-details span { display: flex; align-items: center; gap: 0.3rem; }
        .weather-city  { font-size: 1rem; font-weight: 600; margin-bottom: 0.3rem; }
        .weather-error { opacity: 0.8; font-size: 0.9rem; }
        .weather-loading { opacity: 0.8; }

        /* ── Quick links ── */
        .quick-links { display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .quick-link {
            background: #fff; border-radius: var(--radius-lg); padding: 1.2rem;
            text-decoration: none; color: var(--clr-text);
            box-shadow: var(--shadow-sm); transition: box-shadow 0.2s, transform 0.2s;
            display: flex; align-items: center; gap: 0.8rem;
        }
        .quick-link:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); text-decoration: none; }
        .quick-link .ql-icon { font-size: 1.8rem; }
        .quick-link .ql-label { font-size: 0.9rem; font-weight: 600; color: var(--clr-primary); }
        .quick-link .ql-sub   { font-size: 0.78rem; color: var(--clr-subtext); margin-top: 2px; }
        .admin-only { border: 2px solid #ffd700; }
    </style>
</head>
<body>

<header class="site-header">
    <h1>Apex<span>Planet</span></h1>
    <nav class="nav-right" aria-label="Main navigation">
        <span class="user-pill">
            <?= $_SESSION['user_role'] === 'admin' ? '👑' : '👤' ?>
            <?= htmlspecialchars($_SESSION['user_name']) ?>
        </span>
        <?php if (isAdmin()): ?>
            <a href="manage_users.php" class="btn btn-outline">Manage Users</a>
        <?php endif; ?>
        <a href="profile.php" class="btn btn-outline">My Profile</a>
        <a href="logout.php"  class="btn btn-accent">Logout</a>
    </nav>
</header>

<div class="container">

    <?php if ($unauthorized): ?>
    <div class="alert alert-error" id="flashUnauth">
        <span>🚫 Access denied. Admin privileges required.</span>
        <button class="alert-close" onclick="this.parentElement.remove()">✕</button>
    </div>
    <?php endif; ?>

    <?php if ($welcome): ?>
    <div class="alert alert-success" id="welcomeAlert" role="status">
        <span>✅ Welcome back, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>!
        <?= $_SESSION['user_role'] === 'admin' ? ' You are logged in as <strong>Admin</strong>.' : '' ?>
        </span>
        <button class="alert-close" onclick="this.parentElement.remove()">✕</button>
    </div>
    <?php endif; ?>

    <!-- Welcome hero -->
    <div class="welcome-card" style="margin-bottom:1.5rem;">
        <div class="welcome-avatar">
            <?php if (!empty($_SESSION['user_avatar']) && file_exists('uploads/' . $_SESSION['user_avatar'])): ?>
                <img src="uploads/<?= htmlspecialchars($_SESSION['user_avatar']) ?>"
                     style="width:68px;height:68px;border-radius:50%;object-fit:cover;" alt="Avatar">
            <?php else: ?>
                <?= $_SESSION['user_role'] === 'admin' ? '👑' : '👤' ?>
            <?php endif; ?>
        </div>
        <div class="welcome-text">
            <h2>Hello, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h2>
            <p>
                Role: <strong><?= ucfirst($_SESSION['user_role']) ?></strong> &nbsp;|&nbsp;
                <?= htmlspecialchars($_SESSION['user_email']) ?>
            </p>
        </div>
    </div>

    <!-- Weather API Widget (Task 5: API Integration) -->
    <div class="weather-card" id="weatherWidget">
        <h3>🌤️ Live Weather — Hyderabad, IN</h3>
        <div id="weatherContent" class="weather-loading">Loading weather data…</div>
    </div>

    <!-- Quick Links -->
    <div class="quick-links">
        <a href="profile.php" class="quick-link">
            <span class="ql-icon">👤</span>
            <div><div class="ql-label">My Profile</div><div class="ql-sub">View & edit your details</div></div>
        </a>
        <a href="change_password.php" class="quick-link">
            <span class="ql-icon">🔒</span>
            <div><div class="ql-label">Change Password</div><div class="ql-sub">Update your password</div></div>
        </a>
        <?php if (isAdmin()): ?>
        <a href="manage_users.php" class="quick-link admin-only">
            <span class="ql-icon">👥</span>
            <div><div class="ql-label">Manage Users</div><div class="ql-sub">Admin — CRUD operations</div></div>
        </a>
        <?php endif; ?>
    </div>

    <!-- Info Cards -->
    <div class="info-grid">
        <div class="info-card">
            <div class="ic-icon">✉️</div>
            <div class="ic-label">Email Address</div>
            <div class="ic-value"><?= htmlspecialchars($_SESSION['user_email']) ?></div>
        </div>
        <div class="info-card">
            <div class="ic-icon">🆔</div>
            <div class="ic-label">User ID</div>
            <div class="ic-value">#<?= htmlspecialchars($_SESSION['user_id']) ?></div>
        </div>
        <div class="info-card">
            <div class="ic-icon"><?= $_SESSION['user_role']==='admin'?'👑':'🎭' ?></div>
            <div class="ic-label">Role</div>
            <div class="ic-value" style="color:<?= $_SESSION['user_role']==='admin'?'#b8860b':'var(--clr-primary)' ?>">
                <?= ucfirst(htmlspecialchars($_SESSION['user_role'])) ?>
            </div>
        </div>
        <div class="info-card">
            <div class="ic-icon">🔐</div>
            <div class="ic-label">Session Status</div>
            <div class="ic-value" style="color:var(--clr-success)">Active ✅</div>
        </div>
    </div>

</div>

<script>
// Auto-fade alerts
document.querySelectorAll('.alert').forEach(function(el){
    setTimeout(function(){el.classList.add('fade-out');setTimeout(function(){el.remove();},450);},4000);
});

// ── Weather API Integration (Task 5) ─────────────────────────────────────────
// Using Open-Meteo (free, no API key required)
// WMO weather code → emoji + description mapping
const WMO = {
    0:'☀️ Clear sky', 1:'🌤️ Mainly clear', 2:'⛅ Partly cloudy', 3:'☁️ Overcast',
    45:'🌫️ Foggy', 48:'🌫️ Icy fog',
    51:'🌦️ Light drizzle', 53:'🌧️ Drizzle', 55:'🌧️ Heavy drizzle',
    61:'🌧️ Slight rain', 63:'🌧️ Moderate rain', 65:'🌧️ Heavy rain',
    71:'🌨️ Slight snow', 73:'🌨️ Moderate snow', 75:'❄️ Heavy snow',
    80:'🌦️ Rain showers', 81:'🌧️ Showers', 82:'⛈️ Violent showers',
    95:'⛈️ Thunderstorm', 96:'⛈️ Thunderstorm + hail', 99:'⛈️ Heavy thunderstorm',
};

async function loadWeather() {
    const el = document.getElementById('weatherContent');
    try {
        // Hyderabad coordinates
        const lat = 17.3850, lon = 78.4867;
        const url = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current=temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code&wind_speed_unit=kmh&timezone=Asia%2FKolkata`;
        const res  = await fetch(url);
        if (!res.ok) throw new Error('API error');
        const data = await res.json();
        const c    = data.current;
        const desc = WMO[c.weather_code] || '🌡️ Unknown';
        const [icon, ...words] = desc.split(' ');
        const descText = words.join(' ');

        el.innerHTML = `
            <div class="weather-main">
                <div class="weather-icon">${icon}</div>
                <div>
                    <div class="weather-city">Hyderabad, Telangana 🇮🇳</div>
                    <div class="weather-temp">${Math.round(c.temperature_2m)}<span>°C</span></div>
                    <div class="weather-desc">${descText}</div>
                </div>
            </div>
            <div class="weather-details">
                <span>💧 Humidity: ${c.relative_humidity_2m}%</span>
                <span>💨 Wind: ${c.wind_speed_10m} km/h</span>
                <span>🕒 Updated: ${new Date().toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit'})}</span>
            </div>`;
    } catch(e) {
        el.innerHTML = '<p class="weather-error">⚠️ Could not load weather data. Please check your internet connection.</p>';
    }
}
loadWeather();
</script>
</body>
</html>
