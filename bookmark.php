<?php
session_start();
include "config/koneksi.php";

/* Harus login */
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);

/* CEK PREMIUM — bookmark hanya untuk member premium */
$isPremium = false;

$cek = mysqli_query($conn,"
    SELECT * FROM premium_users
    WHERE user_id='$user_id'
    AND aktif_sampai >= CURDATE()
");

if(mysqli_num_rows($cek) > 0){
    $isPremium = true;
}

/* Kalau bukan premium, tendang ke halaman premium */
if(!$isPremium){
    header("Location: premium.php?need_premium=1");
    exit;
}

$query = mysqli_query($conn,"
    SELECT menus.*
    FROM bookmarks
    JOIN menus ON bookmarks.menu_id = menus.id
    WHERE bookmarks.user_id='$user_id'
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Foodies</title>
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
}

html { scroll-behavior: smooth; }

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--cream);
    color: var(--dark);
    overflow-x: hidden;
}

/* ── NAVBAR ── */
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

.nav-left {
    display: flex;
    gap: 32px;
    align-items: center;
}

.nav-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}

.nav-logo img {
    width: 26px;
    opacity: 0.85;
}

.nav-logo span {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    color: var(--dark);
    letter-spacing: -0.3px;
}

.nav-links {
    display: flex;
    gap: 28px;
}

.nav-links a {
    text-decoration: none;
    color: var(--muted);
    font-size: 14px;
    font-weight: 400;
    letter-spacing: 0.3px;
    transition: color 0.2s;
}

.nav-links a:hover { color: var(--dark); }

.nav-right {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--dark);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.nav-right svg {
    width: 16px;
    height: 16px;
    fill: var(--cream);
}

/* ── HERO ── */
.hero {
    position: relative;
    height: 100vh;
    min-height: 640px;
    overflow: hidden;
}

.hero-img {
    position: absolute;
    inset: 0;
    background: url('https://t4.ftcdn.net/jpg/04/81/33/57/360_F_481335799_khKwqBN9tvn6piqm5NxQBOYj4XYWBPUG.jpg') center/cover no-repeat;
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        120deg,
        rgba(249,245,239,0.97) 0%,
        rgba(249,245,239,0.88) 40%,
        rgba(249,245,239,0.25) 70%,
        rgba(249,245,239,0) 100%
    );
}

.hero-content {
    position: relative;
    z-index: 2;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 0 8%;
    padding-top: 100px;
}

.hero-label {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--accent);
    margin-bottom: 20px;
}

.hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(42px, 5.5vw, 80px);
    line-height: 1.1;
    max-width: 560px;
    color: var(--dark);
    margin-bottom: 24px;
}

.hero h1 em {
    font-style: italic;
    color: var(--warm);
}

.hero-sub {
    font-size: 16px;
    color: var(--muted);
    font-weight: 300;
    max-width: 360px;
    line-height: 1.7;
    margin-bottom: 36px;
}

.hero-cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;

    width: fit-content;      /* biar ga panjang */
    padding: 16px 30px;

    background: var(--dark);
    color: var(--cream);

    text-decoration: none;
    border-radius: 100px;

    font-size: 17px;         /* diperbesar */
    font-weight: 600;

    transition: background 0.25s, transform 0.2s;
}

.hero-cta:hover {
    background: var(--warm);
    transform: translateY(-2px);
}

.hero-cta-arrow {
    width: 18px;
    height: 18px;
    border: 1.5px solid rgba(255,255,255,0.5);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

/* ── SECTION HEADERS ── */
.section {
    padding: 80px 8%;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 48px;
}

.section-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(28px, 3vw, 42px);
    color: var(--dark);
    line-height: 1.2;
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
    white-space: nowrap;
}

.section-link:hover { border-color: var(--warm); }

/* ── CATEGORY CARDS ── */
.categories {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.card {
    background: var(--card-bg);
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid rgba(160,82,45,0.08);
    transition: transform 0.35s cubic-bezier(0.23,1,0.32,1), box-shadow 0.35s;
    cursor: pointer;
}

.card:hover {
    transform: translateY(-8px);
    box-shadow: 0 24px 60px rgba(28,24,17,0.12);
}

.card-img-wrap {
    position: relative;
    overflow: hidden;
    height: 220px;
}

.card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s cubic-bezier(0.23,1,0.32,1);
}

.card:hover img {
    transform: scale(1.06);
}

.card-tag {
    position: absolute;
    top: 14px;
    left: 14px;
    background: rgba(249,245,239,0.9);
    backdrop-filter: blur(8px);
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--warm);
    padding: 5px 12px;
    border-radius: 100px;
}

.card-body {
    padding: 22px 24px 26px;
}

.card-body h2 {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 8px;
}

.card-body p {
    color: var(--muted);
    font-size: 14px;
    line-height: 1.65;
    font-weight: 300;
}

.card-body-footer {
    margin-top: 16px;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: var(--accent);
}

/* ── BANNER ── */
.banner {
    position: relative;
    height: 75vh;
    min-height: 500px;
    overflow: hidden;
    margin: 0 4%;
    border-radius: 28px;
}

.banner-img {
    position: absolute;
    inset: 0;
    background: url('https://img.freepik.com/premium-photo/background-nature-dinner-table-vegetable-plate-food-tree-horizontal-photography-color_1274051-10787.jpg?semt=ais_hybrid&w=740&q=80') center/cover no-repeat;
}

.banner-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to right,
        rgba(28,24,17,0.78) 0%,
        rgba(28,24,17,0.45) 55%,
        rgba(28,24,17,0.1) 100%
    );
}

.banner-content {
    position: relative;
    z-index: 2;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 0 8%;
}

.banner-eyebrow {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--accent);
    margin-bottom: 18px;
}

.banner-content h2 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(36px, 4.5vw, 68px);
    color: #FFFFFF;
    line-height: 1.1;
    max-width: 560px;
    margin-bottom: 24px;
}

.banner-content h2 em {
    font-style: italic;
    color: rgba(255,255,255,0.7);
}

.banner-content p {
    color: rgba(255,255,255,0.65);
    font-size: 16px;
    font-weight: 300;
    max-width: 360px;
    line-height: 1.7;
    margin-bottom: 36px;
}

.banner-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: var(--accent);
    color: var(--white);
    text-decoration: none;
    padding: 14px 28px;
    border-radius: 100px;
    font-size: 14px;
    font-weight: 500;
    width: fit-content;
    transition: background 0.25s, transform 0.2s;
}

.banner-btn:hover {
    background: #d4944a;
    transform: translateY(-2px);
}

/* ── PREFERENCE ── */
.pref-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.pref-card {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    cursor: pointer;
}

.pref-card-img {
    position: relative;
    height: 300px;
    overflow: hidden;
}

.pref-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s cubic-bezier(0.23,1,0.32,1);
}

.pref-card:hover img {
    transform: scale(1.07);
}

.pref-card-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(28,24,17,0.75) 0%, transparent 55%);
}

.pref-card-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px 22px;
}

.pref-card-info h3 {
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    color: var(--white);
    margin-bottom: 4px;
    font-weight: 700;
}

.pref-card-info p {
    color: rgba(255,255,255,0.65);
    font-size: 13px;
    font-weight: 300;
}

/* ── DIVIDER ── */
.divider-band {
    background: var(--dark);
    color: var(--cream);
    padding: 28px 8%;
    display: flex;
    align-items: center;
    gap: 32px;
    overflow: hidden;
}

.divider-band .ticker {
    display: flex;
    gap: 48px;
    animation: ticker 20s linear infinite;
    white-space: nowrap;
}

.divider-band span {
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    font-style: italic;
    opacity: 0.7;
    letter-spacing: 0.5px;
}

.divider-band .dot {
    color: var(--accent);
    font-style: normal;
    opacity: 1;
}

@keyframes ticker {
    from { transform: translateX(0); }
    to { transform: translateX(-50%); }
}

/* ── FOOTER ── */
footer {
    background: var(--dark);
    color: var(--cream);
    padding: 60px 8% 40px;
}

.footer-top {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 48px;
    margin-bottom: 48px;
    padding-bottom: 48px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.footer-brand h3 {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    margin-bottom: 14px;
    color: var(--white);
}

.footer-brand p {
    color: rgba(255,255,255,0.45);
    font-size: 14px;
    line-height: 1.7;
    font-weight: 300;
    max-width: 280px;
}

.footer-col h4 {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--accent);
    margin-bottom: 18px;
}

.footer-col p {
    color: rgba(255,255,255,0.5);
    font-size: 14px;
    margin-bottom: 10px;
    font-weight: 300;
    cursor: pointer;
    transition: color 0.2s;
}

.footer-col p:hover { color: var(--white); }

.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.footer-bottom p {
    color: rgba(255,255,255,0.3);
    font-size: 13px;
}

.detail-btn{
    margin-top:18px;
    display:inline-flex;
    align-items:center;
    gap:8px;

    background:var(--dark);
    color:white;

    text-decoration:none;

    padding:12px 18px;
    border-radius:12px;

    font-size:14px;
    font-weight:500;

    transition:0.2s;
}

.detail-btn:hover{
    background:var(--warm);
    transform:translateY(-2px);
}

.empty-bookmark{
    width:100%;
    text-align:center;
    padding:80px 20px;
    background:white;
    border-radius:24px;
    border:1px solid rgba(160,82,45,0.1);
}

.empty-bookmark h2{
    font-family:'Playfair Display', serif;
    font-size:40px;
    margin-bottom:14px;
}

.empty-bookmark p{
    color:var(--muted);
    margin-bottom:30px;
}

.card-meta{
    display:flex;
    align-items:center;
    gap:6px;

    font-size:12px;
    color:var(--muted);
    font-weight:300;

    margin-bottom:14px;
}

.card-dot{
    width:4px;
    height:4px;
    border-radius:50%;
    background:var(--accent);
}

.card-rating{
    display:flex;
    align-items:center;
    gap:8px;

    margin-top:10px;
    margin-bottom:14px;
}

.card-stars{
    display:flex;
    align-items:center;
    gap:2px;
}

.card-stars .s{
    font-size:14px;
    line-height:1;
}

.card-rating-val{
    font-size:13px;
    font-weight:600;
    color:var(--dark);
}

.card-rating-count{
    font-size:12px;
    color:var(--muted);
}
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="nav-left">
        <a href="index.php" class="nav-logo">
            <img src="https://cdn-icons-png.flaticon.com/512/3075/3075977.png">
            <span>Foodies</span>
        </a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="menu.php">Menu</a>
            
            <?php if($isPremium): ?>
                <a href="tambah_resep.php">Tambah Resep</a>
                <a href="bookmark.php">Bookmark</a>
                
            <?php endif; ?>
            <a href="premium.php" style="color:gold; font-weight:600;">
                Premium
            </a>
        </div>
    </div>
    <div class="nav-right">
        <a href="profile.php">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
            </svg>
        </a>
    </div>
</nav>

<div class="hero">
    <div class="hero-img"></div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <p class="hero-label">Discover · Cook · Enjoy</p>
        <h1>Bookmark Saya</h1>
    </div>
</div>

<!-- BOOKMARK LIST -->
<div class="section">

    <div class="section-header">
        <h2 class="section-title">
            Resep <em style="font-style:italic; color:var(--warm);">Favoritmu</em>
        </h2>
    </div>

    <div class="categories">

        <?php if(mysqli_num_rows($query) > 0): ?>

            <?php while($row = mysqli_fetch_assoc($query)): ?>

            <?php
            $menu_id = $row['id'];

            $q_rating = mysqli_query($conn,"
                SELECT 
                    COUNT(*) as total,
                    AVG(nilai) as avg_rating
                FROM rating
                WHERE menu_id='$menu_id'
            ");

            $d_rating = mysqli_fetch_assoc($q_rating);

            $total = intval($d_rating['total']);
            $avg   = $total > 0 ? round($d_rating['avg_rating'],1) : 0;

            $full  = floor($avg);
            $half  = ($avg - $full) >= 0.5 ? 1 : 0;
            $empty = 5 - $full - $half;
            ?>

            <a href="resep.php?id=<?= $row['id'] ?>" 
   style="text-decoration:none; color:inherit;">

    <div class="card">

        <div class="card-img-wrap">
            <img src="<?= $row['gambar'] ?>" alt="<?= htmlspecialchars($row['nama']) ?>">

            <span class="card-tag">
                <?= ucfirst(htmlspecialchars($row['kategori_menu'])) ?>
            </span>
        </div>

        <div class="card-body">

            <h2><?= htmlspecialchars($row['nama']) ?></h2>

            <div class="card-meta">
                <span><?= ucfirst(htmlspecialchars($row['kategori_menu'])) ?></span>
                <span class="card-dot"></span>
                <span><?= $row['kalori'] ?> kalori</span>
            </div>

            <!-- rating -->
            <div class="card-rating">

                <div class="card-stars">
                    <?php for($i=0;$i<$full;$i++): ?>
                        <span class="s" style="color:#F59E0B;">★</span>
                    <?php endfor; ?>

                    <?php if($half): ?>
                        <span class="s" style="color:#F59E0B;">½</span>
                    <?php endif; ?>

                    <?php for($i=0;$i<$empty;$i++): ?>
                        <span class="s" style="color:#D1C5B8;">★</span>
                    <?php endfor; ?>
                </div>

                <?php if($total > 0): ?>
                    <span class="card-rating-val">
                        <?= number_format($avg,1) ?>
                    </span>

                    <span class="card-rating-count">
                        (<?= $total ?>)
                    </span>
                <?php else: ?>
                    <span class="card-rating-count">
                        Belum ada rating
                    </span>
                <?php endif; ?>

            </div>

            <div class="card-body-footer">
                Lihat resep →
            </div>

        </div>

    </div>

</a>

            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-bookmark">
                <h2>Bookmark masih kosong</h2>
                <p>
                    Kamu belum menyimpan resep favorit.
                </p>
                <br>
                <a href="menu.php" class="hero-cta">
                    Jelajahi Menu
                </a>
            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>