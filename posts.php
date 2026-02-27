<?php 
session_start();
require "db.php"; 


if (isset($_POST['delete_post'])) {

    $post_id = intval($_POST['post_id']);

    $stmt = $conn->prepare("
        UPDATE posts 
        SET deleted_at = NOW()
        WHERE id=? AND (user_id=? OR ?='admin')
    ");

    $stmt->bind_param("iis", $post_id, $_SESSION['user_id'], $_SESSION['role']);
    $stmt->execute();
    $stmt->close();

    header("Location: posts.php");
    exit;
}
if (isset($_GET['success']) && $_GET['success'] == 1) {
    echo "<script>
        alert('Post added successfully');
    </script>";
}


if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$stmt = $conn->prepare("SELECT profile_img FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$profile_img = $user['profile_img'] ?? 'default.png';

/* PROFILE IMAGE UPLOAD */
if(isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] == 0){

    $fileName = $_FILES['profile_img']['name'];
    $tmpName  = $_FILES['profile_img']['tmp_name'];
    $fileSize = $_FILES['profile_img']['size'];


    $allowed = ['jpg','jpeg','png','gif','jfif'];

    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if(in_array($ext, $allowed)){

        $newName = "profile_" . $_SESSION['user_id'] . "_" . time() . "." . $ext;

        $uploadPath = "uploads/" . $newName;

       
        if(move_uploaded_file($tmpName, $uploadPath)){

            $stmt = $conn->prepare("
                UPDATE users 
                SET profile_img=?, profile_updated_at=NOW() 
                WHERE id=?
            ");

            $stmt->bind_param("si", $newName, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();

 
            $_SESSION['profile_img'] = $newName;

            header("Location: posts.php");
            exit;

        }

    }else{

        echo "Invalid file type";

    }

}


$titleError = "";
$contentError = "";
$oldTitle = "";
$oldContent = "";
$oldCategory = "";




if (isset($_POST['add_post'])) {

    $oldTitle = trim($_POST['title'] ?? '');
    $oldContent = trim($_POST['content'] ?? '');
    $oldCategory = trim($_POST['category'] ?? '');
    $newCategory = trim($_POST['new_category'] ?? '');

    if (empty($oldTitle)) $titleError = "Title is required";
    if (empty($oldContent)) $contentError = "Content is required";

    
    if (!empty($newCategory) && isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {

        $oldCategory = strtolower($newCategory);

    }

    if (empty($titleError) && empty($contentError)) {

        $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content, category) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $oldTitle, $oldContent, $oldCategory);

        if ($stmt->execute()) {

            header("Location: posts.php?success=1");
            exit;

        } else {

            echo "Error inserting post: " . $stmt->error;

        }

        $stmt->close();
    }
}


/* ================= POST ACTIONS ================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['add_post'])) {

   

if (isset($_POST['delete_comment'])) {

    $comment_id = intval($_POST['comment_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    if($comment_id > 0){

        $stmt = $conn->prepare("
            UPDATE post_comments 
            SET deleted_at = NOW()
            WHERE id=? AND (user_id=? OR ?='admin')
        ");

        $stmt->bind_param("iis", $comment_id, $user_id, $role);
        $stmt->execute();

        if($stmt->affected_rows > 0){
          
        }else{
            echo "Delete failed";
        }

        $stmt->close();
    }

    header("Location: posts.php");
    exit;
}

     $post_id = intval($_POST['post_id']);

    if (isset($_POST['like'])) {

   

        $check = mysqli_query($conn, "SELECT * FROM post_likes WHERE user_id=$user_id AND post_id=$post_id");

        if (mysqli_num_rows($check) > 0) {

            mysqli_query($conn, "DELETE FROM post_likes WHERE user_id=$user_id AND post_id=$post_id");
            mysqli_query($conn, "UPDATE posts SET likes = likes - 1 WHERE id=$post_id");

        } else {

            mysqli_query($conn, "INSERT INTO post_likes (user_id, post_id) VALUES ($user_id, $post_id)");
            mysqli_query($conn, "UPDATE posts SET likes = likes + 1 WHERE id=$post_id");

        }
    }


   

    if (isset($_POST['add_comment'])) {

        $comment_text = trim($_POST['comment_text'] ?? '');
        $parent_id = intval($_POST['parent_id'] ?? 0);

        if (!empty($comment_text)) {

            $stmt = $conn->prepare("
                INSERT INTO post_comments 
                (post_id, user_id, comment_text, parent_id)
                VALUES (?,?,?,?)
            ");

            if ($parent_id == 0) {

                $null = NULL;
                $stmt->bind_param("iisi", $post_id, $user_id, $comment_text, $null);

            } else {

                $stmt->bind_param("iisi", $post_id, $user_id, $comment_text, $parent_id);

            }

            $stmt->execute();
            $stmt->close();
        }
    }


   

    if (isset($_POST['share'])) {

        mysqli_query($conn, "UPDATE posts SET shares = shares + 1 WHERE id=$post_id");

    }


    
}



/* ================= FETCH POSTS ================= */
$filterCategory = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

$query = "
SELECT posts.*, users.name, users.profile_img,
(
    SELECT COUNT(*) 
    FROM post_comments 
    WHERE post_comments.post_id = posts.id
    AND post_comments.deleted_at IS NULL
) AS comments_count
FROM posts
JOIN users ON posts.user_id = users.id
";

$where = [];


$where[] = "posts.deleted_at IS NULL";

if (!empty($filterCategory)) {
    $safeCategory = mysqli_real_escape_string($conn, $filterCategory);
    $where[] = "posts.category = '$safeCategory'";
}

if (!empty($search)) {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $where[] = "(posts.title LIKE '%$safeSearch%' OR posts.content LIKE '%$safeSearch%')";
}


$query .= " WHERE " . implode(" AND ", $where);

$query .= " ORDER BY posts.created_at DESC";

$posts = mysqli_query($conn, $query);

$categories = [];

$result = mysqli_query($conn, "SELECT DISTINCT category FROM posts WHERE category IS NOT NULL AND category != ''");

while ($row = mysqli_fetch_assoc($result)) {

    $categories[$row['category']] = ucfirst($row['category']);

}
;


/* ================= DISPLAY COMMENTS ================= */

function displayComments($conn, $post_id, $parent_id = NULL, $margin = 0) {

    if ($parent_id === NULL) {

        $query = "
        SELECT post_comments.*, users.name
        FROM post_comments
        JOIN users ON post_comments.user_id = users.id
        WHERE post_id = $post_id 
AND parent_id IS NULL
AND deleted_at IS NULL
        ORDER BY created_at ASC
        ";

    } else {

        $query = "
        SELECT post_comments.*, users.name
        FROM post_comments
        JOIN users ON post_comments.user_id = users.id
        WHERE post_id = $post_id 
AND parent_id = $parent_id
AND deleted_at IS NULL
        ORDER BY created_at ASC
        ";

    }

    $result = mysqli_query($conn, $query);

    while ($c = mysqli_fetch_assoc($result)) {

        echo "<div style='margin-left:".$margin."px;
                padding:10px;
                margin-top:10px;
                border-radius:10px;
                background:white;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>";

        echo "<strong>".htmlspecialchars($c['name']).":</strong> ";
        echo htmlspecialchars($c['comment_text']);


   

        echo "<div style='margin-top:5px; display:flex; gap:5px;'>";

echo "<button class='reply-btn' onclick='reply(".$c['id'].", ".$post_id.")'>
<i class='fa-regular fa-comment-dots'></i> Reply
</button>";

if($c['user_id'] == $_SESSION['user_id'] || $_SESSION['role']=='admin'){

    echo "
    <form method='POST' style='display:inline;'>

        <input type='hidden' name='comment_id' value='".$c['id']."'>
        <input type='hidden' name='post_id' value='".$post_id."'>

        <button type='submit' name='delete_comment'
            class='reply-btn'
            style='background:#ff4d4d;color:white;'>

            <i class='fa-solid fa-trash'></i> Delete

        </button>

    </form>";

}

echo "</div>";

       


        /* reply box with unique id */

        echo "
        <div class='reply-box reply-box-".$c['id']."' style='display:none; margin-top:5px;'>

        <form method='POST'>

        <input type='text' name='comment_text' placeholder='Write a reply...' style='width:500px;'>

        <input type='hidden' name='post_id' value='$post_id'>
        <input type='hidden' name='parent_id' value='".$c['id']."'>

        <button type='submit' name='add_comment'>Reply</button>

        </form>

        </div>
        ";


        displayComments($conn, $post_id, $c['id'], $margin + 30);

        echo "</div>";
    }
 
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Blog Posts</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<style>

body { font-family: Arial, sans-serif; background: #f4f4f9; margin:0; padding:0;}
header { background: #667eea; color: white; padding: 20px; text-align:center;}
header a { color:white; text-decoration:none; margin-left:20px; font-size:14px; }
.container { max-width: 800px; margin:20px auto; padding:0 15px; }
.add-post { background:white; padding:20px; margin-bottom:30px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1);}
.add-post input, .add-post textarea, .add-post select { width:100%; padding:10px; margin-bottom:15px; border-radius:5px; border:1px solid #ccc; font-size:14px; }
.add-post button { background:#667eea; color:white; border:none; padding:10px 15px; border-radius:5px; cursor:pointer; font-size:16px; }
.add-post button:hover { background:#5a67d8; }
.title-content { color: #a14edc; font-weight: bold; text-transform: uppercase; font-family: 'Pacifico', cursive; font-size: 12px; }
.post { background:white; padding:15px 20px; margin-bottom:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.post-header { display:flex; align-items:center; margin-bottom:10px; }
.profile-img { width:40px; height:40px; border-radius:50%; margin-right:10px; }
.post-content { margin-bottom:10px; }
.post-actions { display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee; padding-top:10px; }
.like-btn, .comment-btn, .share-btn { background:none; border:none; cursor:pointer; display:flex; align-items:center; gap:6px; font-size:16px; color:#65676b; }
.like-btn i.liked { color:#e0245e; }
.comment-btn { justify-content:center; flex:1; }
.logout_link { color: blue; text-decoration: none; font-size: 14px; margin-right: 2px; }
.error { color:#142536; background:#f8d7da; padding:6px; margin-top:-8px; margin-bottom:10px; border-radius:4px; font-size:12px; } 
.category-filter { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
.filter-btn { padding:8px 15px; border:none; border-radius:20px; background:#eee; cursor:pointer; transition:0.3s; }
.filter-btn.active { background:#667eea; color:white; }
.filter-btn:hover { background:liner-gredient(blue,white); color:white; }
a {
    text-decoration: none; 
}
.reply-btn {
    padding: 6px 12px;
    border-radius: 20px;
    border: none;
    background: #eee;
    color: #333;
    cursor: pointer;
    font-size: 12px;
    margin-top: 5px;
    transition: 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 5px; 
}

.reply-btn:hover {
    background: #667eea;
    color: white;
}

.reply-btn i {
    font-size: 12px;
}
.like-comment-btn, .dislike-comment-btn {
    padding: 6px 12px;
    border-radius: 20px;
    border: none;
    background: #eee;
    color: #333;
    cursor: pointer;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    gap: 5px; 
    transition: 0.3s;
}

.like-comment-btn:hover, .dislike-comment-btn:hover {
    background: #667eea;
    color: white;
}

.like-comment-btn i, .dislike-comment-btn i {
    font-size: 12px;}

.profile-wrapper {
    position: relative;
    width: 60px;
    height: 60px;
}

.profile-wrapper img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    cursor: pointer;
}

.profile-wrapper label {
    position: absolute;
    bottom: 0;
    right: 0;
    background: #fff;
    border-radius: 50%;
    padding: 4px;
    cursor: pointer;
    border: 1px solid #ccc;
    z-index: 10; 
}

</style>
</head>
<body>

<nav class="navbar navbar-expand-lg bg-body-tertiary">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Navbar</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
      <div class="navbar-nav">
        <a class="nav-link active" href="#">Home</a>
        <a class="nav-link" href="#">Features</a>
      </div>
      <div class="navbar-nav ms-auto">
        <a class="logout_link nav-link" href="logout.php">Logout</a>
      </div>
    </div>
  </div>
</nav>

<header style="display:flex; align-items:center; justify-content:space-between; padding:20px; background:#667eea; color:white;">
    
    <div style="display:flex; align-items:center; gap:15px;">

        <div style="position:relative; width:60px; height:60px;">

            <form method="POST" enctype="multipart/form-data" id="profile-form">

              

     <img src="uploads/<?= htmlspecialchars($profile_img) ?>?t=<?= time() ?>" 
     class="profile-img"
     style="width:60px; height:60px; border-radius:50%; object-fit:cover;">
                <input type="file" name="profile_img" id="profile-upload" style="display:none;">

                <label for="profile-upload"
                       style="position:absolute; bottom:0; right:0; background:white; color:black; border-radius:50%; padding:5px; cursor:pointer;">
                    ✏
                </label>

            </form>

        </div>

        <h1 style="margin:0;">
            Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>!
        </h1>

    </div>

</header>

<div class="container">

<div >
<form method="GET" style="display:flex; gap:10px;">
    
    <input type="text" name="search" placeholder="Search posts..."
        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
        style="flex:1; padding:10px; border-radius:5px; border:1px solid #ccc;">

    <button type="submit"
        style="padding:10px 20px; background:#667eea; color:white; border:none; border-radius:5px;">
        Search
    </button>

</form>
</div>
<div class="category-filter">
    <a href="posts.php" class="filter-btn <?= !isset($_GET['category']) ? 'active' : '' ?>">All</a>
    
    <?php foreach($categories as $key => $val): ?>
        <a href="posts.php?category=<?= $key ?>" 
           class="filter-btn <?= (isset($_GET['category']) && $_GET['category'] == $key) ? 'active' : '' ?>">
           <?= $val ?>
        </a>
    <?php endforeach; ?>
</div>
<div id="show-post-box" 
     style="display: <?= ($titleError || $contentError) ? 'none' : 'block' ?>; background: white; padding: 15px; border-radius: 8px; cursor: pointer; text-align: center; border: 1px dashed #667eea; color: #667eea; margin-bottom: 20px;">
    
    <i class="fa-solid fa-plus-circle"></i> Want to create a post?
</div>
<div class="add-post" id="post-form-div" style="display: <?= ($titleError || $contentError) ? 'block' : 'none' ?>;">
<h2>Add New Post</h2>
<form method="POST">
    <input type="text" name="title" placeholder="Title" value="<?= htmlspecialchars($oldTitle) ?>">
    <?php if($titleError) echo "<div class='error'>$titleError</div>"; ?>

    <textarea name="content" placeholder="Content" rows="4"><?= htmlspecialchars($oldContent) ?></textarea>
    <?php if($contentError) echo "<div class='error'>$contentError</div>"; ?>

    <select name="category">
        <option value="">Select Category</option>
        <?php foreach($categories as $key => $val): ?>
            <option value="<?= $key ?>" <?= ($oldCategory==$key)?'selected':'' ?>><?= $val ?></option>
        <?php endforeach; ?>
    </select>

    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
        <input type="text" name="new_category" placeholder="Add new category" value="<?= htmlspecialchars($_POST['new_category'] ?? '') ?>">
    <?php endif; ?>

    <button type="submit" name="add_post">Add Post</button>
    <button type="button" onclick="hidePostForm()" style="background:#ccc;color:black;">Cancel</button>
</form>
</div>



<?php while($row = mysqli_fetch_assoc($posts)): 
    $liked = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM post_likes WHERE user_id=$user_id AND post_id={$row['id']}")) > 0;
?>
<div class="post" data-category="<?= htmlspecialchars($row['category']) ?>" data-post-id="<?= $row['id'] ?>">
<div class="post-header" style="display:flex; align-items:center; justify-content:space-between;">

    <div style="display:flex; align-items:center; gap:10px;">
       <img src="uploads/<?= htmlspecialchars($row['profile_img']) ?>?t=<?= time() ?>" class="profile-img">
        <strong><?= htmlspecialchars($row['name']) ?></strong>
        
        
    </div>

    <?php if($row['user_id'] == $_SESSION['user_id'] || (isset($_SESSION['role']) && $_SESSION['role']=='admin')): ?>
    
        <div style="display:flex; gap:10px; align-items:center;">

            <!-- Edit -->
            <a href="edit_post.php?id=<?= $row['id'] ?>" 
               style="color:#667eea; font-size:14px; text-decoration:none;">
               <i class="fa-solid fa-ellipsis"></i> Edit
            </a>

            <!-- Delete -->
            <form method="POST" onsubmit="return confirmDelete();" style="display:inline;">
                <input type="hidden" name="post_id" value="<?= $row['id'] ?>">
                
                <button type="submit" name="delete_post"
                        style="background:none;border:none;color:red;cursor:pointer;font-size:14px;">
                    <i class="fa-regular fa-trash-can"></i> Delete
                </button>
            </form>

        </div>

    <?php endif; ?>

</div>
<div style="font-size:12px; color:gray;">
    <?= date("F j, Y, g:i a", strtotime($row['created_at'])) ?>
</div>
    <div class="title-content"><?= htmlspecialchars($row['title']) ?></div>
    <div class="post-content"><?= htmlspecialchars($row['content']) ?></div>

    <div class="post-actions">

    <button class="like-btn" data-post-id="<?= $row['id'] ?>">
        <i class="<?= $liked ? 'fa-solid liked' : 'fa-regular' ?> fa-heart"></i>
        <span><?= $row['likes'] ?? 0 ?></span>
    </button>

        <button class="comment-btn" type="button">
            <i class="fa-regular fa-comment"></i>
            <span><?= $row['comments_count'] ?? 0 ?></span>
        </button>

        <form method="POST" style="display:inline;">
            <input type="hidden" name="post_id" value="<?= $row['id'] ?>">
            <button type="submit" name="share" class="share-btn">
                <i class="fa-solid fa-share"></i>
                <span><?= $row['shares'] ?? 0 ?></span>
            </button>
        </form>
    </div>

    <div class="comments-section" style="display:none; margin-top:10px;">
       
<form method="POST">
  <input type="text" name="comment_text" placeholder="Write a comment..." style="width:600px;">
  <input type="hidden" name="post_id" value="<?= $row['id'] ?>">
  <input type="hidden" name="parent_id" value="0">
  <button type="submit" name="add_comment">Comment</button>
</form>
    

<div class="comments-list">
    <?php displayComments($conn, $row['id']); ?>
</div>
</div>
</div>
<?php endwhile; ?>


<script>
document.getElementById("profile-upload").onchange = function(){
    document.getElementById("profile-form").submit();
};



document.querySelectorAll('.comment-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const post = btn.closest('.post');
    const commentsSection = post.querySelector('.comments-section');
    commentsSection.style.display = commentsSection.style.display === 'none' ? 'block' : 'none';
  });
});


const showBox = document.getElementById('show-post-box');
const postForm = document.getElementById('post-form-div');


showBox.addEventListener('click', function() {
    postForm.style.display = 'block'; 
    showBox.style.display = 'none';   
});


function hidePostForm() {
    postForm.style.display = 'none';  
    showBox.style.display = 'block';  
}
function reply(comment_id, post_id) {

    const replyBox = document.querySelector(".reply-box-" + comment_id);

    if (replyBox.style.display === "none" || replyBox.style.display === "") {

        replyBox.style.display = "block";
        replyBox.querySelector("input[name='comment_text']").focus();

    } else {

        replyBox.style.display = "none";

    }

}
function confirmDelete() {
    return confirm("Are you sure you want to delete this post?");
}
document.querySelectorAll('.like-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault(); 
        const postId = btn.dataset.postId;

        fetch('like_post.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `post_id=${postId}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success){
                
                btn.querySelector('span').textContent = data.likes;
               
                btn.querySelector('i').classList.toggle('liked', data.liked);
            }
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>