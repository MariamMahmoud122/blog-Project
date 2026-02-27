<?php
session_start();
include "db.php";

$emailError = "";
$passwordError = "";
$email = "";
$password = "";
$bigError = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {


    if (isset($_POST['register'])) {
        header("Location: register.php");
        exit;
    }

    if (isset($_POST['login'])) {

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

    
        if (empty($email)) {
            $emailError = "Email is required";
        } 
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailError = "Please type a valid email";
        }

    
        if (empty($password)) {
            $passwordError = "Password is required";
        }

  
        if (empty($emailError) && empty($passwordError)) {

            $query = "SELECT * FROM users WHERE email='$email' LIMIT 1";
            $result = mysqli_query($conn, $query);

            if ($result && mysqli_num_rows($result) == 1) {

                $row = mysqli_fetch_assoc($result);

                if (password_verify($password, $row['password'])) {

                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user_name'] = $row['name'];
                    $_SESSION['user_email'] = $row['email'];
                    $_SESSION['role'] = $row['role']; 
                    $_SESSION['user_id'] = $row['id'];

                    header("Location: posts.php");
                    exit;

                } else {
                    $passwordError = "Wrong password";
                }

            } else {
                $bigError = "Email not found";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login</title>

<style>
body{
    margin:0;
    height:100vh;
    font-family:Arial;
    background:linear-gradient(135deg,#df66ea,#9ce6f2);
    display:flex;
    justify-content:center;
    align-items:center;
}

.login-box{
    background:#fff;
    padding:30px;
    width:320px;
    border-radius:8px;
    text-align:center;
}

.login-box h2{
    margin-bottom:20px;
}

.login-box input{
    width:100%;
    padding:10px;
    margin-bottom:10px;
    border:1px solid #ccc;
    border-radius:5px;
}

.login-box button{
    width:100%;
    padding:10px;
    margin-top:5px;
    background:#939ced;
    border:none;
    color:white;
    border-radius:5px;
    cursor:pointer;
}

.login-box button:hover{
    background:#5ad8cb;
}

.error{
    color:#842029;
    background:#f8d7da;
    padding:6px;
    margin-bottom:10px;
    border-radius:4px;
    font-size:13px;
}
</style>

</head>
<body>

<div class="login-box">

<h2>Login</h2>

<form method="POST">

<?php if (!empty($bigError)) : ?>
<div class="error">
<?php echo $bigError; ?>
</div>
<?php endif; ?>

<input
type="email"
name="email"
placeholder="Email"
value="<?php echo htmlspecialchars($email); ?>"
>

<?php if (!empty($emailError)) : ?>
<div class="error">
<?php echo $emailError; ?>
</div>
<?php endif; ?>

<input
type="password"
name="password"
placeholder="Password"
>

<?php if (!empty($passwordError)) : ?>
<div class="error">
<?php echo $passwordError; ?>
</div>
<?php endif; ?>

<button type="submit" name="login">Login</button>

<button type="submit" name="register">Register</button>

</form>

</div>

</body>
</html>
