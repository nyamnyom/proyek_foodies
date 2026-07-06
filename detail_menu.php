<?php
include "config/koneksi.php";

$id = intval($_GET['id'] ?? 0);

$steps = mysqli_query($conn,"
SELECT * FROM langkah_menu
WHERE menu_id='$id'
ORDER BY step_ke ASC
");

while($s = mysqli_fetch_assoc($steps)):
?>

<style>
    .step-item{
    background:white;
    padding:24px;
    border-radius:20px;
    margin-bottom:24px;
    border:1px solid rgba(160,82,45,0.1);
}

.step-img{
    width:100%;
    border-radius:16px;
    margin-top:16px;
}

.step-video{
    width:100%;
    border-radius:16px;
    margin-top:16px;
}
</style>

<div class="step-item">

    <h3>Step <?= $s['step_ke'] ?></h3>

    <p><?= nl2br(htmlspecialchars($s['deskripsi'])) ?></p>

    <?php if($s['gambar_step']): ?>
        <img src="<?= htmlspecialchars($s['gambar_step']) ?>"
        class="step-img">
    <?php endif; ?>

    <?php if($s['video_step']): ?>
        <video controls class="step-video">
            <source src="<?= htmlspecialchars($s['video_step']) ?>">
        </video>
    <?php endif; ?>

</div>

<?php endwhile; ?>
