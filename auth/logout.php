<?php
require_once __DIR__ . '/../functions/functions.php';
require_once __DIR__ . '/../core/Auth.php';

session_start();
Auth::logout();
redirect('/auth/login.php');
