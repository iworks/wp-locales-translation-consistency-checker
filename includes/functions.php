<?php
error_reporting( E_ALL );

if ( isset( $_SERVER['SERVER_NAME'] ) ) {
	die( 'not allow with www' );
}

$root = dirname( dirname( __FILE__ ) );

print_r( $root );


if ( ! is_file( $root . '/etc/config.php' ) ) {
	echo "\e[91m";
	echo 'ERROR!', PHP_EOL;
	echo "\e[31m";
	echo 'Please create `etc/config.php` file with WordPress location!',PHP_EOL;
	echo 'You can copy `etc/config.example.php`.',PHP_EOL,PHP_EOL;
	echo "\e[39m";
	die;
}

require $root . '/etc/config.php';

require $wordpress_path . '/wp-load.php';

