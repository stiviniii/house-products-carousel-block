<?php
/**
 * House Products Grid — Server-side Render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — escaped inside render_block_output.
echo \HouseProductsCarousel\render_block_output( $attributes, 'grid' );
