<?php

use IAMagicGalleries\scripthandler;

$start = microtime(true);
require_once(__DIR__ . "/src/autoload.php");
require_once (__DIR__."/includes/WP_DB_SideAccess.php");


//echo htmlspecialchars(scripthandler::getMainLibrary());

//echo htmlspecialchars(WP_DB_SideAccess::update_config());

echo WP_DB_SideAccess::get_option("posts_per_page")."<br>";

echo WP_DB_SideAccess::get_option("mailserver_url")."<br>";

//WP_DB_SideAccess::add_option("test", "test_back");

echo WP_DB_SideAccess::get_option("test")."<br>";

echo "<br>" . (microtime(true) - $start);