<?php

session_start();
require_once "includes/session.php";
require_once "includes/csrf.php";
require_once "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") { 
    verify_csrf_token();
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE email = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (
        $user &&
        password_verify($password, $user["password"])
    ) {

        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["id"];

        header("Location: dashboard.php");
        exit();

    } else {

        $message = "Invalid email or password.";

    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bank Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="container">

    <h2>Customer Login</h2>

    <p><?php echo htmlspecialchars($message); ?></p>

    <form method="POST">
        <input type="hidden" name="csrf_token"
       value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

        <input
            type="email"
            name="email"
            placeholder="Email Address"
            required
        >

        <input
            type="password"
            name="password"
            placeholder="Password"
            required
        >

        <button type="submit">Login</button>

    </form>

    <p>Don't have an account?
        <a href="register.php">Register</a>
    </p>

</div>

</body>
</html>