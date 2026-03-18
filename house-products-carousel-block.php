<?php
/**
 * Plugin Name:       House Products Carousel Block
 * Description:       A modern Gutenberg block ensemble that displays WooCommerce products in carousel or grid layouts with house specifications.
 * Version:           1.1.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Steven Ayo
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       house-products-carousel
 * Domain Path:       /languages
 */

namespace HouseProductsCarousel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Guard against redefinition.
if ( ! defined( 'HPC_PLUGIN_DIR' ) ) {
	define( 'HPC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'HPC_PLUGIN_URL' ) ) {
	define( 'HPC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'HPC_VERSION' ) ) {
	define( 'HPC_VERSION', '1.1.0' );
}

/**
 * Load shared functions.
 */
require_once HPC_PLUGIN_DIR . 'includes/render-functions.php';

/**
 * Display an admin notice if WooCommerce is not active.
 */
function admin_dependency_notice() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'House Products Carousel Block requires WooCommerce to be installed and active.', 'house-products-carousel' )
		);
	}
}
add_action( 'admin_notices', __NAMESPACE__ . '\\admin_dependency_notice' );

/**
 * Register blocks.
 */
function register_blocks() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	// Register Carousel Block.
	register_block_type_from_metadata( HPC_PLUGIN_DIR . 'build/carousel' );

	// Register Grid Block.
	register_block_type_from_metadata( HPC_PLUGIN_DIR . 'build/grid' );
}
add_action( 'init', __NAMESPACE__ . '\\register_blocks' );

/**
 * Enqueue assets on the frontend.
 *
 * @param bool $force Whether to force enqueue.
 */
function enqueue_frontend_assets( $force = false ) {
	// Check for either block.
	if ( ! $force && ! has_block( 'house-products/carousel' ) && ! has_block( 'house-products/grid' ) ) {
		return;
	}

	// Splide assets (Carousel only).
	if ( $force || has_block( 'house-products/carousel' ) ) {
		$splide_css = HPC_PLUGIN_DIR . 'node_modules/@splidejs/splide/dist/css/splide-core.min.css';
		$splide_js  = HPC_PLUGIN_DIR . 'node_modules/@splidejs/splide/dist/js/splide.min.js';

		if ( file_exists( $splide_css ) ) {
			wp_enqueue_style( 'hpc-splide-core', HPC_PLUGIN_URL . 'node_modules/@splidejs/splide/dist/css/splide-core.min.css', array(), HPC_VERSION );
		}
		if ( file_exists( $splide_js ) ) {
			wp_enqueue_script( 'hpc-splide', HPC_PLUGIN_URL . 'node_modules/@splidejs/splide/dist/js/splide.min.js', array(), HPC_VERSION, true );
		}

		// Frontend initializer script.
		$frontend_js = HPC_PLUGIN_DIR . 'build/carousel/frontend.js';
		if ( file_exists( $frontend_js ) ) {
			$asset_file = HPC_PLUGIN_DIR . 'build/carousel/frontend.asset.php';
			$asset      = file_exists( $asset_file ) ? require $asset_file : array( 'dependencies' => array(), 'version' => HPC_VERSION );
			wp_enqueue_script( 'hpc-frontend', HPC_PLUGIN_URL . 'build/carousel/frontend.js', array_merge( array( 'hpc-splide' ), $asset['dependencies'] ), $asset['version'], true );
		}
	}

	// Shared Grid/Carousel styles are handled by block registration, 
	// but we might need HPCO assets if active.
	if ( defined( 'HPCO_PLUGIN_URL' ) && defined( 'HPCO_VERSION' ) ) {
		$hpco_enabled = get_option( 'hpco_enable_override', 'yes' );
		if ( apply_filters( 'hpco_enable_override', ( 'yes' === $hpco_enabled ) ) ) {
			wp_enqueue_style( 'hpco-product-card', HPCO_PLUGIN_URL . 'assets/css/product-card.css', array(), HPCO_VERSION );
			wp_enqueue_script( 'hpco-quick-buy', HPCO_PLUGIN_URL . 'assets/js/quick-buy.js', array( 'jquery' ), HPCO_VERSION, true );
			wp_localize_script( 'hpco-quick-buy', 'hpcoData', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'hpco-quick-buy-nonce' ),
			) );
		}
	}
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_frontend_assets' );

/**
 * Provide WooCommerce product categories to the editor.
 */
function enqueue_editor_assets() {
	$categories = array();

	if ( taxonomy_exists( 'product_cat' ) ) {
		$terms = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$categories[] = array(
					'value' => (int) $term->term_id,
					'label' => esc_html( $term->name ),
				);
			}
		}
	}

	// We'll localize for both handles if they exist.
	$handles = array(
		'house-products-carousel-editor-script',
		'house-products-grid-editor-script',
	);

	foreach ( $handles as $handle ) {
		if ( wp_script_is( $handle, 'registered' ) || wp_script_is( $handle, 'enqueued' ) ) {
			wp_localize_script( $handle, 'hpcEditorData', array(
				'categories' => $categories,
			) );
		}
	}
}
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor_assets' );

/**
 * Render callback for dynamic blocks.
 *
 * @param array $attributes Block attributes.
 * @param string $layout Layout type ('carousel' or 'grid').
 * @return string Rendered HTML.
 */
function render_block_output( $attributes, $layout = 'carousel' ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return '<p>' . esc_html__( 'WooCommerce is required for this block.', 'house-products-carousel' ) . '</p>';
	}

	// Ensure assets are enqueued.
	enqueue_frontend_assets( true );

	// Signal to House Product Card Override to load its icons/sprite in the footer.
	add_filter( 'hpco_should_load_assets', '__return_true' );

	$product_ids = get_product_ids( $attributes );

	if ( empty( $product_ids ) ) {
		return '<p class="hpc-no-products">' . esc_html__( 'No products found.', 'house-products-carousel' ) . '</p>';
	}

	if ( 'grid' === $layout ) {
		return render_grid_layout( $product_ids, $attributes );
	}

	return render_carousel_layout( $product_ids, $attributes );
}

/**
 * Load plugin textdomain.
 */
function load_textdomain() {
	load_plugin_textdomain(
		'house-products-carousel',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', __NAMESPACE__ . '\\load_textdomain' );

