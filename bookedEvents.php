<!DOCTYPE html>
<html lang="en">

<?php
/* Author: Mohamed Alalwan 201601446*/
include './templates/header.php';

function getReservation($id, $username)
{
    global $conn;
    $id = mysqli_real_escape_string($conn, $id);
    $sql = "SELECT * FROM dbproj_reservation r, dbproj_user u, dbproj_event e, dbproj_location l
        WHERE
            r.event_id = e.event_id
        AND
            r.user_id = u.user_id 
        AND
            e.location_id = l.location_id
        AND 
            r.reservation_id = '$id'
        AND
            u.username = '$username'";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $reservation = mysqli_fetch_assoc($result);
        if (!empty($reservation)) {
            //success
            return $reservation;
        }
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
}
?>
<?php
if (empty($_SESSION['id'])) {
    redirectHome();
} else {
    removeNotification('booked');
}

if (isset($_POST['submitReservationNum'])) {
    $reservationNum = $_POST['reservationNum'];
    if (!preg_match('/^[0-9]*$/', $reservationNum) || strlen($reservationNum) > 11) {
        $error = '⚠ Lookup Fail: Reservation number must be a number and cannot exceed 11 digits.';
    }
    if (empty($error)) {
        //passed the validation
        $reservation = getReservation($reservationNum, $user['username']);
        if (!empty($reservation)) {
            $_SESSION['reservation'] = $reservation;
            redirectTo('./reservationView.php');
        } else {
            $error = "⚠ Lookup Fail: No reservation with the number $reservationNum is associated with this account.";
        }
    }
}
?>
<div class="container">
    <h1>Booked Events</h1>
    <form action="#" method="POST" class="forms">
        <p class="error"><?= $error ?? "" ?></p>
        <h3>Enter Reservation Number:</h3>
        <input name="reservationNum" type="text" pattern="[0-9]*$" maxlength="11" minlength="1" required>
        <div class="buttons">
            <button type="submit" name="submitReservationNum">Find Event</button>
        </div>
    </form>
</div>

<?php include './templates/footer.php' ?>