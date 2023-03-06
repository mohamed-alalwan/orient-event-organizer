<?php

/* Author: Mohamed Alalwan 201601446*/

include_once './config/db_connect.php';

$error = "";

$items = getItems();

if (!empty($items)) {
    $menus = getMenus();
}

function getMenus()
{
    global $items;
    $menus = [];
    foreach ($items as $item) {
        if (!in_array($item['item_type'], $menus)) {
            array_push($menus, $item['item_type']);
        }
    }
    return $menus;
}

function getItems()
{
    global $conn;
    $sql = "select * from dbproj_item;";
    $result = mysqli_query($conn, $sql);
    if ($result)
        $items = mysqli_fetch_all($result, MYSQLI_ASSOC);
    else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
    return $items;
}

function getItemByID($id)
{
    global $items;
    $itemByID = [];
    foreach ($items as $item) {
        if ($item['item_id'] == $id) {
            $itemByID = [
                'item_id' => $item['item_id'],
                'item_title' => $item['item_title'],
                'item_price' => $item['item_price'],
                'item_type' => $item['item_type'],
                'item_image' => $item['item_image']
            ];
        }
    }
    return $itemByID;
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
?>

<!DOCTYPE html>
<html lang="en">

<?php include './templates/header.php'; ?>
<?php
if (!empty($_SESSION['eventBook'])) {
    $id = $_SESSION['eventBook']['location_id'];
    $startDate = date("M j, Y", strtotime($_SESSION['eventBook']['startDate']));
    $endDate = date("M j, Y", strtotime($_SESSION['eventBook']['endDate']));
    $duration = abs(strtotime($startDate) - strtotime($endDate)) / 60 / 60 / 24;
    $location = getLocation($id);
    $eventTotal = $location['rent_per_hour'] * ($duration * 24);
} else {
    alertBrowser("No booking details found.");
    redirectTo("events.php");
}

//reservation form submitted
if (isset($_POST['reservationSubmit'])) {
    //event name
    $eventName = $_POST['event_name'];
    if (strlen($eventName) > 60 || $eventName == "") {
        $error = "⚠ Booking Fail: event name must not be empty and cannot exceed 60 characters.";
    }

    //event description
    $eventDesc = $_POST['event_description'];
    if (strlen($eventDesc) > 250 || strlen($eventDesc) < 10) {
        $error = "⚠ Booking Fail: event description must be atleast 10 characters and cannot exceed 250 characters.";
    }

    //catering options 
    if (!empty($_POST['json_products']) && !empty($items)) {
        $products = json_decode($_POST['json_products'], true);
        //update prices if changed
        if ($products) {
            foreach ($products as $product) {
                //validating json format
                if (isset($product['id']) && isset($product['price']) && isset($product['name']) && isset($product['inCart']) && isset($product['menu'])) {
                    $itemByID = getItemByID($product['id']);
                    if ($product['price'] != $itemByID['item_price']) {
                        echo "<br>price been fixed due to change.";
                        $products[$product['id']]['id'] = $itemByID['item_price'];
                    }
                } else {
                    $products = "";
                    break;
                }
            }
        } else {
            $products = "";
        }
    } else {
        $products = "";
    }

    //catering duration
    $productsDuration = $_POST['productsDuration'];
    if ($productsDuration < 1 || $productsDuration > $duration || !filter_var($productsDuration, FILTER_VALIDATE_INT)) {
        $error = "⚠ Booking Fail: Service duration cannot be less than 1 and cannot exceed the event duration.";
    }

    //catering service total
    $serviceTotal = 0;
    if (!empty($products)) {
        foreach ($products as $product) {
            $serviceTotal += $product['price'] * $product['inCart'];
        }
        $serviceTotal *= $productsDuration;
    }

    //company name
    $companyName = $_POST['company_name'];
    if (strlen($companyName) > 60 || $companyName == "") {
        $error = "⚠ Booking Fail: Company name must not be empty and cannot exceed 60 characters.";
    }

    //address
    $companyAddress = $_POST['company_address'];
    if (strlen($companyAddress) > 250 || $companyAddress == "") {
        $error = '⚠ Booking Fail: Company address cannot be empty.';
    }

    //contact
    $companyContact = $_POST['company_contact'];
    if (!preg_match('/^[0-9]{8}$/', $companyContact)) {
        $error = '⚠ Booking Fail: Phone number must be a 8 digits number.';
    }

    if ($error == "") {
        //storing info to session
        $array = [
            //event
            'eventName' => $eventName,
            'eventDesc' => $eventDesc,
            'eventDuration' => $duration,
            'startDate' => date("Y-m-d", strtotime($startDate)),
            'endDate' => date("Y-m-d", strtotime($endDate)),
            'location_id' => $id,
            'eventTotal' => $eventTotal,
            //service
            'products' => $products,
            'productsDuration' => $productsDuration,
            'serviceTotal' => $serviceTotal,
            //company
            'companyName' => $companyName,
            'companyAddress' => $companyAddress,
            'companyContact' => $companyContact
        ];

        //update session variables
        unset($_SESSION['eventBook']);
        $_SESSION['eventBook'] = $array;

        //clearing local storage
        echo '
        <script type="text/javascript">
            localStorage.clear();
        </script>
        ';

        redirectTo("reservationConfirm.php");
    }
}
?>

<div class="container">
    <h1>Booking an Event</h1>

    <form action="#" class="forms reservation" method="POST">
        <!-- Progress Bar -->
        <div class="progress-bar">
            <div class="progress" id="progress"></div>
            <div class="progress-step active" data-title="Location"></div>
            <div class="progress-step" data-title="Event"></div>
            <div class="progress-step" data-title="Service"></div>
            <div class="progress-step" data-title="Finish"></div>
        </div>

        <!-- Error -->
        <p class="error"><?= $error; ?></p>

        <!-- Steps -->

        <!-- Confirmation -->
        <div class="form-step active">
            <h2>Location Details</h2>
            <div class="img">
                <img src="<?= $location['location_image']; ?>" alt="location">
            </div>
            <div class="text">
                <h3>Location:<br> <?= ucfirst($location['location_title']); ?></h3>
                <h3>Location Description:<br> <?= $location['location_description']; ?></h3>
                <h3>Max Audience:<br> <?= $location['max_audience']; ?></h3>
                <h3>From:<br> <?= $startDate; ?></h3>
                <h3>To:<br> <?= $endDate; ?></h3>
                <h1>Rental for <?= $duration; ?> day/s:<br> <?= $eventTotal . " BHD"; ?></h1>

            </div>
            <div class="buttons grid">
                <button type="button" class="cancel" onclick="cancelReservation();">Cancel</button>
                <button type="button" class="next">Next</button>
            </div>
        </div>

        <!-- Event Info -->
        <div class="form-step">
            <h2>Enter Your Event Info</h2>
            <!--Error-->
            <p class="error event_button"></p>
            <h3>Event Name:</h3>
            <input id="event_name" type="text" name="event_name" placeholder="Enter Event Name" maxlength="60" value="<?= htmlspecialchars($eventName ?? ""); ?>" required>
            <h3>Event Description:</h3>
            <textarea id="description" name="event_description" class="description" onkeyup="countChar();" onkeydown="countChar();" rows="4" cols="50" maxlength="250" minlength="10" placeholder="Enter Event Description..." required><?= htmlspecialchars($eventDesc ?? ""); ?></textarea>
            <h4 style="text-align:right; margin:0 10%;"><span id="count"></span></h4>
            <div class="buttons grid">
                <button type="button" class="prev">Previous</button>
                <button id="event_button" type="button" class="next">Next</button>
            </div>
        </div>

        <!-- Services -->
        <div class="form-step">
            <h2>Catering Services</h2>
            <h3>Services:</h3>
            <select id="menus" name="service" onchange="displayItemsOnMenu(this.value);">
                <option value="">No, Thanks.</option>
                <?php if (!empty($menus)) : ?>
                    <?php foreach ($menus as $menu) : ?>
                        <option value="<?= $menu ?>"><?= $menu ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <div id="services">
                <!--Items-->
                <!--Cart-->
            </div>
            <div class="service-duration">
                <h3>Duration:</h3>
                <select name="productsDuration" id="duration" onchange="totalChargeDuration();">
                    <?php if (!empty($duration)) : ?>
                        <?php for ($i = $duration; $i >= 1; $i--) : ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    <?php endif; ?>
                </select>
                <h3><span id="total2">Total Price</span> × <span id="durationSpan">Days</span></h3>
                <h3><span id="totalWithDuration">= Total Service Charge</span></h3>
            </div>
            <div class="buttons grid">
                <button type="button" class="prev">Previous</button>
                <button id="service_button" type="button" class="next">Next</button>
            </div>
        </div>

        <!--Contact Info-->
        <div class="form-step">
            <h2>Enter Your Company Info</h2>
            <h3>Company Name:</h3>
            <input name="company_name" type="text" placeholder="Enter your company name" maxlength="60" value="<?= htmlspecialchars($companyName ?? ""); ?>" required>
            <h3>Address:</h3>
            <input name="company_address" type="text" placeholder="Enter your company address" maxlength="250" value="<?= htmlspecialchars($companyAddress ?? ""); ?>" required>
            <h3>Contact Number:</h3>
            <input name="company_contact" type="tel" maxlength="8" minlength="8" placeholder="Enter your contact number" pattern="^[0-9]*$" value="<?= htmlspecialchars($companyContact ?? ""); ?>" required>
            <div class="buttons grid">
                <button type="button" class="prev">Previous</button>
                <button type="submit" name="reservationSubmit">Submit</button>
            </div>
        </div>
        <input id="json_products" type="hidden" name="json_products">
    </form>
</div>

<?php include './scripts/productsScript.php'; ?>
<?php include './templates/footer.php'; ?>