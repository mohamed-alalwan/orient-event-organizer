<!DOCTYPE html>
<html lang="en">

<?php
/* Author: Mohamed Alalwan 201601446*/
include './templates/header.php';

if (empty($_SESSION['id']) || empty($_SESSION['reservation'])) {
    redirectHome();
} else {
    //getting session value
    $reservation = $_SESSION['reservation'];
    //event details
    $eventName = $reservation['event_title'];
    $eventDesc = $reservation['event_description'];
    $eventDuration = $reservation['event_duration'];
    $startDate = $reservation['event_start_date'];
    $endDate = $reservation['event_end_date'];
    $locationTitle = $reservation['location_title'];
    $eventTotal = $reservation['event_total'];
    //service details
    $products = json_decode($reservation['service_items'], true);
    $productsDuration = $reservation['service_duration'];
    $serviceTotal = $reservation['service_total'];
    if (!empty($products)) {
        $serviceOptions = [];
        foreach ($products as $product) {
            if (!in_array($product['menu'], $serviceOptions)) {
                array_push($serviceOptions, $product['menu']);
            }
        }
    }
    //company details
    $companyName = $reservation['company_name'];
    $companyAddress = $reservation['company_address'];
    $companyContact = $reservation['company_contact'];
    //checkout total
    $checkoutTotal = $reservation['checkout_total'];
    //discount
    $discount = $reservation['discount'];
    //amend charge percantage
    $amendCharge = $reservation['amend_charge'];
    //reservation last chance for change date
    $reservationLastDate = date('M j, Y', strtotime('-2 day', strtotime($reservation['event_start_date'])));
    $daysLeft = Round(strtotime($reservationLastDate) - strtotime(date('Y-m-d'))) / 60 / 60 / 24;
    $_SESSION['reservation']['canEdit'] = ($daysLeft > -1) ? true : false;
}

//go back
if (isset($_POST['back'])) {
    redirectTo('./bookedEvents.php');
}

//cancel reservation
if (isset($_POST['cancel'])) {
    redirectTo('./reservationCancel.php');
}

//edit reservation
if (isset($_POST['edit'])) {
    redirectTo('./reservationEdit.php');
}
?>

<div class="container">

    <!--Confirmation-->
    <h1>Reservation Details</h1>
    <form action="#" class="forms" method="POST">
        <!--Booking-->
        <h2>Booking Details</h2>
        <div class="text">
            <h3>
                Event Name:<br>
                <?= htmlspecialchars($eventName); ?>
            </h3>
            <h3>
                Event Location:<br>
                <?= htmlspecialchars(ucfirst($locationTitle)); ?>
            </h3>
            <h3>
                Event Description:<br>
                <span style="text-align: justify; display:inline-block;">
                    <?= htmlspecialchars($eventDesc); ?>
                </span>
            </h3>
            <h3>
                Event Duration:<br>
                <?= htmlspecialchars($eventDuration . " Day/s"); ?>
            </h3>
            <h3>
                Starts From:<br>
                <?= htmlspecialchars(date("M j, Y", strtotime($startDate))); ?>
            </h3>
            <h3>
                Ends In:<br>
                <?= htmlspecialchars(date("M j, Y", strtotime($endDate))); ?>
            </h3>
            <h3>
                Booking Price:<br>
                <span style="font-size: 1.5em;">
                    <?= htmlspecialchars($eventTotal . " BHD"); ?>
                </span>
            </h3>
        </div>

        <br>

        <!--Service-->
        <h2>Catering Service Details</h2>
        <div class="text">
            <h3>
                Service Options:<br>
                <?= htmlspecialchars(!empty($serviceOptions) ? implode(", ", $serviceOptions) : "No Services"); ?>
            </h3>
            <h3>
                Service Items:<br>
                <?php if (!empty($products)) : ?>
                    <?php foreach ($products as $product) : ?>
                        <?= htmlspecialchars($product['name'] . " ({$product['price']})" . " × " . $product['inCart']); ?><br>
                    <?php endforeach; ?>
                    <h3>
                        Service Duration:<br>
                        <?= htmlspecialchars($productsDuration . " Day/s"); ?>
                    </h3>
                <?php else : ?>
                    -
                <?php endif; ?>
            </h3>
            <h3>
                Service Price:<br>
                <span style="font-size: 1.5em;">
                    <?= htmlspecialchars($serviceTotal . " BHD"); ?>
                </span>
            </h3>
        </div>

        <br>

        <!--Company-->
        <h2>Company Details</h2>
        <div class="text">
            <h3>
                Company Name:<br>
                <?= htmlspecialchars($companyName); ?>
            </h3>
            <h3>
                Company Address:<br>
                <?= htmlspecialchars($companyAddress); ?>
            </h3>
            <h3>
                Company Contact Number:<br>
                <?= htmlspecialchars($companyContact); ?>
            </h3>
        </div>

        <br>

        <!--Total-->
        <h2>Total Price Summary</h2>
        <div class="text">
            <h3>
                Booking Price:<br>
                <span style="font-size: 1.5em;">
                    <?= htmlspecialchars("+ " . $eventTotal . " BHD"); ?>
                </span>
            </h3>
            <h3>
                Service Price:<br>
                <span style="font-size: 1.5em;">
                    <?= htmlspecialchars("+ " . $serviceTotal . " BHD"); ?>
                </span>
            </h3>
            <h3>
                Discount Available:<br>
                <span style="font-size: 1.5em;">
                    <?= htmlspecialchars("- " . ($discount * 100) . "%"); ?>
                </span>
            </h3>
            <?php if ($amendCharge) : ?>
                <h3>
                    Amending Charges:<br>
                    <span style="font-size: 1.5em;">
                        <?= htmlspecialchars("+ " . ($amendCharge * 100) . "%"); ?>
                    </span>
                </h3>
            <?php endif; ?>
            <h3>
                Total Price:<br>
                <span style="font-size: 2em;">
                    <?= htmlspecialchars($checkoutTotal . " BHD"); ?>
                </span>
            </h3>
        </div>

        <br>

        <h4>
            <?= "Last chance to modify reservation:"; ?><br>
            <?= htmlspecialchars("$reservationLastDate") ?> <?= ($daysLeft > -1) ? htmlspecialchars("($daysLeft days left)") : "(Past Due)"; ?>
        </h4>
        <div class="buttons grid">
            <?php if ($daysLeft > -1) : ?>
                <button type="submit" name="cancel">Cancel Reservation</button>
                <button type="submit" name="edit">Edit Reservation</button>
            <?php endif ?>
            <button type="submit" name="back">Back</button>
        </div>
    </form>
</div>
<?php include './templates/footer.php'; ?>