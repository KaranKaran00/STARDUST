<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

session_unset();
session_destroy();
session_start();
flash('success', 'You have been logged out.');
redirect(BASE_URL . '/index.php');
