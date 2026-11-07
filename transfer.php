<?php

require_once "includes/auth.php";
require_once "config/database.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $account_number = trim($_POST["account_number"] ?? "");
    $amount = trim($_POST["amount"] ?? "");

    // Validate transfer amount
    if (!preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', $amount)) {

        $message = "Enter a valid transfer amount.";
        $message_type = "error";

    } elseif ($account_number === "") {

        $message = "Enter the recipient account number.";
        $message_type = "error";

    } else {

        // Convert amount into kobo for accurate comparison
        $parts = explode(".", $amount);

        $whole = $parts[0];
        $fraction = str_pad($parts[1] ?? "", 2, "0");

        $amount_kobo =
            ((int)$whole * 100) + (int)$fraction;

        $amount_for_db =
            (string)(int)$whole . "." . $fraction;

        if ($amount_kobo <= 0) {

            $message = "Transfer amount must be greater than zero.";
            $message_type = "error";

        } else {

            try {

                $conn->beginTransaction();

                $sender_id = (int)$_SESSION["user_id"];

                // Find recipient account
                $stmt = $conn->prepare(
                    "SELECT id FROM users
                     WHERE account_number = ?"
                );

                $stmt->execute([$account_number]);

                $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$recipient) {
                    throw new RuntimeException(
                        "Recipient account does not exist."
                    );
                }

                $recipient_id = (int)$recipient["id"];

                // Prevent transfers to the same account
                if ($sender_id === $recipient_id) {
                    throw new RuntimeException(
                        "You cannot transfer money to yourself."
                    );
                }

                // Lock both accounts in a consistent order
                $ids = [$sender_id, $recipient_id];
                sort($ids, SORT_NUMERIC);

                $stmt = $conn->prepare(
                    "SELECT id, balance, account_number
                     FROM users
                     WHERE id IN (?, ?)
                     ORDER BY id
                     FOR UPDATE"
                );

                $stmt->execute($ids);

                $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($accounts) !== 2) {
                    throw new RuntimeException(
                        "Could not verify both accounts."
                    );
                }

                // Identify sender and recipient
                $sender = null;
                $receiver = null;

                foreach ($accounts as $account) {

                    if ((int)$account["id"] === $sender_id) {
                        $sender = $account;
                    }

                    if ((int)$account["id"] === $recipient_id) {
                        $receiver = $account;
                    }
                }

                if (!$sender || !$receiver) {
                    throw new RuntimeException(
                        "Account verification failed."
                    );
                }

                // Convert sender balance into kobo
                $balance_parts = explode(
                    ".",
                    (string)$sender["balance"]
                );

                $balance_kobo =
                    ((int)$balance_parts[0] * 100) +
                    (int)str_pad(
                        $balance_parts[1] ?? "",
                        2,
                        "0"
                    );

                // Check available balance
                if ($amount_kobo > $balance_kobo) {
                    throw new RuntimeException(
                        "Insufficient balance."
                    );
                }

                // Deduct money from sender
                $stmt = $conn->prepare(
                    "UPDATE users
                     SET balance = balance - ?
                     WHERE id = ?"
                );

                $stmt->execute([
                    $amount_for_db,
                    $sender_id
                ]);

                // Add money to recipient
                $stmt = $conn->prepare(
                    "UPDATE users
                     SET balance = balance + ?
                     WHERE id = ?"
                );

                $stmt->execute([
                    $amount_for_db,
                    $recipient_id
                ]);

                // Record sender transaction
                $stmt = $conn->prepare(
                    "INSERT INTO transactions
                    (user_id, transaction_type, amount, description)
                    VALUES (?, 'Transfer', ?, ?)"
                );

                $stmt->execute([
                    $sender_id,
                    $amount_for_db,
                    "Transfer to account " . $account_number
                ]);

                // Record recipient transaction
                $stmt->execute([
                    $recipient_id,
                    $amount_for_db,
                    "Transfer received from account " .
                    $sender["account_number"]
                ]);

                // Complete transfer
                $conn->commit();

                $message = "Transfer successful!";
                $message_type = "success";

            } catch (PDOException $e) {

                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }

                $message = "A database error occurred. Please try again.";
                $message_type = "error";

            } catch (Throwable $e) {

                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }

                $message = $e->getMessage();
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

    <title>Transfer Money</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<div class="container">

    <h2>Transfer Money</h2>

    <?php if ($message !== ""): ?>

        <p class="<?php echo htmlspecialchars($message_type); ?>">
            <?php echo htmlspecialchars($message); ?>
        </p>

    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token"
       value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

        <input
            type="text"
            name="account_number"
            placeholder="Recipient Account Number"
            required
        >

        <input
            type="number"
            name="amount"
            placeholder="Enter Amount (₦)"
            min="0.01"
            step="0.01"
            required
        >

        <button type="submit">
            Transfer Money
        </button>

    </form>

    <br>

    <a href="dashboard.php">Back to Dashboard</a>

</div>

</body>

</html>