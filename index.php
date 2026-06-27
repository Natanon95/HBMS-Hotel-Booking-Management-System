<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/helpers.php';
Auth::start();
redirect(Auth::check() ? '/modules/dashboard/' : '/login.php');
