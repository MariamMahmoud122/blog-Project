<?php
session_start();
require "db.php"; 

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];


if($role == 'admin') {
    $query = "SELECT posts.*, users.name FROM posts 
              JOIN users ON posts.user_id = users.id 
              WHERE posts.deleted_at IS NULL
              ORDER BY posts.created_at DESC";
} else {
    $query = "SELECT posts.*, users.name FROM posts 
              JOIN users ON posts.user_id = users.id 
              WHERE posts.deleted_at IS NULL AND posts.user_id = $user_id
              ORDER BY posts.created_at DESC";
}


$result = $conn->query($query);
if(!$result) {
    die("Query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
<?php include "sidebar.php"; ?>

<div class="flex-grow-1 p-4">
<h2>Posts Dashboard</h2>
<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
<th>ID</th>
<th>Title</th>
<th>User</th>
<th>Date</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['title']}</td>";
    echo "<td>{$row['name']}</td>";
    echo "<td>{$row['created_at']}</td>";
    echo "<td>";

    if($row['user_id'] == $user_id || $role == 'admin') {
        echo "<a href='edit_post.php?id={$row['id']}' class='btn btn-sm btn-warning'>Edit</a> ";
        echo "<a href='delete_post.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure?\")'>Delete</a>";
    } else {
        echo "-";
    }

    echo "</td>";
    echo "</tr>";
}
?>
</tbody>
</table>

</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>