<?php
/**
 * House Categories Carousel — Server-side Render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — escaped inside render_category_carousel_output.
echo \HouseProductsCarousel\render_category_carousel_output( $attributes );
