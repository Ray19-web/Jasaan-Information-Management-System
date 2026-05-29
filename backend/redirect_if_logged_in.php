<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['user_id'])) {

        if (strtolower($_SESSION['role']) === 'admin') {
            header("Location: /jasaan-tourism/admin");
        } else {
            header("Location: /jasaan-tourism/explore");
        }
        
        exit;
    }
?>