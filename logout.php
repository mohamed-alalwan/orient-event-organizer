<!DOCTYPE html>
<html lang="en">

<?php
/* Author: Mohamed Alalwan 201601446*/
include './templates/header.php';
?>
<?php
if (!empty($_SESSION['id'])) {
    session_destroy();
    alertBrowser("Logged out successfully!");
}
redirectHome();
?>
<?php include './templates/footer.php' ?>