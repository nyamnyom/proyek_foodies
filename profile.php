<?php
session_start();
include "config/koneksi.php";

// Redirect kalau belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ambil data user dari DB
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Data user tidak ditemukan");
}

/* DEFAULT ROLE */
if (!isset($user['role']) || empty($user['role'])) {
    $user['role'] = 'user';
}
$stmt->close();
/* CEK PREMIUM */
$isPremium = false;
$premium_expired = null;

$cekPremium = $conn->prepare("
    SELECT aktif_sampai
    FROM premium_users
    WHERE user_id = ?
    AND aktif_sampai >= CURDATE()
    LIMIT 1
");

$cekPremium->bind_param("i", $user_id);
$cekPremium->execute();

$premiumResult = $cekPremium->get_result();

if ($premiumResult->num_rows > 0) {
    $isPremium = true;

    $premiumData = $premiumResult->fetch_assoc();
    $premium_expired = $premiumData['aktif_sampai'];
}

$cekPremium->close();
// Hitung total menu (untuk info umum)
$total_menu = 0;
$res = $conn->query("SELECT COUNT(*) as total FROM menus");
if ($res) {
    $row = $res->fetch_assoc();
    $total_menu = $row['total'];
}

// Ambil sample menu favorit per kategori (untuk display)
$kategori_list = ['dessert', 'utama', 'diet', 'tradisional', 'internasional', 'instant'];
$menu_per_kategori = [];
foreach ($kategori_list as $kat) {
    $s = $conn->prepare("SELECT * FROM menus WHERE kategori_menu = ? LIMIT 1");
    $s->bind_param("s", $kat);
    $s->execute();
    $r = $s->get_result();
    if ($r && $r->num_rows > 0) {
        $menu_per_kategori[$kat] = $r->fetch_assoc();
    }
    $s->close();
}

// Format tanggal join
$created_at = new DateTime($user['created_at']);
$join_date = $created_at->format('d M Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Profil – Foodies</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --cream: #F9F5EF;
    --dark: #1C1811;
    --warm: #A0522D;
    --accent: #C8863C;
    --muted: #7A6E62;
    --white: #FFFFFF;
    --card-bg: #FFFDF9;
    --border: rgba(160,82,45,0.1);
}

html { scroll-behavior: smooth; }

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--cream);
    color: var(--dark);
    overflow-x: hidden;
    min-height: 100vh;
}

/* ── NAVBAR (sama persis dengan index) ── */
.navbar {
    position: fixed;
    top: 24px;
    left: 50%;
    transform: translateX(-50%);
    width: 92%;
    max-width: 1200px;
    background: rgba(255,253,249,0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    padding: 14px 32px;
    border-radius: 100px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid rgba(160,82,45,0.12);
    box-shadow: 0 8px 40px rgba(28,24,17,0.08);
    z-index: 1000;
}
.nav-left { display: flex; gap: 32px; align-items: center; }
.nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
.nav-logo img { width: 26px; opacity: 0.85; }
.nav-logo span { font-family: 'Playfair Display', serif; font-size: 20px; color: var(--dark); letter-spacing: -0.3px; }
.nav-links { display: flex; gap: 28px; }
.nav-links a { text-decoration: none; color: var(--muted); font-size: 14px; font-weight: 400; letter-spacing: 0.3px; transition: color 0.2s; }
.nav-links a:hover, .nav-links a.active { color: var(--dark); }
.nav-right {
    width: 36px; height: 36px; border-radius: 50%;
    background: var(--accent);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; text-decoration: none;
    font-family: 'Playfair Display', serif;
    font-size: 15px; font-weight: 700; color: var(--white);
    text-transform: uppercase;
}

/* ── PAGE WRAPPER ── */
.page-wrapper {
    padding-top: 120px;
    padding-bottom: 80px;
    min-height: 100vh;
}

/* ── PROFILE HERO AREA ── */
.profile-hero {
    padding: 0 8%;
    margin-bottom: 56px;
}

.profile-hero-inner {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 28px;
    padding: 48px 52px;
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 40px;
    align-items: center;
    position: relative;
    overflow: hidden;
}

/* Decorative accent shape */
.profile-hero-inner::before {
    content: '';
    position: absolute;
    top: -60px;
    right: -60px;
    width: 240px;
    height: 240px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(200,134,60,0.08) 0%, transparent 70%);
    pointer-events: none;
}

.avatar-wrap {
    position: relative;
    width: 100px;
    height: 100px;
    flex-shrink: 0;
}

.avatar-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent) 0%, var(--warm) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Playfair Display', serif;
    font-size: 38px;
    font-weight: 700;
    color: var(--white);
    letter-spacing: -1px;
    box-shadow: 0 12px 40px rgba(200,134,60,0.3);
}

.avatar-badge {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: <?php echo $user['role'] === 'admin' ? '#2563EB' : '#22C55E'; ?>;
    border: 3px solid var(--card-bg);
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-badge svg { width: 10px; height: 10px; fill: white; }


.profile-role-tag {
    display: inline-block;
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: <?php echo $user['role'] === 'admin' ? '#2563EB' : 'var(--accent)'; ?>;
    background: <?php echo $user['role'] === 'admin' ? 'rgba(37,99,235,0.08)' : 'rgba(200,134,60,0.1)'; ?>;
    padding: 4px 12px;
    border-radius: 100px;
    margin-bottom: 12px;
}

.profile-name {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    font-weight: 700;
    color: var(--dark);
    line-height: 1.15;
    margin-bottom: 6px;
}

.profile-email {
    font-size: 14px;
    color: var(--muted);
    font-weight: 300;
    margin-bottom: 16px;
}

.profile-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.profile-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--muted);
    font-weight: 400;
}

.profile-meta-item svg {
    width: 14px;
    height: 14px;
    fill: var(--accent);
    opacity: 0.8;
    flex-shrink: 0;
}

.profile-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: flex-end;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 22px;
    border-radius: 100px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.25s;
    cursor: pointer;
    border: none;
    font-family: 'DM Sans', sans-serif;
    white-space: nowrap;
}

.btn-primary {
    background: var(--dark);
    color: var(--cream);
}

.btn-primary:hover {
    background: var(--warm);
    transform: translateY(-1px);
}

.btn-outline {
    background: transparent;
    color: var(--muted);
    border: 1px solid rgba(160,82,45,0.2);
}

.btn-outline:hover {
    border-color: var(--warm);
    color: var(--warm);
    transform: translateY(-1px);
}

.btn-danger {
    background: transparent;
    color: #DC2626;
    border: 1px solid rgba(220,38,38,0.2);
}

.btn-danger:hover {
    background: rgba(220,38,38,0.06);
    border-color: #DC2626;
    transform: translateY(-1px);
}

/* ── STATS STRIP ── */
.stats-strip {
    padding: 0 8%;
    margin-bottom: 56px;
}

.stats-inner {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 28px 32px;
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 40px rgba(28,24,17,0.08);
}

.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 22px;
}

.stat-icon.orange { background: rgba(200,134,60,0.12); }
.stat-icon.brown  { background: rgba(160,82,45,0.1); }
.stat-icon.dark   { background: rgba(28,24,17,0.07); }

.stat-number {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    font-weight: 700;
    color: var(--dark);
    line-height: 1;
    margin-bottom: 4px;
}
.stat-label {
    font-size: 13px;
    color: var(--muted);
    font-weight: 300;
}

/* ── SECTION ── */
.section { padding: 0 8%; margin-bottom: 56px; }

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 28px;
}

.section-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(22px, 2.5vw, 30px);
    color: var(--dark);
}

.section-link {
    font-size: 13px;
    font-weight: 500;
    color: var(--warm);
    text-decoration: none;
    letter-spacing: 0.5px;
    border-bottom: 1px solid rgba(160,82,45,0.3);
    padding-bottom: 2px;
    transition: border-color 0.2s;
}
.section-link:hover { border-color: var(--warm); }

/* ── INFO CARDS ── */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.info-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 28px 32px;
}

.info-card h3 {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--accent);
    margin-bottom: 22px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid rgba(160,82,45,0.06);
}

.info-row:last-child { border-bottom: none; padding-bottom: 0; }
.info-row:first-of-type { padding-top: 0; }

.info-label {
    font-size: 13px;
    color: var(--muted);
    font-weight: 300;
}

.info-value {
    font-size: 14px;
    color: var(--dark);
    font-weight: 500;
    text-align: right;
}

.info-value.role-tag {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 4px 12px;
    border-radius: 100px;
}

.info-value.role-admin {
    color: #2563EB;
    background: rgba(37,99,235,0.08);
}

.info-value.role-user {
    color: var(--accent);
    background: rgba(200,134,60,0.1);
}

/* ── MENU EXPLORE GRID ── */
.menu-mini-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.menu-mini-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    text-decoration: none;
    transition: transform 0.3s, box-shadow 0.3s, border-color 0.3s;
    cursor: pointer;
}

.menu-mini-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(28,24,17,0.09);
    border-color: rgba(200,134,60,0.25);
}

.menu-mini-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(200,134,60,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.menu-mini-name {
    font-size: 14px;
    font-weight: 500;
    color: var(--dark);
    margin-bottom: 2px;
    text-transform: capitalize;
}
.menu-mini-sub {
    font-size: 12px;
    color: var(--muted);
    font-weight: 300;
}

/* ── LOGOUT SECTION ── */
.logout-section {
    padding: 0 8%;
    margin-bottom: 56px;
}

.logout-card {
    background: var(--card-bg);
    border: 1px solid rgba(220,38,38,0.1);
    border-radius: 20px;
    padding: 28px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logout-info h3 {
    font-size: 16px;
    font-weight: 500;
    color: var(--dark);
    margin-bottom: 4px;
}

.logout-info p {
    font-size: 13px;
    color: var(--muted);
    font-weight: 300;
}

/* ── FOOTER ── */
footer {
    background: var(--dark);
    color: var(--cream);
    padding: 40px 8%;
}

.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.footer-bottom p {
    color: rgba(255,255,255,0.3);
    font-size: 13px;
}
.stats-inner {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
}

.stats-inner {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
}



/* ── RESPONSIVE ── */
@media (max-width: 900px) {
    .profile-hero-inner { grid-template-columns: 1fr; gap: 24px; }
    .profile-actions { flex-direction: row; align-items: flex-start; }
    .stats-inner { grid-template-columns: 1fr 1fr; }
    .info-grid { grid-template-columns: 1fr; }
    .menu-mini-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 600px) {
    .navbar { padding: 12px 20px; }
    .nav-links { display: none; }
    .stats-inner { grid-template-columns: 1fr; }
    .menu-mini-grid { grid-template-columns: 1fr; }
    .profile-hero-inner { padding: 28px 24px; }
    .logout-card { flex-direction: column; gap: 16px; align-items: flex-start; }
}
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="nav-left">
        <a href="index.php" class="nav-logo">
            <img src="https://cdn-icons-png.flaticon.com/512/3075/3075977.png" alt="Foodies">
            <span>Foodies</span>
        </a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="menu.php">Menu</a>
            <?php if($isPremium): ?>
                <a href="bookmark.php">Bookmark</a>
            <?php endif; ?>
        </div>
    </div>
    <a href="profile.php" class="nav-right" title="Profil">
        <?php echo strtoupper(substr($user['nama'], 0, 1)); ?>
    </a>
</nav>

<!-- PAGE CONTENT -->
<div class="page-wrapper">

    <!-- PROFILE HERO -->
    <div class="profile-hero">
        <div class="profile-hero-inner">
            <!-- Avatar -->
            <div class="avatar-wrap">
                <div class="avatar-circle">
                    <?php echo strtoupper(substr($user['nama'], 0, 1)); ?>
                </div>
                <div class="avatar-badge">
                    <?php if ($user['role'] === 'admin'): ?>
                    <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    <?php else: ?>
                    <svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info -->
            <div class="profile-info">
                <div class="profile-role-tag">
                    <?php echo ucfirst($user['role']); ?>
                </div>
                <h1 class="profile-name"><?php echo htmlspecialchars($user['nama']); ?></h1>
                <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                <div class="profile-meta">
                    <div class="profile-meta-item">
                        <svg viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Bergabung sejak <?php echo $join_date; ?>
                    </div>
                    <div class="profile-meta-item">
                        <svg viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 0"/></svg>
                        Member Foodies
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="profile-actions">
                <a href="menu.php" class="btn btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                    Jelajahi Menu
                </a>
                <?php if ($user['role'] === 'admin'): ?>
                <a href="admin_index.php" class="btn btn-outline">
                    ⚙ Dashboard Admin
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- STATS STRIP -->
    <div class="stats-strip">
        <div class="stats-inner">

            <div class="stat-card">
                <div class="stat-icon orange">👑</div>
                <div class="stat-text">

                    <?php if($isPremium): ?>

                        <div class="stat-number">Premium</div>
                        <div class="stat-label">
                            Aktif sampai <?= date('d M Y', strtotime($premium_expired)) ?>
                        </div>

                    <?php else: ?>

                        <div class="stat-number">Free</div>
                        <div class="stat-label">
                            Belum upgrade premium
                        </div>

                    <?php endif; ?>

                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">🍽</div>
                <div class="stat-text">
                    <div class="stat-number"><?php echo $total_menu; ?></div>
                    <div class="stat-label">Total Menu Tersedia</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon brown">🗂</div>
                <div class="stat-text">
                    <div class="stat-number"><?php echo count($kategori_list); ?></div>
                    <div class="stat-label">Kategori Masakan</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon dark">⭐</div>
                <div class="stat-text">
                    <div class="stat-number"><?php echo ucfirst($user['role']); ?></div>
                    <div class="stat-label">Status Akun</div>
                </div>
            </div>

        </div>
    </div>

    <!-- INFO DETAIL -->
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">Informasi <em style="font-style:italic; color:var(--warm);">Akun</em></h2>
        </div>
        <div class="info-grid">

            <!-- Data Pribadi -->
            <div class="info-card">
                <h3>Data Pribadi</h3>

                <div class="info-row">
                    <span class="info-label">Nama Lengkap</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['nama']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Role</span>
                    <span class="info-value role-tag <?php echo $user['role'] === 'admin' ? 'role-admin' : 'role-user'; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal Bergabung</span>
                    <span class="info-value"><?php echo $join_date; ?></span>
                </div>
            </div>

            <!-- Keamanan Akun -->
            <div class="info-card">
                <h3>Keamanan</h3>

                <div class="info-row">
                    <span class="info-label">Password</span>
                    <span class="info-value">••••••••</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status Akun</span>
                    <span class="info-value" style="color: #22C55E;">● Aktif</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sesi Login</span>
                    <span class="info-value" style="color: var(--accent);">Aktif Sekarang</span>
                </div>
                <div class="info-row">
                    <span class="info-label">User ID</span>
                    <span class="info-value" style="color: var(--muted); font-size: 12px;">#<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></span>
                </div>
            </div>

        </div>
    </div>

    <!-- MENU CATEGORIES -->
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">Eksplorasi <em style="font-style:italic; color:var(--warm);">Kategori</em></h2>
            <a href="menu.php" class="section-link">Semua menu →</a>
        </div>

        <div class="menu-mini-grid">
            <?php
            $icons = [
                'dessert'       => '🍮',
                'utama'         => '🍛',
                'diet'          => '🥗',
                'tradisional'   => '🫕',
                'internasional' => '🍝',
                'instant'       => '🍜',
            ];
            $counts = [];
            foreach ($kategori_list as $kat) {
                $res2 = $conn->prepare("SELECT COUNT(*) as c FROM menus WHERE kategori_menu = ?");
                $res2->bind_param("s", $kat);
                $res2->execute();
                $row2 = $res2->get_result()->fetch_assoc();
                $counts[$kat] = $row2['c'];
                $res2->close();
            }
            foreach ($kategori_list as $kat):
            ?>
            <a href="menu.php?kategori=<?php echo urlencode($kat); ?>" class="menu-mini-card">
                <div class="menu-mini-icon"><?php echo $icons[$kat] ?? '🍽'; ?></div>
                <div class="menu-mini-info">
                    <div class="menu-mini-name"><?php echo ucfirst($kat); ?></div>
                    <div class="menu-mini-sub"><?php echo $counts[$kat]; ?> menu tersedia</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- LOGOUT SECTION -->
    <div class="logout-section">
        <div class="logout-card">
            <div class="logout-info">
                <h3>Keluar dari Akun</h3>
                <p>Sesi kamu akan diakhiri dan kamu akan diarahkan ke halaman login.</p>
            </div>
            <a href="logout.php" class="btn btn-danger">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
                </svg>
                Logout
            </a>
        </div>
    </div>

    <!-- PREMIUM SECTION -->
<div class="section">

    <div class="section-header">
        <h2 class="section-title">
            Foodies <em style="font-style:italic; color:var(--warm);">Premium</em>
        </h2>
    </div>

    <div class="info-card">

        <?php if($isPremium): ?>

            <h3>Status Premium</h3>

            <div class="info-row">
                <span class="info-label">Membership</span>
                <span class="info-value" style="color:gold;">
                    👑 Premium Active
                </span>
            </div>

            <div class="info-row">
                <span class="info-label">Aktif Sampai</span>
                <span class="info-value">
                    <?= date('d M Y', strtotime($premium_expired)) ?>
                </span>
            </div>

        <?php else: ?>

            <h3>Upgrade Premium</h3>

            <p style="
                color:var(--muted);
                margin-bottom:24px;
                line-height:1.7;
                font-size:14px;
            ">
                Dapatkan akses resep eksklusif premium,
                fitur spesial, dan pengalaman tanpa batas.
            </p>

            <a href="premium.php" class="btn btn-primary">
                👑 Beli Premium
            </a>

        <?php endif; ?>

    </div>

</div>

</div><!-- end page-wrapper -->

<!-- FOOTER -->
<footer>
    <div class="footer-bottom">
        <p>© 2025 Foodies. All rights reserved.</p>
        <p>Made with ♥ for food lovers</p>
    </div>
</footer>

</body>
</html>