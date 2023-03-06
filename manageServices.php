<!DOCTYPE html>
<html lang="en">

<?php
/* Author: Mohamed Alalwan 201601446*/
include './templates/header.php';

$error = "";

//getting limited results
function getItems($start, $perPage)
{
    global $conn;
    $sql = "SELECT * FROM dbproj_item LIMIT $start, $perPage";
    $result = mysqli_query($conn, $sql);
    if ($result)
        $items = mysqli_fetch_all($result, MYSQLI_ASSOC);
    else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
    return $items;
}

//get rows num
function getItemsRows()
{
    global $conn;
    $sql = "SELECT * FROM dbproj_item";
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
function getItemByID($id)
{
    global $conn;
    $id = mysqli_real_escape_string($conn, $id);
    $sql = "SELECT * FROM dbproj_item WHERE item_id = $id";
    $result = mysqli_query($conn, $sql);
    if ($result)
        $item = mysqli_fetch_assoc($result);
    else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
    return $item;
}

//get unique menus
function getItemsMenus()
{
    global $conn;
    $sql = "SELECT DISTINCT item_type FROM dbproj_item";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $menus = mysqli_fetch_all($result, MYSQLI_ASSOC);
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');;
    }
    return $menus;
}

//validate forms
function validateItemDetails()
{
    global $error, $itemTitle, $itemType, $menus, $itemPrice, $path;
    /*------------------------title-----------------------*/
    $itemTitle = $_POST["item_title"];
    if (strlen($itemTitle) > 60 || $itemTitle == "") {
        $error .= "⚠ Title must not be empty and cannot exceed 60 characters.<br>";
    }

    /*------------------------type-----------------------*/
    $itemType = $_POST["item_type"];
    if (!in_array($itemType, array_column($menus, "item_type"))) {
        $error .= "⚠ Type is invalid.<br>";
    }

    /*------------------------Item Price-----------------------*/
    $itemPrice = $_POST["item_price"];
    if (!is_numeric($itemPrice)) {
        $error .= "⚠ Price must be a numeric value in BHD currency.<br>";
    }

    //if chosen to ignore image 
    if (isset($_POST['ignore-image']) && isset($_GET['id'])) {
        $item = getItemByID((int)$_GET['id']);
        $path = $item['item_image'];;
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
        $title = preg_replace('/\s+/', '-', $itemTitle);
        $path = './images/Items/' . "$title-" . $image['name'];
        if (!move_uploaded_file($image['tmp_name'], $path)) {
            $error .= "⚠ Image file couldn't be uploaded.<br>";
        }
    }
}

//add
function addItem($itemTitle, $itemType, $itemPrice, $path)
{
    global $conn;
    $itemTitle = mysqli_real_escape_string($conn, $itemTitle);
    $itemType = mysqli_real_escape_string($conn, $itemType);
    $itemPrice = mysqli_real_escape_string($conn, $itemPrice);
    $path = mysqli_real_escape_string($conn, $path);
    $sql = "INSERT INTO dbproj_item (item_title, item_type, item_price, item_image) VALUES ('$itemTitle', '$itemType', '$itemPrice', '$path')";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        //success
        alertBrowser("Item Added Successfully!");
        redirectTo('./manageServices.php');
        return true;
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
        return false;
    }
}

//edit
function editItem($id, $itemTitle, $itemType, $itemPrice, $path)
{
    global $conn;
    $itemTitle = mysqli_real_escape_string($conn, $itemTitle);
    $itemType = mysqli_real_escape_string($conn, $itemType);
    $itemPrice = mysqli_real_escape_string($conn, $itemPrice);
    $path = mysqli_real_escape_string($conn, $path);
    //check path similarity
    $item = getItemByID($id);
    if ($item['item_image'] !== $path) {
        if (!unlink($item['item_image']))
            die("<b>server error: couldn't update image file.</b>");
    }
    $sql = "UPDATE dbproj_item 
    SET 
        item_title = '$itemTitle', 
        item_type = '$itemType',
        item_price = '$itemPrice', 
        item_image = '$path'
    WHERE
        item_id = $id;
    ";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        //success
        alertBrowser("Item Modified Successfully!");
        redirectTo('./manageServices.php');
        return true;
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
        return false;
    }
}

//delete
function deleteItem($id)
{
    global $conn;
    $sql = "DELETE FROM dbproj_item
    WHERE
        item_id = $id;
    ";
    $item = getItemByID($id);
    if (unlink($item['item_image'])) {
        $result = mysqli_query($conn, $sql);
        if ($result) {
            //success
            alertBrowser("Item Deleted Successfully!");
            redirectTo('./manageServices.php');
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
    //getting items
    $items = getItems($start, $perPage);
    $menus = getItemsMenus();
    $rows =  getItemsRows();
    $pages = ceil($rows / $perPage);
}

//create
if (isset($_POST['confirm-create'])) {
    validateItemDetails();
    if (empty($error)) {
        //success
        addItem(
            $itemTitle,
            $itemType,
            $itemPrice,
            $path
        );
    }
}

//edit
if (isset($_POST['confirm-edit']) && isset($_GET['id'])) {
    validateItemDetails();
    if (empty($error)) {
        //success
        editItem(
            (int)$_GET['id'],
            $itemTitle,
            $itemType,
            $itemPrice,
            $path
        );
    }
}

//delete
if (isset($_POST['confirm-delete']) && isset($_GET['id'])) {
    deleteItem((int)$_GET['id']);
}
?>
<div class="container">
    <!--Container-->
    <h1>Services
        <a href="?create=1#create" class="addNew" title="Add New">
            <ion-icon name="add-circle"></ion-icon>
        </a>
    </h1>

    <div class="table titles container">
        <div class="row">
            <h4 class="icon"></h4>
            <h4 class="icon"></h4>
            <h4 class="icon"></h4>
            <h4>ID</h4>
            <h4>TITLE</h4>
            <h4>MENU</h4>
            <h4>PRICE</h4>
        </div>
    </div>

    <?php foreach ($items as $item) : ?>
        <div class="table container">
            <div class="row">
                <h4 class="icon">
                    <a href="?id=<?= $item['item_id'] ?>&edit=1#edit" class="edit" title="Edit">
                        <ion-icon name="create"></ion-icon>
                    </a>
                </h4>
                <h4 class="icon">
                    <a href="?id=<?= $item['item_id'] ?>&view=1#view" class="view" title="View">
                        <ion-icon name="eye"></ion-icon>
                    </a>
                </h4>
                <h4 class="icon">
                    <a href="?id=<?= $item['item_id'] ?>&delete=1#delete" class="delete" title="Delete">
                        <ion-icon name="trash"></ion-icon>
                    </a>
                </h4>
                <h4><?= htmlspecialchars($item['item_id']) ?></h4>
                <h4><?= htmlspecialchars($item['item_title']) ?></h4>
                <h4><?= htmlspecialchars($item['item_type']) ?></h4>
                <h4><?= htmlspecialchars(round($item['item_price'], 3)) . " BHD" ?></h4>
            </div>
        </div>
    <?php endforeach; ?>


    <div class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++) : ?>
            <a href="?page=<?= $i ?>&per-page=<?= $perPage ?>" <?= $page == $i ? 'class = "page-selected"' : "" ?>><?= $i ?></a>
        <?php endfor; ?>
    </div>

    <!-- Create Item -->
    <?php if (!empty($_GET['create'])) : ?>
        <form id="create" action="#create" class="forms reservation" method="POST" enctype="multipart/form-data">
            <h2>Create Item</h2>
            <p class="error"><?= $error ?></p>
            <h3>Title:</h3>
            <input type="text" name="item_title" placeholder="Enter Item Title" maxlength="60" value="<?= htmlspecialchars($itemTitle ?? ""); ?>" required>
            <h3>Type:</h3>
            <select name="item_type">
                <?php if (!empty($menus)) : ?>
                    <?php foreach ($menus as $type) : ?>
                        <option value="<?= htmlspecialchars($type['item_type']) ?>"><?= htmlspecialchars(ucfirst($type['item_type'])) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <h3>Price (BHD):</h3>
            <input type="text" name="item_price" placeholder="Enter Item Price" maxlength="60" value="<?= htmlspecialchars($itemPrice ?? ""); ?>" required>
            <h3>Image File:</h3>
            <input type="file" name="image" accept="image/*" required>
            <div class="buttons grid">
                <button type="button" onclick="location.href='#';">
                    <ion-icon name="arrow-up"></ion-icon>
                </button>
                <button type="submit" name="confirm-create">Create Item</button>
            </div>
        </form>
    <?php endif ?>

    <!-- View Item -->
    <?php if (!empty($_GET['view']) && !empty($_GET['id'])) : ?>
        <?php if (!empty($item = getItemByID((int)$_GET['id']))) : ?>
            <form id="view" action="#view" class="forms reservation">
                <h2>View Item</h2>
                <div class="form-icons">
                    <h3 class="icon">
                        <a href="?id=<?= $item['item_id'] ?>&edit=1#edit" class="edit" title="Edit">
                            <ion-icon name="create"></ion-icon>
                        </a>
                    </h3>
                    <h3 class="icon">
                        <a href="?id=<?= $item['item_id'] ?>&view=1#view" class="view" title="View">
                            <ion-icon name="eye"></ion-icon>
                        </a>
                    </h3>
                    <h3 class="icon">
                        <a href="?id=<?= $item['item_id'] ?>&delete=1#delete" class="delete" title="Delete">
                            <ion-icon name="trash"></ion-icon>
                        </a>
                    </h3>
                </div>
                <div class="img">
                    <img src="<?= htmlspecialchars($item['item_image']); ?>" alt="item">
                </div>
                <div class="text">
                    <h3>ID:<br> <?= htmlspecialchars($item['item_id']); ?></h3>
                    <h3>Title:<br> <?= htmlspecialchars($item['item_title']); ?></h3>
                    <h3>Menu:<br> <?= htmlspecialchars($item['item_type']) ?></h3>
                    <h3>Price:<br> <?= htmlspecialchars(round($item['item_price'], 3)) . " BHD" ?></h3>
                </div>
                <div class="buttons grid">
                    <button type="button" onclick="location.href='#';">
                        <ion-icon name="arrow-up"></ion-icon>
                    </button>
                </div>
            </form>
        <?php endif ?>
    <?php endif ?>

    <!-- Edit Item -->
    <?php if (!empty($_GET['edit']) && !empty($_GET['id'])) : ?>
        <?php if (!empty($item = getItemByID((int)$_GET['id']))) : ?>
            <form id="edit" action="#edit" class="forms reservation" method="POST" enctype="multipart/form-data">
                <h2>Edit Item</h2>
                <div class="form-icons">
                    <h3 class="icon">
                        <a href="?id=<?= $item['item_id'] ?>&edit=1#edit" class="edit" title="Edit">
                            <ion-icon name="create"></ion-icon>
                        </a>
                    </h3>
                    <h3 class="icon">
                        <a href="?id=<?= $item['item_id'] ?>&view=1#view" class="view" title="View">
                            <ion-icon name="eye"></ion-icon>
                        </a>
                    </h3>
                    <h3 class="icon">
                        <a href="?id=<?= $item['item_id'] ?>&delete=1#delete" class="delete" title="Delete">
                            <ion-icon name="trash"></ion-icon>
                        </a>
                    </h3>
                </div>
                <p class="error"><?= $error ?></p>
                <h3>Title:</h3>
                <input type="text" name="item_title" placeholder="Enter Item Title" maxlength="60" value="<?= htmlspecialchars($itemTitle ?? $item['item_title']); ?>" required>
                <h3>Type:</h3>
                <select name="item_type">
                    <?php if (!empty($menus)) : ?>
                        <?php foreach ($menus as $type) : ?>
                            <option value="<?= htmlspecialchars($type['item_type']) ?>" <?= $item['item_type'] == $type['item_type'] ? "selected" : "" ?>><?= htmlspecialchars(ucfirst($type['item_type'])) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <h3>Price (BHD):</h3>
                <input type="text" name="item_price" placeholder="Enter Hourly Rent Price" maxlength="60" value="<?= htmlspecialchars($itemPrice ?? $item['item_price']); ?>" required>
                <h3>Image File:</h3>
                <div class="img">
                    <img src="<?= $item['item_image']; ?>" alt="item">
                </div>
                <h4>Keep Same Image <input type="checkbox" name="ignore-image"></h4>
                <input type="file" name="image" accept="image/*">
                <div class="buttons grid">
                    <button type="button" onclick="location.href='#';">
                        <ion-icon name="arrow-up"></ion-icon>
                    </button>
                    <button type="submit" name="confirm-edit">Edit Item</button>
                </div>
            </form>
        <?php endif ?>
    <?php endif ?>


    <!-- Delete Item -->
    <?php if (!empty($_GET['delete']) && !empty($_GET['id'])) : ?>
        <?php if (!empty($item = getItemByID((int)$_GET['id']))) : ?>
            <form id="delete" action="#delete" class="forms reservation" method="POST">
                <h2>Delete Item</h2>
                <div class="form-icons">
                    <h3 class="icon">
                        <a href="?id=<?= $item['item_id'] ?>&edit=1#edit" class="edit" title="Edit">
                            <ion-icon name="create"></ion-icon>
                        </a>
                    </h3>
                    <h3 class="icon">
                        <a href="?id=<?= $item['item_id'] ?>&view=1#view" class="view" title="View">
                            <ion-icon name="eye"></ion-icon>
                        </a>
                    </h3>
                    <h3 class="icon">
                        <a href="?id=<?= $item['item_id'] ?>&delete=1#delete" class="delete" title="Delete">
                            <ion-icon name="trash"></ion-icon>
                        </a>
                    </h3>
                </div>
                <p class="error"><?= $error ?></p>
                <h3>Are you sure you want to remove <?= htmlspecialchars(ucfirst($item['item_title'])); ?>?</h3>
                <div class="buttons grid">
                    <button type="button" onclick="location.href='#';">
                        <ion-icon name="arrow-up"></ion-icon>
                    </button>
                    <button type="submit" name="confirm-delete">Delete Item</button>
                </div>
            </form>
        <?php endif ?>
    <?php endif ?>


</div>

<?php include './templates/footer.php' ?>