<?php
session_start();
require "db.php";

$user_id = $_SESSION['user_id'] ?? 0;
$post_id = intval($_POST['post_id'] ?? 0);

$response = ['success' => false];

if($user_id && $post_id){
    // check if liked
    $check = mysqli_query($conn, "SELECT * FROM post_likes WHERE user_id=$user_id AND post_id=$post_id");
    if(mysqli_num_rows($check) > 0){
        mysqli_query($conn, "DELETE FROM post_likes WHERE user_id=$user_id AND post_id=$post_id");
        mysqli_query($conn, "UPDATE posts SET likes = likes - 1 WHERE id=$post_id");
        $response['liked'] = false;
    } else {
        mysqli_query($conn, "INSERT INTO post_likes (user_id, post_id) VALUES ($user_id, $post_id)");
        mysqli_query($conn, "UPDATE posts SET likes = likes + 1 WHERE id=$post_id");
        $response['liked'] = true;
    }

    $res = mysqli_query($conn, "SELECT likes FROM posts WHERE id=$post_id");
    $row = mysqli_fetch_assoc($res);
    $response['likes'] = $row['likes'];
    $response['success'] = true;
}

header('Content-Type: application/json');
echo json_encode($response);
