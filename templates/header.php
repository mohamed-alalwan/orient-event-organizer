<?php
/* Author: Mohamed Alalwan 201601446*/
include_once './config/db_connect.php';

session_start();

$medal = "";
$medal_name = "";

if (!empty($_SESSION['id'])) {
    $sql = "SELECT * from dbproj_user where user_id = {$_SESSION['id']}";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);
    if ($result) {

        if ($user['royalty_points'] > 5 && $user['royalty_points'] <= 10) {
            $medal = "./images/Profile/bronze-medal.png";
            $medal_name = "Bronze";
        } elseif ($user['royalty_points'] > 10 && $user['royalty_points'] <= 15) {
            $medal = "./images/Profile/silver-medal.png";
            $medal_name = "Silver";
        } elseif ($user['royalty_points'] > 15) {
            $medal = "./images/Profile/gold-medal.png";
            $medal_name = "Gold";
        }
    } else {
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
}

if (!empty($_GET['cancelBook']) && $_GET['cancelBook'] == 1) {
    removeBookData();
}

?>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orient Event Organizer</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./styles/styles.css">
    <script src="./scripts/scripts.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <!DOCTYPE html>
    <html lang="en">
    <!--Author: Mohamed Alalwan 201601446-->
</head>

<body>
    <nav class="topNav" id="topNavClick">
        <a href="index.php">
            <img class="logo" src="./images/Navigation/Logo_Black.png" alt="Logo">
        </a>
        <?php if (empty($_SESSION['id'])): ?>
                <a href="login.php"><button>Login / Register</button></a>
        <?php endif; ?>
        <ul>
            <li id="burger"><a href="#!" onclick="dropDownMenu()"><span class="burger-icon"></span></a></li>
            <li><a href="index.php">Home</a></li>
            <?php if (!empty($_SESSION['noti'])): ?>
                    <li>
                        <a href="#!" class="noti-anchor">
                            <span class="noti-container">
                                <ion-icon name="notifications"></ion-icon>
                                <span class="noti-badge">
                                    <?= count($_SESSION['noti']); ?>
                                </span>
                            </span>
                        </a>
                        <ul>
                            <?php foreach ($_SESSION['noti'] as $notification): ?>
                                    <li>
                                        <a href="<?= $notification['path']; ?>">
                                            <ion-icon name="alert-circle"></ion-icon>
                                            <?= $notification['text']; ?>
                                        </a>
                                    </li>
                            <?php endforeach ?>
                        </ul>
                    </li>
            <?php endif; ?>
            <?php if (!empty($_SESSION['id'])): ?>
                    <li><a href="#!">
                            <?php if ($medal != ""): ?>
                                    <img id="medal" src="<?= $medal; ?>" alt="medal">
                            <?php endif ?>
                            <?= ucfirst($_SESSION['username']); ?> ⮟
                        </a>
                        <ul>
                            <li><a href="user_profile.php">Profile</a></li>
                            <li><a href="bookedEvents.php">Booked Events</a></li>
                            <li><a href="logout.php">Logout</a></li>
                        </ul>
                    </li>
            <?php endif; ?>
            <?php
            if (!empty($user['type']))
                if ($user['type'] == "admin"):
                    ?>
                        <li><a href="#!">Admin Portal ⮟</a>
                            <ul>
                                <li><a href="#!">Management <span class="submenu-icon"></span></a>
                                    <ul>
                                        <li><a href="manageLocations.php">Locations</a></li>
                                        <li><a href="manageUsers.php">Users</a></li>
                                        <li><a href="manageServices.php">Services</a></li>
                                    </ul>
                                </li>
                                <li><a href="#!">Reports <span class="submenu-icon"></span></a>
                                    <ul>
                                        <li><a href="reportUsersMembership.php">Users Membership</a></li>
                                        <li><a href="reportReservationMonthlyRevenue.php">Monthly Sales Revenue</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                <?php endif; ?>
            <li><a href="events.php">Events</a></li>
            <li><a href="#!">Contacts</a></li>
        </ul>
    </nav>