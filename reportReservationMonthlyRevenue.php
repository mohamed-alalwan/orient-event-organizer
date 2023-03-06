<!DOCTYPE html>
<html lang="en">

<?php
/* Author: Mohamed Alalwan 201601446*/
include './templates/header.php';

$startDate = $endDate = "";

function getMonthlyRevenues($startDate, $endDate)
{
    global $conn;
    $startDate = mysqli_real_escape_string($conn, $startDate);
    $endDate = mysqli_real_escape_string($conn, $endDate);
    $sql = "SELECT
                YEAR(e.created_at) 'year',
                MONTH(e.created_at) 'month',
                SUM(r.checkout_total) 'monthly_revenue',
                COUNT(r.reservation_id) 'reservations'
            FROM 
                dbproj_reservation r,
                dbproj_event e
            WHERE
                r.event_id = e.event_id
            AND
                e.created_at BETWEEN '$startDate' AND '$endDate'
            GROUP BY 
                YEAR(e.created_at),
                MONTH(e.created_at)
            ORDER BY
                YEAR(e.created_at),
                MONTH(e.created_at)";

    $result = mysqli_query($conn, $sql);
    if ($result)
        $revenues = mysqli_fetch_all($result, MYSQLI_ASSOC);
    else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
    return $revenues;
}


if (empty($_SESSION['id']) || $user['type'] != "admin") {
    redirectHome();
}

if (isset($_POST['submitReport'])) {
    if ((bool) strtotime($_POST['startDate']) && (bool) strtotime($_POST['endDate'])) {
        $startDate = $_POST['startDate'];
        $endDate = $_POST['endDate'];
        $revenues = getMonthlyRevenues($startDate, $endDate);
    }
}

?>
<div class="container events">

    <!--Container-->
    <h1>Reservation Monthly Revenue</h1>
    <form action="#" class="forms" method="POST">
        <h3>Date Range:</h3>
        <div class="dates">
            <h4>From:</h4>
            <input type="date" name="startDate" value="<?= $startDate; ?>" required>
            <h4>To:</h4>
            <input type="date" name="endDate" value="<?= $endDate; ?>" required>
        </div>
        <div class="buttons">
            <a href="reportReservationMonthlyRevenue.php">
                <button type="button">Refresh</button>
            </a>
            <button type="submit" name="submitReport">Submit</button>
        </div>

    </form>

    <?php if (isset($revenues)): ?>
            <div class="table titles container">
                <div class="row">
                    <h4>YEAR</h4>
                    <h4>MONTH</h4>
                    <h4>RESERVATIONS</h4>
                    <h4>REVENUE</h4>
                </div>
            </div>

            <?php if (count($revenues) == 0): ?>
                    <div class="table container">
                        <div class="row">
                            <h3 style="width: 100%;">No revenues found for the selected dates.</h3>
                        </div>
                    </div>
            <?php endif ?>

            <?php foreach ($revenues as $revenue): ?>
                    <div class="table container">
                        <div class="row">
                            <h4><?= htmlspecialchars($revenue['year']) ?></h4>
                            <h4><?= htmlspecialchars(date('F', mktime(0, 0, 0, $revenue['month'], 10))) ?></h4>
                            <h4><?= htmlspecialchars($revenue['reservations']) ?></h4>
                            <h4><?= htmlspecialchars($revenue['monthly_revenue'] . " BHD") ?></h4>
                        </div>
                    </div>
            <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include './templates/footer.php' ?>