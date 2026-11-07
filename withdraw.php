<?php

require_once "includes/auth.php";
require_once "config/database.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $amount = trim($_POST["amount"] ?? "");

    // Validate amount: maximum two decimal places
    if (!preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', $amount)) {

        $message = "Enter a valid withdrawal amount.";
        $message_type = "error";

    } else {

        // Convert amount to kobo to avoid floating-point errors
        $parts = explode(".", $amount);

        $whole = $parts[0];
        $fraction = str_pad(
            $parts[1] ?? "",
            2,
            "0"
        );

        $withdrawal_kobo =
            ((int)$whole * 100) + (int)$fraction;

        if ($withdrawal_kobo <= 0) {

            $message = "Withdrawal amount must be greater than zero.";
            $message_type = "error";

        } else {

            try {

                // Start database transaction
                $conn->beginTransaction();

                // Lock the customer's account during withdrawal
                $stmt = $conn->prepare(
                    "SELECT balance FROM users
                     WHERE id = ? FOR UPDATE"
                );

                $stmt->execute([
                    $_SESSION["user_id"]
                ]);

                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    throw new Exception("Account not found.");
                }

                // Convert available balance to kobo
                $balance_parts = explode(
                    ".",
                    (string)$user["balance"]
                );

                $balance_kobo =
                    ((int)$balance_parts[0] * 100) +
                    (int)str_pad(
                        $balance_parts[1] ?? "",
                        2,
                        "0"
                    );

                // Check available balance
                if ($withdrawal_kobo > $balance_kobo) {

                    throw new Exception(
                        "Insufficient balance."
                    );

                }

                // Update account balance
                $stmt = $conn->prepare(
                    "UPDATE users
                     SET balance = balance - ?
                     WHERE id = ?"
                );

                $stmt->execute([
                    $amount,
                    $_SESSION["user_id"]
                ]);

                // Record withdrawal transaction
                $stmt = $conn->prepare(
                    "INSERT INTO transactions
                    (user_id, transaction_type, amount, description)
                    VALUES (?, 'Withdrawal', ?, ?)"
                );

                $stmt->execute([
                    $_SESSION["user_id"],
                    $amount,
                    "Money withdrawn"
                ]);

                // Save all changes
                $conn->commit();

                $message = "Withdrawal successful!";
                $message_type = "success";

            } catch (Exception $e) {

                // Cancel changes if something fails
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }

                $message = $e->getMessage();
                $message_type = "error";

            } catch (PDOException $e) {

                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }

                $message = "A database error occurred. Please try again.";
                $message_type = "error";

            }

        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Withdraw Money</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<div class="container">

    <h2>Withdraw Money</h2>

    <?php if ($message !== ""): ?>

        <p class="<?php echo htmlspecialchars($message_type); ?>">
            <?php echo htmlspecialchars($message); ?>
        </p>

    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token"
       value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

        <input
            type="number"
            name="amount"
            placeholder="Enter Withdrawal Amount (₦)"
            min="0.01"
            max="9999999999999.99"
            step="0.01"
            required
        >

        <button type="submit">
            Withdraw Money
        </button>

    </form>

    <br>

    <a href="dashboard.php">Back to Dashboard</a>

</div>

</body>

</html>