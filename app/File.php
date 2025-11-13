<?php
namespace Woo_BG;

defined( 'ABSPATH' ) || exit;

class File {
	const CACHE_FOLDER = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'bulgarisation-for-woocommerce' . DIRECTORY_SEPARATOR;

	public static function put_to_file( $file, $data ) {
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			WP_Filesystem();
		}

		$wp_filesystem->put_contents(
			$file,
			$data,
			FS_CHMOD_FILE
		);
	}

	public static function get_file( $file ) {
		$data = '';

		if ( file_exists( $file ) ) {
			global $wp_filesystem;

			if ( ! $wp_filesystem ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				WP_Filesystem();
			}

			$data = $wp_filesystem->get_contents( $file );
		}

		return $data;
	}
}