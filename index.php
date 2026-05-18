<?php
require_once __DIR__ . '/functions/functions.php';
session_start();
if (isLoggedIn()) {
    redirect('/dashboard.php');
} else {
    redirect('/auth/login.php');
}
