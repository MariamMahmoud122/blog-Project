<?php
session_start();
include "db.php";

$user_id = $_SESSION['user_id'];

if(isset($_POST['update'])){

    if(isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] == 0){

        $imageName = time() . "_" . $_FILES['profile_img']['name'];

        $tmpName = $_FILES['profile_img']['tmp_name'];

        move_uploaded_file($tmpName, "uploads/" . $imageName);

        $sql = "UPDATE users SET profile_img='$imageName' WHERE id=$user_id";

        mysqli_query($conn, $sql);

        header("Location: profile.php");
        exit;
    }
}
?>

<h2>Update Profile Image</h2>

<form method="POST" enctype="multipart/form-data">

    <input type="file" name="profile_img" required>

    <br><br>

    <button type="submit" name="update">
        Update
    </button>

</form>