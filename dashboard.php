<?php

require_once "includes/auth.php";
require_once "config/database.php";

// Get logged-in customer
$stmt = $conn->prepare(
    "SELECT id, full_name, email, account_number, balance
     FROM users WHERE id = ?"
);

$stmt->execute([$_SESSION["user_id"]]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Get latest five transactions
$stmt = $conn->prepare(
    "SELECT transaction_type, amount, description, created_at
     FROM transactions
     WHERE user_id = ?
     ORDER BY created_at DESC, id DESC
     LIMIT 5"
);

$stmt->execute([$_SESSION["user_id"]]);

$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Banking Dashboard</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR -->

    <aside class="sidebar" id="sidebar">

        <div class="brand">
            <h2>GOMOSLEAD</h2>
            <span>BANK SIMULATION</span>
        </div>

        <nav>

            <a href="dashboard.php" class="active">
                <span>⌂</span> Dashboard
            </a>

            <a href="deposit.php">
                <span>＋</span> Deposit
            </a>

            <a href="withdraw.php">
                <span>−</span> Withdraw
            </a>

            <a href="transfer.php">
                <span>⇄</span> Transfer
            </a>

            <a href="transactions.php">
                <span>▤</span> Transactions
            </a>

        </nav>

        <a href="logout.php" class="logout">
            Logout
        </a>

    </aside>


    <!-- MAIN CONTENT -->

    <main class="main-content">

        <header class="topbar">

            <button class="menu-toggle"
                    id="menuToggle"
                    aria-label="Toggle navigation">
                ☰
            </button>

            <div>
                <h2>Dashboard</h2>
                <p>Your banking overview</p>
            </div>

            <div class="profile">
                <div class="avatar">
                    <?php echo strtoupper(
                        substr($user["full_name"], 0, 1)
                    ); ?>
                </div>

                <div>
                    <strong><?php echo e($user["full_name"]); ?></strong>
                    <small>Customer</small>
                </div>
            </div>

        </header>


        <!-- WELCOME -->

        <section class="welcome">

            <h1>
                Welcome back,
                <?php echo e($user["full_name"]); ?>!
            </h1>

            <p>Manage your money with ease and confidence.</p>

        </section>


        <!-- BALANCE CARD -->

        <section class="balance-card">

            <div>
                <p>Available Account Balance</p>

                <h1>
                    ₦<?php echo number_format(
                        (float)$user["balance"],
                        2
                    ); ?>
                </h1>

                <small>
                    Account Number:
                    <?php echo e($user["account_number"]); ?>
                </small>
            </div>

            <div class="balance-icon">
                ₦
            </div>

        </section>


        <!-- QUICK ACTIONS -->

        <section class="quick-section">

            <h2>Quick Actions</h2>

            <div class="action-grid">

                <a href="deposit.php" class="action-card">
                    <div class="action-icon green">＋</div>
                    <h3>Deposit</h3>
                    <p>Add money to your account</p>
                </a>

                <a href="withdraw.php" class="action-card">
                    <div class="action-icon orange">−</div>
                    <h3>Withdraw</h3>
                    <p>Withdraw your money</p>
                </a>

                <a href="transfer.php" class="action-card">
                    <div class="action-icon blue">⇄</div>
                    <h3>Transfer</h3>
                    <p>Send money to another account</p>
                </a>

                <a href="transactions.php" class="action-card">
                    <div class="action-icon purple">▤</div>
                    <h3>History</h3>
                    <p>View your transactions</p>
                </a>

            </div>

        </section>


        <!-- RECENT TRANSACTIONS -->

        <section class="recent-section">

            <div class="section-heading">

                <h2>Recent Transactions</h2>

                <a href="transactions.php">
                    View All
                </a>

            </div>

            <?php if (count($transactions) > 0): ?>

                <div class="table-wrapper">

                    <table>

                        <thead>
                            <tr>
                                <th>Transaction</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Amount</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php foreach ($transactions as $tx): ?>

                            <?php

                            $description = $tx["description"] ?? "";

                            $credit = (
                                $tx["transaction_type"] === "Deposit" ||
                                (
                                    $tx["transaction_type"] === "Transfer" &&
                                    strpos(
                                        $description,
                                        "Transfer received from account "
                                    ) === 0
                                )
                            );

                            ?>

                            <tr>

                                <td>
                                    <?php echo e($tx["transaction_type"]); ?>
                                </td>

                                <td>
                                    <?php echo e($description); ?>
                                </td>

                                <td>
                                    <?php echo date(
                                        "d M Y",
                                        strtotime($tx["created_at"])
                                    ); ?>
                                </td>

                                <td class="<?php echo $credit ? 'credit' : 'debit'; ?>">

                                    <?php echo $credit ? '+' : '-'; ?>
                                    ₦<?php echo number_format(
                                        (float)$tx["amount"],
                                        2
                                    ); ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <p class="empty-message">
                    You have no transactions yet.
                </p>

            <?php endif; ?>

        </section>

        <footer>
            © <?php echo date("Y"); ?>
            Gomoslead Bank Simulation System.
            Educational project only.
        </footer>

    </main>

</div>

<script src="js/script.js"></script>

</body>
</html>