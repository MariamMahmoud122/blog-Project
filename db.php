<?php
$conn = mysqli_connect("localhost", "root", "", "blogg");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
