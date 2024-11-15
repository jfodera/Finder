<?php
// auth_middleware.php

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function requireRecorder() {
    if (!isset($_SESSION['user_id']) || !$_SESSION['is_recorder']) {
        header("Location: ../index.php");
        exit();
    }
}

function requireRegularUser() {
    if (!isset($_SESSION['user_id']) || $_SESSION['is_recorder']) {
        header("Location: ../index.php");
        exit();
    }
}
?>