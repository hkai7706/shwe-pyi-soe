<?php

return [
    'admin_path' => trim(env('ADMIN_PATH', 'staff'), '/'),
    'admin_name' => env('ADMIN_NAME', 'School Administrator'),
    'admin_username' => env('ADMIN_USERNAME', 'schooladmin'),
    'admin_email' => env('ADMIN_EMAIL', 'admin@example.com'),
    'admin_password' => env('ADMIN_PASSWORD'),
];
