<?php
function getDashboardStats(PDO $db): array
{
    return [
        'total_bookings' => (int)$db->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
        'today_bookings' => (int)$db->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
        'pending_bookings' => (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn(),
        'total_users' => (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'total_rooms' => (int)$db->query("SELECT COUNT(*) FROM rooms")->fetchColumn(),
        'occupied_rooms' => (int)$db->query("SELECT COUNT(*) FROM rooms WHERE status!='available'")->fetchColumn(),
        'total_revenue' => (float)$db->query("
            SELECT COALESCE(SUM(total_amount),0)
            FROM bookings
            WHERE status IN ('confirmed','paid','completed')
        ")->fetchColumn()
    ];
}
