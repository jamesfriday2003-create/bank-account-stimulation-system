<?php

require_once "includes/auth.php";
require_once "config/database.php";

// Get transactions belonging to the logged-in customer
$stmt = $conn->prepare(
    "SELECT id, transaction_type, amount,
            description, created_at
     FROM transactions
     WHERE user_id = ?
     ORDER BY created_at DESC, id DESC
     LIMIT 100"
);

$stmt->execute([$_SESSION["user_id"]]);

$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Transaction History</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<div class="container history-container">

    <h2>Transaction History</h2>

    <p>View your recent banking activities below.</p>

    <?php if (count($transactions) > 0): ?>

        <div class="transaction-list">

            <?php foreach ($transactions as $transaction): ?>

                <?php

                $type = $transaction["transaction_type"];
                $description = $transaction["description"] ?? "";

                // Determine whether money entered or left the account
                $is_credit = (
                    $type === "Deposit" ||
                    (
                        $type === "Transfer" &&
                        strpos(
                            $description,
                            "Transfer received from account "
                        ) === 0
                    )
                );

                $sign = $is_credit ? "+" : "-";

                $color_class = $is_credit
                    ? "credit"
                    : "debit";

                ?>

                <div class="transaction-item">

                    <div class="transaction-details">

                        <h3>
                            <?php echo htmlspecialchars($type); ?>
                        </h3>

                        <p>
                            <?php echo htmlspecialchars($description); ?>
                        </p>

                        <small>
                            <?php

                            echo htmlspecialchars(
                                date(
                                    "d M Y, h:i A",
                                    strtotime($transaction["created_at"])
                                )
                            );

                            ?>
                        </small>

                    </div>

                    <div class="transaction-amount <?php echo $color_class; ?>">

                        <?php

                        echo $sign . "₦" .
                            number_format(
                                (float)$transaction["amount"],
                                2
                            );

                        ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="empty-history">

            <h3>No Transactions Yet</h3>

            <p>Your banking activities will appear here.</p>

        </div>

    <?php endif; ?>

    <br>

    <a href="dashboard.php">Back to Dashboard</a>

</div>

</body>

</html>