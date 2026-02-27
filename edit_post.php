<?php
session_start();
require "db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$post_id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM posts WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $post_id, $_SESSION['user_id']);
$stmt->execute();

$result = $stmt->get_result();
$post = $result->fetch_assoc();

if(!$post){
    echo "Post not found";
    exit;
}

$titleError = "";
$contentError = "";

if(isset($_POST['update_post'])){

    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category = trim($_POST['category']);

    if(empty($title)) $titleError = "Title required";
    if(empty($content)) $contentError = "Content required";

    if(empty($titleError) && empty($contentError)){

        $stmt = $conn->prepare("
            UPDATE posts 
            SET title=?, content=?, category=? 
            WHERE id=? AND user_id=?
        ");

        $stmt->bind_param("sssii", $title, $content, $category, $post_id, $_SESSION['user_id']);

        if($stmt->execute()){
            header("Location: posts.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Edit Post</title>

<style>

body{
    font-family: Arial;
    background:#f4f4f9;
    margin:0;
}

/* header */
header{
    background:#667eea;
    color:white;
    padding:20px;
}

/* container */
.container{
    max-width:600px;
    margin:40px auto;
}

/* card */
.card{
    background:white;
    padding:25px;
    border-radius:10px;
    box-shadow:0 2px 10px rgba(0,0,0,0.1);
}

/* inputs */
input, textarea{
    width:100%;
    padding:12px;
    margin-top:8px;
    margin-bottom:15px;
    border-radius:6px;
    border:1px solid #ccc;
    font-size:14px;
}

/* buttons */
.btn{
    padding:10px 20px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-size:15px;
}

.btn-update{
    background:#667eea;
    color:white;
}

.btn-update:hover{
    background:#5a67d8;
}

.btn-cancel{
    background:#ccc;
}

.btn-cancel:hover{
    background:#aaa;
}

.error{
    color:red;
    font-size:13px;
    margin-top:-10px;
    margin-bottom:10px;
}

.back-link{
    color:white;
    text-decoration:none;
}

</style>

</head>
<body>


<header>

<a href="posts.php" class="back-link">← Back to Posts</a>

<h2>Edit Post</h2>

</header>


<div class="container">

<div class="card">

<form method="POST">

<label>Title</label>

<input type="text"
       name="title"
       value="<?= htmlspecialchars($post['title']) ?>">

<div class="error"><?= $titleError ?></div>


<label>Content</label>

<textarea name="content" rows="5"><?= htmlspecialchars($post['content']) ?></textarea>

<div class="error"><?= $contentError ?></div>


<label>Category</label>

<input type="text"
       name="category"
       value="<?= htmlspecialchars($post['category']) ?>">


<button class="btn btn-update" name="update_post">
Update Post
</button>


<a href="posts.php">

<button type="button" class="btn btn-cancel">
Cancel
</button>

</a>


</form>

</div>

</div>

</body>
</html>