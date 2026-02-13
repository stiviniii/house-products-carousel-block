=== House Products Carousel Block ===
Contributors: Steven Ayo
Tags: woocommerce, gutenberg, carousel, products, block
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modern Gutenberg block that displays WooCommerce products in a responsive SplideJS carousel with Secure Custom Fields house specifications.

== Description ==

House Products Carousel Block adds a beautiful, customizable product carousel to your WordPress site. Perfect for real-estate style product displays, it renders WooCommerce products with featured images, pricing, ratings, and Secure Custom Fields (SCF) specification fields (floors, bedrooms, bathrooms, dimensions, area).

The plugin is fully compatible with both **Secure Custom Fields (SCF)** and **Advanced Custom Fields (ACF)** — both provide the same `get_field()` function used to retrieve specification data.

**Features:**

* Dynamic Gutenberg block with sidebar controls
* Responsive SplideJS carousel with loop, autoplay, and arrow navigation
* Modern card design with hover animations
* "Best Seller" badge based on product tags
* SCF/ACF specification row for house/property data
* Server-side rendering for SEO and performance
* Mobile-first responsive breakpoints
* Accessibility features (focus-visible, reduced motion, ARIA labels)
* GeneratePress theme compatible

**Requirements:**

* WordPress 6.4+
* WooCommerce 3.0+
* Secure Custom Fields (SCF) or Advanced Custom Fields (ACF) for specification fields

**SCF Custom Fields Used:**

The following custom fields should be created for WooCommerce products:

* `floors` — Number of floors
* `bedrooms` — Number of bedrooms
* `bathrooms` — Number of bathrooms
* `width` — Property width (displayed with "m" suffix)
* `length` — Property length (displayed with "m" suffix)
* `area` — Property area (displayed with "m²" suffix)

== Installation ==

1. Upload the `house-products-carousel-block` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure WooCommerce and Secure Custom Fields are installed and active.
4. Create the required custom fields (floors, bedrooms, bathrooms, width, length, area) for products.
5. Add the "House Products Carousel" block in the Gutenberg editor.
6. Configure carousel settings in the block sidebar panel.

== Frequently Asked Questions ==

= Does this work with ACF instead of SCF? =
Yes. Both Secure Custom Fields (SCF) and Advanced Custom Fields (ACF) provide the same `get_field()` API. The plugin works seamlessly with either.

= What happens if SCF/ACF is not installed? =
The carousel will still display products with images, titles, prices, and ratings. The specification row (floors, bedrooms, etc.) will simply be hidden.

= How do I add the "Best Seller" badge? =
Add a product tag with the slug `best-seller` to any WooCommerce product. The badge will appear automatically on those products.

== Changelog ==

= 1.0.0 =
* Initial release.
