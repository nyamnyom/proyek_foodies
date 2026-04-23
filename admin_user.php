<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit;
}
include "config/koneksi.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: admin_index.php'); exit; }

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'edit_user') {
            $uid   = (int)$_POST['user_id'];
            $nama  = mysqli_real_escape_string($conn, $_POST['nama']);
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $role  = $_POST['role'] === 'admin' ? 'admin' : 'user';
            $sql   = "UPDATE users SET nama='$nama',email='$email',role='$role'";
            if (!empty($_POST['password'])) {
                $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $sql .= ",password='$pass'";
            }
            $sql .= " WHERE id=$uid";
            mysqli_query($conn, $sql);
            header("Location: admin_user.php?id=$uid&msg=edit_ok"); exit;
        }
        if ($_POST['action'] === 'del_review') {
            $rid = (int)$_POST['review_id'];
            mysqli_query($conn, "DELETE FROM rating WHERE id=$rid AND user_id=$id");
            header("Location: admin_user.php?id=$id&msg=del_ok"); exit;
        }
    }
}

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$id"));
if (!$user) { header('Location: admin_index.php'); exit; }

// Reviews with menu name
$r_reviews = mysqli_query($conn,
    "SELECT r.*, m.nama as menu_nama, m.kategori_menu, m.gambar as menu_gambar
     FROM rating r
     JOIN menus m ON r.menu_id = m.id
     WHERE r.user_id = $id
     ORDER BY r.created_at DESC"
);
$total_review = mysqli_num_rows($r_reviews);
$avg_q = mysqli_fetch_row(mysqli_query($conn,"SELECT AVG(nilai) FROM rating WHERE user_id=$id"));
$avg_rating = $avg_q[0] ? round($avg_q[0],1) : 0;

$flash = $_GET['msg'] ?? '';
if (isset($_GET['logout'])) { session_destroy(); header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Detail User — <?= htmlspecialchars($user['nama']) ?> | Foodies Admin</title>
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
.avatar-lg{width:64px;height:64px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:24px;color:var(--white);font-weight:700;flex-shrink:0}
.avatar-md{width:36px;height:36px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:14px;color:var(--white);font-weight:700;flex-shrink:0}
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

/* USER PROFILE HERO */
.user-hero{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:32px}
.user-profile-card{background:var(--card-bg);border-radius:18px;border:1px solid var(--border);padding:28px;display:flex;align-items:center;gap:20px;grid-column:span 2}
.user-info h2{font-family:'Playfair Display',serif;font-size:24px;color:var(--dark);margin-bottom:6px}
.user-email{font-size:14px;color:var(--muted);margin-bottom:10px}
.pill{padding:4px 12px;border-radius:100px;font-size:11px;font-weight:500;letter-spacing:.5px;display:inline-block}
.pill-admin{background:rgba(200,134,60,0.12);color:var(--accent);border:1px solid rgba(200,134,60,0.2)}
.pill-user{background:rgba(122,110,98,0.10);color:var(--muted)}
.user-joined{font-size:12px;color:var(--muted);margin-top:8px}
.stat-mini{background:var(--card-bg);border-radius:18px;border:1px solid var(--border);padding:24px;display:flex;flex-direction:column;justify-content:center;gap:8px;position:relative;overflow:hidden}
.stat-mini::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--accent);border-radius:18px 18px 0 0}
.stat-mini-val{font-family:'Playfair Display',serif;font-size:36px;font-weight:700;color:var(--dark)}
.stat-mini-label{font-size:11px;font-weight:500;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)}
.stat-mini-icon{font-size:22px;margin-bottom:4px}

/* EDIT FORM */
.edit-panel{overflow:hidden;max-height:0;transition:max-height .5s cubic-bezier(0.23,1,0.32,1),margin .4s;margin-bottom:0}
.edit-panel.open{max-height:1000px;margin-bottom:28px}
.edit-form-card{background:var(--card-bg);border-radius:18px;border:1px solid var(--accent);padding:28px 32px}
.edit-form-card h4{font-family:'Playfair Display',serif;font-size:18px;margin-bottom:20px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-group.full{grid-column:1/-1}
label{font-size:11px;font-weight:500;letter-spacing:1px;text-transform:uppercase;color:var(--muted)}
input[type=text],input[type=email],input[type=password],select{width:100%;padding:10px 14px;border-radius:10px;border:1px solid var(--border);background:var(--cream);font-family:'DM Sans',sans-serif;font-size:14px;color:var(--dark);outline:none;transition:border .2s}
input:focus,select:focus{border-color:var(--accent)}
.form-actions{display:flex;gap:12px;align-items:center;margin-top:20px}
.btn-save{padding:10px 28px;border-radius:100px;background:var(--dark);color:var(--white);border:none;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;cursor:pointer;transition:background .2s}
.btn-save:hover{background:var(--warm)}
.btn-cancel{padding:10px 22px;border-radius:100px;background:none;border:1px solid var(--border);font-family:'DM Sans',sans-serif;font-size:13px;color:var(--muted);cursor:pointer;transition:all .2s}
.btn-cancel:hover{border-color:var(--dark);color:var(--dark)}
.btn-edit-main{display:inline-flex;align-items:center;gap:8px;padding:10px 22px;border-radius:100px;background:var(--dark);color:var(--white);border:none;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;cursor:pointer;transition:background .2s}
.btn-edit-main:hover{background:var(--warm)}

/* SEC LABEL */
.sec-label{font-size:10px;font-weight:600;letter-spacing:3px;text-transform:uppercase;color:var(--accent);margin-bottom:16px;display:flex;align-items:center;gap:10px}
.sec-label::after{content:'';flex:1;height:1px;background:var(--border)}

/* REVIEWS TABLE */
.reviews-wrap{background:var(--card-bg);border-radius:18px;border:1px solid var(--border);overflow:hidden}
table{width:100%;border-collapse:collapse}
th{font-size:10px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);text-align:left;padding:12px 18px;background:var(--cream);border-bottom:1px solid var(--border)}
td{padding:14px 18px;font-size:14px;color:var(--dark);border-bottom:1px solid var(--border);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(200,134,60,0.03)}
.td-img{width:40px;height:40px;border-radius:8px;object-fit:cover;border:1px solid var(--border)}
.stars{color:#f0a500;font-size:13px;letter-spacing:1px}
.del-btn{background:none;border:1px solid var(--border);border-radius:8px;padding:5px 12px;font-size:12px;color:var(--red);cursor:pointer;transition:all .2s;font-family:'DM Sans',sans-serif;white-space:nowrap}
.del-btn:hover{background:rgba(192,57,43,0.08);border-color:var(--red)}
.review-text-cell{max-width:300px}
.review-text-cell p{font-size:13px;color:var(--muted);line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.empty-state{text-align:center;padding:48px;color:var(--muted);font-size:14px}
.empty-icon{font-size:40px;margin-bottom:12px}
.rating-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:100px;font-size:12px;font-weight:500}
.rating-high{background:rgba(46,125,82,0.10);color:var(--green)}
.rating-mid{background:rgba(200,134,60,0.10);color:var(--accent)}
.rating-low{background:rgba(192,57,43,0.10);color:var(--red)}

/* SUSPICIOUS INDICATOR */
.sus-flag{display:inline-flex;align-items:center;gap:4px;font-size:11px;color:var(--red);background:rgba(192,57,43,0.08);border:1px solid rgba(192,57,43,0.2);padding:2px 8px;border-radius:100px}
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
      <div class="avatar-md"><?= strtoupper(substr($_SESSION['nama'],0,1)) ?></div>
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
      <a href="admin_index.php?tab=user" class="back-btn">← Kembali</a>
      <div class="topbar-title">
        <h2><?= htmlspecialchars($user['nama']) ?></h2>
        <p>Detail Pengguna · ID #<?= $user['id'] ?></p>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:12px">
      <button class="btn-edit-main" onclick="toggleEdit()">✏ Edit User Ini</button>
      <span class="badge-admin">Admin</span>
    </div>
  </div>

  <div class="content">
    <?php if ($flash): ?>
    <div class="flash ok" id="flash">✓ <?= $flash==='edit_ok'?'Data user berhasil diperbarui.':'Review berhasil dihapus.' ?></div>
    <?php endif; ?>

    <!-- EDIT PANEL -->
    <div class="edit-panel" id="edit-panel">
      <div class="edit-form-card">
        <h4>✏ Edit Pengguna: <?= htmlspecialchars($user['nama']) ?></h4>
        <form method="POST">
          <input type="hidden" name="action" value="edit_user">
          <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
          <div class="form-grid">
            <div class="form-group">
              <label>Nama Lengkap</label>
              <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required>
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="form-group">
              <label>Role</label>
              <select name="role">
                <option value="user" <?= $user['role']==='user'?'selected':'' ?>>User</option>
                <option value="admin" <?= $user['role']==='admin'?'selected':'' ?>>Admin</option>
              </select>
            </div>
            <div class="form-group">
              <label>Password Baru (kosongkan jika tidak diubah)</label>
              <input type="password" name="password" placeholder="Password baru...">
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-save">Simpan Perubahan</button>
            <button type="button" class="btn-cancel" onclick="toggleEdit()">Batal</button>
          </div>
        </form>
      </div>
    </div>

    <!-- USER HERO -->
    <div class="user-hero">
      <div class="user-profile-card">
        <div class="avatar-lg"><?= strtoupper(substr($user['nama'],0,1)) ?></div>
        <div class="user-info">
          <h2><?= htmlspecialchars($user['nama']) ?></h2>
          <div class="user-email">📧 <?= htmlspecialchars($user['email']) ?></div>
          <span class="pill <?= $user['role']==='admin'?'pill-admin':'pill-user' ?>"><?= $user['role'] ?></span>
          <div class="user-joined">🗓 Bergabung sejak <?= date('d M Y', strtotime($user['created_at'])) ?></div>
        </div>
      </div>
      <div class="stat-mini">
        <div class="stat-mini-icon">⭐</div>
        <div class="stat-mini-val"><?= $total_review ?></div>
        <div class="stat-mini-label">Total Review</div>
      </div>
      <div class="stat-mini" style="grid-column:3">
        <div class="stat-mini-icon">📊</div>
        <div class="stat-mini-val"><?= $avg_rating > 0 ? $avg_rating : '—' ?></div>
        <div class="stat-mini-label">Rata-rata Bintang</div>
      </div>
    </div>

    <!-- REVIEWS TABLE -->
    <div class="sec-label">Semua Review dari Pengguna Ini (<?= $total_review ?>)</div>

    <div class="reviews-wrap">
      <?php if ($total_review === 0): ?>
      <div class="empty-state">
        <div class="empty-icon">📭</div>
        <p>Pengguna ini belum memberikan review apapun.</p>
      </div>
      <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Menu</th>
            <th>Nama Menu</th>
            <th>Rating</th>
            <th>Review</th>
            <th>Tanggal</th>
            <th style="text-align:center">Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php
        mysqli_data_seek($r_reviews,0);
        while($rv = mysqli_fetch_assoc($r_reviews)):
          $stars_disp = str_repeat('★',$rv['nilai']) . str_repeat('☆',5-$rv['nilai']);
          // Heuristic: review asal-asalan = text sangat pendek atau hanya angka/karakter berulang
          $is_suspicious = !empty($rv['review']) && (
            strlen(trim($rv['review'])) < 5 ||
            preg_match('/^(.)\1+$/', trim($rv['review'])) ||
            preg_match('/^\d+$/', trim($rv['review']))
          );
          $rating_class = $rv['nilai'] >= 4 ? 'rating-high' : ($rv['nilai'] == 3 ? 'rating-mid' : 'rating-low');
        ?>
        <tr>
          <td>
            <?php if(!empty($rv['menu_gambar'])): ?>
            <img src="<?= htmlspecialchars($rv['menu_gambar']) ?>" class="td-img" onerror="this.style.display='none'">
            <?php else: ?>
            <div class="td-img" style="background:var(--cream);display:flex;align-items:center;justify-content:center;font-size:18px">🍽</div>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-weight:500"><?= htmlspecialchars($rv['menu_nama']) ?></div>
            <div style="font-size:12px;color:var(--muted);text-transform:capitalize"><?= $rv['kategori_menu'] ?></div>
          </td>
          <td>
            <div class="rating-badge <?= $rating_class ?>"><?= $rv['nilai'] ?> ★</div>
            <div class="stars" style="margin-top:4px;font-size:11px"><?= $stars_disp ?></div>
          </td>
          <td class="review-text-cell">
            <?php if(!empty($rv['review'])): ?>
              <p><?= htmlspecialchars($rv['review']) ?></p>
              <?php if($is_suspicious): ?>
              <span class="sus-flag">⚠ Mencurigakan</span>
              <?php endif; ?>
            <?php else: ?>
              <span style="color:var(--muted);font-style:italic;font-size:13px">Tidak ada teks review</span>
            <?php endif; ?>
          </td>
          <td style="font-size:13px;color:var(--muted);white-space:nowrap"><?= date('d M Y<\b\r>H:i', strtotime($rv['created_at'])) ?></td>
          <td style="text-align:center">
            <form method="POST" onsubmit="return confirm('Hapus review ini? Tindakan ini tidak dapat dibatalkan.')">
              <input type="hidden" name="action" value="del_review">
              <input type="hidden" name="review_id" value="<?= $rv['id'] ?>">
              <button type="submit" class="del-btn">🗑 Hapus</button>
            </form>
          </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
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
setTimeout(()=>{const f=document.getElementById('flash');if(f){f.style.opacity='0';setTimeout(()=>f.remove(),400);}},3500);
</script>
</body>
</html>