<?php
session_start();
include "config/koneksi.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* CEK PREMIUM */
$isPremium = false;
$expired = null;

$cek = mysqli_query($conn,"
    SELECT *
    FROM premium_users
    WHERE user_id='$user_id'
    AND aktif_sampai >= CURDATE()
");

if(mysqli_num_rows($cek) > 0){
    $isPremium = true;

    $dataPremium = mysqli_fetch_assoc($cek);
    $expired = $dataPremium['aktif_sampai'];
}

/* BELI PREMIUM */
if(isset($_POST['beli'])){

    $expiredBaru = date('Y-m-d', strtotime('+30 days'));

    if($isPremium){

        mysqli_query($conn,"
            UPDATE premium_users
            SET aktif_sampai='$expiredBaru'
            WHERE user_id='$user_id'
        ");

    } else {

        mysqli_query($conn,"
            INSERT INTO premium_users(user_id, aktif_sampai)
            VALUES('$user_id','$expiredBaru')
        ");
    }

    header("Location: premium.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Foodies Premium</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">

<style>

*, *::before, *::after{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

:root{
    --cream:#F9F5EF;
    --dark:#1C1811;
    --warm:#A0522D;
    --accent:#C8863C;
    --muted:#7A6E62;
    --white:#FFFFFF;
}

body{
    font-family:'DM Sans', sans-serif;
    background:var(--cream);
    color:var(--dark);
}

/* NAVBAR */
.navbar{
    position:fixed;
    top:24px;
    left:50%;
    transform:translateX(-50%);
    width:92%;
    max-width:1200px;

    background:rgba(255,255,255,0.8);
    backdrop-filter:blur(16px);

    padding:14px 32px;
    border-radius:100px;

    display:flex;
    justify-content:space-between;
    align-items:center;

    z-index:1000;

    border:1px solid rgba(160,82,45,0.1);
}

.nav-left{
    display:flex;
    align-items:center;
    gap:32px;
}

.nav-logo{
    display:flex;
    align-items:center;
    gap:10px;
    text-decoration:none;
}

.nav-logo img{
    width:28px;
}

.nav-logo span{
    font-family:'Playfair Display', serif;
    font-size:20px;
    color:var(--dark);
}

.nav-links{
    display:flex;
    gap:24px;
}

.nav-links a{
    text-decoration:none;
    color:var(--muted);
    font-size:14px;
}

.nav-links a:hover{
    color:var(--dark);
}

/* HERO */
.hero{
    min-height:100vh;

    display:flex;
    align-items:center;
    justify-content:center;

    padding:140px 8% 80px;

    position:relative;
    overflow:hidden;
}

.hero::before{
    content:'';

    position:absolute;
    inset:0;

    background:
    radial-gradient(circle at top right,
    rgba(200,134,60,0.18),
    transparent 30%);
}

.hero-inner{
    max-width:1100px;
    width:100%;

    display:grid;
    grid-template-columns:1.1fr 0.9fr;
    gap:50px;
    align-items:center;
}

.hero-left h5{
    font-size:12px;
    letter-spacing:3px;
    color:var(--accent);
    margin-bottom:20px;
    text-transform:uppercase;
}

.hero-left h1{
    font-family:'Playfair Display', serif;
    font-size:70px;
    line-height:1.05;
    margin-bottom:24px;
}

.hero-left h1 em{
    font-style:italic;
    color:var(--warm);
}

.hero-left p{
    color:var(--muted);
    line-height:1.8;
    max-width:520px;
    margin-bottom:34px;
    font-size:15px;
}

/* FEATURES */
.features{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:18px;
    margin-bottom:36px;
}

.feature{
    background:white;
    border-radius:18px;
    padding:20px;
    border:1px solid rgba(160,82,45,0.08);
}

.feature h3{
    margin-bottom:8px;
    font-size:16px;
}

.feature p{
    font-size:13px;
    color:var(--muted);
    line-height:1.6;
}

/* BUTTON */
.btn{
    border:none;
    outline:none;
    cursor:pointer;

    background:var(--dark);
    color:white;

    padding:16px 28px;

    border-radius:100px;

    font-size:15px;
    font-weight:600;

    transition:0.25s;
}

.btn:hover{
    background:var(--warm);
    transform:translateY(-2px);
}

/* PREMIUM CARD */
.premium-card{
    background:white;
    border-radius:32px;
    padding:42px;

    border:1px solid rgba(160,82,45,0.1);

    box-shadow:0 20px 60px rgba(28,24,17,0.08);

    position:relative;
}

.premium-badge{
    position:absolute;
    top:20px;
    right:20px;

    background:gold;
    color:#5B4300;

    font-size:12px;
    font-weight:700;

    padding:8px 14px;
    border-radius:100px;
}

.price{
    font-family:'Playfair Display', serif;
    font-size:64px;
    margin:20px 0;
}

.price span{
    font-size:18px;
    color:var(--muted);
}

.premium-card ul{
    list-style:none;
    margin:30px 0;
}

.premium-card li{
    margin-bottom:16px;
    color:var(--muted);

    display:flex;
    align-items:center;
    gap:10px;
}

.status{
    margin-top:26px;
    padding:18px;
    border-radius:18px;
    background:rgba(34,197,94,0.08);
    color:#15803D;
    font-size:14px;
    line-height:1.6;
}

/* RESPONSIVE */
@media(max-width:900px){

    .hero-inner{
        grid-template-columns:1fr;
    }

    .hero-left h1{
        font-size:52px;
    }

}

@media(max-width:600px){

    .nav-links{
        display:none;
    }

    .features{
        grid-template-columns:1fr;
    }

    .hero-left h1{
        font-size:42px;
    }

    .premium-card{
        padding:28px;
    }

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
                <a href="bookmark.php">Bookmark</a>
            <?php endif; ?>
            <a href="profile.php">Profile</a>
        </div>

    </div>

</nav>

<!-- HERO -->
<section class="hero">

    <div class="hero-inner">

        <!-- LEFT -->
        <div class="hero-left">

            <?php if(isset($_GET['need_premium'])): ?>
                <div style="
                    background:rgba(200,134,60,0.12);
                    border:1px solid rgba(200,134,60,0.3);
                    color:var(--warm);
                    padding:14px 18px;
                    border-radius:14px;
                    margin-bottom:20px;
                    font-size:14px;
                ">
                    🔒 Fitur Bookmark hanya tersedia untuk member Premium. Yuk upgrade dulu!
                </div>
            <?php endif; ?>

            <h5>Exclusive Membership</h5>

            <h1>
                Foodies <em>Premium</em>
            </h1>

            <p>
                Nikmati akses resep eksklusif premium,
                menu spesial chef, pengalaman tanpa batas,
                dan fitur terbaik untuk para pecinta kuliner.
            </p>

            <div class="features">

                <div class="feature">
                    <h3>👑 Premium Recipes</h3>
                    <p>
                        Akses resep eksklusif khusus member premium.
                    </p>
                </div>

                <div class="feature">
                    <h3>⭐ Exclusive Content</h3>
                    <p>
                        Konten spesial dan menu pilihan terbaik.
                    </p>
                </div>

                <div class="feature">
                    <h3>🍽 Unlimited Access</h3>
                    <p>
                        Jelajahi seluruh resep tanpa batas.
                    </p>
                </div>

                <div class="feature">
                    <h3>🔥 New Premium Weekly</h3>
                    <p>
                        Update resep premium terbaru setiap minggu.
                    </p>
                </div>

            </div>

        </div>

        <!-- RIGHT -->
        <div class="premium-card">

            <div class="premium-badge">
                MOST POPULAR
            </div>

            <h2 style="
                font-family:'Playfair Display', serif;
                font-size:38px;
            ">
                Premium Plan
            </h2>

            <div class="price">
                Rp19K
                <span>/ bulan</span>
            </div>

            <ul>
                <li>✔ Akses semua resep premium</li>
                <li>✔ Konten eksklusif member</li>
                <li>✔ Prioritas update resep baru</li>
                <li>✔ Tampilan premium experience</li>
            </ul>

            <?php if($isPremium): ?>

                <button class="btn" style="width:100%; opacity:.7;">
                    👑 Premium Aktif
                </button>

                <div class="status">
                    Membership premium aktif sampai
                    <strong>
                        <?= date('d M Y', strtotime($expired)) ?>
                    </strong>
                </div>

            <?php else: ?>

                <form method="POST">

                    <button type="submit" name="beli" class="btn" style="width:100%;">
                        👑 Beli Premium Sekarang
                    </button>

                </form>

            <?php endif; ?>

        </div>

    </div>

</section>

</body>
</html>