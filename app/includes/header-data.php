<?php

// Make sure the session exists
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

$user_id = $_SESSION['user_id'] ?? null;

$display_name = 'Active Learner';

if ($user_id) {

    $user_sql = "SELECT first_name, last_name
                 FROM users
                 WHERE id = ?
                   AND deleted_at IS NULL";

    $user_stmt = mysqli_prepare($conn, $user_sql);
    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
    mysqli_stmt_execute($user_stmt);
    mysqli_stmt_bind_result($user_stmt, $first_name, $last_name);
    mysqli_stmt_fetch($user_stmt);
    mysqli_stmt_close($user_stmt);

    $display_name = trim(($first_name ?? '') . ' ' . ($last_name ?? ''));

    if ($display_name === '') {
        $display_name = 'Active Learner';
    }
}

$current_streak = 0;

if ($user_id) {

    $session_sql = "SELECT DATE(started_at) AS session_date
                    FROM review_sessions
                    WHERE user_id = ?
                      AND ended_at IS NOT NULL
                      AND total_reviewed > 0
                    GROUP BY DATE(started_at)
                    ORDER BY session_date DESC";

    $session_stmt = mysqli_prepare($conn, $session_sql);
    mysqli_stmt_bind_param($session_stmt, "i", $user_id);
    mysqli_stmt_execute($session_stmt);

    $result = mysqli_stmt_get_result($session_stmt);

    $activity_dates = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $activity_dates[] = $row['session_date'];
    }

    mysqli_stmt_close($session_stmt);

    if (!empty($activity_dates)) {

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // Streak only exists if the latest activity is today or yesterday
        if ($activity_dates[0] === $today || $activity_dates[0] === $yesterday) {

            $check_date = new DateTime($activity_dates[0]);

            foreach ($activity_dates as $date) {

                if ($date === $check_date->format('Y-m-d')) {
                    $current_streak++;
                    $check_date->modify('-1 day');
                } else {
                    break;
                }
            }
        }
    }
}