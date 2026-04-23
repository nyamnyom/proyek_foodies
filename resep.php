<?php
session_start();
include "config/koneksi.php";

$id = intval($_GET['id'] ?? 0);

if(!$id) {
    header("Location: menu.php");
    exit;
}

/* Ambil data menu utama */
$q_menu = "SELECT * FROM menus WHERE id = $id";
$r_menu = mysqli_query($conn, $q_menu);
$menu   = mysqli_fetch_assoc($r_menu);

if(!$menu) {
    header("Location: menu.php");
    exit;
}

/* Ambil bahan */
$q_bahan = "SELECT nama_bahan FROM bahan_menu WHERE menu_id = $id ORDER BY id ASC";
$r_bahan = mysqli_query($conn, $q_bahan);
$bahans  = [];
while($b = mysqli_fetch_assoc($r_bahan)) {
    $bahans[] = $b['nama_bahan'];
}

/* Ambil langkah */
$q_langkah = "SELECT * FROM langkah_menu WHERE menu_id = $id ORDER BY step_ke ASC";
$r_langkah = mysqli_query($conn, $q_langkah);
$langkahs  = [];
while($l = mysqli_fetch_assoc($r_langkah)) {
    $langkahs[] = $l;
}

/* Menu lain (rekomendasi) - kategori sama, beda ID */
$kat = $menu['kategori_menu'];
$q_rekomendasi = "SELECT * FROM menus WHERE kategori_menu = '$kat' AND id != $id LIMIT 4";
$r_rekomendasi = mysqli_query($conn, $q_rekomendasi);
$rekomendasis  = [];
while($r = mysqli_fetch_assoc($r_rekomendasi)) {
    $rekomendasis[] = $r;
}

$img_url = $menu['gambar'] ?? 'https://via.placeholder.com/800x500?text=No+Image';

// ── [NEW] HANDLE SUBMIT RATING ──────────────────────────────────────────────
$rating_msg      = '';
$rating_msg_type = ''; // 'success' | 'error'

if(isset($_POST['submit_rating'])) {
    if(!isset($_SESSION['user_id'])) {
        $rating_msg      = 'Kamu harus login terlebih dahulu untuk memberikan rating.';
        $rating_msg_type = 'error';
    } else {
        $uid    = intval($_SESSION['user_id']);
        $nilai  = intval($_POST['nilai'] ?? 0);
        $review = trim($_POST['review'] ?? '');

        if($nilai < 1 || $nilai > 5) {
            $rating_msg      = 'Pilih rating antara 1 sampai 5 bintang.';
            $rating_msg_type = 'error';
        } else {
            $review_esc = mysqli_real_escape_string($conn, $review);
            // INSERT ... ON DUPLICATE KEY UPDATE (1 user 1 review per menu)
            $sql_r = "INSERT INTO rating (menu_id, user_id, nilai, review)
                      VALUES ($id, $uid, $nilai, '$review_esc')
                      ON DUPLICATE KEY UPDATE nilai = $nilai, review = '$review_esc', created_at = NOW()";
            if(mysqli_query($conn, $sql_r)) {
                $rating_msg      = 'Rating kamu berhasil disimpan! Terima kasih.';
                $rating_msg_type = 'success';
            } else {
                $rating_msg      = 'Terjadi kesalahan, coba lagi.';
                $rating_msg_type = 'error';
            }
        }
    }
}

// ── [NEW] AMBIL SEMUA REVIEW + STATISTIK ────────────────────────────────────
$q_reviews = "SELECT r.*, u.nama FROM rating r
              LEFT JOIN users u ON u.id = r.user_id
              WHERE r.menu_id = $id
              ORDER BY r.created_at DESC";
$r_reviews   = mysqli_query($conn, $q_reviews);
$reviews     = [];
while($rv = mysqli_fetch_assoc($r_reviews)) { $reviews[] = $rv; }

$avg_rating   = 0;
$total_review = count($reviews);
if($total_review > 0) {
    $sum = array_sum(array_column($reviews, 'nilai'));
    $avg_rating = round($sum / $total_review, 1);
}

// Rating user yang sedang login (untuk pre-fill form)
$my_rating = null;
if(isset($_SESSION['user_id'])) {
    $uid_me = intval($_SESSION['user_id']);
    $q_my   = "SELECT * FROM rating WHERE menu_id = $id AND user_id = $uid_me LIMIT 1";
    $r_my   = mysqli_query($conn, $q_my);
    if($r_my && mysqli_num_rows($r_my) > 0) {
        $my_rating = mysqli_fetch_assoc($r_my);
    }
}
// ─────────────────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($menu['nama']) ?> — Foodies</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --cream:   #F9F5EF;
    --dark:    #1C1811;
    --warm:    #A0522D;
    --accent:  #C8863C;
    --muted:   #7A6E62;
    --white:   #FFFFFF;
    --card-bg: #FFFDF9;
    --border:  rgba(160,82,45,0.10);
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
.recipe-hero {
    position: relative;
    height: 520px;
    overflow: hidden;
}

.recipe-hero-img {
    position: absolute; inset: 0;
    background: url('<?= $img_url ?>') center/cover no-repeat;
    transition: transform 0.6s ease;
}

.recipe-hero:hover .recipe-hero-img {
    transform: scale(1.03);
}

.recipe-hero-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(
        to bottom,
        rgba(28,24,17,0.30) 0%,
        rgba(28,24,17,0.15) 40%,
        rgba(28,24,17,0.80) 100%
    );
}

.recipe-hero-content {
    position: relative; z-index: 2;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 0 6% 48px;
    padding-top: 120px;
}

.recipe-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.5);
    margin-bottom: 18px;
}

.recipe-breadcrumb a {
    color: rgba(255,255,255,0.5);
    text-decoration: none;
    transition: color 0.2s;
}

.recipe-breadcrumb a:hover { color: var(--accent); }

.recipe-breadcrumb span { color: rgba(255,255,255,0.3); }

.recipe-hero-tags {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.hero-tag {
    padding: 5px 14px;
    border-radius: 100px;
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

.hero-tag-cat {
    background: rgba(200,134,60,0.25);
    color: var(--accent);
    border: 1px solid rgba(200,134,60,0.3);
}

.hero-tag-kal {
    background: rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.75);
    border: 1px solid rgba(255,255,255,0.15);
}

/* [NEW] hero tag rating */
.hero-tag-rating {
    background: rgba(245,158,11,0.20);
    color: #FBBF24;
    border: 1px solid rgba(245,158,11,0.30);
    display: flex;
    align-items: center;
    gap: 5px;
}

.recipe-hero-content h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(36px, 5vw, 64px);
    color: var(--white);
    line-height: 1.12;
    margin-bottom: 0;
}

.recipe-hero-content h1 em {
    font-style: italic;
    color: rgba(255,255,255,0.70);
}

/* ── MAIN CONTENT ── */
.recipe-wrap {
    width: 92%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 56px 0 0;
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 48px;
    align-items: start;
}

/* ── LEFT COLUMN ── */
.recipe-main {}

/* section label */
.sec-label {
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--accent);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.sec-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border);
}

/* ── STEPS ── */
.steps-list {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.step-item {
    display: flex;
    gap: 24px;
    padding: 28px 0;
    border-bottom: 1px solid var(--border);
    position: relative;
    opacity: 0;
    transform: translateY(16px);
    animation: fadeUp 0.5s forwards;
}

.step-item:last-child { border-bottom: none; }

@keyframes fadeUp {
    to { opacity: 1; transform: translateY(0); }
}

.step-item:nth-child(1) { animation-delay: 0.05s; }
.step-item:nth-child(2) { animation-delay: 0.10s; }
.step-item:nth-child(3) { animation-delay: 0.15s; }
.step-item:nth-child(4) { animation-delay: 0.20s; }
.step-item:nth-child(5) { animation-delay: 0.25s; }

.step-num {
    flex-shrink: 0;
    width: 44px; height: 44px;
    border-radius: 14px;
    background: var(--dark);
    color: var(--cream);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    font-weight: 700;
}

.step-body {}

.step-body p {
    font-size: 16px;
    line-height: 1.65;
    color: var(--dark);
    font-weight: 300;
}

/* ── RIGHT SIDEBAR ── */
.recipe-sidebar {}

.sidebar-card {
    background: var(--card-bg);
    border-radius: 20px;
    border: 1px solid var(--border);
    padding: 28px;
    position: sticky;
    top: 100px;
}

.sidebar-card + .sidebar-card {
    margin-top: 20px;
}

/* ── BAHAN CARD ── */
.bahan-list {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.bahan-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
    font-size: 15px;
    font-weight: 400;
    color: var(--dark);
}

.bahan-item:last-child { border-bottom: none; }

.bahan-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--accent);
    flex-shrink: 0;
}

/* ── NUTRITION CARD ── */
.nutrition-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.nutri-item {
    background: var(--cream);
    border-radius: 14px;
    padding: 16px;
    text-align: center;
}

.nutri-val {
    font-family: 'Playfair Display', serif;
    font-size: 26px;
    font-weight: 700;
    color: var(--dark);
    line-height: 1;
    margin-bottom: 4px;
}

.nutri-val span {
    font-size: 13px;
    font-weight: 400;
    color: var(--muted);
}

.nutri-label {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--muted);
}

.nutri-full {
    grid-column: 1 / -1;
    background: var(--dark);
    border-radius: 14px;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 14px;
}

.nutri-full .nutri-val {
    color: var(--white);
    font-size: 32px;
}

.nutri-full .nutri-val span { color: rgba(255,255,255,0.5); }

.nutri-full .nutri-label { color: rgba(255,255,255,0.45); }

.nutri-full-icon {
    width: 40px; height: 40px;
    background: var(--accent);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

/* ── BACK LINK ── */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    color: var(--muted);
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.5px;
    margin-bottom: 32px;
    padding: 10px 20px;
    border-radius: 100px;
    background: var(--card-bg);
    border: 1px solid var(--border);
    transition: all 0.25s;
}

.back-link:hover {
    color: var(--dark);
    background: var(--white);
    border-color: var(--accent);
    transform: translateX(-3px);
}

/* ── REKOMENDASI ── */
.rekom-section {
    width: 92%;
    max-width: 1200px;
    margin: 0 auto 0;
    padding-top: 48px;
    border-top: 1px solid var(--border);
}

.rekom-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: 28px;
}

.rekom-header h2 {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    color: var(--dark);
}

.rekom-header h2 em { font-style: italic; color: var(--warm); }

.rekom-header a {
    font-size: 13px;
    color: var(--accent);
    text-decoration: none;
    font-weight: 500;
    transition: opacity 0.2s;
}

.rekom-header a:hover { opacity: 0.7; }

.rekom-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 18px;
}

.card {
    background: var(--card-bg);
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid var(--border);
    transition: transform 0.35s cubic-bezier(0.23,1,0.32,1), box-shadow 0.35s;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    display: block;
}

.card:hover {
    transform: translateY(-7px);
    box-shadow: 0 24px 60px rgba(28,24,17,0.12);
}

.card-img-wrap {
    position: relative;
    height: 170px;
    overflow: hidden;
}

.card img {
    width: 100%; height: 100%;
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
    padding: 16px 18px 18px;
}

.card-body h3 {
    font-family: 'Playfair Display', serif;
    font-size: 17px;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 5px;
    line-height: 1.3;
}

.card-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--muted);
    font-weight: 300;
}

.card-dot {
    width: 3px; height: 3px;
    border-radius: 50%;
    background: var(--accent);
    opacity: 0.6;
}

.card-body-footer {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--border);
    font-size: 12px;
    font-weight: 500;
    color: var(--accent);
}

/* ══════════════════════════════════════════════
   [NEW] RATING & REVIEW SECTION
══════════════════════════════════════════════ */
.review-section {
    width: 92%;
    max-width: 1200px;
    margin: 60px auto 0;
    padding-bottom: 60px;
}

/* Ringkasan statistik rating */
.rating-summary {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 36px 40px;
    display: flex;
    align-items: center;
    gap: 48px;
    margin-bottom: 40px;
}

.rating-big {
    text-align: center;
    flex-shrink: 0;
}

.rating-big-num {
    font-family: 'Playfair Display', serif;
    font-size: 64px;
    font-weight: 700;
    color: var(--dark);
    line-height: 1;
    margin-bottom: 8px;
}

.rating-big-stars {
    display: flex;
    justify-content: center;
    gap: 3px;
    margin-bottom: 6px;
}

.rating-big-stars .s {
    font-size: 20px;
    line-height: 1;
}

.rating-big-count {
    font-size: 13px;
    color: var(--muted);
    font-weight: 300;
}

.rating-bars {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.rating-bar-row {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: var(--muted);
}

.rating-bar-label {
    width: 20px;
    text-align: right;
    flex-shrink: 0;
    font-weight: 500;
    color: var(--dark);
}

.rating-bar-track {
    flex: 1;
    height: 8px;
    background: rgba(160,82,45,0.1);
    border-radius: 100px;
    overflow: hidden;
}

.rating-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--accent), var(--warm));
    border-radius: 100px;
    transition: width 0.6s ease;
}

.rating-bar-cnt {
    width: 28px;
    flex-shrink: 0;
    font-weight: 300;
    font-size: 12px;
}

/* ── FORM RATING ── */
.review-form-wrap {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 36px 40px;
    margin-bottom: 40px;
}

.review-form-wrap h3 {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    color: var(--dark);
    margin-bottom: 24px;
}

/* Star picker */
.star-picker {
    display: flex;
    gap: 6px;
    margin-bottom: 20px;
}

.star-picker input[type="radio"] { display: none; }

.star-picker label {
    font-size: 36px;
    cursor: pointer;
    color: #D1C5B8;
    transition: color 0.15s, transform 0.15s;
    line-height: 1;
    user-select: none;
}

.star-picker label:hover,
.star-picker label:hover ~ label { color: #D1C5B8; }

/* Trick: reverse order dengan flex-direction:row-reverse lalu revert icon */
.star-picker { flex-direction: row-reverse; justify-content: flex-end; }

.star-picker label:hover,
.star-picker input:checked ~ label,
.star-picker label:hover ~ label { color: #D1C5B8; }

/* Forward star fill trick */
.star-picker:not(:has(input:checked)) label:hover,
.star-picker:not(:has(input:checked)) label:hover ~ label { color: #D1C5B8; }

/* Simpler approach — JS handles highlight */
.star-picker label.lit { color: #F59E0B; }
.star-picker label:hover { transform: scale(1.15); }

.review-form-wrap textarea {
    width: 100%;
    padding: 16px 20px;
    border-radius: 16px;
    border: 1px solid rgba(160,82,45,0.18);
    background: var(--cream);
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    color: var(--dark);
    resize: vertical;
    min-height: 110px;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    margin-bottom: 18px;
}

.review-form-wrap textarea::placeholder { color: var(--muted); }

.review-form-wrap textarea:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(200,134,60,0.10);
}

.review-submit-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 13px 32px;
    border-radius: 100px;
    background: var(--dark);
    color: var(--cream);
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: background 0.25s, transform 0.2s;
}

.review-submit-btn:hover {
    background: var(--warm);
    transform: translateY(-2px);
}

.review-form-notice {
    padding: 14px 20px;
    border-radius: 14px;
    font-size: 14px;
    font-weight: 400;
    margin-bottom: 20px;
}

.notice-success {
    background: rgba(34,197,94,0.1);
    color: #15803D;
    border: 1px solid rgba(34,197,94,0.2);
}

.notice-error {
    background: rgba(220,38,38,0.08);
    color: #DC2626;
    border: 1px solid rgba(220,38,38,0.15);
}

.login-prompt {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px 24px;
    background: rgba(200,134,60,0.07);
    border: 1px solid rgba(200,134,60,0.18);
    border-radius: 16px;
    font-size: 14px;
    color: var(--muted);
}

.login-prompt a {
    color: var(--accent);
    font-weight: 500;
    text-decoration: none;
    border-bottom: 1px solid rgba(200,134,60,0.3);
    transition: border-color 0.2s;
}

.login-prompt a:hover { border-color: var(--accent); }

/* ── LIST REVIEW ── */
.review-list { display: flex; flex-direction: column; gap: 16px; }

.review-item {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 24px 28px;
    transition: box-shadow 0.3s;
}

.review-item:hover {
    box-shadow: 0 8px 28px rgba(28,24,17,0.07);
}

.review-item-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 12px;
}

.review-avatar {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), var(--warm));
    display: flex; align-items: center; justify-content: center;
    font-family: 'Playfair Display', serif;
    font-size: 16px;
    font-weight: 700;
    color: var(--white);
    flex-shrink: 0;
}

.review-meta { flex: 1; }

.review-name {
    font-size: 15px;
    font-weight: 500;
    color: var(--dark);
    margin-bottom: 2px;
}

.review-date {
    font-size: 12px;
    color: var(--muted);
    font-weight: 300;
}

.review-stars {
    display: flex;
    gap: 2px;
}

.review-stars .s {
    font-size: 14px;
    line-height: 1;
}

.review-text {
    font-size: 14px;
    line-height: 1.7;
    color: var(--muted);
    font-weight: 300;
}

.review-text.empty-text {
    font-style: italic;
    opacity: 0.6;
}

.no-review {
    text-align: center;
    padding: 48px 20px;
    color: var(--muted);
}

.no-review-icon { font-size: 36px; margin-bottom: 12px; opacity: 0.35; }

.no-review p { font-size: 15px; font-weight: 300; }

/* responsive */
@media(max-width: 768px) {
    .recipe-wrap { grid-template-columns: 1fr; gap: 32px; }
    .sidebar-card { position: static; }
    .recipe-hero { height: 400px; }
    .recipe-hero-content h1 { font-size: 34px; }
    .rating-summary { flex-direction: column; gap: 24px; align-items: flex-start; }
    .review-form-wrap { padding: 24px 20px; }
    .review-section { padding-bottom: 40px; }
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
            <a href="menu.php" class="active">Menu</a>
            <a href="#">Resep</a>
        </div>
    </div>
    <div class="nav-right">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
        </svg>
    </div>
</nav>

<!-- HERO -->
<div class="recipe-hero">
    <div class="recipe-hero-img"></div>
    <div class="recipe-hero-overlay"></div>
    <div class="recipe-hero-content">
        <div class="recipe-breadcrumb">
            <a href="index.php">Home</a>
            <span>/</span>
            <a href="menu.php">Menu</a>
            <span>/</span>
            <span style="color:rgba(255,255,255,0.75)"><?= htmlspecialchars($menu['nama']) ?></span>
        </div>
        <div class="recipe-hero-tags">
            <span class="hero-tag hero-tag-cat"><?= ucfirst(htmlspecialchars($menu['kategori_menu'])) ?></span>
            <span class="hero-tag hero-tag-kal"><?= $menu['kalori'] ?> kkal</span>
            <?php if($total_review > 0): ?>
            <span class="hero-tag hero-tag-rating">
                ★ <?= number_format($avg_rating,1) ?> &nbsp;(<?= $total_review ?> ulasan)
            </span>
            <?php endif; ?>
        </div>
        <h1><?= htmlspecialchars($menu['nama']) ?></h1>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="recipe-wrap">

    <!-- LEFT: langkah memasak -->
    <div class="recipe-main">

        <a href="menu.php" class="back-link">
            ← Kembali ke Menu
        </a>

        <div class="sec-label">Cara Memasak</div>

        <div class="steps-list">
            <?php if(empty($langkahs)): ?>
                <p style="color:var(--muted);font-weight:300;">Langkah belum tersedia.</p>
            <?php else: ?>
            <?php foreach($langkahs as $step): ?>
            <div class="step-item">
                <div class="step-num"><?= $step['step_ke'] ?></div>
                <div class="step-body">
                    <p><?= htmlspecialchars($step['deskripsi']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- RIGHT SIDEBAR -->
    <div class="recipe-sidebar">

        <!-- BAHAN -->
        <div class="sidebar-card">
            <div class="sec-label">Bahan-bahan</div>
            <div class="bahan-list">
                <?php if(empty($bahans)): ?>
                    <p style="color:var(--muted);font-weight:300;font-size:14px;">Bahan belum tersedia.</p>
                <?php else: ?>
                <?php foreach($bahans as $b): ?>
                <div class="bahan-item">
                    <span class="bahan-dot"></span>
                    <span><?= htmlspecialchars($b) ?></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- KALORI / NUTRITION -->
        <div class="sidebar-card">
            <div class="sec-label">Informasi Gizi</div>
            <div class="nutrition-grid">
                <div class="nutri-full">
                    <div class="nutri-full-icon">🔥</div>
                    <div>
                        <div class="nutri-val"><?= $menu['kalori'] ?><span> kkal</span></div>
                        <div class="nutri-label">Total Kalori</div>
                    </div>
                </div>

                <div class="nutri-item">
                    <div class="nutri-val"><?= ucfirst(htmlspecialchars($menu['bahan'])) ?></div>
                    <div class="nutri-label">Bahan Utama</div>
                </div>

                <div class="nutri-item">
                    <div class="nutri-val"><?= ucfirst(htmlspecialchars($menu['kategori_menu'])) ?></div>
                    <div class="nutri-label">Kategori</div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ══════════════════════════════════════════════
     [NEW] RATING & REVIEW SECTION
══════════════════════════════════════════════ -->
<div class="review-section">

    <!-- Statistik rating -->
    <div class="sec-label" style="margin-bottom:28px;">Rating & Ulasan</div>

    <?php
    // hitung distribusi per bintang
    $dist = [5=>0,4=>0,3=>0,2=>0,1=>0];
    foreach($reviews as $rv) { $dist[intval($rv['nilai'])]++; }
    $full_avg  = floor($avg_rating);
    $half_avg  = ($avg_rating - $full_avg) >= 0.4 ? 1 : 0;
    $empty_avg = 5 - $full_avg - $half_avg;
    ?>
    <div class="rating-summary">
        <div class="rating-big">
            <div class="rating-big-num"><?= $total_review > 0 ? number_format($avg_rating,1) : '—' ?></div>
            <div class="rating-big-stars">
                <?php if($total_review > 0): ?>
                    <?php for($i=0;$i<$full_avg;$i++): ?><span class="s" style="color:#F59E0B;">★</span><?php endfor; ?>
                    <?php if($half_avg): ?><span class="s" style="color:#F59E0B;">½</span><?php endif; ?>
                    <?php for($i=0;$i<$empty_avg;$i++): ?><span class="s" style="color:#D1C5B8;">★</span><?php endfor; ?>
                <?php else: ?>
                    <?php for($i=0;$i<5;$i++): ?><span class="s" style="color:#D1C5B8;">★</span><?php endfor; ?>
                <?php endif; ?>
            </div>
            <div class="rating-big-count"><?= $total_review ?> ulasan</div>
        </div>

        <div class="rating-bars">
            <?php foreach([5,4,3,2,1] as $star): ?>
            <?php $cnt = $dist[$star]; $pct = $total_review > 0 ? round($cnt/$total_review*100) : 0; ?>
            <div class="rating-bar-row">
                <span class="rating-bar-label"><?= $star ?></span>
                <span style="color:#F59E0B;font-size:12px;">★</span>
                <div class="rating-bar-track">
                    <div class="rating-bar-fill" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="rating-bar-cnt"><?= $cnt ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Form tambah/edit rating -->
    <div class="review-form-wrap">
        <h3><?= $my_rating ? 'Edit Ulasanmu' : 'Tulis Ulasan' ?></h3>

        <?php if($rating_msg): ?>
        <div class="review-form-notice notice-<?= $rating_msg_type ?>">
            <?= htmlspecialchars($rating_msg) ?>
        </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['user_id'])): ?>
        <form method="POST" action="resep.php?id=<?= $id ?>">

            <!-- STAR PICKER -->
            <div style="margin-bottom:6px;font-size:12px;font-weight:500;letter-spacing:1px;text-transform:uppercase;color:var(--muted);">Pilih Bintang</div>
            <div class="star-picker" id="starPicker">
                <?php for($s=5;$s>=1;$s--): ?>
                <label data-val="<?= $s ?>" class="<?= ($my_rating && intval($my_rating['nilai'])==$s)?'lit':'' ?>">★</label>
                <?php endfor; ?>
                <input type="hidden" name="nilai" id="nilaiInput" value="<?= $my_rating ? intval($my_rating['nilai']) : 0 ?>">
            </div>

            <textarea name="review" placeholder="Ceritakan pengalamanmu memasak atau menikmati menu ini..."><?= $my_rating ? htmlspecialchars($my_rating['review']) : '' ?></textarea>

            <button type="submit" name="submit_rating" class="review-submit-btn">
                <?= $my_rating ? '✎  Perbarui Ulasan' : '★  Kirim Ulasan' ?>
            </button>
        </form>

        <?php else: ?>
        <div class="login-prompt">
            <span>💬</span>
            <span>Kamu harus <a href="login.php">login</a> untuk memberikan rating dan ulasan.</span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Daftar review -->
    <?php if(empty($reviews)): ?>
    <div class="no-review">
        <div class="no-review-icon">💬</div>
        <p>Belum ada ulasan. Jadilah yang pertama!</p>
    </div>
    <?php else: ?>
    <div class="review-list">
        <?php foreach($reviews as $rv):
            $rv_full  = floor($rv['nilai']);
            $rv_empty = 5 - $rv_full;
            $rv_date  = date('d M Y', strtotime($rv['created_at']));
        ?>
        <div class="review-item">
            <div class="review-item-header">
                <div class="review-avatar"><?= strtoupper(substr($rv['nama'],0,1)) ?></div>
                <div class="review-meta">
                    <div class="review-name"><?= htmlspecialchars($rv['nama']) ?></div>
                    <div class="review-date"><?= $rv_date ?></div>
                </div>
                <div class="review-stars">
                    <?php for($i=0;$i<$rv_full;$i++):  ?><span class="s" style="color:#F59E0B;">★</span><?php endfor; ?>
                    <?php for($i=0;$i<$rv_empty;$i++): ?><span class="s" style="color:#D1C5B8;">★</span><?php endfor; ?>
                </div>
            </div>
            <?php if(trim($rv['review'])): ?>
            <p class="review-text"><?= nl2br(htmlspecialchars($rv['review'])) ?></p>
            <?php else: ?>
            <p class="review-text empty-text">Tidak ada komentar.</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div><!-- end review-section -->

<!-- REKOMENDASI -->
<?php if(!empty($rekomendasis)): ?>
<div class="rekom-section" style="margin-bottom:80px;">
    <div class="rekom-header">
        <h2>Menu <em>Serupa</em></h2>
        <a href="menu.php?menu=<?= urlencode($kat) ?>">Lihat semua →</a>
    </div>
    <div class="rekom-grid">
        <?php foreach($rekomendasis as $rek):
            $rek_img = $rek['gambar'] ?? 'https://via.placeholder.com/400x300?text=No+Image';
        ?>
        <a href="resep.php?id=<?= $rek['id'] ?>" class="card">
            <div class="card-img-wrap">
                <img src="<?= $rek_img ?>" alt="<?= htmlspecialchars($rek['nama']) ?>">
                <span class="card-tag"><?= ucfirst(htmlspecialchars($rek['kategori_menu'])) ?></span>
                <span class="card-kalori"><?= $rek['kalori'] ?> kkal</span>
            </div>
            <div class="card-body">
                <h3><?= htmlspecialchars($rek['nama']) ?></h3>
                <div class="card-meta">
                    <span><?= ucfirst(htmlspecialchars($rek['kategori_menu'])) ?></span>
                    <span class="card-dot"></span>
                    <span><?= $rek['kalori'] ?> kalori</span>
                </div>
                <div class="card-body-footer">Lihat resep &nbsp;→</div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- [NEW] JS untuk interactive star picker -->
<script>
(function(){
    const picker  = document.getElementById('starPicker');
    if(!picker) return;
    const labels  = Array.from(picker.querySelectorAll('label'));
    const input   = document.getElementById('nilaiInput');
    function setLit(val) {
        labels.forEach(l => {
            l.classList.toggle('lit', parseInt(l.dataset.val) <= val);
        });
    }

    // init
    if(parseInt(input.value) > 0) setLit(parseInt(input.value));

    labels.forEach(label => {
        label.addEventListener('mouseenter', function(){
            setLit(parseInt(this.dataset.val));
        });
        label.addEventListener('click', function(){
            const val = parseInt(this.dataset.val);
            input.value = val;
            setLit(val);
        });
    });

    picker.addEventListener('mouseleave', function(){
        setLit(parseInt(input.value) || 0);
    });
})();
</script>

</body>
</html>