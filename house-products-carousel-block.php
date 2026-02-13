<?php
/**
 * Plugin Name:       House Products Carousel Block
 * Description:       A modern Gutenberg block that displays WooCommerce products in a responsive SplideJS carousel with Secure Custom Fields specifications — perfect for real-estate style product cards.
 * Version:           1.0.0
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
	define( 'HPC_VERSION', '1.0.0' );
}

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
 * Register the block and its assets.
 *
 * Uses block.json "render" property exclusively (no render_callback conflict).
 */
function register_block() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	register_block_type( HPC_PLUGIN_DIR . 'build' );
}
add_action( 'init', __NAMESPACE__ . '\\register_block' );

/**
 * Enqueue Splide JS and CSS on the frontend only when the block is present.
 */
function enqueue_frontend_assets() {
	if ( ! has_block( 'house-products/carousel' ) ) {
		return;
	}

	$splide_css = HPC_PLUGIN_DIR . 'node_modules/@splidejs/splide/dist/css/splide-core.min.css';
	$splide_js  = HPC_PLUGIN_DIR . 'node_modules/@splidejs/splide/dist/js/splide.min.js';

	if ( file_exists( $splide_css ) ) {
		wp_enqueue_style(
			'hpc-splide-core',
			HPC_PLUGIN_URL . 'node_modules/@splidejs/splide/dist/css/splide-core.min.css',
			array(),
			HPC_VERSION
		);
	}

	if ( file_exists( $splide_js ) ) {
		wp_enqueue_script(
			'hpc-splide',
			HPC_PLUGIN_URL . 'node_modules/@splidejs/splide/dist/js/splide.min.js',
			array(),
			HPC_VERSION,
			true
		);
	}

	// Frontend initializer script.
	$frontend_js = HPC_PLUGIN_DIR . 'build/frontend.js';
	if ( file_exists( $frontend_js ) ) {
		$asset_file = HPC_PLUGIN_DIR . 'build/frontend.asset.php';
		$asset      = file_exists( $asset_file )
			? require $asset_file
			: array(
				'dependencies' => array(),
				'version'      => HPC_VERSION,
			);

		wp_enqueue_script(
			'hpc-frontend',
			HPC_PLUGIN_URL . 'build/frontend.js',
			array_merge( array( 'hpc-splide' ), $asset['dependencies'] ),
			$asset['version'],
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_frontend_assets' );

/**
 * Provide WooCommerce product categories to the editor via localized data.
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

	// The editor script handle registered via block.json follows WP convention.
	$handle = 'house-products-carousel-editor-script';

	if ( wp_script_is( $handle, 'registered' ) || wp_script_is( $handle, 'enqueued' ) ) {
		wp_localize_script( $handle, 'hpcEditorData', array(
			'categories' => $categories,
		) );
	}
}
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor_assets' );

/**
 * Render callback for the dynamic block.
 *
 * Called from render.php (block.json "render" property).
 *
 * @param array $attributes Block attributes.
 * @return string Rendered HTML.
 */
function render_block_output( $attributes ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return '<p>' . esc_html__( 'WooCommerce is required for this block.', 'house-products-carousel' ) . '</p>';
	}

	$defaults = array(
		'productsCount' => 8,
		'columns'       => 3,
		'category'      => 0,
		'autoplay'      => false,
		'showArrows'    => true,
		'showRating'    => true,
		'orderBy'       => 'date',
	);

	$attributes = wp_parse_args( $attributes, $defaults );

	// Sanitize and validate orderBy against whitelist.
	$allowed_orderby = array( 'date', 'menu_order', 'price', 'title', 'popularity', 'rating' );
	$order_by        = in_array( $attributes['orderBy'], $allowed_orderby, true )
		? $attributes['orderBy']
		: 'date';

	$query_args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => min( absint( $attributes['productsCount'] ), 24 ),
		'orderby'        => $order_by,
		'order'          => 'DESC',
		'fields'         => 'ids',
		'no_found_rows'  => true,
	);

	// WooCommerce 3.0+ uses product_visibility taxonomy instead of _visibility meta.
	$query_args['tax_query'] = array(
		array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => array( 'exclude-from-catalog' ),
			'operator' => 'NOT IN',
		),
	);

	// Handle price ordering via WC meta.
	if ( 'price' === $order_by ) {
		$query_args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$query_args['orderby']  = 'meta_value_num';
	}

	// Filter by category.
	$category = absint( $attributes['category'] );
	if ( $category > 0 ) {
		$query_args['tax_query'][] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'    => $category,
		);
		$query_args['tax_query']['relation'] = 'AND';
	}

	$product_ids = get_posts( $query_args );

	if ( empty( $product_ids ) ) {
		return '<p class="hpc-no-products">' . esc_html__( 'No products found.', 'house-products-carousel' ) . '</p>';
	}

	$columns = max( 1, min( 6, absint( $attributes['columns'] ) ) );

	$splide_options = wp_json_encode( array(
		'type'         => 'loop',
		'perPage'      => $columns,
		'gap'          => '1.5rem',
		'arrows'       => (bool) $attributes['showArrows'],
		'pagination'   => false,
		'autoplay'     => (bool) $attributes['autoplay'],
		'interval'     => 4000,
		'pauseOnHover' => true,
		'breakpoints'  => array(
			1200 => array( 'perPage' => $columns ),
			768  => array( 'perPage' => 2 ),
			480  => array( 'perPage' => 1 ),
		),
	) );

	ob_start();
	?>
	<div class="hpc-carousel-wrapper" data-splide-options="<?php echo esc_attr( $splide_options ); ?>">
		<div class="splide hpc-carousel" aria-label="<?php esc_attr_e( 'House Products Carousel', 'house-products-carousel' ); ?>">
			<div class="splide__track">
				<ul class="splide__list">
					<?php
					foreach ( $product_ids as $product_id ) {
						echo render_product_card( (int) $product_id, $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render_product_card.
					}
					?>
				</ul>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Render a single product card.
 *
 * @param int   $product_id Product ID.
 * @param array $attributes Block attributes.
 * @return string Card HTML.
 */
function render_product_card( $product_id, $attributes ) {
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return '';
	}

	$title     = $product->get_name();
	$price     = $product->get_price_html();
	$permalink = $product->get_permalink();
	$image_id  = $product->get_image_id();

	// Check for "best-seller" tag.
	$tags          = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'slugs' ) );
	$is_bestseller = is_array( $tags ) && in_array( 'best-seller', $tags, true );

	// Rating.
	$rating_count = $product->get_rating_count();
	$average      = (float) $product->get_average_rating();

	// Secure Custom Fields (SCF) specs.
	$acf_fields = get_acf_specs( $product_id );

	ob_start();
	?>
	<li class="splide__slide">
		<article class="hpc-card">
			<a href="<?php echo esc_url( $permalink ); ?>" class="hpc-card__link" aria-label="<?php echo esc_attr( $title ); ?>">

				<div class="hpc-card__image-wrapper">
					<?php if ( $image_id ) : ?>
						<?php
						echo wp_get_attachment_image(
							$image_id,
							'medium_large',
							false,
							array(
								'class'   => 'hpc-card__image',
								'loading' => 'lazy',
								'decoding' => 'async',
								'alt'     => esc_attr( $title ),
							)
						);
						?>
					<?php else : ?>
						<div class="hpc-card__image hpc-card__image--placeholder">
							<span><?php esc_html_e( 'No Image', 'house-products-carousel' ); ?></span>
						</div>
					<?php endif; ?>

					<?php if ( $is_bestseller ) : ?>
						<span class="hpc-card__badge"><?php esc_html_e( 'Best Seller', 'house-products-carousel' ); ?></span>
					<?php endif; ?>
				</div>

				<div class="hpc-card__body">
					<h3 class="hpc-card__title"><?php echo esc_html( $title ); ?></h3>
					<div class="hpc-card__price"><?php echo wp_kses_post( $price ); ?></div>

					<?php if ( $attributes['showRating'] && $rating_count > 0 ) : ?>
						<div class="hpc-card__rating" aria-label="<?php printf( esc_attr__( 'Rated %s out of 5', 'house-products-carousel' ), esc_attr( number_format( $average, 1 ) ) ); ?>">
							<?php echo render_stars( $average, $product_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- contains safe SVG output. ?>
							<span class="hpc-card__rating-count">(<?php echo esc_html( $rating_count ); ?>)</span>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $acf_fields ) ) : ?>
					<div class="hpc-card__specs">
						<?php foreach ( $acf_fields as $spec ) : ?>
							<div class="hpc-card__spec" title="<?php echo esc_attr( $spec['label'] ); ?>">
								<span class="hpc-card__spec-icon"><?php echo $spec['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded SVG icons. ?></span>
								<span class="hpc-card__spec-value"><?php echo esc_html( $spec['value'] ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

			</a>
		</article>
	</li>
	<?php
	return ob_get_clean();
}

/**
 * Get Secure Custom Fields (SCF) specification fields for a product.
 *
 * Compatible with both SCF and ACF — both provide the get_field() function.
 *
 * @param int $product_id Product ID.
 * @return array Specs data.
 */
function get_acf_specs( $product_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return array();
	}

	$specs  = array();
	$fields = array(
		'floors'    => array(
			'label'  => __( 'Floors', 'house-products-carousel' ),
			'icon'   => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="12" x2="21" y2="12"/></svg>',
			'suffix' => '',
		),
		'bedrooms'  => array(
			'label'  => __( 'Bedrooms', 'house-products-carousel' ),
			'icon'   => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v11a2 2 0 002 2h14a2 2 0 002-2V7"/><path d="M3 11h18"/><path d="M7 11V7a2 2 0 012-2h6a2 2 0 012 2v4"/></svg>',
			'suffix' => '',
		),
		'bathrooms' => array(
			'label'  => __( 'Bathrooms', 'house-products-carousel' ),
			'icon'   => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16a1 1 0 011 1v3a4 4 0 01-4 4H7a4 4 0 01-4-4v-3a1 1 0 011-1z"/><path d="M6 12V5a2 2 0 012-2h0a2 2 0 012 2v1"/></svg>',
			'suffix' => '',
		),
		'width'     => array(
			'label'  => __( 'Width', 'house-products-carousel' ),
			'icon'   => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><polyline points="7 8 3 12 7 16"/><polyline points="17 8 21 12 17 16"/></svg>',
			'suffix' => 'm',
		),
		'length'    => array(
			'label'  => __( 'Length', 'house-products-carousel' ),
			'icon'   => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="3" x2="12" y2="21"/><polyline points="8 7 12 3 16 7"/><polyline points="8 17 12 21 16 17"/></svg>',
			'suffix' => 'm',
		),
		'area'      => array(
			'label'  => __( 'Area', 'house-products-carousel' ),
			'icon'   => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 3v18"/></svg>',
			'suffix' => ' m²',
		),
	);

	foreach ( $fields as $key => $config ) {
		$value = get_field( $key, $product_id );
		if ( $value !== null && $value !== '' && $value !== false ) {
			$specs[] = array(
				'label' => $config['label'],
				'icon'  => $config['icon'],
				'value' => sanitize_text_field( $value ) . $config['suffix'],
			);
		}
	}

	return $specs;
}

/**
 * Render rating stars as SVG with unique gradient IDs to prevent collisions.
 *
 * @param float $rating     The average rating.
 * @param int   $product_id Product ID for unique gradient ID generation.
 * @return string Star SVGs.
 */
function render_stars( $rating, $product_id = 0 ) {
	$rating   = max( 0, min( 5, (float) $rating ) );
	$html     = '';
	$full     = (int) floor( $rating );
	$has_half = ( $rating - $full ) >= 0.5;
	$empty    = 5 - $full - ( $has_half ? 1 : 0 );

	// Unique gradient ID to avoid SVG ID collisions across multiple cards.
	$grad_id = 'hpc-half-' . (int) $product_id;

	$star_full  = '<svg class="hpc-star hpc-star--full" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
	$star_half  = '<svg class="hpc-star hpc-star--half" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" aria-hidden="true"><defs><linearGradient id="' . esc_attr( $grad_id ) . '"><stop offset="50%" stop-color="currentColor"/><stop offset="50%" stop-color="transparent"/></linearGradient></defs><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" fill="url(#' . esc_attr( $grad_id ) . ')" stroke="currentColor" stroke-width="1"/></svg>';
	$star_empty = '<svg class="hpc-star hpc-star--empty" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';

	for ( $i = 0; $i < $full; $i++ ) {
		$html .= $star_full;
	}
	if ( $has_half ) {
		$html .= $star_half;
	}
	for ( $i = 0; $i < $empty; $i++ ) {
		$html .= $star_empty;
	}

	return $html;
}

/**
 * Load plugin textdomain for translations.
 */
function load_textdomain() {
	load_plugin_textdomain(
		'house-products-carousel',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', __NAMESPACE__ . '\\load_textdomain' );
