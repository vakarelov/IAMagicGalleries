<?php
/*
 * Copyright © 2023 Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

spl_autoload_register(function ($class) {
    if (0 === strpos($class, "IAMagicGalleries")) {
        $file = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        $file = realpath(__DIR__ . DIRECTORY_SEPARATOR . $file . '.php');
        if (file_exists($file)) {
            include_once $file;
        }
    }
});