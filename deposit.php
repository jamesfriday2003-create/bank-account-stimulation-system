<?php

require_once "includes/auth.php";
require_once "config/database.php";
require_once __DIR__ . '/includes/csrf.php';
verify_csrf_token();

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $amount = filter_input(
        INPUT_POST,
        "amount",
        FILTER_VALIDATE_FLOAT
    );

    if ($amount === false || $amount === null || !is_finite($amount) || $amount <= 0) {

        $message = "Enter a valid deposit amount.";

    } else {

        try {

            $conn->beginTransaction();

            $stmt = $conn->prepare(
                "UPDATE users SET balance = balance + ?
                 WHERE id = ?"
            );

            $stmt->execute([
                $amount,
                $_SESSION["user_id"]
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception("Account not found.");
            }

            $stmt = $conn->prepare(
                "INSERT INTO transactions
                (user_id, transaction_type, amount, description)
                VALUES (?, 'Deposit', ?, ?)"
            );

            $stmt->execute([
                $_SESSION["user_id"],
                $amount,
                "Money deposited"
            ]);

            $conn->commit();

            $message = "Deposit successful!";

        } catch (Exception $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $message = "Deposit failed. Please try again.";

        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Deposit Money</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="container">

    <h2>Deposit Money</h2>

    <p>
        <?php echo htmlspecialchars($message); ?>
    </p>

    <form method="POST">
        <input type="hidden" name="csrf_token"
       value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

        <input
            type="number"
            name="amount"
            placeholder="Enter Amount (₦)"
            min="0.01"
            step="0.01"
            required
        >

        <button type="submit">
            Deposit Money
        </button>

    </form>

    <br>

    <a href="dashboard.php">Back to Dashboard</a>

</div>

</body>
</html>