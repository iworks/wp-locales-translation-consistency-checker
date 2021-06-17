#!/usr/bin/php -d memory_limit=20480M
<?php
/**
 * WordPress Locales Translation Consistency Checker
 *
 * @category   WordPress_Tools
 * @package    WordPress
 * @subpackage Consistency Checker
 * @author     Marcin Pietrzak <marci@iworks.pl>
 * @license    http://iworks.pl/ commercial
 * @version    SVN: $Id: import_posts.php 4872 2012-07-24 13:53:34Z illi $
 * @link       http://iworks.pl/
 *
 */

require 'includes/functions.php';

echo "\e[92m";
echo 'Success!' . PHP_EOL;
echo "\e[32m";
echo 'Site name: ';
bloginfo( 'name' );
echo PHP_EOL;
echo 'Site URL:  ';
bloginfo( 'url' );
echo PHP_EOL;
echo "\e[39m";


