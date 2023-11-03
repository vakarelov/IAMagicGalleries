<?php

require_once(__DIR__."/../../../wp-load.php");

$cript = random_int(0, PHP_INT_MAX);

setcookie("IAMGid", $cript);

set_transient("id", $cript, 5*60);

echo get_transient("id");

if (isset($_SESSION)){
    var_dump($_SESSION);
}

