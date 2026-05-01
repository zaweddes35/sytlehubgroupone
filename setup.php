<?php
declare(strict_types=1);

require 'db.php';

$checks = [];
$okCount = 0;
$failCount = 0;

function addCheck(array &$checks, string $label, bool $ok, string $details = ''): void
{
    global $okCount, $failCount;
    $checks[] = [
        'label' => $label,
        'ok' => $ok,
        'details' => $details
    ];

    if ($ok) {
        $okCount++;
    } else {
        $failCount++;
    }
}

try {
    addCheck($checks, 'Database connection', true, 'Connected successfully');

    $requiredTables = ['categories', 'users', 'products', 'orders', 'order_items'];
    $foundTablesStmt = $pdo->query('SHOW TABLES');
    $foundTables = $foundTablesStmt->fetchAll(PDO::FETCH_COLUMN);
    $foundMap = array_fill_keys($foundTables, true);

    foreach ($requiredTables as $table) {
        addCheck(
            $checks,
            "Table exists: {$table}",
            isset($foundMap[$table]),
            isset($foundMap[$table]) ? 'OK' : 'Missing'
        );
    }

    $categoryCount = (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    addCheck(
        $checks,
        'Default categories seeded',
        $categoryCount >= 4,
        "Found {$categoryCount} categories"
    );

    $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    addCheck(
        $checks,
        'Admin user exists',
        $adminCount >= 1,
        "Found {$adminCount} admin user(s)"
    );
} catch (Throwable $e) {
    addCheck($checks, 'Unexpected setup check error', false, $e->getMessage());
}

$allGood = $failCount === 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Check - StyleHub</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6fb; margin: 0; color: #222; }
        .wrap { max-width: 820px; margin: 3rem auto; padding: 0 1rem; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.08); padding: 1.25rem 1.5rem; margin-bottom: 1rem; }
        h1 { margin: 0 0 .4rem; font-size: 1.5rem; }
        .summary { font-size: .95rem; color: #555; margin-bottom: 1rem; }
        .status { padding: .4rem .7rem; border-radius: 999px; font-weight: 700; font-size: .8rem; display: inline-block; }
        .ok { background: #d4edda; color: #155724; }
        .bad { background: #f8d7da; color: #721c24; }
        table { width: 100%; border-collapse: collapse; font-size: .95rem; }
        th, td { text-align: left; padding: .65rem .45rem; border-bottom: 1px solid #eee; vertical-align: top; }
        th { color: #666; font-size: .85rem; text-transform: uppercase; letter-spacing: .5px; }
        .footer { margin-top: 1rem; color: #666; font-size: .9rem; }
        a { color: #e94560; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>StyleHub Setup Check</h1>
            <p class="summary">
                <?php if ($allGood): ?>
                    <span class="status ok">Healthy</span>
                    All checks passed (<?= $okCount ?> / <?= $okCount + $failCount ?>).
                <?php else: ?>
                    <span class="status bad">Needs Attention</span>
                    <?= $failCount ?> check(s) failed.
                <?php endif; ?>
            </p>
            <table>
                <thead>
                    <tr>
                        <th>Check</th>
                        <th>Result</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($checks as $check): ?>
                        <tr>
                            <td><?= htmlspecialchars($check['label']) ?></td>
                            <td>
                                <span class="status <?= $check['ok'] ? 'ok' : 'bad' ?>">
                                    <?= $check['ok'] ? 'PASS' : 'FAIL' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($check['details']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="footer">
                Open <a href="index.php">store home</a> or <a href="admin.php">admin panel</a> after all checks pass.
            </p>
        </div>
    </div>
</body>
</html>
