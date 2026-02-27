<?php
session_start();
include "db.php";
$name  = $_SESSION['old']['name'] ?? '';
$email = $_SESSION['old']['email'] ?? '';

$nameError = $_SESSION['errors']['nameError'] ?? '';
$emailError = $_SESSION['errors']['emailError'] ?? '';
$passwordError = $_SESSION['errors']['passwordError'] ?? '';
$confirmError = $_SESSION['errors']['confirmError'] ?? '';

unset($_SESSION['old']);
unset($_SESSION['errors']);

/////////////////////////////
if ($_SERVER["REQUEST_METHOD"] == "POST") {

     $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    /*  Name validation  */
    if (empty($name)) {
        $nameError = "Name is required";
    } elseif (strlen($name) < 3) {
        $nameError = "Name must be at least 3 characters";
    }

    /*  Email validation  */
    if (empty($email)) {
        $emailError = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailError = "Please type a valid email";
    }

    /*  Password validation  */
    if (empty($password)) {
        $passwordError = "Password is required";
    } elseif (
        strlen($password) < 6 ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[\W$!_]/', $password)
    ) {
        $passwordError = "Password must be at least 6 characters and contain a number and a symbol";
    }
     /*   Confirm password */
    if (empty($confirm)) {
        $confirmError = "Confirm password is required";
    } elseif ($password !== $confirm) {
        $confirmError = "Passwords do not match";
    }
    if (!empty($nameError) || !empty($emailError) || 
        !empty($passwordError) || !empty($confirmError)) {

        $_SESSION['old'] = [
            'name' => $name,
            'email' => $email
        ];

        $_SESSION['errors'] = [
            'nameError' => $nameError,
            'emailError' => $emailError,
            'passwordError' => $passwordError,
            'confirmError' => $confirmError
        ];

        header("Location: register.php");
        exit;
    }



///////////////////////////
    if (
    empty($nameError) &&
    empty($emailError) &&
    empty($passwordError) &&
    empty($confirmError)
) {

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $emailError = "Email already exists";
    } else {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $insert = $conn->prepare(
            "INSERT INTO users (name, email, password) VALUES (?, ?, ?)"
        );
        $insert->bind_param("sss", $name, $email, $hashedPassword);

        if ($insert->execute()) {
            header("Location: login.php");
            exit;
        }
    }
}

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <style>
        body {
            margin: 0;
            height: 100vh;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #df66ea, #9ce6f2);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-box {
            background: #fff;
            padding: 30px;
            width: 340px;
            border-radius: 8px;
            text-align: center;
        }
        .login-box input {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .login-box button {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            background: #939ced;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        .login-box button:hover {
            background: #5ad8cb;
        }
        .error {
            color:#842029;
            background:#f8d7da;
            padding:6px;
            margin-top:-8px;
            margin-bottom:10px;
            border-radius:4px;
            font-size:12px;
        }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Register</h2>

    <form method="POST">

        <input type="text" name="name" placeholder="Full Name" value="<?php echo $name; ?>">
               
        <?php if ($nameError): ?><div class="error"><?php echo $nameError; ?></div><?php endif; ?>

        <input type="text" name="email" placeholder="Email"   value="<?php echo $email; ?>"
               >
        <?php if ($emailError): ?><div class="error"><?php echo $emailError; ?></div><?php endif; ?>

        <input type="password" name="password" placeholder="Password" >
        <?php if ($passwordError): ?><div class="error"><?php echo $passwordError; ?></div><?php endif; ?>

        <input type="password" name="confirm_password" placeholder="Confirm Password" >
        <?php if ($confirmError): ?><div class="error"><?php echo $confirmError; ?></div><?php endif; ?>

        <button type="submit">Create Account</button>

        <a href="login.php">
            <button type="button">Login</button>
        </a>
    </form>
</div>

</body>
</html>
