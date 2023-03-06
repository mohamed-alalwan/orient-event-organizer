<!DOCTYPE html>
<html lang="en">

<?php
/* Author: Mohamed Alalwan 201601446*/
include './templates/header.php';

$error = "";

//getting limited results
function getLocations($start, $perPage)
{
    global $conn;
    $sql = "SELECT * FROM dbproj_location LIMIT $start, $perPage";
    $result = mysqli_query($conn, $sql);
    if ($result)
        $locations = mysqli_fetch_all($result, MYSQLI_ASSOC);
    else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
    return $locations;
}

//get rows num
function getLocationRows()
{
    global $conn;
    $sql = "SELECT * FROM dbproj_location";
    $result = mysqli_query($conn, $sql);
    if ($result)
        $rows = mysqli_num_rows($result);
    else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
    return $rows;
}

//get result by id
function getLocationByID($id)
{
    global $conn;
    $id = mysqli_real_escape_string($conn, $id);
    $sql = "SELECT * FROM dbproj_location WHERE location_id = $id";
    $result = mysqli_query($conn, $sql);
    if ($result)
        $location = mysqli_fetch_assoc($result);
    else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
    return $location;
}

//get unique types
function getLocationTypes()
{
    global $conn;
    $sql = "SELECT DISTINCT location_type FROM dbproj_location";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $types = mysqli_fetch_all($result, MYSQLI_ASSOC);
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
    return $types;
}

//validate forms
function validateLocationDetails()
{
    global $error, $locationTitle, $locationType, $types, $locationDesc, $locationAudience, $locationRent, $path;
    /*------------------------title-----------------------*/
    $locationTitle = $_POST["location_title"];
    if (strlen($locationTitle) > 60 || $locationTitle == "") {
        $error .= "⚠ Title must not be empty and cannot exceed 60 characters.<br>";
    }

    /*------------------------type-----------------------*/
    $locationType = $_POST["location_type"];
    if (!in_array($locationType, array_column($types, "location_type"))) {
        $error .= "⚠ Type is invalid.<br>";
    }

    /*------------------------description-----------------------*/
    $locationDesc = $_POST["location_description"];
    if (strlen($locationDesc) > 250 || strlen($locationDesc) < 10) {
        $error .= "⚠ Description must be atleast 10 characters and cannot exceed 250 characters.<br>";
    }

    /*------------------------audience-----------------------*/
    $locationAudience = $_POST["audience"];
    if ($locationAudience < 1 || $locationAudience > 500 || !filter_var($locationAudience, FILTER_VALIDATE_INT)) {
        $error .= "⚠ Audience must be at least 1 and cannot exceed 500.<br>";
    }

    /*------------------------hourly rent-----------------------*/
    $locationRent = $_POST["location_rent"];
    if (!is_numeric($locationRent)) {
        $error .= "⚠ Rent must be a numeric value in BHD currency.<br>";
    }

    //if chosen to ignore image 
    if (isset($_POST['ignore-image']) && isset($_GET['id'])) {
        $location = getLocationByID((int)$_GET['id']);
        $path = $location['location_image'];
        return;
    }

    /*------------------------image handling-----------------------*/
    $image = $_FILES['image'];
    //allow .jpg .png .gif
    $allowed = array("image/jpeg", "image/gif", "image/png");
    if (!in_array($image['type'], $allowed)) {
        $error .= "⚠ Image file format not accepted.<br>";
    }
    //check error
    if ($image['error']) {
        $error .= "⚠ Image file couldn't be uploaded.<br>";
    }
    //file size
    if ($image['size'] > 1000000) {
        $error .= "⚠ Image size cannot exceed 1MB.<br>";
    }
    //no errors - proceed with upload
    if (empty($error)) {
        //setup path
        $title = preg_replace('/\s+/', '-', $locationTitle);
        $path = './images/Events/' . "$title-" . $image['name'];
        if (!move_uploaded_file($image['tmp_name'], $path)) {
            $error .= "⚠ Image file couldn't be uploaded.<br>";
        }
    }
}

//add
function addLocation($locationTitle, $locationType, $locationDesc, $locationAudience, $locationRent, $path)
{
    global $conn;
    $locationTitle = mysqli_real_escape_string($conn, $locationTitle);
    $locationType = mysqli_real_escape_string($conn, $locationType);
    $locationDesc = mysqli_real_escape_string($conn, $locationDesc);
    $locationAudience = mysqli_real_escape_string($conn, $locationAudience);
    $locationRent = mysqli_real_escape_string($conn, $locationRent);
    $path = mysqli_real_escape_string($conn, $path);
    $sql = "INSERT INTO dbproj_location (location_title, location_type, location_description, max_audience, rent_per_hour, location_image) VALUES ('$locationTitle', '$locationType', '$locationDesc', '$locationAudience', '$locationRent', '$path')";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        //success
        alertBrowser("Location Added Successfully!");
        redirectTo('./manageLocations.php');
        return true;
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
        return false;
    }
}

//edit
function editLocation($id, $locationTitle, $locationType, $locationDesc, $locationAudience, $locationRent, $path)
{
    global $conn;
    $locationTitle = mysqli_real_escape_string($conn, $locationTitle);
    $locationType = mysqli_real_escape_string($conn, $locationType);
    $locationDesc = mysqli_real_escape_string($conn, $locationDesc);
    $locationAudience = mysqli_real_escape_string($conn, $locationAudience);
    $locationRent = mysqli_real_escape_string($conn, $locationRent);
    $path = mysqli_real_escape_string($conn, $path);
    //check path similarity
    $location = getLocationByID($id);
    if ($location['location_image'] !== $path) {
        if (!unlink($location['location_image']))
            die("<b>server error: couldn't update image file.</b>");
    }
    $sql = "UPDATE dbproj_location 
    SET 
        location_title = '$locationTitle', 
        location_type = '$locationType',
        location_description = '$locationDesc', 
        max_audience = '$locationAudience', 
        rent_per_hour = '$locationRent', 
        location_image = '$path'
    WHERE
        location_id = $id;
    ";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        //success
        alertBrowser("Location Modified Successfully!");
        redirectTo('./manageLocations.php');
        return true;
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
        return false;
    }
}

//delete
function deleteLocation($id)
{
    global $conn;
    $sql = "DELETE FROM dbproj_location
    WHERE
        location_id = $id;
    ";
    $location = getLocationByID($id);
    if (unlink($location['location_image'])) {
        $result = mysqli_query($conn, $sql);
        if ($result) {
            //success
            alertBrowser("Location Deleted Successfully!");
            redirectTo('./manageLocations.php');
            return true;
        } else {
            //fail: query error
            die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
            return false;
        }
    } else {
        //fail: deleting image
        die('<b>' . "server error: couldn't remove image file." . '</b>');
        return false;
    }
}

//validate user access
if (empty($_SESSION['id']) || $user['type'] != "admin") {
    redirectHome();
} else {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per-page']) && (int)$_GET['per-page'] <= 20 ? (int)$_GET['per-page'] : 5;
    $start = ($page > 1) ? ($page * $perPage) - $perPage : 0;
    //getting locations
    $locations = getLocations($start, $perPage);
    $types = getLocationTypes();
    $rows =  getLocationRows();
    $pages = ceil($rows / $perPage);
}

//create
if (isset($_POST['confirm-create'])) {
    validateLocationDetails();
    if (empty($error)) {
        //success
        addLocation(
            $locationTitle,
            $locationType,
            $locationDesc,
            $locationAudience,
            $locationRent,
            $path
        );
    }
}

//edit
if (isset($_POST['confirm-edit']) && isset($_GET['id'])) {
    validateLocationDetails();
    if (empty($error)) {
        //success
        editLocation(
            (int)$_GET['id'],
            $locationTitle,
            $locationType,
            $locationDesc,
            $locationAudience,
            $locationRent,
            $path
        );
    }
}

//delete
if (isset($_POST['confirm-delete']) && isset($_GET['id'])) {
    deleteLocation((int)$_GET['id']);
}
?>
<div class="container">
    <!--Container-->
    <h1>Locations
        <a href="?create=1#create" class="addNew" title="Add New">
            <ion-icon name="add-circle"></ion-icon>
        </a>
    </h1>

    <div class="table titles container">
        <div class="row location">
            <h4 class="icon"></h4>
            <h4 class="icon"></h4>
            <h4 class="icon"></h4>
            <h4>ID</h4>
            <h4>TITLE</h4>
            <h4>TYPE</h4>
            <h4>CAPACITY</h4>
            <h4>HOURLY RENT</h4>
        </div>
    </div>

    <?php foreach ($locations as $location) : ?>
        <div class="table container">
            <div class="row location">
                <h4 class="icon">
                    <a href="?id=<?= htmlspecialchars($location['location_id']) ?>&edit=1#edit" class="edit" title="Edit">
                        <ion-icon name="create"></ion-icon>
                    </a>
                </h4>
                <h4 class="icon">
                    <a href="?id=<?= htmlspecialchars($location['location_id']) ?>&view=1#view" class="view" title="View">
                        <ion-icon name="eye"></ion-icon>
                    </a>
                </h4>
                <h4 class="icon">
                    <a href="?id=<?= htmlspecialchars($location['location_id']) ?>&delete=1#delete" class="delete" title="Delete">
                        <ion-icon name="trash"></ion-icon>
                    </a>
                </h4>
                <h4><?= htmlspecialchars($location['location_id']) ?></h4>
                <h4><?= htmlspecialchars(ucfirst($location['location_title'])) ?></h4>
                <h4><?= htmlspecialchars(ucfirst($location['location_type'])) ?></h4>
                <h4><?= htmlspecialchars($location['max_audience']) ?></h4>
                <h4><?= htmlspecialchars(round($location['rent_per_hour'], 3)) . " BHD" ?></h4>
            </div>
        </div>
    <?php endforeach; ?>


    <div class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++) : ?>
            <a href="?page=<?= $i ?>&per-page=<?= $perPage ?>" <?= $page == $i ? 'class = "page-selected"' : "" ?>><?= $i ?></a>
        <?php endfor; ?>
    </div>

    <!-- Create Location -->
    <?php if (!empty($_GET['create'])) : ?>
        <form id="create" action="#create" class="forms reservation" method="POST" enctype="multipart/form-data">
            <h2>Create Location</h2>
            <p class="error"><?= $error ?></p>
            <h3>Title:</h3>
            <input type="text" name="location_title" placeholder="Enter Location Title" maxlength="60" value="<?= htmlspecialchars($locationTitle ?? ""); ?>" required>
            <h3>Type:</h3>
            <select name="location_type">
                <?php if (!empty($types)) : ?>
                    <?php foreach ($types as $type) : ?>
                        <option value="<?= htmlspecialchars($type['location_type']) ?>"><?= htmlspecialchars(ucfirst($type['location_type'])) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <h3>Description:</h3>
            <textarea id="description" name="location_description" class="description" onkeyup="countChar();" onkeydown="countChar();" rows="4" cols="50" maxlength="250" minlength="10" placeholder="Enter Location Description..." required><?= htmlspecialchars($locationDesc ?? ""); ?></textarea>
            <h4 style="text-align:right; margin:0 10%;"><span id="count"></span></h4>
            <h3>Capacity:</h3>
            <input type="number" placeholder="No." name="audience" min="1" max="500" value="<?= $locationAudience ?? ""; ?>" required>
            <h3>Hourly Rent (BHD):</h3>
            <input type="text" name="location_rent" placeholder="Enter Hourly Rent Price" maxlength="60" value="<?= htmlspecialchars($locationRent ?? ""); ?>" required>
            <h3>Image File:</h3>
            <input type="file" name="image" accept="image/*" required>
            <div class="buttons grid">
                <button type="button" onclick="location.href='#';">
                    <ion-icon name="arrow-up"></ion-icon>
                </button>
                <button type="submit" name="confirm-create">Create Location</button>
            </div>
        </form>
    <?php endif ?>

    <!-- View Location -->
    <?php if (!empty($_GET['view']) && !empty($_GET['id'])) : ?>
        <?php if (!empty($location = getLocationByID((int)$_GET['id']))) : ?>
            <form id="view" action="#" class="forms reservation">
                <h2>View Location</h2>
                <div class="form-icons">
                    <h3 class="icon">
                        <a href="?id=<?= htmlspecialchars($location['location_id']) ?>&edit=1#edit" class="edit" title="Edit">
                            <ion-icon name="create"></ion-icon>
                        </a>
                    </h3>
                    <h3 class="icon">
                        <a href="?id=<?= htmlspecialchars($location['location_id']) ?>&view=1#view" class="view" title="View">
                            <ion-icon name="eye"></ion-icon>
                        </a>
                    </h3>
                    <h3 class="icon">
                        <a href="?id=<?= htmlspecialchars($location['location_id']) ?>&delete=1#delete" class="delete" title="Delete">
                            <ion-icon name="trash"></ion-icon>
                        </a>
                    </h3>
                </div>
                <div class="img">
                    <img src="<?= htmlspecialchars($location['location_image']) ?>" alt="location">
                </div>
                <div class="text">
                    <h3>ID:<br> <?= htmlspecialchars($location['location_id']) ?></h3>
                    <h3>Title:<br> <?= htmlspecialchars(ucfirst($location['location_title'])) ?></h3>
                    <h3>Type:<br> <?= htmlspecialchars(ucfirst($location['location_type'])) ?></h3>
                    <h3>Description:<br> <?= htmlspecialchars($location['location_description']) ?></h3>
                    <h3>Capacity:<br> <?= htmlspecialchars($location['max_audience']) ?></h3>
                    <h3>Hourly Rent:<br> <?= htmlspecialchars(round($location['rent_per_hour'], 3)) . " BHD" ?></h3>
                </div>
                <div class="buttons grid">
                    <button type="button" onclick="location.href='#';">
                        <ion-icon name="arrow-up"></ion-icon>
                    </button>
                </div>
            </form>
        <?php endif ?>
    <?php endif ?>

    <!-- Edit Location -->
    <?php if (!empty($_GET['edit']) && !empty($_GET['id'])) : ?>
        <?php if (!empty($location = getLocationByID((int)$_GET['id']))) : ?>
            <form id="edit" action="#edit" class="forms reservation" method="POST" enctype="multipart/form-data">
                <h2>Edit Location</h2>
                <div class="form-icons">
                    <h3 class="icon">
                        <a href="?id=<?= htmlspecialchars($location['location_id']) ?>&edit=1#edit" class="edit" title="Edit">
                            <ion-icon name="create"></ion-icon>
                        </a>
                    </h3>
                    <h3 class="icon">
                        <a href="?id=<?= htmlspecialchars($location['location_id']) ?>&view=1#view" class="view" title="View">
                            <ion-icon name="eye"></ion-icon>
                        </a>
                    </h3>
                    <h3 class="icon">
                        <a href="?id=<?= htmlspecialchars($location['location_id']) ?>&delete=1#delete" class="delete" title="Delete">
                            <ion-icon name="trash"></ion-icon>
                        </a>
                    </h3>
                </div>
                <p class="error"><?= $error ?></p>
                <h3>Title:</h3>
                <input type="text" name="location_title" placeholder="Enter Location Title" maxlength="60" value="<?= htmlspecialchars($locationTitle ?? $location['location_title']); ?>" required>
                <h3>Type:</h3>
                <select name="location_type">
                    <?php if (!empty($types)) : ?>
                        <?php foreach ($types as $type) : ?>
                            <option value="<?= htmlspecialchars($type['location_type']) ?>" <?= $location['location_type'] == $type['location_type'] ? "selected" : "" ?>><?= htmlspecialchars(ucfirst($type['location_type'])) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <h3>Description:</h3>
                <textarea id="description" name="location_description" class="description" onkeyup="countChar();" onkeydown="countChar();" rows="4" cols="50" maxlength="250" minlength="10" placeholder="Enter Location Description..." required><?= htmlspecialchars($locationDesc ?? $location['location_description']); ?></textarea>
                <h4 style="text-align:right; margin:0 10%;"><span id="count"></span></h4>
                <h3>Capacity:</h3>
                <input type="number" placeholder="No." name="audience" min="1" max="500" value="<?= htmlspecialchars($locationAudience ?? $location['max_audience']); ?>" required>
                <h3>Hourly Rent (BHD):</h3>
                <input type="text" name="location_rent" placeholder="Enter Hourly Rent Price" maxlength="60" value="<?= htmlspecialchars($locationRent ?? $location['rent_per_hour']); ?>" required>
                <h3>Image File:</h3>
                <div class="img">
                    <img src="<?= $location['location_image']; ?>" alt="location">
                </div>
                <h4>Keep Same Image <input type="checkbox" name="ignore-image"></h4>
                <input type="file" name="image" accept="image/*">
                <div class="buttons grid">
                    <button type="button" onclick="location.href='#';">
                        <ion-icon name="arrow-up"></ion-icon>
                    </button>
                    <button type="submit" name="confirm-edit">Edit Location</button>
                </div>
            </form>
        <?php endif ?>
    <?php endif ?>


    <!-- Delete Location -->
    <?php if (!empty($_GET['delete']) && !empty($_GET['id'])) : ?>
        <?php if (!empty($location = getLocationByID((int)$_GET['id']))) : ?>
            <form id="delete" action="#delete" class="forms reservation" method="POST">
                <h2>Delete Location</h2>
                <div class="form-icons">
                    <h3 class="icon">
                        <a href="?id=<?= htmlspecialchars($location['location_id']) ?>&edit=1#edit" class="edit" title="Edit">
                            <ion-icon name="create"></ion-icon>
                        </a>
                    </h3>
                    <h3 class="icon">
                        <a href="?id=<?= htmlspecialchars($location['location_id']) ?>&view=1#view" class="view" title="View">
                            <ion-icon name="eye"></ion-icon>
                        </a>
                    </h3>
                    <h3 class="icon">
                        <a href="?id=<?= htmlspecialchars($location['location_id']) ?>&delete=1#delete" class="delete" title="Delete">
                            <ion-icon name="trash"></ion-icon>
                        </a>
                    </h3>
                </div>
                <p class="error"><?= $error ?></p>
                <h3>Are you sure you want to remove <?= htmlspecialchars(ucfirst($location['location_title'])); ?>?</h3>
                <div class="buttons grid">
                    <button type="button" onclick="location.href='#';">
                        <ion-icon name="arrow-up"></ion-icon>
                    </button>
                    <button type="submit" name="confirm-delete">Delete Location</button>
                </div>
            </form>
        <?php endif ?>
    <?php endif ?>


</div>

<?php include './templates/footer.php' ?>