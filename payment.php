<?php
session_start();
include "config/koneksi.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* HARGA & DURASI (bisa disesuaikan) */
$hargaPremium = 19000;
$durasiHari   = 30;

/* CEK STATUS PREMIUM SEKARANG (untuk hitung tanggal baru) */
$cek = mysqli_query($conn,"
    SELECT *
    FROM premium_users
    WHERE user_id='$user_id'
    AND aktif_sampai >= CURDATE()
");

$isPremium = mysqli_num_rows($cek) > 0;
$dataPremium = $isPremium ? mysqli_fetch_assoc($cek) : null;

/* KONFIRMASI PEMBAYARAN -> BARU JADI PREMIUM DI SINI */
if(isset($_POST['konfirmasi_bayar'])){

    if($isPremium){
        // perpanjang dari tanggal expired sebelumnya
        $expiredBaru = date('Y-m-d', strtotime($dataPremium['aktif_sampai']." +{$durasiHari} days"));

        mysqli_query($conn,"
            UPDATE premium_users
            SET aktif_sampai='$expiredBaru'
            WHERE user_id='$user_id'
        ");

    } else {
        $expiredBaru = date('Y-m-d', strtotime("+{$durasiHari} days"));

        mysqli_query($conn,"
            INSERT INTO premium_users(user_id, aktif_sampai)
            VALUES('$user_id','$expiredBaru')
        ");
    }

    header("Location: premium.php?payment=success");
    exit;
}

/* Data bank simulasi (logo bisa diganti sesuai kebutuhan) */
$banks = [
    "bca"     => "BCA Virtual Account",
    "mandiri" => "Mandiri Virtual Account",
    "bni"     => "BNI Virtual Account",
    "bri"     => "BRI Virtual Account",
    "qris"    => "QRIS (Semua e-wallet & bank)",
];

// data dummy untuk ditampilkan sebagai "kode pembayaran"
$kodeVA = strtoupper(substr(md5($user_id.time()),0,12));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Pembayaran - Foodies Premium</title>
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
    --green:#15803D;
}

body{
    font-family:'DM Sans', sans-serif;
    background:var(--cream);
    color:var(--dark);
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:40px 5%;
}

.wrap{
    width:100%;
    max-width:480px;
}

.topbar{
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:22px;
}

.topbar a{
    text-decoration:none;
    color:var(--muted);
    font-size:14px;
    display:flex;
    align-items:center;
    gap:6px;
}

.card{
    background:white;
    border-radius:28px;
    padding:32px;
    border:1px solid rgba(160,82,45,0.1);
    box-shadow:0 20px 60px rgba(28,24,17,0.08);
}

h1{
    font-family:'Playfair Display', serif;
    font-size:28px;
    margin-bottom:6px;
}

.subtitle{
    color:var(--muted);
    font-size:14px;
    margin-bottom:26px;
}

.total-box{
    background:var(--cream);
    border-radius:16px;
    padding:16px 18px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:26px;
}

.total-box .label{
    font-size:13px;
    color:var(--muted);
}

.total-box .value{
    font-family:'Playfair Display', serif;
    font-size:22px;
}

/* STEP LABEL */
.step-label{
    font-size:13px;
    font-weight:700;
    color:var(--accent);
    text-transform:uppercase;
    letter-spacing:1.5px;
    margin-bottom:14px;
}

/* BANK LIST */
.bank-list{
    display:flex;
    flex-direction:column;
    gap:10px;
    margin-bottom:10px;
}

.bank-option{
    display:flex;
    align-items:center;
    gap:14px;

    border:1.5px solid rgba(160,82,45,0.15);
    border-radius:16px;
    padding:14px 16px;

    cursor:pointer;
    transition:0.2s;
}

.bank-option:hover{
    border-color:var(--warm);
    background:rgba(200,134,60,0.05);
}

.bank-option.selected{
    border-color:var(--warm);
    background:rgba(200,134,60,0.1);
}

.bank-option input{
    accent-color:var(--warm);
    width:18px;
    height:18px;
}

.bank-option .bicon{
    width:36px;
    height:36px;
    border-radius:10px;
    background:var(--dark);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:14px;
    font-weight:700;
}

.bank-option .bname{
    font-size:15px;
    font-weight:500;
}

/* QR SECTION */
#qr-section{
    display:none;
    margin-top:24px;
    text-align:center;
    animation:fadein 0.35s ease;
}

@keyframes fadein{
    from{opacity:0; transform:translateY(6px);}
    to{opacity:1; transform:translateY(0);}
}

#qr-section img{
    width:190px;
    height:190px;
    border-radius:16px;
    border:1.5px solid rgba(160,82,45,0.15);
    padding:10px;
    background:white;
}

.qr-caption{
    font-size:13px;
    color:var(--muted);
    margin-top:14px;
    line-height:1.6;
}

.qr-code{
    display:inline-block;
    margin-top:10px;
    font-family:monospace;
    font-size:15px;
    letter-spacing:2px;
    background:var(--cream);
    padding:8px 16px;
    border-radius:100px;
    font-weight:700;
}

/* BUTTON */
.btn{
    border:none;
    outline:none;
    cursor:pointer;
    width:100%;

    background:var(--dark);
    color:white;

    padding:16px 28px;
    border-radius:100px;

    font-size:15px;
    font-weight:600;

    transition:0.25s;
    margin-top:22px;
}

.btn:hover{
    background:var(--warm);
    transform:translateY(-2px);
}

.btn:disabled{
    opacity:0.5;
    cursor:not-allowed;
    transform:none;
}

.note{
    font-size:12px;
    color:var(--muted);
    text-align:center;
    margin-top:14px;
    line-height:1.6;
}

</style>
</head>
<body>

<div class="wrap">

    <div class="topbar">
        <a href="premium.php">&larr; Kembali</a>
    </div>

    <div class="card">

        <h1>Selesaikan Pembayaran</h1>
        <p class="subtitle">Upgrade akunmu ke Foodies Premium</p>

        <div class="total-box">
            <div>
                <div class="label">Total Pembayaran</div>
                <div class="value">Rp<?= number_format($hargaPremium,0,',','.') ?></div>
            </div>
            <div class="label">Berlaku <?= $durasiHari ?> hari</div>
        </div>

        <form method="POST" id="payment-form">

            <div class="step-label">1. Pilih Metode Pembayaran</div>

            <div class="bank-list" id="bank-list">
                <?php foreach($banks as $kode => $nama): ?>
                <label class="bank-option" data-bank="<?= $kode ?>">
                    <input type="radio" name="metode" value="<?= $kode ?>" required>
                    <div class="bicon"><?= strtoupper(substr($kode,0,3)) ?></div>
                    <div class="bname"><?= $nama ?></div>
                </label>
                <?php endforeach; ?>
            </div>

            <div id="qr-section">
                <div class="step-label" style="margin-top:20px;">2. Scan / Bayar</div>
                <img id="qr-image" src="" alt="QR Pembayaran">
                <div class="qr-caption">
                    Scan QR di atas menggunakan aplikasi m-banking atau e-wallet kamu.<br>
                    Kode Referensi:
                </div>
                <div class="qr-code"><?= $kodeVA ?></div>

                <button type="submit" name="konfirmasi_bayar" class="btn">
                    ✅ Saya Sudah Bayar / Selesai
                </button>

                <div class="note">
                    *Ini adalah simulasi pembayaran. Klik tombol di atas untuk menyelesaikan
                    dan mengaktifkan Premium.
                </div>
            </div>

        </form>

    </div>

</div>

<script>
const options = document.querySelectorAll('.bank-option');
const qrSection = document.getElementById('qr-section');
const qrImage = document.getElementById('qr-image');

options.forEach(opt => {
    opt.addEventListener('click', () => {
        options.forEach(o => o.classList.remove('selected'));
        opt.classList.add('selected');

        const bank = opt.dataset.bank;
        const qrData = "FOODIES-PREMIUM-<?= $kodeVA ?>-" + bank;

        qrImage.src = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" + encodeURIComponent(qrData);

        qrSection.style.display = "block";
        qrSection.scrollIntoView({behavior:"smooth", block:"nearest"});
    });
});
</script>

</body>
</html>