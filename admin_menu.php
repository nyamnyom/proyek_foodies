<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit;
}
include "config/koneksi.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: admin_index.php'); exit; }

// Handle POST actions from this page too
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'edit_menu') {
        $mid    = (int)$_POST['menu_id'];
        $nama   = mysqli_real_escape_string($conn, $_POST['nama']);
        $bahan  = mysqli_real_escape_string($conn, $_POST['bahan_utama']);
        $kat    = mysqli_real_escape_string($conn, $_POST['kategori']);
        $kalori = (int)$_POST['kalori'];
        $gambar = mysqli_real_escape_string($conn, $_POST['gambar']);
        mysqli_query($conn, "UPDATE menus SET nama='$nama',bahan='$bahan',kategori_menu='$kat',kalori=$kalori,gambar='$gambar' WHERE id=$mid");
        mysqli_query($conn, "DELETE FROM bahan_menu WHERE menu_id=$mid");
        mysqli_query($conn, "DELETE FROM langkah_menu WHERE menu_id=$mid");
        if (!empty($_POST['bahan_list'])) {
            foreach (array_filter(array_map('trim', explode("\n", $_POST['bahan_list']))) as $b) {
                $b = mysqli_real_escape_string($conn, $b);
                mysqli_query($conn, "INSERT INTO bahan_menu (menu_id,nama_bahan) VALUES ($mid,'$b')");
            }
        }
        if (!empty($_POST['langkah_list'])) {
            $steps = array_filter(array_map('trim', explode("\n", $_POST['langkah_list'])));
            $sk = 1;
            foreach ($steps as $s) {
                $s = mysqli_real_escape_string($conn, $s);
                mysqli_query($conn, "INSERT INTO langkah_menu (menu_id,step_ke,deskripsi) VALUES ($mid,$sk,'$s')");
                $sk++;
            }
        }
        header("Location: admin_menu.php?id=$mid&msg=edit_ok"); exit;
    }
    if (isset($_POST['action']) && $_POST['action'] === 'del_review') {
        $rid = (int)$_POST['review_id'];
        mysqli_query($conn, "DELETE FROM rating WHERE id=$rid AND menu_id=$id");
        header("Location: admin_menu.php?id=$id&msg=del_ok"); exit;
    }
}

$menu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM menus WHERE id=$id"));
if (!$menu) { header('Location: admin_index.php'); exit; }

$r_bahan   = mysqli_query($conn, "SELECT * FROM bahan_menu WHERE menu_id=$id ORDER BY id");
$r_langkah = mysqli_query($conn, "SELECT * FROM langkah_menu WHERE menu_id=$id ORDER BY step_ke");
$r_review  = mysqli_query($conn, "SELECT r.*, u.nama as user_nama, u.email FROM rating r JOIN users u ON r.user_id=u.id WHERE r.menu_id=$id ORDER BY r.created_at DESC");
$total_review = mysqli_num_rows($r_review);
$avg_q = mysqli_fetch_row(mysqli_query($conn, "SELECT AVG(nilai) FROM rating WHERE menu_id=$id"));
$avg_rating = $avg_q[0] ? round($avg_q[0],1) : 0;

// Build multiline strings for edit form
$bahan_arr = []; $res = mysqli_query($conn,"SELECT nama_bahan FROM bahan_menu WHERE menu_id=$id ORDER BY id");
while($row = mysqli_fetch_row($res)) $bahan_arr[] = $row[0];
$bahan_str = implode("\n",$bahan_arr);

$lang_arr = []; $res2 = mysqli_query($conn,"SELECT deskripsi FROM langkah_menu WHERE menu_id=$id ORDER BY step_ke");
while($row = mysqli_fetch_row($res2)) $lang_arr[] = $row[0];
$lang_str = implode("\n",$lang_arr);

$flash = $_GET['msg'] ?? '';
if (isset($_GET['logout'])) { session_destroy(); header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Detail Menu — <?= htmlspecialchars($menu['nama']) ?> | Foodies Admin</title>
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --cream:#F9F5EF;--dark:#1C1811;--warm:#A0522D;--accent:#C8863C;
  --muted:#7A6E62;--white:#FFFFFF;--card-bg:#FFFDF9;
  --border:rgba(160,82,45,0.10);--sidebar:#16130F;
  --green:#2E7D52;--red:#C0392B;
}
html{scroll-behavior:smooth}
body{font-family:'DM Sans',sans-serif;background:var(--cream);color:var(--dark);display:flex;min-height:100vh}
.sidebar{width:260px;background:var(--sidebar);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100}
.sidebar-logo{display:flex;align-items:center;gap:12px;padding:28px 28px 24px;border-bottom:1px solid rgba(255,255,255,0.06);text-decoration:none}
.sidebar-logo img{width:26px;opacity:.85}
.sidebar-logo span{font-family:'Playfair Display',serif;font-size:20px;color:var(--white);letter-spacing:-.3px}
.sidebar-label{font-size:10px;font-weight:500;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.25);padding:24px 28px 10px}
.sidebar-nav{display:flex;flex-direction:column;gap:2px;padding:0 16px;flex:1}
.sidebar-item{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;color:rgba(255,255,255,0.50);text-decoration:none;font-size:14px;font-weight:400;transition:background .2s,color .2s}
.sidebar-item:hover,.sidebar-item.active{background:rgba(200,134,60,0.12);color:var(--white)}
.sidebar-item.active{color:var(--accent)}
.sidebar-icon{font-size:16px;width:20px;text-align:center}
.sidebar-bottom{padding:16px;border-top:1px solid rgba(255,255,255,0.06)}
.sidebar-profile{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;background:rgba(255,255,255,0.04)}
.avatar{width:36px;height:36px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:14px;color:var(--white);font-weight:700;flex-shrink:0}
.profile-info{flex:1;min-width:0}
.profile-name{font-size:13px;font-weight:500;color:var(--white);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.profile-role{font-size:11px;color:rgba(255,255,255,0.35);letter-spacing:.5px}
.logout-btn{background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.3);font-size:16px;padding:4px;transition:color .2s;text-decoration:none}
.logout-btn:hover{color:#E74C3C}
.main{margin-left:260px;flex:1;display:flex;flex-direction:column;min-height:100vh}
.topbar{background:rgba(255,253,249,0.92);backdrop-filter:blur(12px);padding:18px 40px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:50}
.topbar-left{display:flex;align-items:center;gap:14px}
.back-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:100px;border:1px solid var(--border);background:var(--white);font-size:13px;color:var(--muted);text-decoration:none;transition:all .2s}
.back-btn:hover{border-color:var(--accent);color:var(--accent)}
.topbar-title h2{font-family:'Playfair Display',serif;font-size:20px;color:var(--dark)}
.topbar-title p{font-size:12px;color:var(--muted);margin-top:1px}
.badge-admin{padding:5px 14px;border-radius:100px;background:rgba(200,134,60,0.12);border:1px solid rgba(200,134,60,0.2);font-size:11px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;color:var(--accent)}
.content{padding:36px 40px 80px}
.flash{padding:12px 20px;border-radius:12px;font-size:13px;font-weight:500;margin-bottom:24px;display:flex;align-items:center;gap:10px;transition:opacity .4s}
.flash.ok{background:rgba(46,125,82,0.10);color:var(--green);border:1px solid rgba(46,125,82,0.2)}

/* HERO */
.hero-grid{display:grid;grid-template-columns:340px 1fr;gap:28px;margin-bottom:32px}
.hero-img-wrap{border-radius:20px;overflow:hidden;aspect-ratio:4/3;background:var(--cream);border:1px solid var(--border)}
.hero-img-wrap img{width:100%;height:100%;object-fit:cover}
.hero-img-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:64px}
.hero-info{display:flex;flex-direction:column;gap:16px}
.meta-card{background:var(--card-bg);border-radius:18px;border:1px solid var(--border);padding:24px}
.meta-card h1{font-family:'Playfair Display',serif;font-size:28px;font-weight:700;color:var(--dark);margin-bottom:8px}
.pill{padding:4px 12px;border-radius:100px;font-size:11px;font-weight:500;letter-spacing:.5px;display:inline-block}
.pill-cat{background:rgba(160,82,45,0.10);color:var(--warm)}
.meta-row{display:flex;gap:24px;margin-top:16px;flex-wrap:wrap}
.meta-item{display:flex;flex-direction:column;gap:3px}
.meta-item .label{font-size:10px;font-weight:500;letter-spacing:1px;text-transform:uppercase;color:var(--muted)}
.meta-item .val{font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:var(--dark)}
.stars{color:#f0a500;font-size:18px;letter-spacing:2px}

/* SECTION */
.sec-label{font-size:10px;font-weight:600;letter-spacing:3px;text-transform:uppercase;color:var(--accent);margin-bottom:16px;display:flex;align-items:center;gap:10px}
.sec-label::after{content:'';flex:1;height:1px;background:var(--border)}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px}
.card{background:var(--card-bg);border-radius:18px;border:1px solid var(--border);padding:24px}
.bahan-list{list-style:none;display:flex;flex-direction:column;gap:10px}
.bahan-list li{display:flex;align-items:center;gap:10px;font-size:14px;color:var(--dark)}
.bahan-list li::before{content:'';width:7px;height:7px;border-radius:50%;background:var(--accent);flex-shrink:0}
.step-list{list-style:none;display:flex;flex-direction:column;gap:14px}
.step-item{display:flex;gap:14px;align-items:flex-start}
.step-num{width:28px;height:28px;border-radius:50%;background:var(--dark);color:var(--white);font-family:'Playfair Display',serif;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px}
.step-text{font-size:14px;color:var(--dark);line-height:1.6;padding-top:4px}

/* REVIEWS */
.review-card{background:var(--card-bg);border-radius:18px;border:1px solid var(--border);padding:20px;display:flex;gap:16px;align-items:flex-start;transition:border-color .2s}
.review-card:hover{border-color:rgba(200,134,60,0.3)}
.rev-avatar{width:38px;height:38px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:14px;color:var(--white);font-weight:700;flex-shrink:0}
.rev-body{flex:1}
.rev-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;flex-wrap:wrap;gap:8px}
.rev-name{font-weight:500;font-size:14px}
.rev-email{font-size:12px;color:var(--muted)}
.rev-stars{color:#f0a500;font-size:14px}
.rev-date{font-size:11px;color:var(--muted)}
.rev-text{font-size:14px;color:var(--dark);line-height:1.6;margin-top:6px}
.del-btn{background:none;border:1px solid var(--border);border-radius:8px;padding:5px 12px;font-size:12px;color:var(--red);cursor:pointer;transition:all .2s;font-family:'DM Sans',sans-serif}
.del-btn:hover{background:rgba(192,57,43,0.08);border-color:var(--red)}
.empty-state{text-align:center;padding:40px;color:var(--muted);font-size:14px}

/* EDIT FORM */
.edit-panel{overflow:hidden;max-height:0;transition:max-height .5s cubic-bezier(0.23,1,0.32,1),margin .4s;margin-bottom:0}
.edit-panel.open{max-height:2000px;margin-bottom:32px}
.edit-form-card{background:var(--card-bg);border-radius:18px;border:1px solid var(--accent);padding:28px 32px}
.edit-form-card h4{font-family:'Playfair Display',serif;font-size:18px;margin-bottom:20px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-group.full{grid-column:1/-1}
label{font-size:11px;font-weight:500;letter-spacing:1px;text-transform:uppercase;color:var(--muted)}
input[type=text],input[type=number],select,textarea{width:100%;padding:10px 14px;border-radius:10px;border:1px solid var(--border);background:var(--cream);font-family:'DM Sans',sans-serif;font-size:14px;color:var(--dark);outline:none;transition:border .2s}
input:focus,select:focus,textarea:focus{border-color:var(--accent)}
textarea{resize:vertical;min-height:90px}
.gambar-preview{width:60px;height:60px;border-radius:10px;object-fit:cover;border:1px solid var(--border);display:none}
.form-actions{display:flex;gap:12px;align-items:center;margin-top:20px}
.btn-save{padding:10px 28px;border-radius:100px;background:var(--dark);color:var(--white);border:none;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;cursor:pointer;transition:background .2s}
.btn-save:hover{background:var(--warm)}
.btn-cancel{padding:10px 22px;border-radius:100px;background:none;border:1px solid var(--border);font-family:'DM Sans',sans-serif;font-size:13px;color:var(--muted);cursor:pointer;transition:all .2s}
.btn-cancel:hover{border-color:var(--dark);color:var(--dark)}
.divider-label{font-size:10px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--accent);margin:20px 0 12px;padding-bottom:8px;border-bottom:1px solid var(--border)}
.btn-edit-main{display:inline-flex;align-items:center;gap:8px;padding:10px 22px;border-radius:100px;background:var(--dark);color:var(--white);border:none;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;cursor:pointer;transition:background .2s}
.btn-edit-main:hover{background:var(--warm)}
.reviews-grid{display:flex;flex-direction:column;gap:14px}
</style>
</head>
<body>

<aside class="sidebar">
  <a href="admin_index.php" class="sidebar-logo">
    <img src="https://cdn-icons-png.flaticon.com/512/3075/3075977.png">
    <span>Foodies</span>
  </a>
  <p class="sidebar-label">Navigasi</p>
  <nav class="sidebar-nav">
    <a href="admin_index.php" class="sidebar-item active"><span class="sidebar-icon">◈</span> Dashboard</a>
    <a href="index.php" class="sidebar-item"><span class="sidebar-icon">🌐</span> Lihat Situs</a>
  </nav>
  <div class="sidebar-bottom">
    <div class="sidebar-profile">
      <div class="avatar"><?= strtoupper(substr($_SESSION['nama'],0,1)) ?></div>
      <div class="profile-info">
        <div class="profile-name"><?= htmlspecialchars($_SESSION['nama']) ?></div>
        <div class="profile-role">Administrator</div>
      </div>
      <a href="?logout=1" class="logout-btn" title="Logout">⏻</a>
    </div>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <div class="topbar-left">
      <a href="admin_index.php?tab=menu" class="back-btn">← Kembali</a>
      <div class="topbar-title">
        <h2><?= htmlspecialchars($menu['nama']) ?></h2>
        <p>Detail Resep Menu · ID #<?= $menu['id'] ?></p>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:12px">
      <button class="btn-edit-main" onclick="toggleEdit()">✏ Edit Menu Ini</button>
      <span class="badge-admin">Admin</span>
    </div>
  </div>

  <div class="content">
    <?php if ($flash): ?>
    <div class="flash ok" id="flash">✓ <?= $flash==='edit_ok'?'Menu berhasil diperbarui.':'Review berhasil dihapus.' ?></div>
    <?php endif; ?>

    <!-- EDIT PANEL -->
    <div class="edit-panel" id="edit-panel">
      <div class="edit-form-card">
        <h4>✏ Edit Menu: <?= htmlspecialchars($menu['nama']) ?></h4>
        <form method="POST">
          <input type="hidden" name="action" value="edit_menu">
          <input type="hidden" name="menu_id" value="<?= $menu['id'] ?>">
          <div class="form-grid">
            <div class="form-group">
              <label>Nama Menu</label>
              <input type="text" name="nama" value="<?= htmlspecialchars($menu['nama']) ?>" required>
            </div>
            <div class="form-group">
              <label>Bahan Utama</label>
              <input type="text" name="bahan_utama" value="<?= htmlspecialchars($menu['bahan']) ?>">
            </div>
            <div class="form-group">
              <label>Kategori</label>
              <select name="kategori">
                <?php foreach(['utama','diet','dessert','tradisional','internasional','instant'] as $opt): ?>
                <option value="<?= $opt ?>" <?= $menu['kategori_menu']===$opt?'selected':'' ?>><?= ucfirst($opt) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Kalori (kkal)</label>
              <input type="number" name="kalori" value="<?= $menu['kalori'] ?>">
            </div>
            <div class="form-group full">
              <label>URL Gambar (Copy Image Address dari Google)</label>
              <input type="text" name="gambar" value="<?= htmlspecialchars($menu['gambar'] ?? '') ?>" id="edit-gambar-url" oninput="previewImg(this,'edit-gambar-prev')" placeholder="https://...">
              <img id="edit-gambar-prev" class="gambar-preview" src="<?= htmlspecialchars($menu['gambar'] ?? '') ?>" style="<?= !empty($menu['gambar'])?'display:block':'display:none' ?>;margin-top:8px">
            </div>
          </div>
          <div class="divider-label">Bahan-bahan (satu per baris)</div>
          <textarea name="bahan_list" rows="6"><?= htmlspecialchars($bahan_str) ?></textarea>
          <div class="divider-label">Langkah Memasak (satu langkah per baris)</div>
          <textarea name="langkah_list" rows="7"><?= htmlspecialchars($lang_str) ?></textarea>
          <div class="form-actions">
            <button type="submit" class="btn-save">Simpan Perubahan</button>
            <button type="button" class="btn-cancel" onclick="toggleEdit()">Batal</button>
          </div>
        </form>
      </div>
    </div>

    <!-- HERO -->
    <div class="hero-grid">
      <div class="hero-img-wrap">
        <?php if (!empty($menu['gambar'])): ?>
        <img src="<?= htmlspecialchars($menu['gambar']) ?>" alt="<?= htmlspecialchars($menu['nama']) ?>">
        <?php else: ?>
        <div class="hero-img-placeholder">🍽</div>
        <?php endif; ?>
      </div>
      <div class="hero-info">
        <div class="meta-card">
          <h1><?= htmlspecialchars($menu['nama']) ?></h1>
          <span class="pill pill-cat"><?= ucfirst($menu['kategori_menu']) ?></span>
          <div class="meta-row">
            <div class="meta-item">
              <span class="label">Kalori</span>
              <span class="val"><?= $menu['kalori'] ?> <span style="font-size:14px;color:var(--muted)">kkal</span></span>
            </div>
            <div class="meta-item">
              <span class="label">Bahan Utama</span>
              <span class="val" style="font-size:18px;text-transform:capitalize"><?= htmlspecialchars($menu['bahan']) ?></span>
            </div>
            <div class="meta-item">
              <span class="label">Rating</span>
              <span class="val"><?= $avg_rating > 0 ? $avg_rating : '—' ?> <span style="font-size:14px;color:var(--muted)">/5</span></span>
            </div>
            <div class="meta-item">
              <span class="label">Review</span>
              <span class="val"><?= $total_review ?></span>
            </div>
          </div>
          <?php if($avg_rating > 0): ?>
          <div class="stars" style="margin-top:14px">
            <?php for($i=1;$i<=5;$i++) echo $i<=$avg_rating ? '★' : '☆'; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- BAHAN & LANGKAH -->
    <div class="two-col">
      <div>
        <div class="sec-label">Bahan-bahan</div>
        <div class="card">
          <?php
          mysqli_data_seek($r_bahan,0);
          $has_b = false;
          ?>
          <ul class="bahan-list">
          <?php while($b = mysqli_fetch_assoc($r_bahan)): $has_b=true; ?>
            <li><?= htmlspecialchars($b['nama_bahan']) ?></li>
          <?php endwhile; ?>
          <?php if(!$has_b): ?><li style="color:var(--muted);font-style:italic">Belum ada bahan.</li><?php endif; ?>
          </ul>
        </div>
      </div>
      <div>
        <div class="sec-label">Langkah Memasak</div>
        <div class="card">
          <?php
          mysqli_data_seek($r_langkah,0);
          $has_l = false;
          ?>
          <ol class="step-list" style="list-style:none">
          <?php while($l = mysqli_fetch_assoc($r_langkah)): $has_l=true; ?>
            <li class="step-item">
              <div class="step-num"><?= $l['step_ke'] ?></div>
              <div class="step-text"><?= htmlspecialchars($l['deskripsi']) ?></div>
            </li>
          <?php endwhile; ?>
          <?php if(!$has_l): ?><li style="color:var(--muted);font-style:italic">Belum ada langkah.</li><?php endif; ?>
          </ol>
        </div>
      </div>
    </div>

    <!-- REVIEWS -->
    <div class="sec-label">Review Pengguna (<?= $total_review ?>)</div>
    <div class="reviews-grid">
      <?php
      mysqli_data_seek($r_review,0);
      $has_r = false;
      while($rv = mysqli_fetch_assoc($r_review)): $has_r=true;
        $stars = str_repeat('★',$rv['nilai']) . str_repeat('☆',5-$rv['nilai']);
      ?>
      <div class="review-card">
        <div class="rev-avatar"><?= strtoupper(substr($rv['user_nama'],0,1)) ?></div>
        <div class="rev-body">
          <div class="rev-header">
            <div>
              <div class="rev-name"><?= htmlspecialchars($rv['user_nama']) ?> <span class="rev-email">(<?= htmlspecialchars($rv['email']) ?>)</span></div>
              <div style="display:flex;align-items:center;gap:10px;margin-top:3px">
                <span class="rev-stars"><?= $stars ?></span>
                <span class="rev-date"><?= date('d M Y, H:i', strtotime($rv['created_at'])) ?></span>
              </div>
            </div>
            <form method="POST" onsubmit="return confirm('Hapus review ini?')">
              <input type="hidden" name="action" value="del_review">
              <input type="hidden" name="review_id" value="<?= $rv['id'] ?>">
              <button type="submit" class="del-btn">🗑 Hapus</button>
            </form>
          </div>
          <?php if(!empty($rv['review'])): ?>
          <div class="rev-text"><?= htmlspecialchars($rv['review']) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endwhile; ?>
      <?php if(!$has_r): ?>
      <div class="empty-state">Belum ada review untuk menu ini.</div>
      <?php endif; ?>
    </div>

  </div>
</div>

<script>
function toggleEdit() {
  const p = document.getElementById('edit-panel');
  p.classList.toggle('open');
  if(p.classList.contains('open')) setTimeout(()=>p.scrollIntoView({behavior:'smooth',block:'start'}),100);
}
function previewImg(input, imgId) {
  const img = document.getElementById(imgId);
  if(input.value.trim()) {
    img.src=input.value.trim(); img.style.display='block';
    img.onerror=()=>img.style.display='none';
  } else img.style.display='none';
}
setTimeout(()=>{const f=document.getElementById('flash');if(f){f.style.opacity='0';setTimeout(()=>f.remove(),400);}},3500);
</script>
</body>
</html>