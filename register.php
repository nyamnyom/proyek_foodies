<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin_index.php' : 'index.php'));
    exit;
}

include "config/koneksi.php";

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    if (!$nama || !$email || !$password || !$confirm) {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $email_safe = mysqli_real_escape_string($conn, $email);
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email_safe' LIMIT 1");

        if (mysqli_num_rows($cek) > 0) {
            $error = 'Email sudah terdaftar. Silakan gunakan email lain.';
        } else {
            $hashed   = password_hash($password, PASSWORD_BCRYPT);
            $nama_safe = mysqli_real_escape_string($conn, $nama);

            $ins = "INSERT INTO users (nama, email, password, role)
                    VALUES ('$nama_safe', '$email_safe', '$hashed', 'user')";

            if (mysqli_query($conn, $ins)) {
                $success = 'Akun berhasil dibuat! Silakan login.';
            } else {
                $error = 'Terjadi kesalahan. Coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar — Foodies</title>
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
    --border:  rgba(160,82,45,0.12);
    --error:   #C0392B;
    --success: #27AE60;
}

html, body { height: 100%; }

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--cream);
    color: var(--dark);
    display: flex;
    min-height: 100vh;
    overflow-x: hidden;
}

/* ── LEFT PANEL ── */
.left-panel {
    flex: 1;
    position: relative;
    overflow: hidden;
    display: none;
}

@media(min-width: 900px) { .left-panel { display: block; } }

.left-bg {
    position: absolute; inset: 0;
    background: url('https://img.freepik.com/premium-photo/background-nature-dinner-table-vegetable-plate-food-tree-horizontal-photography-color_1274051-10787.jpg') center/cover no-repeat;
}

.left-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(
        160deg,
        rgba(28,24,17,0.78) 0%,
        rgba(160,82,45,0.40) 100%
    );
}

.left-content {
    position: relative; z-index: 2;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 48px;
}

.left-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}

.left-logo img { width: 28px; opacity: 0.9; }

.left-logo span {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    color: var(--white);
    letter-spacing: -0.3px;
}

.left-quote {
    margin-bottom: 48px;
}

.left-quote blockquote {
    font-family: 'Playfair Display', serif;
    font-size: clamp(28px, 3vw, 44px);
    color: var(--white);
    line-height: 1.2;
    margin-bottom: 20px;
}

.left-quote blockquote em {
    font-style: italic;
    color: rgba(255,255,255,0.65);
}

.left-quote p {
    font-size: 14px;
    color: rgba(255,255,255,0.5);
    font-weight: 300;
}

/* Fitur list */
.feat-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin-top: 28px;
}

.feat-item {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    color: rgba(255,255,255,0.7);
    font-weight: 300;
}

.feat-icon {
    width: 32px; height: 32px;
    background: rgba(200,134,60,0.25);
    border: 1px solid rgba(200,134,60,0.3);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}

/* ── RIGHT PANEL ── */
.right-panel {
    width: 100%;
    max-width: 560px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px 32px;
    background: var(--card-bg);
    overflow-y: auto;
}

.form-box {
    width: 100%;
    max-width: 440px;
}

.mobile-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    margin-bottom: 40px;
}

.mobile-logo img { width: 26px; opacity: 0.85; }

.mobile-logo span {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    color: var(--dark);
}

@media(min-width: 900px) { .mobile-logo { display: none; } }

.form-eyebrow {
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--accent);
    margin-bottom: 10px;
}

.form-box h1 {
    font-family: 'Playfair Display', serif;
    font-size: 34px;
    font-weight: 700;
    color: var(--dark);
    line-height: 1.15;
    margin-bottom: 8px;
}

.form-box h1 em {
    font-style: italic;
    color: var(--warm);
}

.form-desc {
    font-size: 14px;
    color: var(--muted);
    font-weight: 300;
    line-height: 1.6;
    margin-bottom: 32px;
}

/* alerts */
.alert-error, .alert-success {
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 14px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.alert-error {
    background: rgba(192,57,43,0.08);
    border: 1px solid rgba(192,57,43,0.2);
    color: var(--error);
}

.alert-success {
    background: rgba(39,174,96,0.08);
    border: 1px solid rgba(39,174,96,0.2);
    color: var(--success);
}

/* fields */
.field {
    margin-bottom: 18px;
}

.field label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: 0.8px;
    color: var(--muted);
    text-transform: uppercase;
    margin-bottom: 8px;
}

.field input {
    width: 100%;
    padding: 13px 16px;
    border-radius: 12px;
    border: 1px solid rgba(160,82,45,0.15);
    background: var(--cream);
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    color: var(--dark);
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.field input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(200,134,60,0.12);
}

.field input::placeholder { color: rgba(122,110,98,0.45); }

/* password strength bar */
.strength-bar-wrap {
    margin-top: 8px;
    height: 4px;
    background: rgba(160,82,45,0.12);
    border-radius: 100px;
    overflow: hidden;
}

.strength-bar {
    height: 100%;
    width: 0%;
    border-radius: 100px;
    transition: width 0.3s, background 0.3s;
}

.strength-label {
    font-size: 11px;
    color: var(--muted);
    margin-top: 4px;
    min-height: 16px;
}

/* submit */
.btn-submit {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 12px;
    background: var(--dark);
    color: var(--cream);
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.25s, transform 0.15s;
    margin-top: 8px;
    letter-spacing: 0.3px;
}

.btn-submit:hover {
    background: var(--warm);
    transform: translateY(-1px);
}

.form-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 28px 0;
}

.form-divider span {
    font-size: 12px;
    color: var(--muted);
    white-space: nowrap;
}

.form-divider::before,
.form-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(160,82,45,0.12);
}

.login-link {
    text-align: center;
    font-size: 14px;
    color: var(--muted);
    font-weight: 300;
}

.login-link a {
    color: var(--accent);
    font-weight: 500;
    text-decoration: none;
    transition: color 0.2s;
}

.login-link a:hover { color: var(--warm); }

.form-footer-note {
    margin-top: 32px;
    padding-top: 20px;
    border-top: 1px solid rgba(160,82,45,0.10);
    font-size: 12px;
    color: rgba(122,110,98,0.6);
    text-align: center;
    font-weight: 300;
}

/* terms */
.terms-note {
    font-size: 12px;
    color: rgba(122,110,98,0.7);
    text-align: center;
    font-weight: 300;
    line-height: 1.6;
    margin-top: 12px;
}
</style>
</head>
<body>

<!-- LEFT PANEL -->
<div class="left-panel">
    <div class="left-bg"></div>
    <div class="left-overlay"></div>
    <div class="left-content">
        <a href="index.php" class="left-logo">
            <img src="https://cdn-icons-png.flaticon.com/512/3075/3075977.png">
            <span>Foodies</span>
        </a>
        <div class="left-quote">
            <blockquote>
                Bergabunglah &<br>temukan <em>inspirasi</em><br>masakmu.
            </blockquote>
            <p>Ribuan resep menunggumu hari ini.</p>
            <div class="feat-list">
                <div class="feat-item">
                    <div class="feat-icon">🍽</div>
                    <span>Akses 30+ resep pilihan</span>
                </div>
                <div class="feat-item">
                    <div class="feat-icon">🥗</div>
                    <span>Filter menu berdasarkan BMI</span>
                </div>
                <div class="feat-item">
                    <div class="feat-icon">⭐</div>
                    <span>Simpan resep favoritmu</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RIGHT PANEL (form) -->
<div class="right-panel">
    <div class="form-box">

        <a href="index.php" class="mobile-logo">
            <img src="https://cdn-icons-png.flaticon.com/512/3075/3075977.png">
            <span>Foodies</span>
        </a>

        <p class="form-eyebrow">Mulai Perjalananmu</p>
        <h1>Buat <em>Akun</em><br>Gratis</h1>
        <p class="form-desc">Daftarkan dirimu dan mulai eksplorasi ribuan resep lezat.</p>

        <?php if($error): ?>
        <div class="alert-error"><span>⚠</span> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if($success): ?>
        <div class="alert-success">
            <span>✓</span> <?= htmlspecialchars($success) ?>
            <a href="login.php" style="margin-left:8px; color:var(--success); font-weight:500;">Login →</a>
        </div>
        <?php endif; ?>

        <?php if(!$success): ?>
        <form method="POST" id="regForm">
            <div class="field">
                <label>Nama Lengkap</label>
                <input
                    type="text"
                    name="nama"
                    placeholder="Nama kamu"
                    value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                    required
                    autocomplete="name"
                >
            </div>

            <div class="field">
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    placeholder="contoh@email.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="field">
                <label>Password</label>
                <input
                    type="password"
                    name="password"
                    id="pwdInput"
                    placeholder="Minimal 6 karakter"
                    required
                    autocomplete="new-password"
                    oninput="checkStrength(this.value)"
                >
                <div class="strength-bar-wrap">
                    <div class="strength-bar" id="strengthBar"></div>
                </div>
                <div class="strength-label" id="strengthLabel"></div>
            </div>

            <div class="field">
                <label>Konfirmasi Password</label>
                <input
                    type="password"
                    name="confirm"
                    placeholder="Ulangi password"
                    required
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="btn-submit">Buat Akun →</button>
            <p class="terms-note">Dengan mendaftar, kamu menyetujui syarat & ketentuan Foodies.</p>
        </form>
        <?php endif; ?>

        <div class="form-divider"><span>atau</span></div>

        <p class="login-link">
            Sudah punya akun? <a href="login.php">Masuk di sini</a>
        </p>

        <p class="form-footer-note">© 2025 Foodies · Dibuat dengan ♥ untuk pencinta kuliner</p>
    </div>
</div>

<script>
function checkStrength(val) {
    const bar   = document.getElementById('strengthBar');
    const label = document.getElementById('strengthLabel');
    let score   = 0;

    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { w: '0%',   bg: 'transparent', txt: '' },
        { w: '25%',  bg: '#E74C3C',     txt: 'Lemah' },
        { w: '50%',  bg: '#E67E22',     txt: 'Cukup' },
        { w: '75%',  bg: '#F1C40F',     txt: 'Baik' },
        { w: '100%', bg: '#27AE60',     txt: 'Kuat 💪' },
    ];

    const lv = Math.min(score, 4);
    bar.style.width      = levels[lv].w;
    bar.style.background = levels[lv].bg;
    label.textContent    = val.length ? levels[lv].txt : '';
}
</script>
</body>
</html>