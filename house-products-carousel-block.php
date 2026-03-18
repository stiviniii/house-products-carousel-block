<?php
/**
 * Plugin Name:       House Products Carousel Block
 * Description:       A modern Gutenberg block ensemble that displays WooCommerce products in carousel or grid layouts with house specifications.
 * Version:           1.2.0
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
	define( 'HPC_VERSION', '1.2.0' );
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

	// Register Categories Carousel Block.
	register_block_type_from_metadata( HPC_PLUGIN_DIR . 'build/categories-carousel' );
}
add_action( 'init', __NAMESPACE__ . '\\register_blocks' );

/**
 * Enqueue assets on the frontend.
 *
 * @param bool $force Whether to force enqueue.
 */
function enqueue_frontend_assets( $force = false ) {
	// Check for either block.
	if ( ! $force && ! has_block( 'house-products/carousel' ) && ! has_block( 'house-products/grid' ) && ! has_block( 'house-products/categories-carousel' ) ) {
		return;
	}

	// Splide assets (Carousel-based blocks only).
	if ( $force || has_block( 'house-products/carousel' ) || has_block( 'house-products/categories-carousel' ) ) {
		$splide_css = HPC_PLUGIN_DIR . 'assets/vendor/splide/splide-core.min.css';
		$splide_js  = HPC_PLUGIN_DIR . 'assets/vendor/splide/splide.min.js';

		if ( file_exists( $splide_css ) ) {
			wp_enqueue_style( 'hpc-splide-core', HPC_PLUGIN_URL . 'assets/vendor/splide/splide-core.min.css', array(), HPC_VERSION );
		}
		if ( file_exists( $splide_js ) ) {
			wp_enqueue_script( 'hpc-splide', HPC_PLUGIN_URL . 'assets/vendor/splide/splide.min.js', array(), HPC_VERSION, true );
		}

		// Enqueue the common script that initializes Splide.
		$frontend_js = HPC_PLUGIN_DIR . 'build/hpc-frontend.js';
		if ( file_exists( $frontend_js ) ) {
			$asset_file = HPC_PLUGIN_DIR . 'build/hpc-frontend.asset.php';
			$asset      = file_exists( $asset_file ) ? require $asset_file : array( 'dependencies' => array(), 'version' => HPC_VERSION );
			wp_enqueue_script( 'hpc-frontend', HPC_PLUGIN_URL . 'build/hpc-frontend.js', array_merge( array( 'hpc-splide' ), $asset['dependencies'] ), $asset['version'], true );
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
				$thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
				$image_url    = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'medium_large' ) : '';

				$categories[] = array(
					'value' => (int) $term->term_id,
					'label' => esc_html( $term->name ),
					'count' => (int) $term->count,
					'image' => $image_url,
				);
			}
		}
	}

	// We'll localize for both handles if they exist.
	$handles = array(
		'house-products-carousel-editor-script',
		'house-products-grid-editor-script',
		'house-products-categories-carousel-editor-script',
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

/**
 * Add category image to REST API response.
 */
function add_category_image_to_rest_api() {
	register_rest_field(
		'product_cat',
		'image',
		array(
			'get_callback' => function( $term ) {
				$thumbnail_id = get_term_meta( $term['id'], 'thumbnail_id', true );
				if ( $thumbnail_id ) {
					$image_url = wp_get_attachment_image_url( $thumbnail_id, 'medium_large' );
					return array(
						'id'  => $thumbnail_id,
						'src' => $image_url,
						'alt' => get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ),
					);
				}
				return null;
			},
			'schema' => array(
				'description' => __( 'Category image', 'house-products-carousel' ),
				'type'        => 'object',
				'properties'  => array(
					'id'  => array(
						'type' => 'integer',
					),
					'src' => array(
						'type' => 'string',
					),
					'alt' => array(
						'type' => 'string',
					),
				),
			),
		)
	);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\\add_category_image_to_rest_api' );