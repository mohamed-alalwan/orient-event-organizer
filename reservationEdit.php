<!DOCTYPE html>
<html lang="en">

<?php
/* Author: Mohamed Alalwan 201601446*/
include './templates/header.php';

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

//Modify Reservation
function editReservation($eventID, $eventName, $eventDesc, $serviceItems, $serviceDuration, $serviceTotal, $companyName, $companyAddress, $companyContact, $totalPrice, $amendCharge)
{
    global $conn;
    $eventName = mysqli_real_escape_string($conn, $eventName);
    $eventDesc = mysqli_real_escape_string($conn, $eventDesc);
    $serviceItems = json_encode($serviceItems);
    $serviceDuration = mysqli_real_escape_string($conn, $serviceDuration);
    $companyName = mysqli_real_escape_string($conn, $companyName);
    $companyAddress = mysqli_real_escape_string($conn, $companyAddress);
    $companyContact = mysqli_real_escape_string($conn, $companyContact);

    //Edit Event
    $sql = "UPDATE dbproj_event e
            SET 
                e.event_title = '$eventName',
                e.event_description = '$eventDesc'
            WHERE e.event_id = '$eventID'";

    //Edit Reservation
    $sql2 = "UPDATE dbproj_reservation r
                SET 
                    r.service_items = '$serviceItems',
                    r.service_duration = '$serviceDuration',
                    r.service_total = '$serviceTotal',
                    r.company_name = '$companyName',
                    r.company_address = '$companyAddress',
                    r.company_contact = '$companyContact',
                    r.checkout_total = '$totalPrice',
                    r.amend_charge =
                                    CASE 
                                        WHEN r.amend_charge IS NULL
                                            THEN '$amendCharge'
                                        ELSE r.amend_charge + '$amendCharge'
                                    END
                WHERE r.event_id = '$eventID'";

    if (mysqli_query($conn, $sql) && mysqli_query($conn, $sql2)) {
        //success
        return true;
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
        return false;
    }
}

if (empty($_SESSION['id']) || empty($_SESSION['reservation']) || empty($_SESSION['reservation']['canEdit'])) {
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
    //amend charge %
    $amendCharge = $reservation['amend_charge'];
    //new amend charge %
    $newAmendCharge = 0.05;
}

//go back
if (isset($_POST['back'])) {
    redirectTo('./reservationView.php');
}

//preview reservation
if (isset($_POST['preview'])) {
    //event name
    $eventName = $_POST['event_name'];
    if (strlen($eventName) > 60 || $eventName == "") {
        $error = "⚠ Edit Fail: event name must not be empty and cannot exceed 60 characters.";
    }

    //event description
    $eventDesc = $_POST['event_description'];
    if (strlen($eventDesc) > 250 || strlen($eventDesc) < 10) {
        $error = "⚠ Edit Fail: event description must be atleast 10 characters and cannot exceed 250 characters.";
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
    if ($productsDuration < 1 || $productsDuration > $eventDuration || !filter_var($productsDuration, FILTER_VALIDATE_INT)) {
        $error = "⚠ Edit Fail: Service duration cannot be less than 1 and cannot exceed the event duration.";
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
        $error = "⚠ Edit Fail: Company name must not be empty and cannot exceed 60 characters.";
    }

    //address
    $companyAddress = $_POST['company_address'];
    if (strlen($companyAddress) > 250 || $companyAddress == "") {
        $error = '⚠ Edit Fail: Company address cannot be empty.';
    }

    //contact
    $companyContact = $_POST['company_contact'];
    if (!preg_match('/^[0-9]{8}$/', $companyContact)) {
        $error = '⚠ Edit Fail: Phone number must be a 8 digits number.';
    }

    //show preview
    if ($error == "") {
        $newTotal = ($serviceTotal + $eventTotal) * (1 - $discount);
        $newTotal *= 1 + (($amendCharge) ? ($amendCharge) : 0) + ($newAmendCharge);
        $newTotal = round($newTotal, 3);
        $difference = $checkoutTotal - $newTotal;
        $preview = true;
        $array = [
            //event
            'eventID' => $reservation['event_id'],
            'eventName' => $eventName,
            'eventDesc' => $eventDesc,
            //service
            'products' => $products,
            'productsDuration' => $productsDuration,
            'serviceTotal' => $serviceTotal,
            //company
            'companyName' => $companyName,
            'companyAddress' => $companyAddress,
            'companyContact' => $companyContact,
            //price changes
            'totalPrice' => $newTotal,
            'amendCharge' => $newAmendCharge
        ];
        //update session variables
        unset($_SESSION['reservation']['edit']);
        $_SESSION['reservation']['edit'] = $array;

        //clearing local storage
        echo '
        <script type="text/javascript">
            localStorage.clear();
        </script>
        ';
    }
}

//save reservation changes 
if (isset($_POST['save'])) {
    //get session edit values
    $edit = $_SESSION['reservation']['edit'];
    if (
        editReservation(
            $edit['eventID'],
            $edit['eventName'],
            $edit['eventDesc'],
            $edit['products'],
            $edit['productsDuration'],
            $edit['serviceTotal'],
            $edit['companyName'],
            $edit['companyAddress'],
            $edit['companyContact'],
            $edit['totalPrice'],
            $edit['amendCharge']
        )
    ) {
        //clearing local storage
        echo '
        <script type="text/javascript">
            localStorage.clear();
        </script>
        ';
        //clearing session from reservation values
        unset($_SESSION['reservation']);
        //alert and return to booked events page
        alertBrowser("Reservation has been modified successfully!");
        redirectTo('./bookedEvents.php');
    }
}
?>

<div class="container">

    <!--Confirmation-->
    <h1>Reservation Details</h1>
    <form action="#preview" class="forms" method="POST" onsubmit="saveProducts();">
        <!-- Error -->
        <p class="error"><?= $error; ?></p>
        <!--Event-->
        <h2>Event Details</h2>
        <h3>Event Name:</h3>
        <input id="event_name" type="text" name="event_name" placeholder="Enter Event Name" maxlength="60" value="<?= htmlspecialchars($eventName ?? ""); ?>" required>
        <h3>Event Description:</h3>
        <textarea id="description" name="event_description" class="description" onkeyup="countChar();" onkeydown="countChar();" rows="4" cols="50" maxlength="250" minlength="10" placeholder="Enter Event Description..." required><?= htmlspecialchars($eventDesc ?? ""); ?></textarea>
        <h4 style="text-align:right; margin:0 10%;"><span id="count"></span></h4>

        <br>

        <!--Service-->
        <h2>Catering Service Details</h2>
        <h3>
            Service Options:<br>
        </h3>
        <select id="menus" name="service" onchange="displayItemsOnMenu(this.value);">
            <option value="">No, Thanks.</option>
            <?php if (!empty($menus)): ?>
                                <?php foreach ($menus as $menu): ?>
                                                    <option value="<?= $menu ?>" <?php if (!empty($serviceOptions)): ?><?= (in_array($menu, $serviceOptions)) ? "selected" : "" ?><?php endif; ?>><?= $menu ?></option>
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
                <?php if (!empty($eventDuration)): ?>
                                    <?php for ($i = $eventDuration; $i >= 1; $i--): ?>
                                                        <option value="<?= $i ?>" <?= ($i == $productsDuration) ? "selected" : "" ?>><?= $i ?></option>
                                    <?php endfor; ?>
                <?php endif; ?>
            </select>
            <h3><span id="total2">Total Price</span> × <span id="durationSpan">Days</span></h3>
            <h3><span id="totalWithDuration">= Total Service Charge</span></h3>
        </div>
        <input id="json_products" type="hidden" name="json_products">

        <br>

        <!--Company-->
        <h2>Company Details</h2>
        <h3>Company Name:</h3>
        <input name="company_name" type="text" placeholder="Enter your company name" maxlength="60" value="<?= htmlspecialchars($companyName ?? ""); ?>" required>
        <h3>Company Address:</h3>
        <input name="company_address" type="text" placeholder="Enter your company address" maxlength="250" value="<?= htmlspecialchars($companyAddress ?? ""); ?>" required>
        <h3>Company Contact Number:</h3>
        <input name="company_contact" type="tel" maxlength="8" minlength="8" placeholder="Enter your contact number" pattern="^[0-9]*$" value="<?= htmlspecialchars($companyContact ?? ""); ?>" required>

        <br><br>

        <div class="buttons grid">
            <button type="button" name="back" onclick="history.go(-1);">Back</button>
            <button type="submit" name="preview">Preview Changes</button>
        </div>
    </form>
    <?php if (!empty($preview)): ?>
                        <br>
                        <form action="#preview" class="forms" method="POST">
                            <!--Total-->
                            <h2 id='preview'>Total Price Summary</h2>
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
                                <?php if ($amendCharge): ?>
                                                    <h3>
                                                        Previous Amending Charges:<br>
                                                        <span style="font-size: 1.5em;">
                                                            <?= htmlspecialchars("+ " . ($amendCharge * 100) . "%"); ?>
                                                        </span>
                                                    </h3>
                                <?php endif; ?>
                                <h3>
                                    Current Amending Charges:<br>
                                    <span style="font-size: 1.5em;">
                                        <?= htmlspecialchars("+ " . ($newAmendCharge * 100) . "%"); ?>
                                    </span>
                                </h3>
                                <h3>
                                    Total Price:<br>
                                    <span style="font-size: 2em;">
                                        <?= htmlspecialchars($newTotal . " BHD"); ?>
                                    </span>
                                </h3>
                            </div>
                            <h3>
                                <?= ($difference > 0) ? "You will get a refund of: " : "You will be charged extra: " ?>
                                <?= htmlspecialchars(abs(round($difference, 3)) . " BHD") ?>
                            </h3>
                            <div class="buttons grid">
                                <button type="submit" name="back">Back</button>
                                <button type="submit" name="save">Save Changes</button>
                            </div>
                        </form>
    <?php endif; ?>
</div>
<?php include './scripts/productsScript.php'; ?>
<?php include './templates/footer.php'; ?>