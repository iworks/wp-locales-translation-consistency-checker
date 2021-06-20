<?php
/*
 * Plugin Name:       Locales Translation Consistency Checker
 * Plugin URI:        http://iworks.pl/
 * Description:       WordPress Locales Translation Consistency Checker
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Version:           1.0.0
 * Author:            Marcin Pietrzak
 * Author URI:        http://iworks.pl/
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-locales-translation-consistency-checker
 * Domain Path:       /languages
 *

 Copyright 2020-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

 this program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License, version 2, as
 published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * i18n
 */
load_plugin_textdomain( 'wp-locales-translation-consistency-checker', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );


class wp_locales_translation_consistency_checker {

	/**
	 * post type
	 */
	private $post_type_name = '_wp_tc';
	/**
	 * meta fields
	 */
	private $meta_counter     = '_wp_tc_counter';
	private $meta_last_update = '_wp_tc_last_update';
	private $meta_string      = '_wp_tc_string';
	private $meta_url         = '_wp_tc_url';
	private $meta_translation = '_wp_tc_translation';
	private $meta_wordpress   = '_wp_tc_wordpress';

	public function __construct() {
		/**
		 * WordPress Hooks
		 */
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'template_include', array( $this, 'archive_template' ) );
		add_filter( 'the_content', array( $this, 'the_content' ) );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts_register' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts_enqueue' ), 20 );
		add_action( 'wp_ajax_wp_locales_translation_consistency_checker_mark_done', array( $this, 'ajax_done' ) );
		add_action( 'wp_ajax_nopriv_wp_locales_translation_consistency_checker_mark_done', array( $this, 'ajax_done' ) );
		/**
		 * Plugin Custom Hooks
		 */
		add_action( 'wp_locales_translation_consistency_checker_run', array( $this, 'run' ) );
		add_action( 'wp_locales_translation_consistency_checker_export', array( $this, 'export' ) );
	}

	public function ajax_done() {
		$nonce   = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
		$post_id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		if ( empty( $nonce ) || empty( $post_id ) ) {
			wp_send_json_error();
		}
		if ( ! wp_verify_nonce( $nonce, get_the_title( $post_id ) ) ) {
			wp_send_json_error();
		}
		$result = wp_delete_post( $post_id, true );
		if ( $result ) {
			wp_send_json_success();
		}
		wp_send_json_error();
	}

	public function scripts_enqueue() {
		if ( is_archive( $this->post_type_name ) ) {
			wp_enqueue_script( __CLASS__ );
		}
	}

	public function scripts_register() {
		wp_register_script(
			__CLASS__,
			plugins_url( basename( dirname( __FILE__ ) ) ) . '/frontend.js',
			array( 'jquery' ),
			filemtime( dirname( __FILE__ ) . '/frontend.js' ),
			true
		);
	}

	public function pre_get_posts( $query ) {
		if ( ! $query->is_main_query() ) {
			return;
		}
		if ( $query->get( 'post_type' ) !== $this->post_type_name ) {
			return;
		}
		$query->set(
			'meta_query',
			array(
				$this->meta_counter => array(
					'key'     => $this->meta_counter,
					'value'   => 1,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			)
		);
		$query->set( 'orderby', $this->meta_counter );
	}

	public function the_content( $content ) {
		if ( is_admin() ) {
			return $content;
		}
		if ( ! is_main_query() ) {
			return $content;
		}
		if ( ! is_archive( $this->post_type_name ) ) {
			return $content;
		}
		$content  = '<tr>';
		$content .= sprintf( '<td>%d</td>', get_post_meta( get_the_ID(), $this->meta_counter, true ) );
		$content .= sprintf(
			'<td><a href="%s" target="_blank">%s</a></td>',
			get_post_meta( get_the_ID(), $this->meta_url, true ),
			get_post_meta( get_the_ID(), $this->meta_string, true )
		);
		$content .= sprintf(
			'<td><button class="wp-locales-translation-consistency-checker" data-nonce="%s" data-id="%d">%s</button></td>',
			wp_create_nonce( get_the_title() ),
			esc_attr( get_the_ID() ),
			esc_html__( 'Mark done!', 'wp-locales-translation-consistency-checker' )
		);
		$content .= '</tr>';
		return $content;
	}

	public function archive_template( $template ) {
		if ( is_archive( $this->post_type_name ) ) {
			return dirname( __FILE__ ) . '/archive-_wp_tc.php';
		}
		return $template;
	}

	public function run( $config ) {
		$root  = WP_CONTENT_DIR . '/languages';
		$dirs  = array(
			$root,
			$root . '/plugins',
			$root . '/themes',
		);
		$files = array();
		foreach ( $dirs as $dir ) {
			if ( ! is_dir( $dir ) ) {
				continue;
			}
			foreach ( scandir( $dir ) as $file ) {
				if ( preg_match( '/' . $config['language_code'] . '.*po$/', $file ) ) {
					$files[] = $dir . '/' . $file;
				}
			}
		}
		if ( empty( $files ) ) {
			die( 'files not found' );
		}
		$checked = array();
		foreach ( $files as $file ) {
			echo $file;
			echo PHP_EOL;
			$data    = file_get_contents( $file );
			$strings = array();
			foreach ( explode( "\n", $data ) as $one ) {
				if ( preg_match( '/^msgid "(.{3,})"$/', $one, $matches ) ) {
					$strings[] = $matches[1];
				}
			}
			foreach ( $strings as $string ) {
				if ( isset( $checked[ $string ] ) ) {
					continue;
				}
				$checked[ $string ] = true;
				$this->check( $string, $config );
			}
		}
	}

	private function check( $string, $config ) {
		if ( empty( $string ) ) {
			return;
		}
		$string = str_replace( '\"', '"', $string );
		echo $string;
		echo PHP_EOL;
		/**
		 * get url
		 */
		$url = add_query_arg(
			array(
				'search'                => urlencode( $string ),
				'set'                   => $config['language_set'],
				'search_case_sensitive' => 1,
			),
			'https://translate.wordpress.org/consistency/'
		);
		/**
		 * post title
		 */
		$post_title = md5( $string );
		/**
		 * get or insert post
		 */
		$post_id = 0;
		$post    = get_page_by_title( $post_title, OBJECT, $this->post_type_name );
		if ( $post instanceof WP_Post ) {
			$post_id     = $post->ID;
			$last_update = intval( get_post_meta( $post_id, $this->meta_last_update, true ) );
			if ( ( time() - $last_update ) < 14 * DAY_IN_SECONDS ) {
				return;
			}
		} else {
			$postarr = array(
				'post_status'    => 'publish',
				'post_title'     => $post_title,
				'post_type'      => $this->post_type_name,
				'ping_status'    => 'closed',
				'comment_status' => 'closed',
			);
			$post_id = wp_insert_post( $postarr );
			if ( is_wp_error( $post_id ) ) {
				return;
			}
			add_post_meta( $post_id, $this->meta_last_update, time(), true );
			add_post_meta( $post_id, $this->meta_wordpress, 'no', true );
			add_post_meta( $post_id, $this->meta_translation, '', true );
			add_post_meta( $post_id, $this->meta_url, $url, true );
			add_post_meta( $post_id, $this->meta_counter, 0, true );
			add_post_meta( $post_id, $this->meta_string, $string, true );
		}
		/**
		 * check data
		 */
		$response = wp_remote_get( $url );
		if ( is_wp_error( $response ) || ! is_array( $response ) ) {
			return;
		}
		update_post_meta( $post_id, $this->meta_last_update, time() );
		if ( preg_match( '/There are (\d+) different translations/', $response['body'], $matches ) ) {
			update_post_meta( $post_id, $this->meta_counter, intval( $matches[1] ) );
		} else {
			if ( preg_match( '@<strong>(.+)</strong>@', $response['body'], $matches ) ) {
				update_post_meta( $post_id, $this->meta_translation, html_entity_decode( $matches[1] ) );
			}
			update_post_meta( $post_id, $this->meta_counter, 0 );
		}
		/**
		 * Is WordPress Project?
		 */
		$value = preg_match( '/class="project-wordpress"/', $response['body'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, $this->meta_wordpress, $value );
	}

	public function register() {
		$args = array(
			'label'               => __( 'Translation Consistency', 'wp-locales-translation-consistency-checker' ),
			'public'              => true,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'has_archive'         => true,
			'supports'            => array( 'title' ),
		);
		register_post_type( $this->post_type_name, $args );
	}

	public function export( $config ) {
		global $wpdb;
		error_reporting( 0 );
		echo '# Translation of WordPress - 5.7.x' . PHP_EOL;
		echo 'msgid ""' . PHP_EOL;
		echo 'msgstr ""' . PHP_EOL;
		printf( '"PO-Revision-Date: %s0000\n"%s', date( 'c' ), PHP_EOL );
		echo '"MIME-Version: 1.0\n"' . PHP_EOL;
		echo '"Content-Type: text/plain; charset=UTF-8\n"' . PHP_EOL;
		echo '"Content-Transfer-Encoding: 8bit\n"' . PHP_EOL;
		printf( '"Plural-Forms: %s;\n"%s', $config['po_plural_forms'], PHP_EOL );
		echo '"X-Generator: WP Translation Consistence Checker\n"' . PHP_EOL;
		printf( '"Language: %s\n"%s', $config['po_short_language_code'], PHP_EOL );
		echo '"Project-Id-Version: WordPress - Common Translation\n"' . PHP_EOL;
		echo PHP_EOL;
		/**
		 * get Consistence Translations
		 */
		$args  = array(
			'post_type'  => $this->post_type_name,
			'nopaging'   => true,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => $this->meta_counter,
					'value'   => 0,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => $this->meta_translation,
					'value'   => '',
					'compare' => '!=',
				),
			),
			'fields'     => 'ids',
		);
		$query = new WP_Query( $args );
		foreach ( $query->posts as $post_id ) {
			$msgid = get_post_meta( $post_id, $this->meta_string, true );
			if (
				isset( $config['po_export_string_max_length'] )
				&& 0 < $config['po_export_string_max_length']
				&& strlen( $msgid ) > $config['po_export_string_max_length']
			) {
				continue;
			}
			printf(
				'msgid "%s"%s',
				addslashes( $msgid ),
				PHP_EOL
			);
			printf(
				'msgstr "%s"%s',
				addslashes( get_post_meta( $post_id, $this->meta_translation, true ) ),
				PHP_EOL
			);
			echo PHP_EOL;
		}
	}
}

new wp_locales_translation_consistency_checker;

