#!/usr/bin/php -d memory_limit=20480M
<?php
/**
 * WordPress add admin user
 *
 * PHP version 5
 *
 * @category   WordPress_Tools
 * @package    WordPress
 * @subpackage Users
 * @author     Marcin Pietrzak <marcin@iworks.pl>
 * @license    http://iworks.pl/ BSD
 * @version    SVN: $Id: add_admin.php 12440 2013-07-23 10:48:15Z illi $
 * @link       http://iworks.pl/
 *
 */


/**
 * load WordPress without theme
 */
define( 'WP_USE_THEMES', false );
$root = dirname( dirname( __FILE__ ) );

require $root . '/etc/config.php';
require $root . '/includes/functions.php';

if ( ! has_action( 'wp_locales_translation_consistency_checker_run' ) ) {
	$string  = "\e[31m" . PHP_EOL;
	$string .= 'Turn on ';
	$string .= "\e[91m";
	$string .= 'WP Locales Translation Consistency';
	$string .= "\e[31m";
	$string .= ' plugin on!' . PHP_EOL;
	die( $string );
}

$config = array(
	'language_set'           => $language_set,
	'language_code'          => $language_code,
	'po_short_language_code' => $po_short_language_code,
	'po_plural_forms'        => $po_plural_forms,
);


do_action( 'wp_locales_translation_consistency_checker_export', $config );


