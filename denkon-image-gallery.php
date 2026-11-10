<?php
/**
 * Plugin Name: Denkon Image Gallery
 * Plugin URI: https://denkon.ru/quick-start/funkcionalnaia-galereia-izobrazenii-na-wordpress
 * Description: Fast, responsive image galleries powered by the native WordPress Media Library.
 * Version: 0.2.27
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: Denkon
 * Author URI: https://denkon.ru
 * Text Domain: denkon-image-gallery
 * License: GPL-2.0-or-later
 *
 * @package DenkonImageGallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DENKON_IMAGE_GALLERY_VERSION', '0.2.27' );
define( 'DENKON_IMAGE_GALLERY_FILE', __FILE__ );
define( 'DENKON_IMAGE_GALLERY_DIR', plugin_dir_path( __FILE__ ) );
define( 'DENKON_IMAGE_GALLERY_URL', plugin_dir_url( __FILE__ ) );

require_once DENKON_IMAGE_GALLERY_DIR . 'includes/class-denkon-image-gallery.php';

function denkon_image_gallery() {
	return Denkon_Image_Gallery::instance();
}

denkon_image_gallery();
