<?php
session_start();
include "config/koneksi.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$error = "";
$sukses = "";

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $nama = mysqli_real_escape_string($conn,$_POST['nama']);
    $kategori = mysqli_real_escape_string($conn,$_POST['kategori']);
    $gambar = mysqli_real_escape_string($conn,$_POST['gambar']);
    $bahan = mysqli_real_escape_string($conn,$_POST['bahan']);
    $langkah = mysqli_real_escape_string($conn,$_POST['langkah']);

    $uid = $_SESSION['user_id'];

    $insert = mysqli_query($conn,"
        INSERT INTO menus
        (nama,kategori_menu,gambar,created_by,is_premium,bahan)
        VALUES
        ('$nama','$kategori','$gambar','$uid',0,'$bahan')
    ");

    if($insert){

        $menu_id = mysqli_insert_id($conn);

        // insert bahan (optional kalau kamu pakai tabel relasi)
        $bahanList = array_filter(array_map('trim', explode("\n",$bahan)));
        foreach($bahanList as $b){
            mysqli_query($conn,"INSERT INTO bahan_menu(menu_id,nama_bahan) VALUES ($menu_id,'$b')");
        }

        // insert langkah
        $steps = array_filter(array_map('trim', explode("\n",$langkah)));
        $i = 1;
        foreach($steps as $s){
            mysqli_query($conn,"INSERT INTO langkah_menu(menu_id,step_ke,deskripsi) VALUES ($menu_id,$i,'$s')");
            $i++;
        }

        $sukses = "Resep berhasil ditambahkan!";
    } else {
        $error = "Gagal menambah resep!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tambah Resep | Foodies</title>
<meta name="viewport" content="width=device-width,initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
:root{
  --cream:#F9F5EF;
  --dark:#1C1811;
  --accent:#C8863C;
  --muted:#7A6E62;
  --white:#fff;
  --card:#FFFDF9;
  --border:rgba(160,82,45,0.12);
}

*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:'DM Sans',sans-serif;
  background:var(--cream);
  color:var(--dark);
}

/* layout */
.container{
  max-width:1000px;
  margin:40px auto;
  padding:0 20px;
}

/* header */
.header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:24px;
}

.title{
  font-family:'Playfair Display',serif;
  font-size:28px;
}

.back{
  text-decoration:none;
  padding:10px 16px;
  border:1px solid var(--border);
  border-radius:100px;
  color:var(--muted);
  background:var(--white);
}

/* card */
.card{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:18px;
  padding:28px;
}

/* alert */
.alert{
  padding:12px 16px;
  border-radius:12px;
  margin-bottom:16px;
  font-size:14px;
}
.success{
  background:rgba(46,125,82,0.1);
  color:#2E7D52;
}
.error{
  background:rgba(192,57,43,0.1);
  color:#C0392B;
}

/* form */
.grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:16px;
}

.full{
  grid-column:1/-1;
}

label{
  font-size:11px;
  letter-spacing:2px;
  text-transform:uppercase;
  color:var(--muted);
  margin-bottom:6px;
  display:block;
}

input,select,textarea{
  width:100%;
  padding:12px 14px;
  border-radius:10px;
  border:1px solid var(--border);
  background:var(--cream);
  font-family:inherit;
  outline:none;
}

textarea{
  min-height:100px;
  resize:vertical;
}

input:focus,select:focus,textarea:focus{
  border-color:var(--accent);
}

/* button */
.btn{
  margin-top:20px;
  padding:12px 26px;
  border:none;
  border-radius:100px;
  background:var(--dark);
  color:white;
  cursor:pointer;
  font-weight:500;
  transition:.2s;
}

.btn:hover{
  background:var(--accent);
}

/* helper */
.hint{
  font-size:12px;
  color:var(--muted);
  margin-top:4px;
}
</style>
</head>

<body>

<div class="container">

  <div class="header">
    <div class="title">Tambah Resep 🍽</div>
    <a class="back" href="index.php">← Kembali</a>
  </div>

  <?php if($sukses): ?>
    <div class="alert success"><?= $sukses ?></div>
  <?php endif; ?>

  <?php if($error): ?>
    <div class="alert error"><?= $error ?></div>
  <?php endif; ?>

  <div class="card">

    <form method="POST">

      <div class="grid">

        <div>
          <label>Nama Resep</label>
          <input type="text" name="nama" required>
        </div>

        <div>
          <label>Kategori</label>
          <select name="kategori" required>
            <option value="utama">Utama</option>
            <option value="dessert">Dessert</option>
            <option value="diet">Diet</option>
            <option value="tradisional">Tradisional</option>
            <option value="internasional">Internasional</option>
          </select>
        </div>

        <div>
          <label>URL Gambar</label>
          <input type="text" name="gambar" placeholder="https://...">
        </div>

        <div class="full">
          <label>Bahan (1 baris = 1 bahan)</label>
          <textarea name="bahan" placeholder="Contoh:
200g ayam
1 bawang putih
garam secukupnya"></textarea>
        </div>

        <div class="full">
          <label>Langkah Memasak (1 baris = 1 step)</label>
          <textarea name="langkah" placeholder="Contoh:
Potong ayam
Tumis bawang
Masukkan ayam dan bumbu"></textarea>
        </div>

      </div>

      <button class="btn" type="submit">+ Tambah Resep</button>

    </form>

  </div>

</div>

</body>
</html>