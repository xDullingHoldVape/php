<?php
spl_autoload_register(function (string $class): void {
    // Strip any namespace, just get the class name
    $parts = explode('\\', $class);
    $className = end($parts);
    
    $file = __DIR__ . '/../Classes/' . $className . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});