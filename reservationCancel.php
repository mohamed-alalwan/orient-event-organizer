<!DOCTYPE html>
<html lang="en">

<?php
/* Author: Mohamed Alalwan 201601446*/
include './templates/header.php';

if (empty($_SESSION['id']) || empty($_SESSION['reservation']) || empty($_SESSION['reservation']['canEdit'])) {
    redirectHome();
}

function cancelReservation($id)
{
    global $conn;

    //Update Royalty Points and Discount
    $sql = "UPDATE dbproj_user 
                SET 
                    royalty_points = royalty_points - 1,
                    discount_rate =
                                    CASE
                                    /*More than 5*/
                                    WHEN (royalty_points) > 5 
                                    AND (royalty_points) <= 10
                                    THEN
                                    '0.05'
                                    /*More than 10*/
                                    WHEN (royalty_points) > 10 
                                    AND (royalty_points) <= 15
                                    THEN
                                    '0.1'
                                    /*More than 15*/
                                    WHEN (royalty_points) > 15 
                                    THEN
                                    '0.2'
                                    ELSE discount_rate END
                WHERE user_id = (select user_id from dbproj_reservation where event_id = id)";

    //Delete Records from event and reservation
    $sql2 = "DELETE FROM dbproj_reservation WHERE event_id = id;
                DELETE FROM dbproj_event WHERE event_id = id";

    $result = mysqli_query($conn, $sql) && mysqli_query($conn, $sql2);
    if ($result) {
        //sucess
        return true;
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
        return false;
    }
}
?>

<?php if (empty($_GET['confirm'])): ?>

                <script>
                    deleteReservation();
                </script>

<?php else: ?>
                <div class="container">
                    <!--Confirmation-->
                    <h1>Canceling Reservation</h1>
                    <form action="#" class="forms" method="POST">
                        <h2>Canceling Result</h2>
                        <?php if (cancelReservation($_SESSION['reservation']['event_id'])): ?>
                                        <?php unset($_SESSION['reservation']); ?>
                                        <div class="text">
                                            <h3 style="text-align:justify">
                                                Your cancelation is compelete. Your refund will be processed in 5-7 business days. Thank you for using Orient Event Organizer, please don't hesitate to choose our site again for booking your next event.
                                            </h3>
                                        </div>
                                        <div class="buttons grid">
                                            <button type="button" onclick="returnHome();">Back</button>
                                        </div>
                        <?php endif; ?>
                    </form>
                </div>
<?php endif; ?>
<?php include './templates/footer.php'; ?>