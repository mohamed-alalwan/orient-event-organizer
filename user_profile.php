<!DOCTYPE html>
<html lang="en">

<?php

/* Author: Mohamed Alalwan 201601446*/

include './templates/header.php';
if (empty($_SESSION['id'])) {
    redirectHome();
}
?>

<div class="container profile">
    <h2><?= ucfirst($user['username']) . "'s Profile"; ?></h2>
    <div class="membership">
        <?php if ($medal != "") : ?>
            <div>
                <img id="medal" src="<?= $medal; ?>" alt="medal">
                <h4 class="<?= lcfirst($medal_name); ?>"><?= $medal_name; ?> Member</h4>
            </div>
        <?php endif ?>
        <h3><?= "Account Type: " . ucfirst($user['type']); ?></h3>
        <h3><?= "Email: " . $user['email']; ?></h3>
        <h3>
            <?= "Royalty Points: " . $user['royalty_points']; ?>
        </h3>
        <h3><?= "Applied Discount: " . "%" . ($user['discount_rate'] * 100); ?></h3>
    </div>
</div>


<?php include './templates/footer.php' ?>