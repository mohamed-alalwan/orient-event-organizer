<?php
/* Author: Mohamed Alalwan 201601446*/

include './config/db_connect.php';

session_start();
if (empty($_SESSION['eventBook']) || empty($_SESSION['id'])) {
    redirectHome();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Page</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./styles/payment.css">
</head>

<body>
    <!-- Text -->
    <div class="body-text">The total reservation price is <?= $_SESSION['checkoutTotal'] . " BHD"; ?>. Please enter your card information to finish your booking process. Upon successful payment, you will be sent an email with the reservation summary as well as the reservation number.</div>
    <form action="./reservationConfirm.php" method="POST">
        <div class="form-container">
            <div class="personal-information">
                <h1>Payment Information</h1>
            </div>

            <!--Credit Info-->
            <input id="column-left" type="text" name="first-name" placeholder="First Name" maxlength="30" minlength="1" required />
            <input id="column-right" type="text" name="last-name" placeholder="Surname" maxlength="30" minlength="1" required />
            <input id="input-field" type="text" name="number" placeholder="Card Number" maxlength="19" minlength="16" required />
            <input id="column-left" type="text" name="expiry" placeholder="MM / YY" required />
            <input id="column-right" type="text" name="cvc" placeholder="CCV" minlength="3" maxlength="4" required />
            <div class="card-wrapper"></div>

            <!--Billing-->
            <input id="input-field" type="text" name="streetaddress" required="required" autocomplete="on" maxlength="45" placeholder="Streed Address" />
            <input id="column-left" type="text" name="city" required="required" autocomplete="on" maxlength="20" placeholder="City" />
            <input id="column-right" type="text" name="zipcode" required="required" autocomplete="on" pattern="[0-9]*" maxlength="5" placeholder="ZIP code" />

            <!--Submit-->
            <input id="input-button" type="submit" name="paymentSubmit" value="submit" />

    </form>
    </div>
    <!-- Scripts -->
    <script src='//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js'></script>
    <script src='https://s3-us-west-2.amazonaws.com/s.cdpn.io/121761/card.js'></script>
    <script src='https://s3-us-west-2.amazonaws.com/s.cdpn.io/121761/jquery.card.js'></script>
    <script src="./scripts/payment.js"></script>

</body>

</html>