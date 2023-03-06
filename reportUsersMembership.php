<!DOCTYPE html>
<html lang="en">

<?php
/* Author: Mohamed Alalwan 201601446*/
include './templates/header.php';

$statusSelect = '';
//set statuses
$statuses = ['Golden', 'Silver', 'Bronze'];

function getUsers($status)
{
    global $conn;
    $condition = "";
    if (!empty($status)) {
        if ($status == "Bronze") {
            $condition = " WHERE royalty_points > 5 AND royalty_points <= 10";
        } elseif ($status == "Silver") {
            $condition = " WHERE royalty_points > 10 AND royalty_points <= 15";
        } elseif ($status == "Golden") {
            $condition = " WHERE royalty_points > 15";
        }
    }
    $sql = "SELECT * FROM dbproj_user" . $condition . " ORDER BY royalty_points DESC";
    $result = mysqli_query($conn, $sql);
    if ($result)
        $users = mysqli_fetch_all($result, MYSQLI_ASSOC);
    else {
        //fail: query error
        die('<b>' . 'query error: ' . mysqli_error($conn) . '</b>');
    }
    return $users;
}


if (empty($_SESSION['id']) || $user['type'] != "admin") {
    redirectHome();
}

if (isset($_POST['submitReport'])) {
    $statusSelect = $_POST['statusSelect'];
    if (in_array($statusSelect, $statuses) || empty($statusSelect))
        $users = getUsers($statusSelect);

    if (!empty($users)) {
        for ($i = 0; $i < count($users); $i++) {
            if ($users[$i]['royalty_points'] > 5 && $users[$i]['royalty_points'] <= 10) {
                $users[$i]['medal'] = "./images/Profile/bronze-medal.png";
                $users[$i]['medal_name'] = "Bronze";
            } elseif ($users[$i]['royalty_points'] > 10 && $users[$i]['royalty_points'] <= 15) {
                $users[$i]['medal'] = "./images/Profile/silver-medal.png";
                $users[$i]['medal_name'] = "Silver";
            } elseif ($users[$i]['royalty_points'] > 15) {
                $users[$i]['medal'] = "./images/Profile/gold-medal.png";
                $users[$i]['medal_name'] = "Golden";
            } else {
                $users[$i]['medal'] = "";
                $users[$i]['medal_name'] = "";
            }
        }
    }
}

?>
<div class="container events">

    <!--Container-->
    <h1>Users Membership Report</h1>
    <form action="#" class="forms" method="POST">
        <h3>Membership Status:
            <select name="statusSelect">
                <option value="">All</option>
                <?php foreach ($statuses as $status) :  ?>
                    <option value="<?= $status; ?>" <?= ($statusSelect == $status) ? "selected" : ""; ?>>
                        <?= ucfirst($status); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </h3>
        <div class="buttons">
            <a href="reportUsersMembership.php">
                <button type="button">Refresh</button>
            </a>
            <button type="submit" name="submitReport">Submit</button>
        </div>

    </form>

    <?php if (isset($users)) : ?>
        <div class="table titles container">
            <div class="row location">
                <h4>USERNAME</h4>
                <h4>EMAIL</h4>
                <h4>ROYALTY POINTS</h4>
                <h4>DISCOUNT</h4>
                <h4>STATUS</h4>
            </div>
        </div>

        <?php if (count($users) == 0) : ?>
            <div class="table container">
                <div class="row">
                    <h2>No users found.</h2>
                </div>
            </div>
        <?php endif ?>

        <?php foreach ($users as $user) : ?>
            <div class="table container">
                <div class="row location">
                    <h4><?= htmlspecialchars($user['username']) ?></h4>
                    <h4><?= htmlspecialchars($user['email']) ?></h4>
                    <h4><?= htmlspecialchars($user['royalty_points']) ?></h4>
                    <h4><?= htmlspecialchars($user['discount_rate'] * 100) . "%" ?></h4>
                    <h4>
                        <?php if ($user['medal'] != "" && $user['medal_name'] != "") : ?>
                            <img id="medal" src="<?= $user['medal'] ?>" alt="medal">
                            <?= $user['medal_name'] ?>
                        <?php else : ?>
                            <?= "None" ?>
                        <?php endif; ?>
                    </h4>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include './templates/footer.php' ?>