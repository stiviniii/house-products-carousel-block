<?php
/**
 * Shared rendering functions for House Products blocks.
 */

namespace HouseProductsCarousel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get common Splide options.
 *
 * @param int   $columns    Number of columns.
 * @param array $attributes Block attributes.
 * @return array Splide options array.
 */
function get_common_splide_options( $columns, $attributes ) {
	return array(
		'type'         => 'slide',
		'perPage'      => $columns,
		'perMove'      => 1,
		'gap'          => '1.5rem',
		'arrows'       => (bool) ( $attributes['showArrows'] ?? true ),
		'arrowPath'    => 'M34.63,20.88l-11.25,11.25a1.25,1.25,0,0,1-1.77-1.77L30.73,21.25H6.25a1.25,1.25,0,0,1,0-2.5H30.73L21.62,9.63a1.25,1.25,0,0,1,1.77-1.77l11.25,11.25A1.25,1.25,0,0,1,34.63,20.88Z',
		'pagination'   => false,
		'autoplay'     => (bool) ( $attributes['autoplay'] ?? false ),
		'interval'     => 4000,
		'pauseOnHover' => true,
		'rewind'       => false,
		'breakpoints'  => array(
			1200 => array( 'perPage' => $columns ),
			768  => array( 'perPage' => 2 ),
			480  => array( 'perPage' => 1 ),
		),
	);
}

/**
 * Get product IDs based on block attributes.
 *
 * @param array $attributes Block attributes.
 * @return array List of product IDs.
 */
function get_product_ids( $attributes ) {
	$defaults = array(
		'productsCount'     => 8,
		'category'          => 0,
		'excludeCategories' => array(),
		'orderBy'           => 'date',
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
	$tax_query = array(
		'relation' => 'AND',
		array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => array( 'exclude-from-catalog' ),
			'operator' => 'NOT IN',
		),
	);

	// Base exclusion: ALWAYS exclude 'hidecat'
	$excluded_slugs = array( 'hidecat' );
	$excluded_ids   = array();

	if ( ! empty( $attributes['excludeCategories'] ) && is_array( $attributes['excludeCategories'] ) ) {
		foreach ( $attributes['excludeCategories'] as $val ) {
			if ( is_numeric( $val ) ) {
				$excluded_ids[] = absint( $val );
			} else {
				$excluded_slugs[] = $val;
			}
		}
	}

	if ( ! empty( $excluded_ids ) ) {
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'    => $excluded_ids,
			'operator' => 'NOT IN',
		);
	}
	if ( ! empty( $excluded_slugs ) ) {
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => $excluded_slugs,
			'operator' => 'NOT IN',
		);
	}

	// Filter by included category if set.
	$category = absint( $attributes['category'] ?? 0 );
	if ( $category > 0 ) {
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'    => $category,
		);
	}

	$query_args['tax_query'] = $tax_query;

	// Handle price ordering via WC meta.
	if ( 'price' === $order_by ) {
		$query_args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$query_args['orderby']  = 'meta_value_num';
	}

	return get_posts( $query_args );
}

/**
 * Render Category Carousel Block Output
 *
 * @param array $attributes Block attributes.
 * @return string Block HTML.
 */
function render_category_carousel_output( $attributes ) {
	$category_ids = ! empty( $attributes['categoryIds'] ) ? $attributes['categoryIds'] : array();

	if ( empty( $category_ids ) ) {
		return '<div class="hpc-no-products">' . esc_html__( 'No categories selected.', 'house-products-carousel' ) . '</div>';
	}

	$columns = max( 1, min( 6, absint( $attributes['columns'] ?? 3 ) ) );
	$splide_options = wp_json_encode( get_common_splide_options( $columns, $attributes ) );

	$wrapper_classes = 'hpc-carousel-wrapper hpc-categories-carousel-wrapper';
	if ( (bool) ( $attributes['enableAnimation'] ?? true ) ) {
		$wrapper_classes .= ' hpc-animate-reveal';
	}
	if ( (bool) ( $attributes['trackOverflowVisible'] ?? false ) ) {
		$wrapper_classes .= ' hpc-track-overflow-visible';
	}

	$wrapper_style = sprintf(
		'--hpc-anim-duration: %dms; --hpc-anim-stagger: %dms;',
		absint( $attributes['animationDuration'] ?? 800 ),
		absint( $attributes['animationStagger'] ?? 150 )
	);

	ob_start();
	?>
	<div class="<?php echo esc_attr( $wrapper_classes ); ?>" 
		 data-splide-options="<?php echo esc_attr( $splide_options ); ?>"
		 style="<?php echo esc_attr( $wrapper_style ); ?>">
		<div class="splide hpc-carousel" aria-label="<?php esc_attr_e( 'House Categories Carousel', 'house-products-carousel' ); ?>">
			<div class="splide__track">
				<ul class="splide__list">
					<?php foreach ( $category_ids as $cat_id ) : ?>
						<li class="splide__slide">
							<?php echo render_category_card( absint( $cat_id ), $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Render a single category card.
 *
 * @param int   $cat_id     Category ID.
 * @param array $attributes Block attributes.
 * @return string Card HTML.
 */
function render_category_card( $cat_id, $attributes ) {
	$category = get_term( $cat_id, 'product_cat' );
	if ( ! $category || is_wp_error( $category ) ) {
		return '';
	}

	$thumbnail_id = get_term_meta( $cat_id, 'thumbnail_id', true );
	$image_url    = '';
	
	if ( $thumbnail_id ) {
		$image_url = wp_get_attachment_image_url( $thumbnail_id, 'medium_large' );
	}
	
	// Fallback to WooCommerce placeholder if no image found
	if ( ! $image_url ) {
		if ( function_exists( 'wc_placeholder_img_src' ) ) {
			$image_url = wc_placeholder_img_src();
		} else {
			// Ultimate fallback - use a data URI placeholder
			$image_url = 'data:image/svg+xml;base64,' . base64_encode(
				'<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300" fill="#f0f0f0">
					<rect width="300" height="300" fill="#f8f8f8"/>
					<text x="150" y="150" text-anchor="middle" dy="0.3em" font-family="Arial, sans-serif" font-size="14" fill="#999">No Image</text>
				</svg>'
			);
		}
	}
	$link         = get_term_link( $category );
	$name         = $category->name;
	$count        = $category->count;
	$show_count   = (bool) ( $attributes['showProductCount'] ?? true );

	ob_start();
	?>
	<div class="hpc-category-card">
		<a href="<?php echo esc_url( $link ); ?>" class="hpc-category-card__link">
			<div class="hpc-category-card__image-wrapper">
				<img src="<?php echo esc_url( $image_url ); ?>" 
					 alt="<?php echo esc_attr( $name ); ?>" 
					 class="hpc-category-card__image"
					 loading="lazy">
				<div class="hpc-category-card__overlay"></div>
				<div class="hpc-category-card__content">
					<h3 class="hpc-category-card__title"><?php echo esc_html( $name ); ?></h3>
					<?php if ( $show_count ) : ?>
						<span class="hpc-category-card__count">
							<?php printf( esc_html( _n( '%s Product', '%s Products', $count, 'house-products-carousel' ) ), number_format_i18n( $count ) ); ?>
						</span>
					<?php endif; ?>
				</div>
			</div>
		</a>
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
	$product_obj = wc_get_product( $product_id );
	if ( ! $product_obj ) {
		return '';
	}

	ob_start();
	?>
	<?php
	/**
	 * Check if the House Product Card Override plugin is active and enabled.
	 * If so, use the standard WooCommerce hook that HPCO uses to render the card.
	 */
	$hpco_enabled = defined( 'HPCO_VERSION' ) && ( get_option( 'hpco_enable_override', 'yes' ) === 'yes' );
	$rendered     = false;

	if ( $hpco_enabled ) {
		global $product;
		$old_product = $product;
		$product     = $product_obj;

		// Capture output from the override hook.
		ob_start();
		do_action( 'woocommerce_before_shop_loop_item' );
		$override_html = ob_get_clean();

		if ( ! empty( trim( $override_html ) ) ) {
			echo $override_html;
			$rendered = true;
		}

		// Restore global product.
		$product = $old_product;
	}

	if ( ! $rendered ) :
		// Fallback to internal card design (Original Carousel Design)
		$title     = $product_obj->get_name();
		$price     = $product_obj->get_price_html();
		$permalink = $product_obj->get_permalink();
		$image_id  = $product_obj->get_image_id();

		$tags          = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'slugs' ) );
		$is_bestseller = is_array( $tags ) && in_array( 'best-seller', $tags, true );

		$rating_count = $product_obj->get_rating_count();
		$average      = (float) $product_obj->get_average_rating();

		$acf_fields = get_acf_specs( $product_id );
		?>
		<article class="hpc-card">
			<a href="<?php echo esc_url( $permalink ); ?>" class="hpc-card__link" aria-label="<?php echo esc_attr( $title ); ?>">

				<div class="hpc-card__image-wrapper">
					<?php
					$gallery_ids    = $product_obj->get_gallery_image_ids();
					$hover_image_id = ! empty( $gallery_ids ) ? $gallery_ids[0] : null;
					?>

					<?php if ( $image_id ) : ?>
						<?php
						echo wp_get_attachment_image(
							$image_id,
							'medium_large',
							false,
							array(
								'class'    => 'hpc-card__image hpc-card__image--featured',
								'loading'  => 'lazy',
								'decoding' => 'async',
								'alt'      => esc_attr( $title ),
							)
						);
						?>
						<?php if ( $hover_image_id ) : ?>
							<?php
							echo wp_get_attachment_image(
								$hover_image_id,
								'medium_large',
								false,
								array(
									'class'    => 'hpc-card__image hpc-card__image--hover',
									'loading'  => 'lazy',
									'decoding' => 'async',
									'alt'      => esc_attr( $title ),
								)
							);
							?>
						<?php endif; ?>
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

					<?php if ( ( $attributes['showRating'] ?? true ) && $rating_count > 0 ) : ?>
						<div class="hpc-card__rating" aria-label="<?php printf( esc_attr__( 'Rated %s out of 5', 'house-products-carousel' ), esc_attr( number_format( $average, 1 ) ) ); ?>">
							<?php echo render_stars( $average, $product_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span class="hpc-card__rating-count">(<?php echo esc_html( $rating_count ); ?>)</span>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $acf_fields ) ) : ?>
					<div class="hpc-card__specs">
						<?php foreach ( $acf_fields as $spec ) : ?>
							<div class="hpc-card__spec" title="<?php echo esc_attr( $spec['label'] ); ?>">
								<span class="hpc-card__spec-icon"><?php echo $spec['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<span class="hpc-card__spec-value"><?php echo esc_html( $spec['value'] ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

			</a>
		</article>
	<?php endif; ?>
	<?php
	return ob_get_clean();
}

/**
 * Get Secure Custom Fields (SCF) specification fields for a product.
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
			'label'           => __( 'Floors', 'house-products-carousel' ),
			'icon'            => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="12" x2="21" y2="12"/></svg>',
			'suffix'          => '',
			'suffix_singular' => ' Floor',
			'suffix_plural'   => ' Floors',
		),
		'bedrooms'  => array(
			'label'           => __( 'Bedrooms', 'house-products-carousel' ),
			'icon'            => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v11a2 2 0 002 2h14a2 2 0 002-2V7"/><path d="M3 11h18"/><path d="M7 11V7a2 2 0 012-2h6a2 2 0 012 2v4"/></svg>',
			'suffix'          => '',
			'suffix_singular' => ' Bedroom',
			'suffix_plural'   => ' Bedrooms',
		),
		'bathrooms' => array(
			'label'           => __( 'Bathrooms', 'house-products-carousel' ),
			'icon'            => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16a1 1 0 011 1v3a4 4 0 01-4 4H7a4 4 0 01-4-4v-3a1 1 0 011-1z"/><path d="M6 12V5a2 2 0 012-2h0a2 2 0 012 2v1"/></svg>',
			'suffix'          => '',
			'suffix_singular' => ' Bathroom',
			'suffix_plural'   => ' Bathrooms',
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
			if ( isset( $config['suffix_singular'], $config['suffix_plural'] ) ) {
				$numeric_value = floatval( $value );
				$suffix        = ( $numeric_value > 1 ) ? $config['suffix_plural'] : $config['suffix_singular'];
			} else {
				$suffix = $config['suffix'];
			}

			$specs[] = array(
				'label' => $config['label'],
				'icon'  => $config['icon'],
				'value' => sanitize_text_field( $value ) . $suffix,
			);
		}
	}

	return $specs;
}

/**
 * Render rating stars.
 */
function render_stars( $rating, $product_id = 0 ) {
	$rating   = max( 0, min( 5, (float) $rating ) );
	$html     = '';
	$full     = (int) floor( $rating );
	$has_half = ( $rating - $full ) >= 0.5;
	$empty    = 5 - $full - ( $has_half ? 1 : 0 );

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
 * Render carousel layout.
 */
function render_carousel_layout( $product_ids, $attributes ) {
	$columns = max( 1, min( 6, absint( $attributes['columns'] ?? 3 ) ) );
	$splide_options = wp_json_encode( get_common_splide_options( $columns, $attributes ) );

	$wrapper_classes = 'hpc-carousel-wrapper';
	if ( (bool) ( $attributes['enableAnimation'] ?? true ) ) {
		$wrapper_classes .= ' hpc-animate-reveal';
	}
	if ( (bool) ( $attributes['trackOverflowVisible'] ?? false ) ) {
		$wrapper_classes .= ' hpc-track-overflow-visible';
	}

	$wrapper_style = sprintf(
		'--hpc-anim-duration: %dms; --hpc-anim-stagger: %dms;',
		absint( $attributes['animationDuration'] ?? 800 ),
		absint( $attributes['animationStagger'] ?? 150 )
	);

	ob_start();
	?>
	<div class="<?php echo esc_attr( $wrapper_classes ); ?>" 
		 data-splide-options="<?php echo esc_attr( $splide_options ); ?>"
		 style="<?php echo esc_attr( $wrapper_style ); ?>">
		<div class="splide hpc-carousel" aria-label="<?php esc_attr_e( 'House Products Carousel', 'house-products-carousel' ); ?>">
			<div class="splide__track">
				<ul class="splide__list">
					<?php foreach ( $product_ids as $product_id ) : ?>
						<li class="splide__slide">
							<?php echo render_product_card( (int) $product_id, $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Render grid layout.
 */
function render_grid_layout( $product_ids, $attributes ) {
	$columns = max( 1, min( 6, absint( $attributes['columns'] ?? 3 ) ) );

	$wrapper_classes = 'hpc-grid-wrapper';
	if ( (bool) ( $attributes['enableAnimation'] ?? true ) ) {
		$wrapper_classes .= ' hpc-animate-reveal';
	}

	$wrapper_style = sprintf(
		'--hpc-columns: %d; --hpc-anim-duration: %dms; --hpc-anim-stagger: %dms;',
		$columns,
		absint( $attributes['animationDuration'] ?? 800 ),
		absint( $attributes['animationStagger'] ?? 150 )
	);

	ob_start();
	?>
	<div class="<?php echo esc_attr( $wrapper_classes ); ?>" style="<?php echo esc_attr( $wrapper_style ); ?>">
		<div class="hpc-grid">
			<?php foreach ( $product_ids as $product_id ) : ?>
				<div class="hpc-grid__item">
					<?php echo render_product_card( (int) $product_id, $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
