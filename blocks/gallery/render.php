<?php
/**
 * Dynamic block templates are captured with output buffering by WordPress.
 * The gallery markup must therefore be printed, rather than returned.
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo denkon_image_gallery()->render_block( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
