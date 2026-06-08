<?php

// Autoloader
spl_autoload_register(function (string $class): void {
    $parts = explode('\\', $class);
    $className = end($parts);
    $file = __DIR__ . '/../Classes/' . $className . '.php';
 
    if (file_exists($file)) {
        require $file;
    }
});
 