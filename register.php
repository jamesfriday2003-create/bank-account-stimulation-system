<?php

require_once "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (
        $name === "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 8
    ) {
        $message = "Enter valid details. Password must be at least 8 characters.";
    } else {

        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        try {

            $account_number = "100" . random_int(
                1000000000,
                9999999999
            );

            $sql = "INSERT INTO users
            (full_name, email, password, account_number)
            VALUES (?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                $name,
                $email,
                $hashed_password,
                $account_number
            ]);

            $message = "Account created successfully! Your account number is "
                     . $account_number;

        } catch (PDOException $e) {

            $message = "Registration failed. Email may already exist.";

        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bank Registration</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="container">

    <h2>Create Bank Account</h2>

    <p><?php echo htmlspecialchars($message); ?></p>

    <form method="POST">
        <input type="hidden" name="csrf_token"
       value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

        <input
            type="text"
            name="full_name"
            placeholder="Full Name"
            required
        >

        <input
            type="email"
            name="email"
            placeholder="Email Address"
            required
        >

        <input
            type="password"
            name="password"
            placeholder="Create Password (minimum 8 characters)"
            minlength="8"
            required
        >

        <button type="submit">Register</button>

    </form>

    <p>Already registered?
        <a href="login.php">Login</a>
    </p>

</div>

</body>
</html>