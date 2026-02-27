
<?php
session_start();
include "db.php";

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'];

// هل اليوزر عامل لايك قبل كده؟
$check = mysqli_query($conn,
    "SELECT * FROM post_likes WHERE user_id=$user_id AND post_id=$post_id"
);

if (mysqli_num_rows($check) == 0) {
 
    mysqli_query($conn,
        "INSERT INTO post_likes (user_id, post_id) VALUES ($user_id, $post_id)"
    );
    mysqli_query($conn,
        "UPDATE posts SET likes = likes + 1 WHERE id = $post_id"
    );
} else {
    
    mysqli_query($conn,
        "DELETE FROM post_likes WHERE user_id=$user_id AND post_id=$post_id"
    );
    mysqli_query($conn,
        "UPDATE posts SET likes = likes - 1 WHERE id = $post_id"
    );
}

// رجّع العدد الجديد
$result = mysqli_query($conn, "SELECT likes FROM posts WHERE id=$post_id");
$row = mysqli_fetch_assoc($result);

echo $row['likes'];
