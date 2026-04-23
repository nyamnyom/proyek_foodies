<?php
session_start();
 
// Kalau sudah login, redirect langsung
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin_index.php' : 'index.php'));
    exit;
}
 
include "config/koneksi.php";
 
$error = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
 
    if (!$email || !$password) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $email_safe = mysqli_real_escape_string($conn, $email);
        $q = "SELECT * FROM users WHERE email = '$email_safe' LIMIT 1";
        $r = mysqli_query($conn, $q);
        $user = mysqli_fetch_assoc($r);
 
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
 
            if ($user['role'] === 'admin') {
                header('Location: admin_index.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Login — Foodies</title>
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
 
/* ── LEFT PANEL (visual) ── */
.left-panel {
    flex: 1;
    position: relative;
    overflow: hidden;
    display: none;
}
 
@media(min-width: 900px) { .left-panel { display: block; } }
 
.left-bg {
    position: absolute; inset: 0;
    background: url('https://t4.ftcdn.net/jpg/04/81/33/57/360_F_481335799_khKwqBN9tvn6piqm5NxQBOYj4XYWBPUG.jpg') center/cover no-repeat;
}
 
.left-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(
        160deg,
        rgba(28,24,17,0.72) 0%,
        rgba(160,82,45,0.45) 100%
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
 
/* ── RIGHT PANEL (form) ── */
.right-panel {
    width: 100%;
    max-width: 520px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px 32px;
    background: var(--card-bg);
}
 
@media(min-width: 900px) { .right-panel { min-height: 100vh; } }
 
.form-box {
    width: 100%;
    max-width: 400px;
}
 
/* mobile logo */
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
    margin-bottom: 36px;
}
 
/* error */
.alert-error {
    background: rgba(192,57,43,0.08);
    border: 1px solid rgba(192,57,43,0.2);
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 14px;
    color: var(--error);
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 8px;
}
 
/* form fields */
.field {
    margin-bottom: 20px;
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
 
.field input::placeholder { color: rgba(122,110,98,0.5); }
 
.field-hint {
    font-size: 12px;
    color: var(--muted);
    margin-top: 6px;
    font-weight: 300;
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
 
/* divider */
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
 
/* register link */
.register-link {
    text-align: center;
    font-size: 14px;
    color: var(--muted);
    font-weight: 300;
}
 
.register-link a {
    color: var(--accent);
    font-weight: 500;
    text-decoration: none;
    transition: color 0.2s;
}
 
.register-link a:hover { color: var(--warm); }
 
/* footer note */
.form-footer-note {
    margin-top: 40px;
    padding-top: 24px;
    border-top: 1px solid rgba(160,82,45,0.10);
    font-size: 12px;
    color: rgba(122,110,98,0.6);
    text-align: center;
    font-weight: 300;
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
                Masak dengan <em>cinta,</em><br>
                sajikan dengan <em>bangga.</em>
            </blockquote>
            <p>Ribuan resep menunggumu di sini.</p>
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
 
        <p class="form-eyebrow">Selamat Datang Kembali</p>
        <h1>Masuk ke<br><em>Akun</em> Kamu</h1>
        <p class="form-desc">Login untuk menikmati semua fitur resep & menu Foodies.</p>
 
        <?php if($error): ?>
        <div class="alert-error">
            <span>⚠</span> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
 
        <form method="POST">
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
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >
            </div>
 
            <button type="submit" class="btn-submit">Masuk →</button>
        </form>
 
        <div class="form-divider"><span>atau</span></div>
 
        <p class="register-link">
            Belum punya akun? <a href="register.php">Daftar sekarang</a>
        </p>
 
        <p class="form-footer-note">© 2025 Foodies · Dibuat dengan ♥ untuk pencinta kuliner</p>
    </div>
</div>
 
</body>
</html>