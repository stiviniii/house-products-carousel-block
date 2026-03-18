<?php
/**
 * House Products Carousel — Server-side Render
 *
 * This file is referenced in block.json as the "render" property.
 * It is the sole render entry point — no render_callback is used,
 * preventing double-rendering issues.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content (empty for dynamic blocks).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — escaped inside render_block_output.
echo \HouseProductsCarousel\render_block_output( $attributes );
