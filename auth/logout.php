<?php
// File: auth/logout.php
require_once __DIR__ . '/../includes/app.php';

// Destroy user session
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);

// You might want to unset other session data specific to the user, like the cart
// unset($_SESSION['cart']); 

$_SESSION['success_message'] = "You have been logged out successfully.";
header('Location: /');
exit();