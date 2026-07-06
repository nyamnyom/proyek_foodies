<?php
session_start();
include "../config/koneksi.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$menu_id = intval($_POST['menu_id'] ?? 0);

/* Bookmark hanya untuk member premium */
$isPremium = false;

$cek = mysqli_query($conn,"
    SELECT * FROM premium_users
    WHERE user_id='$user_id'
    AND aktif_sampai >= CURDATE()
");

if(mysqli_num_rows($cek) > 0){
    $isPremium = true;
}

if(!$isPremium){
    header("Location: ../premium.php?need_premium=1");
    exit;
}

if($menu_id > 0){
    mysqli_query($conn,"
        INSERT IGNORE INTO bookmarks(user_id, menu_id)
        VALUES('$user_id','$menu_id')
    ");
}

header("Location: ../menu.php");
exit;
