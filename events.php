<?php

include_once './config/db_connect.php';

$errors = ['advanced' => '', 'keepSearching' => false];
$startDate = $endDate = $locationSelect = $audience = "";

$sql = "SELECT * FROM dbproj_location ORDER BY max_audience DESC;";

//get location titles
$result = mysqli_query($conn, $sql);
if ($result) {
    $titles = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $maxAudience = max(array_column($titles, 'max_audience'));
} else {
    //fail: query error
    die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
}

//simple search
if (isset($_POST['simpleSearch'])) {
    $searchText = mysqli_escape_string($conn, $_POST['searchText']);
    $sql = "SELECT * from dbproj_location WHERE location_type LIKE '%$searchText%' OR location_title LIKE '%$searchText%' OR location_description LIKE '%$searchText%' OR max_audience LIKE '%$searchText%'";
}

//advanced search
if (isset($_POST['searchBtn'])) {
    $startDate = date(mysqli_escape_string($conn, $_POST['startDate']));
    $endDate = date(mysqli_escape_string($conn, $_POST['endDate']));
    $audience = mysqli_escape_string($conn, $_POST['audience']);
    $locationSelect = mysqli_escape_string($conn, $_POST['locationSelect']);
    //dates check
    if (checkDates()) {
        //setting check
        advancedSearch();
    }
}

//do search query
searchEvents();

function advancedSearch()
{
    global $sql, $startDate, $endDate, $conn, $audience, $locationSelect, $events;
    //check events overlap
    $sql = "SELECT * FROM dbproj_location l, dbproj_event e WHERE l.location_id = e.location_id AND (e.event_start_date <= '$endDate' AND e.event_end_date >= '$startDate');";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $events = mysqli_fetch_all($result, MYSQLI_ASSOC);
        if (isset($events)) {
            $condition = "";
            foreach ($events as $event) {
                $condition .= " AND location_id != {$event['location_id']}";
            }
            $sql = "SELECT * FROM dbproj_location WHERE max_audience >= $audience " . $condition;
            unset($events);
        } else {
            $sql = "SELECT * FROM dbproj_location WHERE max_audience >= $audience";
        }
        //adding optional location selection
        if (!empty($locationSelect) && isset($locationSelect)) {
            $sql .= " AND location_title = '$locationSelect';";
        }
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
}

function searchEvents()
{
    global $result, $sql, $conn, $locations;
    $result = mysqli_query($conn, $sql);
    if ($result)
        $locations = mysqli_fetch_all($result, MYSQLI_ASSOC);
    else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
}

function checkDates()
{
    global $startDate, $endDate, $errors, $duration;
    $valid = true;
    if ($endDate < $startDate) {
        $errors['advanced'] = "⚠ Date Error: End Date cannot be behind the Start Date!";
        $startDate = $endDate = "";
        $valid = false;
    } else {
        $duration = abs(strtotime($startDate) - strtotime($endDate)) / 60 / 60 / 24;

        if (!($duration == 1 || $duration == 7 || $duration == 15)) {
            $errors['advanced'] = "⚠ Date Error: Events can only be 1, 7, or 15 days!";
            $startDate = $endDate = "";
            $valid = false;
        }
    }
    if (!$valid) {
        $_GET['advancedSearch'] = true;
    }
    return $valid;
}

function getAvailableSlot($condition)
{
    global $endDate, $startDate, $conn, $duration;
    //get end date of last overlap
    $sql = "SELECT * FROM dbproj_location l, dbproj_event e WHERE l.location_id = e.location_id AND (e.event_start_date <= '$endDate' AND e.event_end_date >= '$startDate')" . $condition;
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $events = mysqli_fetch_all($result, MYSQLI_ASSOC);
        if (count($events) > 0) {
            //last overlapped event
            $lastEvent = $events[count($events) - 1];
            $slotFound = false;
            //search start date
            $_startDate = strtotime("+1 day", strtotime($lastEvent['event_end_date']));
            $_startDate = date("Y-m-d", $_startDate);
            //search end date
            $_endDate = strtotime("+$duration day", strtotime($_startDate));
            $_endDate = date("Y-m-d", $_endDate);

            //search available slot 
            while ($slotFound != true) {
                //get end date of last overlap
                $sql = "SELECT * FROM dbproj_location l, dbproj_event e WHERE l.location_id = e.location_id AND (e.event_start_date <= '$_endDate' AND e.event_end_date >= '$_startDate')" . $condition;
                $result = mysqli_query($conn, $sql);
                if ($result) {
                    $events = mysqli_fetch_all($result, MYSQLI_ASSOC);
                    if (count($events) > 0) {
                        //last overlapped event
                        $lastEvent = $events[count($events) - 1];
                        $slotFound = false;
                        //search start date
                        $_startDate = strtotime("+1 day", strtotime($lastEvent['event_end_date']));
                        $_startDate = date("Y-m-d", $_startDate);
                        //search end date
                        $_endDate = strtotime("+$duration day", strtotime($_startDate));
                        $_endDate = date("Y-m-d", $_endDate);
                    } else {
                        //available slot found
                        $slotFound = true;
                        break;
                    }
                } else {
                    //fail: query error
                    die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
                    break;
                }
            }
            $lastEvent['_startDate'] = $_startDate;
            $lastEvent['_endDate'] = $_endDate;
            return $lastEvent;
        }
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<?php include './templates/header.php';
//Book Now Clicked
if (isset($_POST['bookNow'])) {
    $startDate = date($_POST['startDate']);
    $endDate = date($_POST['endDate']);
    $id = $_POST['id'];
    if (checkDates()) {
        //check events overlap
        $overlap_sql = "SELECT * FROM dbproj_event WHERE (event_start_date <= '$endDate' AND event_end_date >= '$startDate') AND location_id = $id;";
        $result = mysqli_query($conn, $overlap_sql);
        if ($result) {
            $events = mysqli_fetch_all($result, MYSQLI_ASSOC);
            if (count($events) == 0) {
                //no overlap!
                $_SESSION['eventBook'] = ['startDate' => "$startDate", 'endDate' => "$endDate", 'location_id' => "$id"];
                redirectTo("reservation.php");
            } else {
                //overlap
                unset($events);
                $errors['advanced'] = "⚠ Booking Error: Event is already booked within selected date range, please select another event or change the selection below!";
                $errors['keepSearching'] = true;
                $_GET['advancedSearch'] = 1;
            }
        } else {
            //fail: query error
            die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
        }
    }
}
//displaying advanced search
if (isset($_GET['advancedSearch'])) {
    echo "<script> 
                    window.onload = function _(){
                        window.location.hash = 'advance';
                        displayAdvancedSearch();
                    }
                    </script>";
}
?>

<div class="container events">

    <h1>Events</h1>

    <!-- Simple Search -->
    <form id="simpleSearch" action="#simpleSearch" class="forms events" method="POST" <?php if (isset($_POST['searchBtn']))
        echo 'style ="border-radius: 5px; margin-bottom:10px;"'; ?>>
        <input type="text" placeholder="Search Event" name="searchText" value="<?php if (isset($_POST['searchText']))
            echo htmlspecialchars($_POST['searchText']); ?>" required>
        <div class="buttons" style="display: inline-block;">
            <button type="submit" name="simpleSearch">Search</button>
            <a href="#advance" onclick="displayAdvancedSearch();">
                <button type="button">Advanced</button>
            </a>
        </div>
    </form>

    <!-- Jump (Advanced Search) -->
    <span id="advance"></span>

    <!-- Advanced Search -->
    <form id="advancedSearch" action="#advance" class="forms events" method="POST" style="<?php if (!isset($_POST['searchBtn']))
        echo "display:none"; ?>">
        <p class="error">
            <?= $errors['advanced']; ?>
        </p>
        <h2>Advanced Search</h2>
        <div class="dates">
            <h4>Start Date:</h4>
            <input type="date" name="startDate" min="<?= date("Y-m-d"); ?>" value="<?= $startDate; ?>" required>
            <h4>End Date:</h4>
            <input type="date" name="endDate" min="<?= date("Y-m-d"); ?>" value="<?= $endDate; ?>" required>
        </div>
        <h4>Location:</h4>
        <select name="locationSelect">
            <option value="">Any</option>
            <?php if (isset($titles)): ?>
                    <?php foreach ($titles as $title): ?>
                            <option value="<?= $title['location_title']; ?>" <?php if ($title['location_title'] == $locationSelect)
                                  echo "selected"; ?>>
                                <?= ucfirst($title['location_title']); ?>
                            </option>
                    <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <h4>Audience:</h4>
        <input type="number" placeholder="No." name="audience" min="1" max="<?= $maxAudience ?>" value="<?= $audience; ?>" required>
        <div class="buttons">
            <button type="submit" name="searchBtn">Search</button>
            <a href="events.php">
                <button type="button">Refresh</button>
            </a>
        </div>
    </form>

    <?php if ((isset($_POST['searchBtn']) || isset($_POST['simpleSearch'])) && empty($errors['advanced'])): ?>
            <!--Display Results Number-->
            <h3 class="result">
                <?php
                if (!empty($locations))
                    echo count($locations);
                else
                    echo 0;
                ?> results found!</h3>

    <?php endif; ?>

    <!--Specific Recommendation-->
    <?php if (isset($_POST['bookNow']) && empty($events)): ?>
            <?php
            $condition = " AND l.location_id = $id";
            $recommendedEvent = getAvailableSlot($condition);
            ?>
            <?php if (!empty($recommendedEvent)): ?>
                    <h2 class="result recommend"><i>Recommended:</i></h2>
                    <div class="event container recommend">
                        <div class="event farleft">
                            <div class="text">
                                <h3>
                                    <?= $recommendedEvent['location_title']; ?>
                                </h3>
                                <h4><?= $recommendedEvent['location_description']; ?></h4>
                                <h3 class="audience">
                                    <img src="./images/Events/audience.png" alt="audience icon">
                                    <?= $recommendedEvent['max_audience']; ?>
                                </h3>
                            </div>
                            <img src="<?= $recommendedEvent['location_image']; ?>" alt="room image">
                        </div>
                        <div class="event right recommend">
                            <h3>Available!</h3>
                            <h2><?= date("M j, Y", strtotime($recommendedEvent['_startDate'])); ?><br>⇩<br> <?= date("M j, Y", strtotime($recommendedEvent['_endDate'])); ?>
                                <form action="#" method="POST">
                                    <input type="hidden" name="startDate" value="<?= $recommendedEvent['_startDate']; ?>">
                                    <input type="hidden" name="endDate" value="<?= $recommendedEvent['_endDate']; ?>">
                                    <input type="hidden" name="id" value="<?= $recommendedEvent['location_id']; ?>">
                                    <button type="submit" name="bookNow">Book Now</button>
                                </form>
                            </h2>
                            <h1><?= $recommendedEvent['rent_per_hour'] . " BHD"; ?></h1>
                            <h4>Rent Per Hour</h4>
                        </div>
                    </div>
            <?php endif; ?>

    <?php endif; ?>

    <!-- Events -->
    <?php if (empty($errors['advanced']) || $errors['keepSearching']): ?>
            <?php if (!empty($locations)): ?>
                    <?php foreach ($locations as $location): ?>
                            <div class="event container">
                                <div class="event farleft">
                                    <div class="text">
                                        <h3>
                                            <?= $location['location_title']; ?>
                                        </h3>
                                        <h4><?= $location['location_description']; ?></h4>
                                        <h3 class="audience">
                                            <img src="./images/Events/audience.png" alt="audience icon">
                                            <?= $location['max_audience']; ?>
                                        </h3>
                                    </div>
                                    <img src="<?= $location['location_image']; ?>" alt="room image">
                                </div>
                                <div class="event right">
                                    <?php if (empty($startDate) && empty($endDate)): ?>
                                            <form action="#" class="forms" method="POST">
                                                <div class="dates">
                                                    <h4>Start Date:</h4>
                                                    <br>
                                                    <input type="date" name="startDate" min="<?= date("Y-m-d"); ?>" required>
                                                    <br>
                                                    <h4>End Date:</h4>
                                                    <br>
                                                    <input type="date" name="endDate" min="<?= date("Y-m-d"); ?>" required>
                                                </div>
                                                <input type="hidden" name="id" value="<?= $location['location_id']; ?>">
                                                <button type="submit" name="bookNow">Book Now</button>
                                            </form>
                                    <?php else: ?>
                                            <form action="#" method="POST">
                                                <input type="hidden" name="startDate" value="<?= $startDate; ?>">
                                                <input type="hidden" name="endDate" value="<?= $endDate; ?>">
                                                <input type="hidden" name="id" value="<?= $location['location_id']; ?>">
                                                <button type="submit" name="bookNow">Book Now</button>
                                            </form>
                                    <?php endif ?>
                                    <h1><?= $location['rent_per_hour'] . " BHD"; ?></h1>
                                    <h4>Rent Per Hour</h4>
                                </div>
                            </div>
                    <?php endforeach; ?>
            <?php elseif (isset($_POST['searchBtn'])): ?>
                    <?php
                    if (!empty($titles)) {
                        $condition = "";
                        if (!empty($locationSelect)) {
                            $condition = " AND l.location_title = '$locationSelect'";
                            $recommendedEvent = getAvailableSlot($condition);
                        } else {
                            $recommendedEvents = [];
                            foreach ($titles as $title) {
                                $condition = " AND l.location_title = '{$title['location_title']}'";
                                $recommendedEvent = getAvailableSlot($condition);
                                if (!empty($recommendedEvent))
                                    array_push($recommendedEvents, $recommendedEvent);
                            }
                            unset($recommendedEvent);
                        }
                    }
                    ?>

                    <!-- Recommended -->
                    <h2 class="result recommend"><i>Recommended:</i></h2>
                    <?php if (!empty($recommendedEvent)): ?>
                            <div class="event container recommend">
                                <div class="event farleft">
                                    <div class="text">
                                        <h3>
                                            <?= $recommendedEvent['location_title']; ?>
                                        </h3>
                                        <h4><?= $recommendedEvent['location_description']; ?></h4>
                                        <h3 class="audience">
                                            <img src="./images/Events/audience.png" alt="audience icon">
                                            <?= $recommendedEvent['max_audience']; ?>
                                        </h3>
                                    </div>
                                    <img src="<?= $recommendedEvent['location_image']; ?>" alt="room image">
                                </div>
                                <div class="event right recommend">
                                    <h3>Available!</h3>
                                    <h2><?= date("M j, Y", strtotime($recommendedEvent['_startDate'])); ?><br>⇩<br> <?= date("M j, Y", strtotime($recommendedEvent['_endDate'])); ?>
                                        <form action="#" method="POST">
                                            <input type="hidden" name="startDate" value="<?= $recommendedEvent['_startDate']; ?>">
                                            <input type="hidden" name="endDate" value="<?= $recommendedEvent['_endDate']; ?>">
                                            <input type="hidden" name="id" value="<?= $recommendedEvent['location_id']; ?>">
                                            <button type="submit" name="bookNow">Book Now</button>
                                        </form>
                                    </h2>
                                    <h1><?= $recommendedEvent['rent_per_hour'] . " BHD"; ?></h1>
                                    <h4>Rent Per Hour</h4>
                                </div>
                            </div>
                    <?php elseif (!empty($recommendedEvents)): ?>
                            <?php foreach ($recommendedEvents as $recommendedEvent): ?>
                                    <div class="event container recommend">
                                        <div class="event farleft">
                                            <div class="text">
                                                <h3>
                                                    <?= $recommendedEvent['location_title']; ?>
                                                </h3>
                                                <h4><?= $recommendedEvent['location_description']; ?></h4>
                                                <h3 class="audience">
                                                    <img src="./images/Events/audience.png" alt="audience icon">
                                                    <?= $recommendedEvent['max_audience']; ?>
                                                </h3>
                                            </div>
                                            <img src="<?= $recommendedEvent['location_image']; ?>" alt="room image">
                                        </div>
                                        <div class="event right recommend">
                                            <h3>Available!</h3>
                                            <h2><?= date("M j, Y", strtotime($recommendedEvent['_startDate'])); ?><br>⇩<br><?= date("M j, Y", strtotime($recommendedEvent['_endDate'])); ?>
                                                <form action="#" method="POST">
                                                    <input type="hidden" name="startDate" value="<?= $recommendedEvent['_startDate']; ?>">
                                                    <input type="hidden" name="endDate" value="<?= $recommendedEvent['_endDate']; ?>">
                                                    <input type="hidden" name="id" value="<?= $recommendedEvent['location_id']; ?>">
                                                    <button type="submit" name="bookNow">Book Now</button>
                                                </form>
                                            </h2>
                                            <h1><?= $recommendedEvent['rent_per_hour'] . " BHD"; ?></h1>
                                            <h4>Rent Per Hour</h4>
                                        </div>
                                    </div>
                            <?php endforeach; ?>
                    <?php endif; ?>
            <?php endif; ?>
    <?php endif; ?>

</div>
<?php include './templates/footer.php'; ?>