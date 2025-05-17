<?php
require "dbconnect.php";
require "active.php";

$data = [];

// Total items
$query = "SELECT COUNT(*) AS total FROM menu";
$res = pg_query($conn, $query);
$row = pg_fetch_assoc($res);
$data['total'] = $row['total'] ?? 0;

// Veg items
$query = "SELECT COUNT(*) AS veg FROM menu WHERE cid = 1";
$res = pg_query($conn, $query);
$row = pg_fetch_assoc($res);
$data['veg'] = $row['veg'] ?? 0;

// Non-Veg items
$query = "SELECT COUNT(*) AS nonveg FROM menu WHERE cid = 2";
$res = pg_query($conn, $query);
$row = pg_fetch_assoc($res);
$data['nonveg'] = $row['nonveg'] ?? 0;

// Today's statistics
$today = date('Y-m-d');
$today_start = $today . ' 00:00:00';
$today_end = $today . ' 23:59:59';

// ✅ FIXED: Added missing closing quote
$query = "SELECT COUNT(*) AS orders FROM orders WHERE order_at BETWEEN '$today_start' AND '$today_end'";
$res = pg_query($conn, $query);
$row = pg_fetch_assoc($res);
$data['orders'] = $row['orders'] ?? 0;

// ✅ FIXED: Use BETWEEN instead of '=' for same day, and corrected $date to $today_start and $today_end
$query = "SELECT SUM(ordercost) AS total_cost FROM orders WHERE order_at BETWEEN '$today_start' AND '$today_end'";
$res = pg_query($conn, $query);
$row = pg_fetch_assoc($res);
$data['sales'] = $row['total_cost'] ?? 0;

return $data;
?>
