<?php

spl_autoload_register(function($class) {
    $file = str_replace('\\', '/', $class) . '.php';
    if (file_exists("src/$file")) {
        require $file;
    }
});