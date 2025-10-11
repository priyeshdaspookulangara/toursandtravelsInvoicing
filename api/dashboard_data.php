<?php
// Set the content type to JSON
header('Content-Type: application/json');

// Include necessary files. We need db.php for database functions.
// auth.php is included to ensure only authenticated users can access this data.
require_once '../auth.php';
require_once '../db.php';

$response = [
    'success' => false,
    'message' => '',
    'data' => [
        'monthly_revenue' => [],
        'invoice_status' => []
    ]
];

$conn = db_connect();
if (!$conn) {
    $response['message'] = "Database connection failed.";
    echo json_encode($response);
    exit;
}

// --- 1. Fetch Monthly Revenue for the last 6 months ---
// This query sums the grand_total of 'Paid' invoices for each of the last 6 months.
$monthly_revenue_sql = "
    SELECT
        DATE_FORMAT(invoice_date, '%Y-%m') AS month,
        SUM(grand_total) AS total_revenue
    FROM invoices
    WHERE status = 'Paid' AND invoice_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
    ORDER BY month ASC
";

$revenue_result = db_query($monthly_revenue_sql);
if ($revenue_result) {
    $revenue_data = db_fetch_all($revenue_result);

    // Prepare a default structure for the last 6 months with 0 revenue
    $last_six_months = [];
    for ($i = 5; $i >= 0; $i--) {
        $month_key = date('Y-m', strtotime("-$i months"));
        $last_six_months[$month_key] = 0;
    }

    // Fill in the revenue data from the query
    foreach ($revenue_data as $row) {
        $last_six_months[$row['month']] = (float)$row['total_revenue'];
    }

    $response['data']['monthly_revenue'] = [
        'labels' => array_keys($last_six_months),
        'values' => array_values($last_six_months)
    ];

} else {
    $response['message'] = 'Failed to fetch monthly revenue data.';
    // Don't exit here, we can still try to fetch the other data
}


// --- 2. Fetch Invoice Counts by Status ---
$invoice_status_sql = "
    SELECT
        status,
        COUNT(id) AS status_count
    FROM invoices
    GROUP BY status
";

$status_result = db_query($invoice_status_sql);
if ($status_result) {
    $status_data = db_fetch_all($status_result);

    $labels = [];
    $values = [];
    foreach ($status_data as $row) {
        $labels[] = $row['status'];
        $values[] = (int)$row['status_count'];
    }

    $response['data']['invoice_status'] = [
        'labels' => $labels,
        'values' => $values
    ];
} else {
    $response['message'] .= ' Failed to fetch invoice status data.';
}

$response['success'] = true;

// Output the final JSON response
echo json_encode($response);
?>