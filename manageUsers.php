<!DOCTYPE html>
<html lang="en">

<?php
/* Author: Mohamed Alalwan 201601446*/
include './templates/header.php';

$error = "";

//getting limited results
function getUsers($start, $perPage)
{
    global $conn;
    $sql = "SELECT * FROM dbproj_user 
    WHERE
        `type` = 'client' OR `approval` = 'N'
    LIMIT $start, $perPage";
    $result = mysqli_query($conn, $sql);
    if ($result)
        $users = mysqli_fetch_all($result, MYSQLI_ASSOC);
    else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
    return $users;
}

//get rows num
function getUsersRows()
{
    global $conn;
    $sql = "SELECT * FROM dbproj_user 
    WHERE
        `type` = 'client' OR `approval` = 'N'
    ";
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
function getUserByID($id)
{
    global $conn;
    $id = mysqli_real_escape_string($conn, $id);
    $sql = "SELECT * FROM dbproj_user WHERE user_id = $id";
    $result = mysqli_query($conn, $sql);
    if ($result)
        $user = mysqli_fetch_assoc($result);
    else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
    return $user;
}

//get unique types
function getUsersTypes()
{
    global $conn;
    $sql = "SELECT DISTINCT `type` FROM dbproj_user";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $types = mysqli_fetch_all($result, MYSQLI_ASSOC);
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
    return $types;
}

//check if user exists
function checkUserExistence()
{
    global $conn, $username, $email, $error;
    //if ignoring both username and password
    if (isset($_POST['ignore-username']) && isset($_POST['ignore-email'])) {
        return true;
    }
    //ignoring only username 
    elseif (isset($_POST['ignore-username'])) {
        $sql = "SELECT * from dbproj_user where email = '$email'";
        $i = 1;
    }
    //ignoring only email
    elseif (isset($_POST['ignore-email'])) {
        $sql = "SELECT * from dbproj_user where username = '$username'";
        $i = 2;
    }
    //no ignoring
    else {
        $sql = "SELECT * from dbproj_user where username = '$username' or email = '$email'";
        $i = 3;
    }
    //run query if exists
    if (!empty($sql)) {
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $users = mysqli_fetch_all($result, MYSQLI_ASSOC);
            if (count($users) > 0) {
                //fail: exists
                if ($i === 1)
                    $msg = "⚠ A user with the same email already exists!<br>";
                elseif ($i === 2)
                    $msg = "⚠ A user with the same username already exists!<br>";
                else
                    $msg = "⚠ A user with the same email or username already exists!<br>";
                $error .= $msg;
                return false;
            } else {
                //sucess: doesn't exist
                return true;
            }
        } else {
            //fail: query error
            die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
            return false;
        }
    }
}

//validate forms
function validateUserDetails($mode)
{
    global $error, $username, $userType, $types, $userDiscount, $email, $password, $passwordConfirm, $hashedPassword, $royaltyPoints, $approval;

    /*------------------------user-----------------------*/
    if (isset($_POST['ignore-username']) && isset($_GET['id'])) {
        $user = getUserByID((int)$_GET['id']);
        $username = $user['username'];
    } else {
        $username = $_POST["username"];
        if (!preg_match('/^[A-Za-z][0-9a-zA-Z_]{3,19}$/', $username)) {
            //fail: invalid username
            $error .= '⚠ Username must start with a letter and can contain 4-20 characters combination of letters, numbers or underscores.<br>';
        }
    }

    //check email
    if (isset($_POST['ignore-email']) && isset($_GET['id'])) {
        $user = $user ?? getUserByID((int)$_GET['id']);
        $email = $user['email'];
    } else {
        $email = $_POST['email'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error .= '⚠ Email must be a valid email adress!<br>';
        }
    }

    //check username and email existence
    checkUserExistence();

    //if chosen to ignore password 
    if (isset($_POST['ignore-password']) && isset($_GET['id'])) {
        $user = $user ?? getUserByID((int)$_GET['id']);
        $hashedPassword = $user['password'];
    } else {
        //check password
        $password = $_POST['password'];
        $number = preg_match('@[0-9]@', $password);
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);
        if (strlen($password) < 8 || !$number || !$uppercase || !$lowercase || !$specialChars) {
            //fail: password not strong enough
            $error .= "⚠ Password must be at least 8 characters in length and must contain at least one number, one upper case letter, one lower case letter and one special character.<br>";
        }

        //check password confirmation match
        $passwordConfirm = $_POST['passwordConfirm'];
        if (!($password === $passwordConfirm)) {
            //fail: passwords dont match
            $error .= "⚠ Passwords don't match!<br>";
        }
    }

    /*------------------------type-----------------------*/
    $userType = $_POST["type"];
    if (!in_array($userType, array_column($types, "type"))) {
        $error .= "⚠ Type is invalid.<br>";
    }

    if ($mode === "edit") {
        /*------------------------approval-----------------------*/
        $approval = $_POST["approval"];
        if (!in_array($approval, array('Y', 'N'))) {
            $error .= "⚠ Approval input is invalid.<br>";
        }

        /*------------------------discount rate-----------------------*/
        $userDiscount = $_POST["discount_rate"];
        if (!is_numeric($userDiscount)) {
            $error .= "⚠ Discount must be a numeric value in decimal format.<br>";
        }

        /*------------------------royalty_points-----------------------*/
        $royaltyPoints = (int)$_POST["royalty_points"];
        if ($royaltyPoints > 500) {
            $error .= "⚠ Royalty Points must be a number and cannot exceed 500.<br>";
        }
    }
}

//add
function addUser($username, $email, $password, $userType)
{
    global $conn;
    $username = mysqli_real_escape_string($conn, $username);
    $userType = mysqli_real_escape_string($conn, $userType);
    $password = mysqli_real_escape_string($conn, $password);
    $hashedPassword = hash('sha512', $password);
    $email = mysqli_real_escape_string($conn, $email);
    $sql = "INSERT INTO dbproj_user(`username`, `email`, `password`, `type`) VALUES('$username', '$email',  '$hashedPassword', '$userType');";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        //success
        alertBrowser("User Added Successfully!");
        redirectTo('./manageUsers.php');
        return true;
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
        return false;
    }
}

//edit
function editUser($id, $username, $email, $password, $userType, $userDiscount, $royaltyPoints, $approval)
{
    global $conn, $hashedPassword;
    $username = mysqli_real_escape_string($conn, $username);
    $email = mysqli_real_escape_string($conn, $email);
    $password = mysqli_real_escape_string($conn, $password);
    $hashedPassword = !empty($hashedPassword) ? mysqli_real_escape_string($conn, $hashedPassword) : hash('sha512', $password);
    $userType = mysqli_real_escape_string($conn, $userType);
    $userDiscount = mysqli_real_escape_string($conn, $userDiscount);
    $royaltyPoints = mysqli_real_escape_string($conn, $royaltyPoints);
    $sql = "UPDATE dbproj_user 
    SET 
        `username` = '$username',
        `email` = '$email',
        `password` = '$hashedPassword', 
        `type` = '$userType',
        `discount_rate` = '$userDiscount', 
        `royalty_points` = '$royaltyPoints', 
        `approval` = '$approval'
    WHERE
        user_id = $id;
    ";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        //success
        alertBrowser("User Modified Successfully!");
        redirectTo('./manageUsers.php');
        return true;
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
        return false;
    }
}

//delete
function deleteUser($id)
{
    global $conn;
    $sql = "DELETE FROM dbproj_user
    WHERE
        user_id = $id;
    ";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        //success
        alertBrowser("User Deleted Successfully!");
        redirectTo('./manageUsers.php');
        return true;
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
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
    //getting users
    $users = getUsers($start, $perPage);
    $types = getUsersTypes();
    $rows =  getUsersRows();
    $pages = ceil($rows / $perPage);
}

//create
if (isset($_POST['confirm-create'])) {
    validateUserDetails("create");
    if (empty($error)) {
        //success
        addUser($username, $email, $password, $userType);
    }
}

//edit
if (isset($_POST['confirm-edit']) && isset($_GET['id'])) {
    validateUserDetails("edit");
    if (empty($error)) {
        //success
        editUser(
            (int)$_GET['id'],
            $username,
            $email,
            $password,
            $userType,
            $userDiscount,
            $royaltyPoints,
            $approval
        );
    }
}

//delete
if (isset($_POST['confirm-delete']) && isset($_GET['id'])) {
    deleteUser((int)$_GET['id']);
}
?>
<div class="container">
    <!--Container-->
    <h1>Users
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
            <h4>USERNAME</h4>
            <h4>EMAIL</h4>
            <h4>TYPE</h4>
            <h4>ROYALTY POINTS</h4>
        </div>
    </div>

    <?php foreach ($users as $user) : ?>
        <?php $approvalMsg = ($user['approval'] != "Y") ? "(Needs Approval)" : ""; ?>
        <div class="table container">
            <div class="row location">
                <h4 class="icon">
                    <a href="?id=<?= $user['user_id'] ?>&edit=1#edit" class="edit" title="Edit">
                        <ion-icon name="create"></ion-icon>
                    </a>
                </h4>
                <h4 class="icon">
                    <a href="?id=<?= $user['user_id'] ?>&view=1#view" class="view" title="View">
                        <ion-icon name="eye"></ion-icon>
                    </a>
                </h4>
                <h4 class="icon">
                    <a href="?id=<?= $user['user_id'] ?>&delete=1#delete" class="delete" title="Delete">
                        <ion-icon name="trash"></ion-icon>
                    </a>
                </h4>
                <h4><?= htmlspecialchars($user['user_id']) ?></h4>
                <h4><?= htmlspecialchars($user['username']) ?></h4>
                <h4><?= htmlspecialchars($user['email']) ?></h4>
                <h4><?= htmlspecialchars(ucfirst($user['type']) . " $approvalMsg") ?></h4>
                <h4><?= htmlspecialchars($user['royalty_points']) ?></h4>
            </div>
        </div>
    <?php endforeach; ?>


    <div class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++) : ?>
            <a href="?page=<?= $i ?>&per-page=<?= $perPage ?>" <?= $page == $i ? 'class = "page-selected"' : "" ?>><?= $i ?></a>
        <?php endfor; ?>
    </div>

    <!-- Create user -->
    <?php if (!empty($_GET['create'])) : ?>
        <form id="create" action="#create" class="forms reservation" method="POST" enctype="multipart/form-data">
            <h2>Create User</h2>
            <p class="error"><?= $error ?></p>
            <h3>Username:</h3>
            <input type="text" name="username" placeholder="Enter Username" maxlength="60" value="<?= htmlspecialchars($username ?? ""); ?>" required>
            <h3>Email:</h3>
            <input type="email" placeholder="Enter Email" name="email" value="<?= htmlspecialchars($email ?? ""); ?>" required>
            <h3>Password:</h3>
            <input type="password" placeholder="Enter Password" name="password" value="<?= htmlspecialchars($password ?? ""); ?>" required>
            <h3>Confirm Password:</h3>
            <input type="password" placeholder="Enter Password Again" name="passwordConfirm" value="<?= htmlspecialchars($passwordConfirm ?? ""); ?>" required>
            <h3>Type:</h3>
            <select name="type">
                <?php if (!empty($types)) : ?>
                    <?php foreach ($types as $type) : ?>
                        <option value="<?= htmlspecialchars($type['type']) ?>"><?= htmlspecialchars(ucfirst($type['type'])) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <div class="buttons grid">
                <button type="button" onclick="location.href='#';">
                    <ion-icon name="arrow-up"></ion-icon>
                </button>
                <button type="submit" name="confirm-create">Create User</button>
            </div>
        </form>
    <?php endif ?>

    <!-- View user -->
    <?php if (!empty($_GET['view']) && !empty($_GET['id'])) : ?>
        <?php if (!empty($user = getUserByID((int)$_GET['id']))) : ?>
            <form id="view" action="#view" class="forms reservation">
                <h2>View User</h2>
                <div class="form-icons">
                    <h3 class="icon">
                        <a href="?id=<?= $user['user_id'] ?>&edit=1#edit" class="edit" title="Edit">
                            <ion-icon name="create"></ion-icon>
                        </a>
                    </h3>
                    <h3 class="icon">
                        <a href="?id=<?= $user['user_id'] ?>&view=1#view" class="view" title="View">
                            <ion-icon name="eye"></ion-icon>
                        </a>
                    </h3>
                    <h3 class="icon">
                        <a href="?id=<?= $user['user_id'] ?>&delete=1#delete" class="delete" title="Delete">
                            <ion-icon name="trash"></ion-icon>
                        </a>
                    </h3>
                </div>
                <div class="text">
                    <h3>ID:<br> <?= htmlspecialchars($user['user_id']); ?></h3>
                    <h3>Username:<br> <?= htmlspecialchars($user['username']); ?></h3>
                    <h3>Email:<br> <?= htmlspecialchars($user['email']) ?></h3>
                    <h3>Type:<br> <?= htmlspecialchars(ucfirst($user['type'])) ?></h3>
                    <h3>Royalty Points:<br> <?= htmlspecialchars($user['royalty_points']) ?></h3>
                    <h3>Discount Rate:<br> <?= htmlspecialchars($user['discount_rate'] * 100 . "%") ?></h3>
                    <h3>Created At:<br> <?= htmlspecialchars($user['created_at']); ?></h3>
                    <h3>Approval:<br><?= htmlspecialchars($user['approval'] == 'Y' ? "Approved" : "Not Approved"); ?></h3>
                </div>
                <div class="buttons grid">
                    <button type="button" onclick="location.href='#';">
                        <ion-icon name="arrow-up"></ion-icon>
                    </button>
                </div>
            </form>
        <?php endif ?>
    <?php endif ?>

    <!-- Edit user -->
    <?php if (!empty($_GET['edit']) && !empty($_GET['id'])) : ?>
        <?php if (!empty($user = getUserByID((int)$_GET['id']))) : ?>
            <form id="edit" action="#edit" class="forms reservation" method="POST" enctype="multipart/form-data">
                <h2>Edit User</h2>
                <div class="form-icons">
                    <h3 class="icon">
                        <a href="?id=<?= $user['user_id'] ?>&edit=1#edit" class="edit" title="Edit">
                            <ion-icon name="create"></ion-icon>
                        </a>
                    </h3>
                    <h3 class="icon">
                        <a href="?id=<?= $user['user_id'] ?>&view=1#view" class="view" title="View">
                            <ion-icon name="eye"></ion-icon>
                        </a>
                    </h3>
                    <h3 class="icon">
                        <a href="?id=<?= $user['user_id'] ?>&delete=1#delete" class="delete" title="Delete">
                            <ion-icon name="trash"></ion-icon>
                        </a>
                    </h3>
                </div>
                <p class="error"><?= $error ?></p>
                <h3>Username:</h3>
                <h4>Keep Same Username <input type="checkbox" name="ignore-username"></h4>
                <input type="text" name="username" placeholder="Enter user Title" maxlength="60" value="<?= htmlspecialchars($username ?? $user['username']); ?>">
                <h3>Email:</h3>
                <h4>Keep Same Email <input type="checkbox" name="ignore-email"></h4>
                <input type="email" placeholder="Enter Email" name="email" value="<?= htmlspecialchars($email ?? $user['email']); ?>">
                <h3>Password:</h3>
                <h4>Keep Same Password <input type="checkbox" name="ignore-password"></h4>
                <h3>New Password:</h3>
                <input type="password" placeholder="Enter Password" name="password" value="<?= htmlspecialchars($password ?? ""); ?>">
                <h3>Confirm New Password:</h3>
                <input type="password" placeholder="Enter Password Again" name="passwordConfirm" value="<?= htmlspecialchars($passwordConfirm ?? ""); ?>">
                <h3>Type:</h3>
                <select name="type">
                    <?php if (!empty($types)) : ?>
                        <?php foreach ($types as $type) : ?>
                            <option value="<?= htmlspecialchars($type['type']) ?>" <?= $user['type'] == $type['type'] ? "selected" : "" ?>><?= htmlspecialchars(ucfirst($type['type'])) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <h3>Approval:</h3>
                <select name="approval">
                    <option value="N" <?= ($user['approval'] == 'N') ? "selected" : "" ?>>Not Approved</option>
                    <option value="Y" <?= ($user['approval'] == 'Y') ? "selected" : "" ?>>Approved</option>
                </select>
                <h3>Discount Rate (Decimal):</h3>
                <input type="text" name="discount_rate" placeholder="Enter Discount Rate" maxlength="60" value="<?= htmlspecialchars($userDiscount ?? $user['discount_rate']); ?>" required>
                <h3>Royalty Points:</h3>
                <input type="number" placeholder="No." name="royalty_points" max="500" value="<?= htmlspecialchars($royaltyPoints ?? $user['royalty_points']); ?>" required>
                <div class="buttons grid">
                    <button type="button" onclick="location.href='#';">
                        <ion-icon name="arrow-up"></ion-icon>
                    </button>
                    <button type="submit" name="confirm-edit">Edit User</button>
                </div>
            </form>
        <?php endif ?>
    <?php endif ?>


    <!-- Delete user -->
    <?php if (!empty($_GET['delete']) && !empty($_GET['id'])) : ?>
        <?php if (!empty($user = getUserByID((int)$_GET['id']))) : ?>
            <form id="delete" action="#delete" class="forms reservation" method="POST">
                <h2>Delete User</h2>
                <div class="form-icons">
                    <h3 class="icon">
                        <a href="?id=<?= $user['user_id'] ?>&edit=1#edit" class="edit" title="Edit">
                            <ion-icon name="create"></ion-icon>
                        </a>
                    </h3>
                    <h3 class="icon">
                        <a href="?id=<?= $user['user_id'] ?>&view=1#view" class="view" title="View">
                            <ion-icon name="eye"></ion-icon>
                        </a>
                    </h3>
                    <h3 class="icon">
                        <a href="?id=<?= $user['user_id'] ?>&delete=1#delete" class="delete" title="Delete">
                            <ion-icon name="trash"></ion-icon>
                        </a>
                    </h3>
                </div>
                <p class="error"><?= $error ?></p>
                <h3>Are you sure you want to remove <?= htmlspecialchars(ucfirst($user['username'])); ?>?</h3>
                <div class="buttons grid">
                    <button type="button" onclick="location.href='#';">
                        <ion-icon name="arrow-up"></ion-icon>
                    </button>
                    <button type="submit" name="confirm-delete">Delete User</button>
                </div>
            </form>
        <?php endif ?>
    <?php endif ?>


</div>

<?php include './templates/footer.php' ?>