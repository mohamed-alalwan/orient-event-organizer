<?php

/* Author: Mohamed Alalwan 201601446*/

include_once './config/db_connect.php';

//declaration
$errors = ['username' => '', 'email' => '', 'password' => '', 'passwordConfirm' => '', 'login' => ''];
$username = $email = $password = $passwordConfirm = $type = "";

//user register
if (isset($_POST['submitRegister'])) {
    registerUser();
}

//user login
if (isset($_POST['submitLogin'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    loginUser($username, $password);
}

function loginUser($username, $password)
{
    global $conn, $errors;
    //hashing password
    $hashedPassword = hash('sha512', $password);
    $sql = "SELECT * FROM dbproj_user where (username = '$username' or email = '$username') and password = '$hashedPassword'";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $user = mysqli_fetch_assoc($result);
        if (!empty($user)) {
            if ($user['approval'] == "N") {
                //fail: needs admin approval
                $message = "⚠ Login Fail: The user {$user['username']} needs approval!";
                $errors['login'] = $message;
                echo "<script> 
                        window.location.hash = '';
                    </script>";
                return;
            }
            session_start();
            $_SESSION['id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $message = "Logged in as {$user['username']}!";
            alertBrowser($message);
            redirectHome();
        } else {
            //fail: wrong login details
            $errors['login'] = "⚠ Login Fail: Username or password are incorrect!";
        }
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
}

function registerUser()
{
    global $conn, $username, $email, $password, $passwordConfirm, $type;
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $passwordConfirm = mysqli_real_escape_string($conn, $_POST['passwordConfirm']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $approval = 'Y';
    if ($type == "admin") {
        $approval = 'N';
    }
    //validate data
    if (!validateData($username, $email, $password, $passwordConfirm)) {
        return;
    }
    //hashing password
    $hashedPassword = hash('sha512', $password);
    $sql = "INSERT INTO dbproj_user(username, email, password, type, approval) VALUES('$username', '$email',  '$hashedPassword', '$type', '$approval');";
    if (mysqli_query($conn, $sql)) {
        //success
        alertBrowser("The user $username has been successfully registered!");
        loginUser($username, $password);
    } else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
}

function validateData($username, $email, $password, $passwordConfirm)
{
    global $errors;
    $valid = true;
    //check username
    if (!preg_match('/^[A-Za-z][0-9a-zA-Z_]{3,19}$/', $username)) {
        //fail: invalid username
        $errors['username'] = '⚠ Username must start with a letter and can contain 4-20 characters combination of letters, numbers or underscores.';
        $valid = false;
    }
    //check email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = '⚠ Email must be a valid email adress!';
        $valid = false;
    }
    //check username and email existence
    if (!checkUserExistence($username, $email)) {
        //fail: user exists
        $errors['username'] = "⚠ A user with the same email or username already exists!";
        $valid = false;
    }
    //check password
    $number = preg_match('@[0-9]@', $password);
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $specialChars = preg_match('@[^\w]@', $password);
    if (strlen($password) < 8 || !$number || !$uppercase || !$lowercase || !$specialChars) {
        //fail: password not strong enough
        $errors['password'] = "⚠ Password must be at least 8 characters in length and must contain at least one number, one upper case letter, one lower case letter and one special character.";
        $valid = false;
    }
    //check password confirmation match
    if (!($password === $passwordConfirm)) {
        //fail: passwords dont match
        $errors['passwordConfirm'] = "⚠ Passwords don't match!";
        $valid = false;
    }
    return $valid;
}

function checkUserExistence($username, $email)
{
    global $conn;
    $sql = "SELECT * from dbproj_user where username = '$username' or email = '$email'";
    $result = mysqli_query($conn, $sql);
    $users = mysqli_fetch_all($result, MYSQLI_ASSOC);
    if (count($users) > 0) {
        //fail: exists
        return false;
    } else {
        //sucess: doesn't exist
        return true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<?php
include './templates/header.php';
if (!empty($_SESSION['id'])) {
    redirectHome();
}
?>
<div class="container login">
    <br>
    <!-- Login Form -->
    <form class="forms login" action="#login" method="POST">
        <h2>Login To Your Account</h2>
        <p class="error">
            <?= $errors['login']; ?>
        </p>
        <h3>Username:</h3>
        <input id="login" type="text" placeholder="Enter Username Or Email" name="username" value="<?php if (isset($_POST['submitLogin'])) echo htmlspecialchars($username); ?>" required>
        <h3>Password:</h3>
        <input type="password" placeholder="Enter Password" name="password" value="<?php if (isset($_POST['submitLogin'])) echo htmlspecialchars($password); ?>" required>
        <div class="buttons">
            <a href="#register" onclick="displayRegister()">
                <h4>Don't Have An Account?</h4>
            </a>
            <br>
            <button type="submit" name="submitLogin">Login</button>
            <button type="reset">Reset</button>
        </div>
    </form>
    
     <?php
    //unset username if not email
    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $email = $username;
        $username = "";
    }
    ?>
    
    <!-- Login Form -->
    <form id="registerForm" class="forms register" action="#register" method="POST" <?php if (isset($_POST['submitRegister'])) echo 'style="display: block;"' ?>>
        <h2>Register Now</h2>
        <h3>Username:</h3>
        <input id="register" type="text" placeholder="Enter Username" name="username" value="<?= htmlspecialchars($username); ?>" required>
        <p class="error">
            <?= $errors['username']; ?>
        </p>
        <h3>Email:</h3>
        <input type="email" placeholder="Enter Email" name="email" value="<?= htmlspecialchars($email); ?>" required>
        <p class="error">
            <?= $errors['email']; ?>
        </p>
        <h3>Password:</h3>
        <input type="password" placeholder="Enter Password" name="password" value="<?= htmlspecialchars($password); ?>" required>
        <p class="error">
            <?= $errors['password']; ?>
        </p>
        <h3>Confirm Password:</h3>
        <input type="password" placeholder="Enter Password Again" name="passwordConfirm" value="<?= htmlspecialchars($passwordConfirm); ?>" required>
        <p class="error">
            <?= $errors['passwordConfirm']; ?>
        </p>
        <h3>Type:</h3>
        <select name="type">
            <option value="client" <?php if ($type == "client") echo "checked"; ?>>Client</option>
            <option value="admin" <?php if ($type == "admin") echo "checked"; ?>>Admin</option>
        </select>
        <div class="buttons">
            <button type="submit" name="submitRegister">Register</button>
            <button type="reset">Reset</button>
        </div>
    </form>
</div>
<?php include './templates/footer.php' ?>