<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit;
}
include "config/koneksi.php";

$msg = '';

/* ── CRUD Menu ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ADD Menu
    if (isset($_POST['action']) && $_POST['action'] === 'add_menu') {
        $nama   = mysqli_real_escape_string($conn, $_POST['nama']);
        $bahan  = mysqli_real_escape_string($conn, $_POST['bahan_utama']);
        $kat    = mysqli_real_escape_string($conn, $_POST['kategori']);
        $kalori = (int)$_POST['kalori'];
        $gambar = mysqli_real_escape_string($conn, $_POST['gambar']);
        mysqli_query($conn, "INSERT INTO menus (nama,bahan,kategori_menu,kalori,gambar) VALUES ('$nama','$bahan','$kat',$kalori,'$gambar')");
        $new_id = mysqli_insert_id($conn);
        // bahan
        if (!empty($_POST['bahan_list'])) {
            foreach (array_filter(array_map('trim', explode("\n", $_POST['bahan_list']))) as $b) {
                $b = mysqli_real_escape_string($conn, $b);
                mysqli_query($conn, "INSERT INTO bahan_menu (menu_id,nama_bahan) VALUES ($new_id,'$b')");
            }
        }
        // langkah
        if (!empty($_POST['langkah_list'])) {
            $steps = array_filter(array_map('trim', explode("\n", $_POST['langkah_list'])));
            $step_ke = 1;
            foreach ($steps as $s) {
                $s = mysqli_real_escape_string($conn, $s);
                mysqli_query($conn, "INSERT INTO langkah_menu (menu_id,step_ke,deskripsi) VALUES ($new_id,$step_ke,'$s')");
                $step_ke++;
            }
        }
        $msg = 'success|Menu berhasil ditambahkan!';
        header("Location: admin_index.php?tab=menu&msg=add_ok"); exit;
    }

    // EDIT Menu
    if (isset($_POST['action']) && $_POST['action'] === 'edit_menu') {
        $id     = (int)$_POST['menu_id'];
        $nama   = mysqli_real_escape_string($conn, $_POST['nama']);
        $bahan  = mysqli_real_escape_string($conn, $_POST['bahan_utama']);
        $kat    = mysqli_real_escape_string($conn, $_POST['kategori']);
        $kalori = (int)$_POST['kalori'];
        $gambar = mysqli_real_escape_string($conn, $_POST['gambar']);
        mysqli_query($conn, "UPDATE menus SET nama='$nama',bahan='$bahan',kategori_menu='$kat',kalori=$kalori,gambar='$gambar' WHERE id=$id");
        // reset bahan & langkah
        mysqli_query($conn, "DELETE FROM bahan_menu WHERE menu_id=$id");
        mysqli_query($conn, "DELETE FROM langkah_menu WHERE menu_id=$id");
        if (!empty($_POST['bahan_list'])) {
            foreach (array_filter(array_map('trim', explode("\n", $_POST['bahan_list']))) as $b) {
                $b = mysqli_real_escape_string($conn, $b);
                mysqli_query($conn, "INSERT INTO bahan_menu (menu_id,nama_bahan) VALUES ($id,'$b')");
            }
        }
        if (!empty($_POST['langkah_list'])) {
            $steps = array_filter(array_map('trim', explode("\n", $_POST['langkah_list'])));
            $step_ke = 1;
            foreach ($steps as $s) {
                $s = mysqli_real_escape_string($conn, $s);
                mysqli_query($conn, "INSERT INTO langkah_menu (menu_id,step_ke,deskripsi) VALUES ($id,$step_ke,'$s')");
                $step_ke++;
            }
        }
        header("Location: admin_index.php?tab=menu&msg=edit_ok"); exit;
    }

    // DELETE Menu
    if (isset($_POST['action']) && $_POST['action'] === 'del_menu') {
        $id = (int)$_POST['menu_id'];
        mysqli_query($conn, "DELETE FROM menus WHERE id=$id");
        header("Location: admin_index.php?tab=menu&msg=del_ok"); exit;
    }

    // ADD User
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $nama   = mysqli_real_escape_string($conn, $_POST['nama']);
        $email  = mysqli_real_escape_string($conn, $_POST['email']);
        $pass   = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role   = $_POST['role'] === 'admin' ? 'admin' : 'user';
        mysqli_query($conn, "INSERT INTO users (nama,email,password,role) VALUES ('$nama','$email','$pass','$role')");
        header("Location: admin_index.php?tab=user&msg=add_ok"); exit;
    }

    // EDIT User
    if (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
        $id   = (int)$_POST['user_id'];
        $nama  = mysqli_real_escape_string($conn, $_POST['nama']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $role  = $_POST['role'] === 'admin' ? 'admin' : 'user';
        $sql = "UPDATE users SET nama='$nama',email='$email',role='$role'";
        if (!empty($_POST['password'])) {
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql .= ",password='$pass'";
        }
        $sql .= " WHERE id=$id";
        mysqli_query($conn, $sql);
        header("Location: admin_index.php?tab=user&msg=edit_ok"); exit;
    }

    // DELETE User
    if (isset($_POST['action']) && $_POST['action'] === 'del_user') {
        $id = (int)$_POST['user_id'];
        mysqli_query($conn, "DELETE FROM users WHERE id=$id");
        header("Location: admin_index.php?tab=user&msg=del_ok"); exit;
    }
}

/* ── Stats ── */
$total_menu    = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM menus"))[0];
$total_user    = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM users WHERE role='user'"))[0];
$kalori_tinggi = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM menus WHERE kalori>=400"))[0];
$kalori_rendah = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM menus WHERE kalori<400"))[0];
$total_review  = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM rating"))[0];

$r_menus = mysqli_query($conn,"SELECT * FROM menus ORDER BY id ASC");
$r_users = mysqli_query($conn,"SELECT * FROM users ORDER BY created_at DESC");

$active_tab = $_GET['tab'] ?? 'menu';
$flash_msg  = $_GET['msg'] ?? '';

if (isset($_GET['logout'])) { session_destroy(); header('Location: login.php'); exit; }

// Helper: get bahan & langkah for a menu id (for JS populate)
function get_bahan($conn, $id) {
    $r = mysqli_query($conn,"SELECT nama_bahan FROM bahan_menu WHERE menu_id=$id ORDER BY id");
    $arr = [];
    while($row = mysqli_fetch_row($r)) $arr[] = $row[0];
    return implode("\n", $arr);
}
function get_langkah($conn, $id) {
    $r = mysqli_query($conn,"SELECT deskripsi FROM langkah_menu WHERE menu_id=$id ORDER BY step_ke");
    $arr = [];
    while($row = mysqli_fetch_row($r)) $arr[] = $row[0];
    return implode("\n", $arr);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard — Foodies</title>
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

/* SIDEBAR */
.sidebar{width:260px;background:var(--sidebar);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100}
.sidebar-logo{display:flex;align-items:center;gap:12px;padding:28px 28px 24px;border-bottom:1px solid rgba(255,255,255,0.06);text-decoration:none}
.sidebar-logo img{width:26px;opacity:.85}
.sidebar-logo span{font-family:'Playfair Display',serif;font-size:20px;color:var(--white);letter-spacing:-.3px}
.sidebar-label{font-size:10px;font-weight:500;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.25);padding:24px 28px 10px}
.sidebar-nav{display:flex;flex-direction:column;gap:2px;padding:0 16px;flex:1}
.sidebar-item{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;color:rgba(255,255,255,0.50);text-decoration:none;font-size:14px;font-weight:400;transition:background .2s,color .2s;cursor:pointer;border:none;background:none;width:100%;text-align:left}
.sidebar-item:hover,.sidebar-item.active{background:rgba(200,134,60,0.12);color:var(--white)}
.sidebar-item.active{color:var(--accent)}
.sidebar-icon{font-size:16px;width:20px;text-align:center}
.sidebar-bottom{padding:16px;border-top:1px solid rgba(255,255,255,0.06)}
.sidebar-profile{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;background:rgba(255,255,255,0.04)}
.avatar{width:36px;height:36px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:14px;color:var(--white);font-weight:700;flex-shrink:0}
.profile-info{flex:1;min-width:0}
.profile-name{font-size:13px;font-weight:500;color:var(--white);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.profile-role{font-size:11px;color:rgba(255,255,255,0.35);letter-spacing:.5px}
.logout-btn{background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.3);font-size:16px;padding:4px;transition:color .2s}
.logout-btn:hover{color:#E74C3C}

/* MAIN */
.main{margin-left:260px;flex:1;display:flex;flex-direction:column;min-height:100vh}
.topbar{background:rgba(255,253,249,0.92);backdrop-filter:blur(12px);padding:18px 40px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:50}
.topbar-title h2{font-family:'Playfair Display',serif;font-size:22px;color:var(--dark);font-weight:700}
.topbar-title p{font-size:13px;color:var(--muted);font-weight:300;margin-top:2px}
.topbar-right{display:flex;align-items:center;gap:12px}
.badge-admin{padding:5px 14px;border-radius:100px;background:rgba(200,134,60,0.12);border:1px solid rgba(200,134,60,0.2);font-size:11px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;color:var(--accent)}
.btn-view-site{display:inline-flex;align-items:center;gap:8px;padding:9px 20px;border-radius:100px;background:var(--dark);color:var(--cream);text-decoration:none;font-size:13px;font-weight:500;transition:background .2s}
.btn-view-site:hover{background:var(--warm)}
.content{padding:36px 40px 80px}

/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:36px}
.stat-card{background:var(--card-bg);border-radius:18px;border:1px solid var(--border);padding:20px 22px;display:flex;flex-direction:column;gap:6px;position:relative;overflow:hidden;transition:transform .25s,box-shadow .25s;cursor:default}
.stat-card:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(28,24,17,0.08)}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--accent);border-radius:18px 18px 0 0}
.stat-card.dark{background:var(--dark)}
.stat-card.dark::before{background:var(--accent)}
.stat-icon{font-size:20px;margin-bottom:4px}
.stat-val{font-family:'Playfair Display',serif;font-size:32px;font-weight:700;color:var(--dark);line-height:1}
.stat-card.dark .stat-val{color:var(--white)}
.stat-label{font-size:11px;font-weight:500;letter-spacing:.5px;color:var(--muted);text-transform:uppercase}
.stat-card.dark .stat-label{color:rgba(255,255,255,0.4)}

/* TABS */
.tab-nav{display:flex;gap:0;background:rgba(160,82,45,0.07);border-radius:12px;padding:4px;margin-bottom:28px;width:fit-content}
.tab-btn{padding:9px 22px;border-radius:9px;border:none;background:none;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;cursor:pointer;color:var(--muted);transition:background .2s,color .2s}
.tab-btn.active{background:var(--white);color:var(--dark);box-shadow:0 2px 8px rgba(28,24,17,0.08)}
.tab-panel{display:none}
.tab-panel.active{display:block}

/* SECTION HEADER */
.sec-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.sec-header h3{font-family:'Playfair Display',serif;font-size:20px;color:var(--dark)}
.btn-add{display:inline-flex;align-items:center;gap:8px;padding:9px 20px;border-radius:100px;background:var(--accent);color:var(--white);border:none;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;cursor:pointer;transition:background .2s}
.btn-add:hover{background:var(--warm)}

/* TABLE */
.table-card{background:var(--card-bg);border-radius:18px;border:1px solid var(--border);overflow:hidden;margin-bottom:0}
table{width:100%;border-collapse:collapse}
th{font-size:10px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);text-align:left;padding:12px 18px;background:var(--cream);border-bottom:1px solid var(--border)}
td{padding:12px 18px;font-size:14px;color:var(--dark);border-bottom:1px solid var(--border);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(200,134,60,0.03)}
.td-img{width:42px;height:42px;border-radius:10px;object-fit:cover;border:1px solid var(--border)}
.pill{padding:3px 11px;border-radius:100px;font-size:11px;font-weight:500;letter-spacing:.5px;display:inline-block}
.pill-cat{background:rgba(160,82,45,0.10);color:var(--warm)}
.pill-admin{background:rgba(200,134,60,0.12);color:var(--accent);border:1px solid rgba(200,134,60,0.2)}
.pill-user{background:rgba(122,110,98,0.10);color:var(--muted)}

/* ICON ACTIONS */
.actions{display:flex;gap:6px;align-items:center}
.act-btn{width:32px;height:32px;border-radius:8px;border:1px solid var(--border);background:var(--white);display:flex;align-items:center;justify-content:center;font-size:14px;cursor:pointer;transition:all .2s;text-decoration:none;color:var(--dark)}
.act-btn:hover{background:var(--cream)}
.act-btn.view:hover{border-color:var(--accent);color:var(--accent)}
.act-btn.edit:hover{border-color:#2980b9;color:#2980b9}
.act-btn.del:hover{border-color:var(--red);color:var(--red)}

/* EDIT FORM PANEL */
.edit-panel{overflow:hidden;max-height:0;transition:max-height .5s cubic-bezier(0.23,1,0.32,1),margin .4s;margin-top:0}
.edit-panel.open{max-height:2000px;margin-top:20px}
.edit-form-card{background:var(--card-bg);border-radius:18px;border:1px solid var(--accent);padding:28px 32px}
.edit-form-card h4{font-family:'Playfair Display',serif;font-size:18px;margin-bottom:20px;color:var(--dark)}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-grid.three{grid-template-columns:1fr 1fr 1fr}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-group.full{grid-column:1/-1}
label{font-size:11px;font-weight:500;letter-spacing:1px;text-transform:uppercase;color:var(--muted)}
input[type=text],input[type=email],input[type=password],input[type=number],select,textarea{
  width:100%;padding:10px 14px;border-radius:10px;border:1px solid var(--border);
  background:var(--cream);font-family:'DM Sans',sans-serif;font-size:14px;color:var(--dark);
  outline:none;transition:border .2s
}
input:focus,select:focus,textarea:focus{border-color:var(--accent)}
textarea{resize:vertical;min-height:90px}
.gambar-preview{width:60px;height:60px;border-radius:10px;object-fit:cover;border:1px solid var(--border);display:none}
.form-actions{display:flex;gap:12px;align-items:center;margin-top:20px}
.btn-save{padding:10px 28px;border-radius:100px;background:var(--dark);color:var(--white);border:none;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;cursor:pointer;transition:background .2s}
.btn-save:hover{background:var(--warm)}
.btn-cancel{padding:10px 22px;border-radius:100px;background:none;border:1px solid var(--border);font-family:'DM Sans',sans-serif;font-size:13px;color:var(--muted);cursor:pointer;transition:all .2s}
.btn-cancel:hover{border-color:var(--dark);color:var(--dark)}
.divider-label{font-size:10px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--accent);margin:20px 0 12px;padding-bottom:8px;border-bottom:1px solid var(--border)}

/* ADD FORM PANEL (same style but different toggle) */
.add-panel{overflow:hidden;max-height:0;transition:max-height .5s cubic-bezier(0.23,1,0.32,1),margin .4s;margin-top:0}
.add-panel.open{max-height:2000px;margin-top:0;margin-bottom:24px}

/* KAL BAR */
.kal-bar-wrap{width:60px;height:4px;background:rgba(160,82,45,0.12);border-radius:100px;overflow:hidden;display:inline-block;vertical-align:middle;margin-left:8px}
.kal-bar{height:100%;border-radius:100px;background:var(--accent)}

/* FLASH */
.flash{padding:12px 20px;border-radius:12px;font-size:13px;font-weight:500;margin-bottom:24px;display:flex;align-items:center;gap:10px}
.flash.ok{background:rgba(46,125,82,0.10);color:var(--green);border:1px solid rgba(46,125,82,0.2)}
.flash.err{background:rgba(192,57,43,0.10);color:var(--red);border:1px solid rgba(192,57,43,0.2)}

.avatar-sm{width:32px;height:32px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:12px;color:var(--white);font-weight:700;flex-shrink:0}
</style>
</head>
<body>

<!-- SIDEBAR -->
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

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div class="topbar-title">
      <h2>Dashboard Admin</h2>
      <p>Kelola resep, menu, dan pengguna Foodies</p>
    </div>
    <div class="topbar-right">
      <span class="badge-admin">Admin</span>
      <a href="index.php" class="btn-view-site">Lihat Situs →</a>
    </div>
  </div>

  <div class="content">

    <?php if ($flash_msg): ?>
    <div class="flash ok">
      ✓ <?= match($flash_msg) {
        'add_ok'  => 'Data berhasil ditambahkan.',
        'edit_ok' => 'Data berhasil diperbarui.',
        'del_ok'  => 'Data berhasil dihapus.',
        default   => 'Operasi berhasil.'
      } ?>
    </div>
    <?php endif; ?>

    <!-- STAT CARDS -->
    <div class="stats-grid">
      <div class="stat-card dark">
        <div class="stat-icon">🍽</div>
        <div class="stat-val"><?= $total_menu ?></div>
        <div class="stat-label">Total Menu</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">👤</div>
        <div class="stat-val"><?= $total_user ?></div>
        <div class="stat-label">Total User</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🔥</div>
        <div class="stat-val"><?= $kalori_tinggi ?></div>
        <div class="stat-label">Kalori Tinggi</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🥗</div>
        <div class="stat-val"><?= $kalori_rendah ?></div>
        <div class="stat-label">Kalori Rendah</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">⭐</div>
        <div class="stat-val"><?= $total_review ?></div>
        <div class="stat-label">Total Review</div>
      </div>
    </div>

    <!-- MAIN TABS -->
    <div class="tab-nav">
      <button class="tab-btn <?= $active_tab==='menu'?'active':'' ?>" onclick="switchTab('menu',this)">🍽 Master Menu</button>
      <button class="tab-btn <?= $active_tab==='user'?'active':'' ?>" onclick="switchTab('user',this)">👤 Master User</button>
    </div>

    <!-- ══════════ TAB MENU ══════════ -->
    <div class="tab-panel <?= $active_tab==='menu'?'active':'' ?>" id="tab-menu">

      <div class="sec-header">
        <h3>Master Resep Menu</h3>
        <button class="btn-add" onclick="toggleAdd('menu')">＋ Tambah Menu</button>
      </div>

      <!-- ADD PANEL MENU -->
      <div class="add-panel" id="add-panel-menu">
        <div class="edit-form-card">
          <h4>✦ Tambah Menu Baru</h4>
          <form method="POST">
            <input type="hidden" name="action" value="add_menu">
            <div class="form-grid">
              <div class="form-group">
                <label>Nama Menu</label>
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
                  <option value="diet">Diet</option>
                  <option value="dessert">Dessert</option>
                  <option value="tradisional">Tradisional</option>
                  <option value="internasional">Internasional</option>
                  <option value="instant">Instant</option>
                </select>
              </div>
              <div class="form-group">
                <label>Kalori (kkal)</label>
                <input type="number" name="kalori" required placeholder="cth: 350">
              </div>
              <div class="form-group full">
                <label>URL Gambar (paste dari Google Image → Copy Image Address)</label>
                <input type="text" name="gambar" id="add-gambar-url" placeholder="https://..." oninput="previewImg(this,'add-gambar-prev')">
                <img id="add-gambar-prev" class="gambar-preview" style="margin-top:8px">
              </div>
            </div>
            <div class="divider-label">Bahan-bahan (satu per baris)</div>
            <textarea name="bahan_list" rows="5" placeholder="Beras 200g&#10;Telur 2 butir&#10;Kecap manis 2 sdm"></textarea>
            <div class="divider-label">Langkah Memasak (satu langkah per baris)</div>
            <textarea name="langkah_list" rows="6" placeholder="Panaskan minyak di wajan&#10;Tumis bawang hingga harum&#10;Masukkan nasi dan aduk rata"></textarea>
            <div class="form-actions">
              <button type="submit" class="btn-save">Simpan Menu</button>
              <button type="button" class="btn-cancel" onclick="toggleAdd('menu')">Batal</button>
            </div>
          </form>
        </div>
      </div>

      <!-- TABLE MENU -->
      <div class="table-card">
        <table>
          <thead>
            <tr>
              <th>#</th><th>Foto</th><th>Nama Menu</th><th>Bahan Utama</th>
              <th>Kategori</th><th>Kalori</th><th style="text-align:center">Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php
          mysqli_data_seek($r_menus,0);
          while($m = mysqli_fetch_assoc($r_menus)):
            $kal_pct = min(round(($m['kalori']/600)*100),100);
            $bahan_js   = addslashes(get_bahan($conn,$m['id']));
            $langkah_js = addslashes(get_langkah($conn,$m['id']));
            $gambar_js  = addslashes($m['gambar'] ?? '');
          ?>
          <tr>
            <td style="color:var(--muted);font-size:12px"><?= $m['id'] ?></td>
            <td>
              <?php if(!empty($m['gambar'])): ?>
              <img src="<?= htmlspecialchars($m['gambar']) ?>" class="td-img">
              <?php else: ?>
              <div class="td-img" style="background:var(--cream);display:flex;align-items:center;justify-content:center;font-size:18px">🍽</div>
              <?php endif; ?>
            </td>
            <td style="font-weight:500"><?= htmlspecialchars($m['nama']) ?></td>
            <td style="color:var(--muted);text-transform:capitalize"><?= htmlspecialchars($m['bahan']) ?></td>
            <td><span class="pill pill-cat"><?= ucfirst($m['kategori_menu']) ?></span></td>
            <td>
              <span><?= $m['kalori'] ?> kkal</span>
              <div class="kal-bar-wrap"><div class="kal-bar" style="width:<?= $kal_pct ?>%"></div></div>
            </td>
            <td>
              <div class="actions" style="justify-content:center">
                <a href="admin_menu.php?id=<?= $m['id'] ?>" class="act-btn view" title="Lihat Detail">👁</a>
                <button class="act-btn edit" title="Edit" onclick='openEditMenu(<?= $m['id'] ?>, <?= json_encode($m['nama']) ?>, <?= json_encode($m['bahan']) ?>, <?= json_encode($m['kategori_menu']) ?>, <?= $m['kalori'] ?>, <?= json_encode($m['gambar'] ?? '') ?>, <?= json_encode($bahan_js) ?>, <?= json_encode($langkah_js) ?>)'>✏️</button>
                <form method="POST" onsubmit="return confirm('Hapus menu ini?')" style="display:inline">
                  <input type="hidden" name="action" value="del_menu">
                  <input type="hidden" name="menu_id" value="<?= $m['id'] ?>">
                  <button type="submit" class="act-btn del" title="Hapus">🗑</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <!-- EDIT PANEL MENU -->
      <div class="edit-panel" id="edit-panel-menu">
        <div class="edit-form-card">
          <h4>✏ Edit Menu</h4>
          <form method="POST" id="edit-menu-form">
            <input type="hidden" name="action" value="edit_menu">
            <input type="hidden" name="menu_id" id="edit-menu-id">
            <div class="form-grid">
              <div class="form-group">
                <label>Nama Menu</label>
                <input type="text" name="nama" id="edit-menu-nama" required>
              </div>
              <div class="form-group">
                <label>Bahan Utama</label>
                <input type="text" name="bahan_utama" id="edit-menu-bahan">
              </div>
              <div class="form-group">
                <label>Kategori</label>
                <select name="kategori" id="edit-menu-kat">
                  <option value="utama">Utama</option>
                  <option value="diet">Diet</option>
                  <option value="dessert">Dessert</option>
                  <option value="tradisional">Tradisional</option>
                  <option value="internasional">Internasional</option>
                  <option value="instant">Instant</option>
                </select>
              </div>
              <div class="form-group">
                <label>Kalori (kkal)</label>
                <input type="number" name="kalori" id="edit-menu-kalori">
              </div>
              <div class="form-group full">
                <label>URL Gambar</label>
                <input type="text" name="gambar" id="edit-menu-gambar" placeholder="https://..." oninput="previewImg(this,'edit-gambar-prev')">
                <img id="edit-gambar-prev" class="gambar-preview" style="margin-top:8px">
              </div>
            </div>
            <div class="divider-label">Bahan-bahan (satu per baris)</div>
            <textarea name="bahan_list" id="edit-menu-bahan-list" rows="5"></textarea>
            <div class="divider-label">Langkah Memasak (satu langkah per baris)</div>
            <textarea name="langkah_list" id="edit-menu-langkah-list" rows="6"></textarea>
            <div class="form-actions">
              <button type="submit" class="btn-save">Simpan Perubahan</button>
              <button type="button" class="btn-cancel" onclick="closeEdit('menu')">Batal</button>
            </div>
          </form>
        </div>
      </div>

    </div><!-- /tab-menu -->

    <!-- ══════════ TAB USER ══════════ -->
    <div class="tab-panel <?= $active_tab==='user'?'active':'' ?>" id="tab-user">

      <div class="sec-header">
        <h3>Master Pengguna</h3>
        <button class="btn-add" onclick="toggleAdd('user')">＋ Tambah User</button>
      </div>

      <!-- ADD PANEL USER -->
      <div class="add-panel" id="add-panel-user">
        <div class="edit-form-card">
          <h4>✦ Tambah Pengguna Baru</h4>
          <form method="POST">
            <input type="hidden" name="action" value="add_user">
            <div class="form-grid three">
              <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" required placeholder="cth: Budi Santoso">
              </div>
              <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="cth: budi@email.com">
              </div>
              <div class="form-group">
                <label>Role</label>
                <select name="role">
                  <option value="user">User</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
              <div class="form-group full">
                <label>Password</label>
                <input type="password" name="password" required placeholder="minimal 8 karakter">
              </div>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn-save">Simpan User</button>
              <button type="button" class="btn-cancel" onclick="toggleAdd('user')">Batal</button>
            </div>
          </form>
        </div>
      </div>

      <!-- TABLE USER -->
      <div class="table-card">
        <table>
          <thead>
            <tr>
              <th>#</th><th>Nama</th><th>Email</th><th>Role</th>
              <th>Bergabung</th><th style="text-align:center">Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php
          mysqli_data_seek($r_users,0);
          while($u = mysqli_fetch_assoc($r_users)):
          ?>
          <tr>
            <td style="color:var(--muted);font-size:12px"><?= $u['id'] ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div class="avatar-sm"><?= strtoupper(substr($u['nama'],0,1)) ?></div>
                <?= htmlspecialchars($u['nama']) ?>
              </div>
            </td>
            <td style="color:var(--muted);font-size:13px"><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="pill <?= $u['role']==='admin'?'pill-admin':'pill-user' ?>"><?= $u['role'] ?></span></td>
            <td style="color:var(--muted);font-size:13px"><?= date('d M Y',strtotime($u['created_at'])) ?></td>
            <td>
              <div class="actions" style="justify-content:center">
                <a href="admin_user.php?id=<?= $u['id'] ?>" class="act-btn view" title="Lihat Detail">👁</a>
                <button class="act-btn edit" title="Edit" onclick='openEditUser(<?= $u['id'] ?>, <?= json_encode($u['nama']) ?>, <?= json_encode($u['email']) ?>, <?= json_encode($u['role']) ?>)'>✏️</button>
                <form method="POST" onsubmit="return confirm('Hapus user ini?')" style="display:inline">
                  <input type="hidden" name="action" value="del_user">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="act-btn del" title="Hapus">🗑</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <!-- EDIT PANEL USER -->
      <div class="edit-panel" id="edit-panel-user">
        <div class="edit-form-card">
          <h4>✏ Edit Pengguna</h4>
          <form method="POST">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="user_id" id="edit-user-id">
            <div class="form-grid three">
              <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" id="edit-user-nama" required>
              </div>
              <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="edit-user-email" required>
              </div>
              <div class="form-group">
                <label>Role</label>
                <select name="role" id="edit-user-role">
                  <option value="user">User</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
              <div class="form-group full">
                <label>Password Baru (kosongkan jika tidak diubah)</label>
                <input type="password" name="password" placeholder="Password baru...">
              </div>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn-save">Simpan Perubahan</button>
              <button type="button" class="btn-cancel" onclick="closeEdit('user')">Batal</button>
            </div>
          </form>
        </div>
      </div>

    </div><!-- /tab-user -->

  </div><!-- /content -->
</div><!-- /main -->

<script>
// Tab switching
function switchTab(name, btn) {
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('tab-'+name).classList.add('active');
  // Update URL without reload
  const url = new URL(window.location);
  url.searchParams.set('tab', name);
  window.history.replaceState(null,'',url);
}

// Toggle add panel
function toggleAdd(type) {
  const panel = document.getElementById('add-panel-'+type);
  panel.classList.toggle('open');
  if(panel.classList.contains('open')) {
    setTimeout(()=>panel.scrollIntoView({behavior:'smooth',block:'nearest'}),100);
  }
}

// Preview image from URL
function previewImg(input, imgId) {
  const img = document.getElementById(imgId);
  if(input.value.trim()) {
    img.src = input.value.trim();
    img.style.display = 'block';
    img.onerror = ()=>img.style.display='none';
  } else {
    img.style.display = 'none';
  }
}

// Open edit panel MENU
function openEditMenu(id, nama, bahan, kat, kalori, gambar, bahanList, langkahList) {
  document.getElementById('edit-menu-id').value = id;
  document.getElementById('edit-menu-nama').value = nama;
  document.getElementById('edit-menu-bahan').value = bahan;
  document.getElementById('edit-menu-kalori').value = kalori;
  document.getElementById('edit-menu-gambar').value = gambar;
  document.getElementById('edit-menu-bahan-list').value = bahanList;
  document.getElementById('edit-menu-langkah-list').value = langkahList;
  // set select
  const sel = document.getElementById('edit-menu-kat');
  for(let i=0;i<sel.options.length;i++) {
    if(sel.options[i].value===kat) sel.selectedIndex=i;
  }
  // preview image
  const prevImg = document.getElementById('edit-gambar-prev');
  if(gambar) { prevImg.src=gambar; prevImg.style.display='block'; }
  else { prevImg.style.display='none'; }
  // open
  const panel = document.getElementById('edit-panel-menu');
  panel.classList.add('open');
  setTimeout(()=>panel.scrollIntoView({behavior:'smooth',block:'nearest'}),100);
}

// Open edit panel USER
function openEditUser(id, nama, email, role) {
  document.getElementById('edit-user-id').value = id;
  document.getElementById('edit-user-nama').value = nama;
  document.getElementById('edit-user-email').value = email;
  const sel = document.getElementById('edit-user-role');
  for(let i=0;i<sel.options.length;i++) {
    if(sel.options[i].value===role) sel.selectedIndex=i;
  }
  const panel = document.getElementById('edit-panel-user');
  panel.classList.add('open');
  setTimeout(()=>panel.scrollIntoView({behavior:'smooth',block:'nearest'}),100);
}

function closeEdit(type) {
  document.getElementById('edit-panel-'+type).classList.remove('open');
}

// Auto-dismiss flash
setTimeout(()=>{
  const f = document.querySelector('.flash');
  if(f) f.style.opacity='0', setTimeout(()=>f.remove(),400);
}, 3500);
</script>
</body>
</html>