<?php
function getAdminStats(PDO $db): array {
    return [
        'total_bookings' => (int)$db->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
        'pending_bookings' => (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn(),
        'total_users' => (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'total_rooms' => (int)$db->query("SELECT COUNT(*) FROM rooms")->fetchColumn(),
    ];
}
