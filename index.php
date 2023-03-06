<?php
/* Author: Mohamed Alalwan 201601446*/
include 'templates/header.php';
?>

<div class="container header">
    <h1 class="title"> ☻ Welcome <?= ucfirst($_SESSION['username'] ?? "")  ?> to Orient Event Organizer!</h1>
    <div class="slideshow">
        <div class="slideshow-item">
            <img src="./images/SlideShow/bkg_1.jpg" alt="background">
            <div class="slideshow-item-text">
                <h5>Simple</h5>
                <p>Yes, it is that simple. No funny business, instant booking and all is required is a couple of clicks. It only takes a minute at most to get our exclusive originzing services for your events. Give it a try! </p>
            </div>
        </div>
        <div class="slideshow-item">
            <img src="./images/SlideShow/bkg_2.jpg" alt="background">
            <div class="slideshow-item-text">
                <h5>Experience</h5>
                <p>With our certified partners, you are guaranteed to get great experience no matter what event you are planning. Orient Event Organizer only offers the highest ranking management for events. Specialised in workshops, trainings, and seminars. </p>
            </div>
        </div>
        <div class="slideshow-item">
            <img src="./images/SlideShow/bkg_3.jpg" alt="background">
            <div class="slideshow-item-text">
                <h5>About Us</h5>
                <p> With our extensive 12+ years experience in the event organizing business, we can safely assure our clients premium and satisfactory services. In the market since since 2010.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php' ?>