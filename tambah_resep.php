<?php
session_start();
include "config/koneksi.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}
/* CEK PREMIUM — tambah resep hanya untuk member premium */
$uid_cek = intval($_SESSION['user_id']);
$isPremium = false;

$cekPremium = mysqli_query($conn,"
    SELECT * FROM premium_users
    WHERE user_id='$uid_cek'
    AND aktif_sampai >= CURDATE()
");

if(mysqli_num_rows($cekPremium) > 0){
    $isPremium = true;
}

/* Kalau bukan premium, tendang ke halaman premium */
if(!$isPremium){
    header("Location: premium.php?need_premium=1");
    exit;
}
$error = "";
$sukses = "";

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $nama        = mysqli_real_escape_string($conn, trim($_POST['nama'] ?? ''));
    $kategori    = mysqli_real_escape_string($conn, $_POST['kategori'] ?? '');
    $bahan_utama = mysqli_real_escape_string($conn, trim($_POST['bahan_utama'] ?? ''));
    $gambar      = mysqli_real_escape_string($conn, trim($_POST['gambar'] ?? ''));

    $kalori      = (int)($_POST['kalori'] ?? 0);
    $protein     = (float)($_POST['protein'] ?? 0);
    $karbohidrat = (float)($_POST['karbohidrat'] ?? 0);
    $lemak       = (float)($_POST['lemak'] ?? 0);
    $gula        = (float)($_POST['gula'] ?? 0);
    $serat       = (float)($_POST['serat'] ?? 0);
    $sodium      = (float)($_POST['sodium'] ?? 0);

    $bahan_list   = $_POST['bahan_list'] ?? '';

    $uid = (int)$_SESSION['user_id'];

    if($nama === '' || $kategori === '' || $bahan_utama === ''){
        $error = "Nama, kategori, dan bahan utama wajib diisi!";
    } else {

        $insert = mysqli_query($conn,"
            INSERT INTO menus
            (nama,bahan,kategori_menu,kalori,protein,karbohidrat,lemak,gula,serat,sodium,gambar,created_by,is_premium,status)
            VALUES
            ('$nama','$bahan_utama','$kategori',$kalori,$protein,$karbohidrat,$lemak,$gula,$serat,$sodium,'$gambar',$uid,0,'pending')
        ");

        if($insert){

            $menu_id = mysqli_insert_id($conn);

            // insert bahan
            if(!empty($bahan_list)){
                $bahanArr = array_filter(array_map('trim', explode("\n", $bahan_list)));
                foreach($bahanArr as $b){
                    $b = mysqli_real_escape_string($conn, $b);
                    mysqli_query($conn,"INSERT INTO bahan_menu(menu_id,nama_bahan) VALUES ($menu_id,'$b')");
                }
            }

            // insert langkah (step by step, sama seperti form admin)
            if(!empty($_POST['step_deskripsi'])){
                foreach($_POST['step_deskripsi'] as $i => $desc){
                    $desc = mysqli_real_escape_string($conn, trim($desc));
                    $img  = mysqli_real_escape_string($conn, trim($_POST['step_gambar'][$i] ?? ''));
                    $vid  = mysqli_real_escape_string($conn, trim($_POST['step_video'][$i] ?? ''));

                    if($desc !== ''){
                        $stepKe = $i + 1;
                        mysqli_query($conn,"
                            INSERT INTO langkah_menu(menu_id,step_ke,deskripsi,gambar_step,video_step)
                            VALUES ($menu_id,$stepKe,'$desc','$img','$vid')
                        ");
                    }
                }
            }

            $sukses = "Resep berhasil ditambahkan! Resep kamu akan tampil setelah divalidasi oleh admin.";
        } else {
            $error = "Gagal menambah resep!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tambah Resep | Foodies</title>
<meta name="viewport" content="width=device-width,initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --cream:#F9F5EF;
  --dark:#1C1811;
  --warm:#A0522D;
  --accent:#C8863C;
  --muted:#7A6E62;
  --white:#fff;
  --card:#FFFDF9;
  --border:rgba(160,82,45,0.12);
  --green:#2E7D52;
  --red:#C0392B;
}

body{
  font-family:'DM Sans',sans-serif;
  background:var(--cream);
  color:var(--dark);
}

/* layout */
.container{
  max-width:1000px;
  margin:40px auto;
  padding:0 20px 80px;
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

.subtitle{
  font-size:13px;
  color:var(--muted);
  margin-top:4px;
}

.back{
  text-decoration:none;
  padding:10px 16px;
  border:1px solid var(--border);
  border-radius:100px;
  color:var(--muted);
  background:var(--white);
  font-size:13px;
  transition:all .2s;
}
.back:hover{border-color:var(--accent);color:var(--accent)}

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
  margin-bottom:20px;
  font-size:14px;
}
.success{
  background:rgba(46,125,82,0.1);
  color:var(--green);
  border:1px solid rgba(46,125,82,0.2);
}
.error{
  background:rgba(192,57,43,0.1);
  color:var(--red);
  border:1px solid rgba(192,57,43,0.2);
}

/* form */
.form-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:16px;
}
.form-grid.three{grid-template-columns:1fr 1fr 1fr}

.full{
  grid-column:1/-1;
}

.form-group{display:flex;flex-direction:column;gap:6px}

label{
  font-size:11px;
  letter-spacing:1.5px;
  text-transform:uppercase;
  color:var(--muted);
  margin-bottom:2px;
  display:block;
  font-weight:500;
}

input[type=text],input[type=number],select,textarea{
  width:100%;
  padding:12px 14px;
  border-radius:10px;
  border:1px solid var(--border);
  background:var(--cream);
  font-family:inherit;
  font-size:14px;
  color:var(--dark);
  outline:none;
}

textarea{
  min-height:100px;
  resize:vertical;
}

input:focus,select:focus,textarea:focus{
  border-color:var(--accent);
}

.gambar-preview{
  width:70px;height:70px;border-radius:10px;object-fit:cover;
  border:1px solid var(--border);display:none;margin-top:8px;
}

/* divider */
.divider-label{
  font-size:10px;
  font-weight:600;
  letter-spacing:2px;
  text-transform:uppercase;
  color:var(--accent);
  margin:24px 0 12px;
  padding-bottom:8px;
  border-bottom:1px solid var(--border);
}

/* step box (sama seperti panel admin) */
.step-box{
    padding:20px;
    border:1px solid var(--border);
    border-radius:16px;
    margin-bottom:16px;
    background:rgba(255,255,255,0.5);
}

.btn-add-step{
    margin-top:6px;
    padding:10px 18px;
    border:none;
    border-radius:100px;
    background:var(--accent);
    color:white;
    cursor:pointer;
    font-size:13px;
    font-weight:500;
}
.btn-add-step:hover{background:var(--warm)}

.btn-remove-step{
    margin-top:12px;
    padding:6px 14px;
    border:1px solid var(--border);
    border-radius:100px;
    background:none;
    color:var(--red);
    cursor:pointer;
    font-size:12px;
}
.btn-remove-step:hover{border-color:var(--red);background:rgba(192,57,43,0.06)}

/* button */
.btn{
  margin-top:24px;
  padding:12px 26px;
  border:none;
  border-radius:100px;
  background:var(--dark);
  color:white;
  cursor:pointer;
  font-weight:500;
  font-size:14px;
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
    <div>
      <div class="title">Tambah Resep 🍽</div>
      <div class="subtitle">Resep kamu akan tampil ke publik setelah divalidasi oleh admin.</div>
    </div>
    <a class="back" href="index.php">← Kembali</a>
  </div>

  <?php if($sukses): ?>
    <div class="alert success"><?= htmlspecialchars($sukses) ?></div>
  <?php endif; ?>

  <?php if($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card">

    <form method="POST">

      <div class="form-grid">

        <div class="form-group">
          <label>Nama Resep</label>
          <input type="text" name="nama" required placeholder="cth: Nasi Goreng Spesial">
        </div>

        <div class="form-group">
          <label>Bahan Utama</label>
          <input type="text" name="bahan_utama" required placeholder="cth: ayam">
        </div>

        <div class="form-group">
          <label>Kategori</label>
          <select name="kategori" required>
            <option value="">— Pilih —</option>
            <option value="utama">Utama</option>
            <option value="dessert">Dessert</option>
            <option value="diet">Diet</option>
            <option value="tradisional">Tradisional</option>
            <option value="internasional">Internasional</option>
            <option value="instant">Instant</option>
          </select>
        </div>

        <div class="form-grid three full">

          <div class="form-group">
            <label>Kalori (kkal)</label>
            <input type="number" name="kalori" required placeholder="350">
          </div>

          <div class="form-group">
            <label>Protein (g)</label>
            <input type="number" step="0.1" name="protein" placeholder="18">
          </div>

          <div class="form-group">
            <label>Karbohidrat (g)</label>
            <input type="number" step="0.1" name="karbohidrat" placeholder="42">
          </div>

          <div class="form-group">
            <label>Lemak (g)</label>
            <input type="number" step="0.1" name="lemak" placeholder="12">
          </div>

          <div class="form-group">
            <label>Gula (g)</label>
            <input type="number" step="0.1" name="gula" placeholder="5">
          </div>

          <div class="form-group">
            <label>Serat (g)</label>
            <input type="number" step="0.1" name="serat" placeholder="4">
          </div>

          <div class="form-group">
            <label>Sodium / Garam (mg)</label>
            <input type="number" step="0.1" name="sodium" placeholder="220">
          </div>

        </div>

        <div class="form-group full">
          <label>URL Gambar (paste dari Google Image → Copy Image Address)</label>
          <input type="text" name="gambar" id="add-gambar-url" placeholder="https://..." oninput="previewImg(this,'add-gambar-prev')">
          <img id="add-gambar-prev" class="gambar-preview">
        </div>

      </div>

      <div class="divider-label">Bahan-bahan (satu per baris)</div>
      <textarea name="bahan_list" placeholder="Beras 200g&#10;Telur 2 butir&#10;Kecap manis 2 sdm"></textarea>

      <div class="divider-label">Step By Step Memasak</div>
      <div id="steps-container">

        <div class="step-box">
          <div class="form-group full">
            <label>Deskripsi Langkah</label>
            <textarea name="step_deskripsi[]" rows="3" placeholder="cth: Panaskan minyak lalu tumis bawang"></textarea>
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label>Gambar Step (URL)</label>
              <input type="text" name="step_gambar[]" placeholder="https://gambar.com/step1.jpg">
            </div>

            <div class="form-group">
              <label>Video Pendek (URL)</label>
              <input type="text" name="step_video[]" placeholder="https://video.com/video.mp4">
            </div>
          </div>
        </div>

      </div>

      <button type="button" class="btn-add-step" onclick="addStep()">＋ Tambah Step</button>

      <div>
        <button class="btn" type="submit">+ Tambah Resep</button>
      </div>

    </form>

  </div>

</div>

<script>
function previewImg(input, imgId) {
  const img = document.getElementById(imgId);
  if(input.value.trim()) {
    img.src = input.value.trim();
    img.style.display = 'block';
    img.onerror = () => img.style.display = 'none';
  } else {
    img.style.display = 'none';
  }
}

function addStep(){
  const html = `
  <div class="step-box">

      <div class="form-group full">
          <label>Deskripsi Langkah</label>
          <textarea name="step_deskripsi[]" rows="3"
          placeholder="cth: Masukkan nasi lalu aduk rata"></textarea>
      </div>

      <div class="form-grid">

          <div class="form-group">
              <label>Gambar Step (URL)</label>
              <input type="text"
              name="step_gambar[]"
              placeholder="https://gambar.com/step.jpg">
          </div>

          <div class="form-group">
              <label>Video Pendek (URL)</label>
              <input type="text"
              name="step_video[]"
              placeholder="https://video.com/video.mp4">
          </div>

      </div>

      <button type="button" class="btn-remove-step" onclick="this.closest('.step-box').remove()">🗑 Hapus Step Ini</button>

  </div>
  `;

  document
  .getElementById('steps-container')
  .insertAdjacentHTML('beforeend', html);
}
</script>

</body>
</html>
