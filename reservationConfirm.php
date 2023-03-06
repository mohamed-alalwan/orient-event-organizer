<!DOCTYPE html>
<html lang="en">

<?php
/* Author: Mohamed Alalwan 201601446*/

include './templates/header.php';

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';
//sending mail
function sendEmail()
{
    //prepare message
    global $username, $reservation_id, $eventName, $location, $startDate, $endDate, $checkoutTotal, $user_email;
    $message = file_get_contents('./templates/reservationMail.html');
    $message = str_replace('%username%', ucfirst($username), $message);
    $message = str_replace('%reservationNum%', $reservation_id, $message);
    $message = str_replace('%eventName%', $eventName, $message);
    $message = str_replace('%location%', ucfirst($location['location_title']), $message);
    $message = str_replace('%startDate%', date("M j, Y", strtotime($startDate)), $message);
    $message = str_replace('%endDate%', date("M j, Y", strtotime($endDate)), $message);
    $message = str_replace('%checkoutTotal%', $checkoutTotal, $message);
    //handle mailing
    $mail = new PHPMailer(true);
    try {
        //config
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'email@example.com';
        $mail->Password = 'password';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        //recipients
        $mail->addAddress($user_email);
        //content
        $mail->isHTML(true);
        $mail->Subject = "Reservation - Successful";
        $mail->msgHTML($message);
        //send
        $mail->send();
        return 'Message has been sent';
    } catch (Exception $e) {
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

function getReservationID($event_id)
{
    global $conn;
    $sql = "SELECT reservation_id FROM dbproj_reservation WHERE event_id = '$event_id'";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $reservation = mysqli_fetch_assoc($result);
        if (!empty($reservation)) {
            return $reservation['reservation_id'];
        }
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
}

function getEventID($startDate, $endDate, $id)
{
    global $conn;
    $sql = "SELECT event_id FROM dbproj_event WHERE event_start_date = '$startDate' AND event_end_date = '$endDate' AND location_id = $id;";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $event = mysqli_fetch_assoc($result);
        if (!empty($event)) {
            //overlap
            return $event['event_id'];
        }
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
}

//check events overlap
function checkEventOverlap($startDate, $endDate, $id)
{
    global $conn;
    $overlap_sql = "SELECT * FROM dbproj_event WHERE (event_start_date <= '$endDate' AND event_end_date >= '$startDate') AND location_id = $id;";
    $result = mysqli_query($conn, $overlap_sql);
    if ($result) {
        $events = mysqli_fetch_all($result, MYSQLI_ASSOC);
        if (count($events) == 0) {
            //no overlap
            return true;
        } else {
            //overlap
            unset($events);
            return false;
        }
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
        return false;
    }
}

//add event
function addEvent($eventName, $eventDesc, $eventDuration, $startDate, $endDate, $location_id)
{
    global $conn, $event_id;
    $eventName = mysqli_real_escape_string($conn, $eventName);
    $eventDesc = mysqli_real_escape_string($conn, $eventDesc);
    $eventDuration = mysqli_real_escape_string($conn, $eventDuration);
    $startDate = mysqli_real_escape_string($conn, $startDate);
    $endDate = mysqli_real_escape_string($conn, $endDate);
    $location_id = mysqli_real_escape_string($conn, $location_id);

    if (checkEventOverlap($startDate, $endDate, $location_id)) {
        $sql = "INSERT INTO dbproj_event(location_id, event_title, event_description, event_duration, event_start_date, event_end_date) VALUES ($location_id, '$eventName', '$eventDesc', '$eventDuration', '$startDate', '$endDate');";

        if (mysqli_query($conn, $sql)) {
            //success
            $event_id = getEventID($startDate, $endDate, $location_id);
            return true;
        } else {
            //fail: query error
            die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
            return false;
        }
    } else {
        alertBrowser("⚠ Too Slow: We are sorry, the location you trying to book has been booked in the current date selection. Please try again with different dates.");
        redirectTo('./events.php');
        return false;
    }
}

//Add Reservation
function addReservation($user_id, $event_id, $eventTotal, $products, $productsDuration, $serviceTotal, $user_discount, $checkoutTotal, $companyName, $companyAddress, $companyContact)
{
    global $conn, $reservation_id;
    $products = json_encode($products);
    $productsDuration = mysqli_real_escape_string($conn, $productsDuration);
    $companyName = mysqli_real_escape_string($conn, $companyName);
    $companyAddress = mysqli_real_escape_string($conn, $companyAddress);
    $companyContact = mysqli_real_escape_string($conn, $companyContact);

    $sql = "INSERT INTO dbproj_reservation (user_id, event_id, event_total, service_items, service_duration, service_total, discount, checkout_total, company_name, company_address, company_contact) VALUES ('$user_id', '$event_id', '$eventTotal', '$products', '$productsDuration', '$serviceTotal', '$user_discount', '$checkoutTotal', '$companyName', '$companyAddress', '$companyContact');";

    if (mysqli_query($conn, $sql)) {
        //success
        $reservation_id = getReservationID($event_id);
        return true;
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
        return false;
    }
}

function increaseRoyalityPoints($username)
{
    global $conn;
    $sql = "UPDATE dbproj_user 
    SET 
        royalty_points = royalty_points + 1,
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
                        /*More than 10*/
                        WHEN (royalty_points) > 15 
                        THEN
                        '0.2'
                        ELSE discount_rate END
    WHERE username = '$username';";

    if (mysqli_query($conn, $sql)) {
        //success
        return true;
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
        return false;
    }
}

function getLocation($id)
{
    global $conn;
    $sql = "SELECT * from dbproj_location where location_id = $id";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $location = mysqli_fetch_assoc($result);
        return $location;
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
}

//check if booking details exist
if (empty($_SESSION['eventBook']['companyContact'])) {
    redirectHome();
} else {
    try {
        //getting values
        $booking = $_SESSION['eventBook'];
        //event details
        $eventName = $booking['eventName'];
        $eventDesc = $booking['eventDesc'];
        $eventDuration = $booking['eventDuration'];
        $startDate = $booking['startDate'];
        $endDate = $booking['endDate'];
        $location_id = $booking['location_id'];
        $location = getLocation($location_id);
        $eventTotal = $booking['eventTotal'];
        //service details
        $products = $booking['products'];
        $productsDuration = $booking['productsDuration'];
        $serviceTotal = $booking['serviceTotal'];
        if (!empty($products)) {
            $serviceOptions = [];
            foreach ($products as $product) {
                if (!in_array($product['menu'], $serviceOptions)) {
                    array_push($serviceOptions, $product['menu']);
                }
            }
        }
        //Company details
        $companyName = $booking['companyName'];
        $companyAddress = $booking['companyAddress'];
        $companyContact = $booking['companyContact'];
        //user
        if (!empty($_SESSION['id'])) {
            $username = $_SESSION['username'];
            $user_email = $user['email'];
            $user_discount = $user['discount_rate'];
            $user_id = $user['user_id'];
        }
        //getting total
        $checkoutTotal = $_SESSION['checkoutTotal'] ?? "";
        //set notification
        addNotification('booking', './reservationConfirm.php', "Continue Your Booking - $eventName");
    } catch (Exception $e) {
        alertBrowser("Error: " . $e);
        redirectHome();
    }
}
//proceed to payment page
if (isset($_POST['proceed'])) {
    $checkoutTotal = ($serviceTotal + $eventTotal) * (1 - $user_discount);
    $_SESSION['checkoutTotal'] = $checkoutTotal;
    //echo $_SESSION['checkoutTotal'];
    redirectTo('./payment.php');
}
?>

<div class="container">

    <?php if (!isset($_POST['paymentSubmit'])): ?>
                <!--Confirmation-->
                <h1>Proceed to Payment:</h1>
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
                            <?= htmlspecialchars(ucfirst($location['location_title'])); ?>
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
                            <?php if (!empty($products)): ?>
                                        <?php foreach ($products as $product): ?>
                                                    <?= htmlspecialchars($product['name'] . " ({$product['price']})" . " × " . $product['inCart']); ?><br>
                                        <?php endforeach; ?>
                                        <h3>
                                            Service Duration:<br>
                                            <?= htmlspecialchars($productsDuration . " Day/s"); ?>
                                        </h3>
                            <?php else: ?>
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

                    <!--User-->
                    <?php if (!empty($_SESSION['id'])): ?>
                                <h2>User Details</h2>
                                <div class="text">
                                    <h3>
                                        Username:<br>
                                        <?= htmlspecialchars($username); ?>
                                    </h3>
                                    <h3>
                                        Email:<br>
                                        <?= htmlspecialchars($user_email); ?>
                                    </h3>
                                    <h3>
                                        Royalty Points:<br>
                                        <?= htmlspecialchars($user['royalty_points']); ?>
                                    </h3>
                                    <h3>
                                        Discount Available:<br>
                                        <span style="font-size: 1.5em;">
                                            <?= htmlspecialchars(($user_discount * 100) . "%"); ?>
                                        </span>
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
                                            <?= htmlspecialchars("- " . ($user['discount_rate'] * 100) . "%"); ?>
                                        </span>
                                    </h3>
                                    <h3>
                                        Total Price:<br>
                                        <span style="font-size: 2em;">
                                            <?= htmlspecialchars(($serviceTotal + $eventTotal) * (1 - $user['discount_rate']) . " BHD"); ?>
                                        </span>
                                    </h3>
                                </div>

                                <br>

                                <h3>Do you want to proceed to payment?</h3>

                                <div class="buttons grid">
                                    <button type="button" class="cancel" onclick="cancelReservation();">Cancel</button>
                                    <button type="submit" name="proceed">Proceed</button>
                                </div>
                    <?php else: ?>
                                <p class="error">⚠ You need to <a href="./login.php" style="color: white;">log in</a> to proceed!</p>
                                <br>
                                <div class="buttons grid">
                                    <button type="button" class="cancel" onclick="cancelReservation();">Cancel</button>
                                </div>
                    <?php endif; ?>
                </form>
                <!--Confirmation End-->

                <!--Success-->
    <?php elseif ($_POST['paymentSubmit'] === "submit"): ?>
                <h1>Successful Payment</h1>
                <form class="forms">
                    <h2>Reservation Details</h2>
                    <?php
                    //remove booking details
                    removeBookData();
                    //Add Event
                    if (addEvent($eventName, $eventDesc, $eventDuration, $startDate, $endDate, $location_id)) {
                        //Add Reservation
                        $reserve = addReservation($user_id, $event_id, $eventTotal, $products, $productsDuration, $serviceTotal, $user_discount, $checkoutTotal, $companyName, $companyAddress, $companyContact) && increaseRoyalityPoints($username);
                    }
                    //Display Reservation Details
                    if ($reserve): ?>
                                <div class="text">
                                    <h3>Your reservation is successful!</h3>
                                    <h4 style="text-align: justify">
                                        Thank you <?= ucfirst($username) ?> for booking your event on our site, your
                                        reservation information icluding the reservation number is being sent to your email (<?= $user_email ?>).
                                    </h4>
                                </div>
                                <br>
                                <?= "<h3>Email Status: " . $email = sendEmail() . "</h3>"; ?>
                                <div class="buttons grid">
                                    <button type="button" onclick="returnHome();">Return Home</button>
                                </div>
                                <?php addNotification('booked', './bookedEvents.php', "Booking Successful - $eventName"); ?>
                    <?php endif; ?>
                </form>
    <?php endif; ?>
</div>
<?php include './templates/footer.php'; ?>