<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$booking_id = $data['booking_id'] ?? null;

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'No booking ID provided']);
    exit();
}

try {
    $db = getDB();
    $user_id = $_SESSION['user_id'];
    
    // Verify user owns this booking
    $stmt = $db->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ?");
    $stmt->execute([$booking_id, $user_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Booking not found or access denied']);
        exit();
    }
    
    // Update booking status to cancelled
    $stmt = $db->prepare("UPDATE bookings SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$booking_id]);
    
    echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>