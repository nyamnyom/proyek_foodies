<?php
session_start();
include "config/koneksi.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$menu_id = $_POST['menu_id'];

mysqli_query($conn,"
    INSERT IGNORE INTO bookmarks(user_id, menu_id)
    VALUES('$user_id','$menu_id')
");

header("Location: ../menu.php");?>
