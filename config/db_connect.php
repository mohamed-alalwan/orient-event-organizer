<?php
/* Author: Mohamed Alalwan 201601446*/

$conn = mysqli_connect('host', 'user', 'password', 'db');

if (!$conn) {
    die('<b>' . 'Database Conection Error: ' . mysqli_connect_error() . '</b>');
}

function alertBrowser($message)
{
    echo "<script>alert('$message');</script>";
}

function redirectHome()
{
    echo "<script>window.location.replace('index.php');</script>";
}

function redirectTo($page)
{
    echo "<script>window.location.replace('$page');</script>";
}

function removeBookData()
{
    unset($_SESSION['eventBook']);
    unset($_SESSION['checkoutTotal']);
    removeNotification('booking');
}

/*----------notifications(Session Must Be Started)------------*/

//add
function addNotification($id, $path, $text)
{
    //set notification
    $notification = ['id' => $id, 'path' => $path, 'text' => $text];
    if (empty($_SESSION['noti'])) {
        $_SESSION['noti'] = [];
        array_push($_SESSION['noti'], $notification);
    } else if (!in_array($notification, $_SESSION['noti'])) {
        array_push($_SESSION['noti'], $notification);
    }
}

//remove
function removeNotification($id)
{
    if (!empty($_SESSION['noti'])) {
        for ($i = 0; $i < count($_SESSION['noti']); $i++) {
            if ($_SESSION['noti'][$i]['id'] == $id) {
                unset($_SESSION['noti'][$i]);
            }
        }
    }
}