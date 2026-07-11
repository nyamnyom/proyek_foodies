<?php
session_start();
include "config/koneksi.php";

/* FILTER */
$search  = $_GET['search']  ?? '';
$bahan   = $_GET['bahan']   ?? '';
$menu    = $_GET['menu']    ?? '';
$min_bintang = intval($_GET['bintang'] ?? 0);

/* BMI */
$bb  = $_GET['bb'] ?? '';
$tb  = $_GET['tb'] ?? '';
$bmi = null;
$status = "";

if($bb && $tb){
    $tb_m = $tb / 100;
    $bmi  = $bb / ($tb_m * $tb_m);

    if($bmi < 18.5)        { $status = "Kurus"; }
    elseif($bmi < 25)      { $status = "Normal"; }
    elseif($bmi < 30)      { $status = "Overweight"; }
    else                   { $status = "Obesitas"; }
}

$query = "SELECT m.*, 
          ROUND(COALESCE(AVG(r.nilai), 0), 1) AS avg_rating,
          COUNT(r.id) AS total_rating
          FROM menus m
          LEFT JOIN rating r ON r.menu_id = m.id
          WHERE m.status != 'rejected'";

if($search) { $query .= " AND m.nama LIKE '%".mysqli_real_escape_string($conn,$search)."%'"; }
if($bahan)  { $query .= " AND m.bahan='".mysqli_real_escape_string($conn,$bahan)."'"; }
if($menu)   { $query .= " AND m.kategori_menu='".mysqli_real_escape_string($conn,$menu)."'"; }

if($status == "Kurus")          { $query .= " AND m.kalori >= 350"; }
elseif($status == "Normal")     { $query .= " AND m.kalori BETWEEN 200 AND 400"; }
elseif($status == "Overweight") { $query .= " AND m.kalori <= 350"; }
elseif($status == "Obesitas")   { $query .= " AND m.kalori <= 250"; }

$query .= " GROUP BY m.id";

if($min_bintang >= 1) {
    $query .= " HAVING avg_rating >= " . $min_bintang;
}

if($min_bintang >= 1) {
    $query .= " ORDER BY avg_rating DESC";
} else {
    /*
      Urutan tampilan (dari atas ke bawah):
      1) Resep buatan admin (created_by IS NULL) - selalu paling atas, urut berdasarkan id.
      2) Resep buatan user yang SUDAH divalidasi admin - urut berdasarkan waktu validasi
         paling awal duluan (yang duluan divalidasi ada di atas yang belakangan).
      3) Resep buatan user yang masih pending - urut berdasarkan urutan input (id).
    */
    $query .= " ORDER BY
        (CASE
            WHEN m.created_by IS NULL THEN 0
            WHEN m.status = 'approved' THEN 1
            ELSE 2
        END) ASC,
        (CASE
            WHEN m.created_by IS NULL THEN m.id
            WHEN m.status = 'approved' THEN COALESCE(UNIX_TIMESTAMP(m.validated_at), UNIX_TIMESTAMP(m.created_at))
            ELSE m.id
        END) ASC";
}

$result = mysqli_query($conn, $query);

$isPremium = false;

if(isset($_SESSION['user_id'])){

    $uid = $_SESSION['user_id'];

    $cek = mysqli_query($conn,"
        SELECT * FROM premium_users
        WHERE user_id='$uid'
        AND aktif_sampai >= CURDATE()
    ");

    if(mysqli_num_rows($cek) > 0){
        $isPremium = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Menu — Foodies</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --cream:    #F9F5EF;
    --dark:     #1C1811;
    --warm:     #A0522D;
    --accent:   #C8863C;
    --muted:    #7A6E62;
    --white:    #FFFFFF;
    --card-bg:  #FFFDF9;
    --border:   rgba(160,82,45,0.10);
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

.nav-left { display: flex; gap: 32px; align-items: center; }

.nav-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}

.nav-logo img { width: 26px; opacity: 0.85; }

.nav-logo span {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    color: var(--dark);
    letter-spacing: -0.3px;
}

.nav-links { display: flex; gap: 28px; }

.nav-links a {
    text-decoration: none;
    color: var(--muted);
    font-size: 14px;
    font-weight: 400;
    letter-spacing: 0.3px;
    transition: color 0.2s;
}

.nav-links a:hover,
.nav-links a.active { color: var(--dark); }

.nav-right {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: var(--dark);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
}

.nav-right svg { width: 16px; height: 16px; fill: var(--cream); }

/* ── HERO ── */
.hero {
    position: relative;
    height: 360px;
    overflow: hidden;
}

.hero-img {
    position: absolute; inset: 0;
    background: url('https://as2.ftcdn.net/v2/jpg/03/02/72/97/1000_F_302729718_z1KstZHQzZ4PFwhPxjFbkMB7mZxucveS.jpg') center/cover no-repeat;
}

.hero-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(
        to bottom,
        rgba(28,24,17,0.55) 0%,
        rgba(28,24,17,0.30) 60%,
        rgba(28,24,17,0.65) 100%
    );
}

.hero-content {
    position: relative; z-index: 2;
    height: 100%;
    display: flex; flex-direction: column;
    justify-content: center; align-items: center;
    text-align: center;
    padding: 0 5%;
    padding-top: 90px;
}

.hero-label {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--accent);
    margin-bottom: 14px;
}

.hero-content h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(36px, 5vw, 58px);
    color: var(--white);
    line-height: 1.15;
    margin-bottom: 12px;
}

.hero-content h1 em {
    font-style: italic;
    color: rgba(255,255,255,0.75);
}

.hero-content p {
    color: rgba(255,255,255,0.65);
    font-size: 16px;
    font-weight: 300;
}

/* ── FILTER PANEL ── */
.filter-wrap {
    width: 92%;
    max-width: 1200px;
    margin: -30px auto 0;
    position: relative;
    z-index: 10;
}

.filter {
    background: var(--card-bg);
    border-radius: 20px;
    border: 1px solid var(--border);
    box-shadow: 0 16px 60px rgba(28,24,17,0.10);
    padding: 28px 32px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.filter-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-row label {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--muted);
    width: 100%;
}

.filter-divider {
    width: 100%;
    height: 1px;
    background: var(--border);
}

.filter input,
.filter select {
    padding: 12px 16px;
    border-radius: 12px;
    border: 1px solid rgba(160,82,45,0.15);
    background: var(--cream);
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    color: var(--dark);
    flex: 1;
    min-width: 140px;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    -webkit-appearance: none;
    appearance: none;
}

.filter input:focus,
.filter select:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(200,134,60,0.12);
}

.filter input::placeholder { color: var(--muted); }

.bmi-group {
    display: flex;
    gap: 12px;
    flex: 1;
}

.bmi-group input { flex: 1; min-width: 100px; }

.filter-btn {
    padding: 12px 28px;
    border: none;
    border-radius: 12px;
    background: var(--dark);
    color: var(--cream);
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.25s, transform 0.15s;
    white-space: nowrap;
}

.filter-btn:hover {
    background: var(--warm);
    transform: translateY(-1px);
}

/* ── BINTANG FILTER PILLS ── */
.bintang-filter {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}

.bintang-filter .bintang-label {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--muted);
    margin-right: 4px;
}

.bintang-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 7px 16px;
    border-radius: 100px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    border: 1px solid rgba(160,82,45,0.18);
    color: var(--muted);
    background: var(--cream);
    transition: all 0.2s;
    white-space: nowrap;
}

.bintang-pill:hover {
    border-color: var(--accent);
    color: var(--accent);
}

.bintang-pill.active {
    background: var(--dark);
    color: var(--cream);
    border-color: var(--dark);
}

.bintang-pill .star-ico { color: #F59E0B; font-size: 12px; }

/* ── BMI RESULT BADGE ── */
.bmi-result {
    width: 92%;
    max-width: 1200px;
    margin: 20px auto 0;
    padding: 16px 28px;
    border-radius: 14px;
    background: var(--dark);
    color: var(--white);
    display: flex;
    align-items: center;
    gap: 20px;
    font-size: 14px;
}

.bmi-result .bmi-num {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    font-weight: 700;
    color: var(--accent);
    line-height: 1;
}


.bmi-result .bmi-info span {
    display: block;
    font-size: 11px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.45);
    margin-bottom: 4px;
}

.bmi-result .bmi-info strong {
    font-size: 15px;
    font-weight: 500;
}

.bmi-status-pill {
    margin-left: auto;
    padding: 6px 18px;
    border-radius: 100px;
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.5px;
}

.pill-kurus     { background: rgba(23,131,236,0.2); color: #7BC8FF; }
.pill-normal    { background: rgba(99,153,34,0.2);  color: #A8D96A; }
.pill-overweight{ background: rgba(186,117,23,0.2); color: #F5C87E; }
.pill-obesitas  { background: rgba(226,75,74,0.2);  color: #F09595; }

.bmi-result .bmi-tip {
    font-size: 13px;
    color: rgba(255,255,255,0.5);
    font-weight: 300;
    max-width: 300px;
    text-align: right;
}

/* ── SECTION / RESULTS ── */
.results-wrap {
    width: 92%;
    max-width: 1200px;
    margin: 40px auto 80px;
}

.results-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: 32px;
}

.results-header h2 {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    color: var(--dark);
}

.results-header h2 em {
    font-style: italic;
    color: var(--warm);
}

.results-count {
    font-size: 13px;
    color: var(--muted);
    font-weight: 300;
}

/* ── MENU GRID ── */
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 22px;
}

/* ── MENU CARD ── */
.card {
    background: var(--card-bg);
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid var(--border);
    transition: transform 0.35s cubic-bezier(0.23,1,0.32,1), box-shadow 0.35s;
    cursor: pointer;
}

.card:hover {
    transform: translateY(-7px);
    box-shadow: 0 24px 60px rgba(28,24,17,0.12);
}

.card-img-wrap {
    position: relative;
    height: 195px;
    overflow: hidden;
}

.card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s cubic-bezier(0.23,1,0.32,1);
}

.card:hover img { transform: scale(1.06); }

.card-tag {
    position: absolute;
    top: 12px; left: 12px;
    background: rgba(249,245,239,0.92);
    backdrop-filter: blur(8px);
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--warm);
    padding: 4px 12px;
    border-radius: 100px;
}

.card-kalori {
    position: absolute;
    top: 12px; right: 12px;
    background: rgba(28,24,17,0.70);
    backdrop-filter: blur(8px);
    font-size: 11px;
    font-weight: 500;
    color: rgba(255,255,255,0.9);
    padding: 4px 10px;
    border-radius: 100px;
}

.card-body {
    padding: 18px 20px 20px;
}

.card-body h3 {
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 6px;
    line-height: 1.3;
}

.card-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--muted);
    font-weight: 300;
}

.card-dot {
    width: 3px; height: 3px;
    border-radius: 50%;
    background: var(--accent);
    opacity: 0.6;
    flex-shrink: 0;
}

/* ── RATING ROW DI CARD ── */
.card-rating {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 10px;
}

.card-stars {
    display: flex;
    gap: 2px;
}

.card-stars .s {
    font-size: 13px;
    line-height: 1;
}

.card-rating-val {
    font-size: 13px;
    font-weight: 500;
    color: var(--dark);
}

.card-rating-count {
    font-size: 12px;
    color: var(--muted);
    font-weight: 300;
}

.card-body-footer {
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
    font-weight: 500;
    color: var(--accent);
}

/* ── EMPTY STATE ── */
.empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 80px 20px;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 20px;
    opacity: 0.4;
}

.empty h3 {
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    color: var(--dark);
    margin-bottom: 8px;
}

.empty p {
    color: var(--muted);
    font-size: 15px;
    font-weight: 300;
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

<!-- HERO -->
<div class="hero">
    <div class="hero-img"></div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <p class="hero-label">Explore · Filter · Discover</p>
        <h1>Temukan <em>Menu</em> Terbaik<br>Untukmu</h1>
        <p>Sesuaikan dengan kebutuhan dan kesehatanmu</p>
    </div>
</div>

<!-- FILTER -->
<div class="filter-wrap">
    <form class="filter" method="GET">

        <div class="filter-row">
            <label>Cari & Filter Menu</label>
            <input type="text"   name="search" placeholder="Cari nama menu..." value="<?= htmlspecialchars($search) ?>">
            <select name="bahan">
                <option value="">Semua Bahan</option>
                <option value="ayam"     <?= $bahan=='ayam'     ?'selected':'' ?>>Ayam</option>
                <option value="sayuran"  <?= $bahan=='sayuran'  ?'selected':'' ?>>Sayuran</option>
                <option value="daging"   <?= $bahan=='daging'   ?'selected':'' ?>>Daging</option>
                <option value="manis"    <?= $bahan=='manis'    ?'selected':'' ?>>Manis</option>
            </select>
            <select name="menu">
                <option value="">Semua Kategori</option>
                <option value="tradisional"   <?= $menu=='tradisional'  ?'selected':'' ?>>Tradisional</option>
                <option value="diet"          <?= $menu=='diet'         ?'selected':'' ?>>Diet</option>
                <option value="utama"         <?= $menu=='utama'        ?'selected':'' ?>>Utama</option>
                <option value="dessert"       <?= $menu=='dessert'      ?'selected':'' ?>>Dessert</option>
                <option value="internasional" <?= $menu=='internasional'?'selected':'' ?>>Internasional</option>
                <option value="instant"       <?= $menu=='instant'      ?'selected':'' ?>>Instant</option>
            </select>
            <input type="hidden" name="bintang" value="<?= $min_bintang ?>">
        </div>

        <div class="filter-divider"></div>

        <div class="filter-row">
            <label>Rekomendasi Berdasarkan BMI (opsional)</label>
            <div class="bmi-group">
                <input type="number" name="bb" placeholder="Berat badan (kg)" value="<?= htmlspecialchars($bb) ?>">
                <input type="number" name="tb" placeholder="Tinggi badan (cm)" value="<?= htmlspecialchars($tb) ?>">
            </div>
            <button type="submit" class="filter-btn">Cari Menu →</button>
        </div>

        <div class="filter-divider"></div>

        <!-- BINTANG FILTER ROW -->
        <div class="filter-row" style="align-items:center;">
            <label style="width:auto; margin-right:4px;">Filter Rating</label>
            <div class="bintang-filter">
                <?php
                $qs_base = http_build_query(array_filter([
                    'search' => $search,
                    'bahan'  => $bahan,
                    'menu'   => $menu,
                    'bb'     => $bb,
                    'tb'     => $tb,
                ]));
                ?>
                <a href="menu.php?<?= $qs_base ?>"
                   class="bintang-pill <?= $min_bintang == 0 ? 'active' : '' ?>">
                    Semua
                </a>
                <?php foreach([1,2,3,4,5] as $b_val): ?>
                <a href="menu.php?<?= $qs_base ?>&bintang=<?= $b_val ?>"
                   class="bintang-pill <?= $min_bintang == $b_val ? 'active' : '' ?>">
                    <span class="star-ico">★</span> <?= $b_val ?>+ Bintang
                </a>
                <?php endforeach; ?>
            </div>
        </div>

    </form>
</div>

<!-- BMI RESULT -->
<?php if($bmi): ?>
<?php
$tip_map = [
    'Kurus'      => 'Menampilkan menu kalori tinggi (≥350 kkal) untuk mendukung penambahan berat badan.',
    'Normal'     => 'Menampilkan menu seimbang (200–400 kkal) untuk menjaga berat badan idealmu.',
    'Overweight' => 'Menampilkan menu rendah kalori (≤350 kkal) untuk membantu menjaga berat badan.',
    'Obesitas'   => 'Menampilkan menu kalori sangat rendah (≤250 kkal) untuk mendukung dietmu.',
];
$pill_class = [
    'Kurus'=>'pill-kurus','Normal'=>'pill-normal','Overweight'=>'pill-overweight','Obesitas'=>'pill-obesitas'
];
?>
<div class="bmi-result" style="width:92%;max-width:1200px;margin:20px auto 0;">
    <div class="bmi-num"><?= number_format($bmi,1) ?></div>
    <div class="bmi-info">
        <span>Indeks Massa Tubuh</span>
        <strong>Status: <?= $status ?></strong>
    </div>
    <span class="bmi-status-pill <?= $pill_class[$status] ?>"><?= $status ?></span>
    <p class="bmi-tip"><?= $tip_map[$status] ?></p>
</div>
<?php endif; ?>

<!-- RESULTS -->
<div class="results-wrap">

    <div class="results-header">
        <h2>Semua <em>Menu</em><?php if($min_bintang >= 1): ?> <span style="font-size:20px;color:var(--accent);">★<?= $min_bintang ?>+</span><?php endif; ?></h2>
        <span class="results-count"><?= mysqli_num_rows($result) ?> menu ditemukan</span>
    </div>

    <div class="grid">
    <?php
    $count = mysqli_num_rows($result);
    if($count == 0):
    ?>
        <div class="empty">
            <div class="empty-icon">🍽</div>
            <h3>Menu tidak ditemukan</h3>
            <p>Coba ubah filter pencarian atau kata kunci yang berbeda.</p>
        </div>
    <?php else: ?>
    <?php while($row = mysqli_fetch_assoc($result)):
        $avg   = floatval($row['avg_rating']);
        $total = intval($row['total_rating']);
        $full  = floor($avg);
        $half  = ($avg - $full) >= 0.4 ? 1 : 0;
        $empty = 5 - $full - $half;
    ?>
        <div class="card">
            <div class="card-img-wrap">
                <img src="<?= htmlspecialchars($row['gambar']) ?>" alt="<?= htmlspecialchars($row['nama']) ?>">
                <span class="card-tag"><?= ucfirst(htmlspecialchars($row['kategori_menu'])) ?></span>
                <span class="card-kalori"><?= $row['kalori'] ?> kkal</span>
            </div>
            <a href="resep.php?id=<?= $row['id'] ?>" style="text-decoration: none; color: inherit;">
                <div class="card-body">
                    <h3><?= htmlspecialchars($row['nama']) ?></h3>
                    <div class="card-meta">
                        <span><?= ucfirst(htmlspecialchars($row['kategori_menu'])) ?></span>
                        <span class="card-dot"></span>
                        <span><?= $row['kalori'] ?> kalori</span>
                    </div>

                    <!-- RATING BINTANG -->
                    <div class="card-rating">
                        <div class="card-stars">
                            <?php for($i=0;$i<$full;$i++):  ?><span class="s" style="color:#F59E0B;">★</span><?php endfor; ?>
                            <?php if($half):                  ?><span class="s" style="color:#F59E0B;">½</span><?php endif; ?>
                            <?php for($i=0;$i<$empty;$i++): ?><span class="s" style="color:#D1C5B8;">★</span><?php endfor; ?>
                        </div>
                        <?php if($total > 0): ?>
                            <span class="card-rating-val"><?= number_format($avg,1) ?></span>
                            <span class="card-rating-count">(<?= $total ?>)</span>
                        <?php else: ?>
                            <span class="card-rating-count">Belum ada rating</span>
                        <?php endif; ?>
                    </div>

                    <div class="card-body-footer">
                        Lihat resep &nbsp;→
                    </div>
                </div>
            </a>
        </div>
    <?php endwhile; endif; ?>
    </div>

</div>

</body>
</html>