# 🏠 House Products Gutenberg Blocks Ensemble

![WordPress Version](https://img.shields.io/badge/WordPress-6.4+-blue.svg?logo=wordpress)
![WooCommerce Version](https://img.shields.io/badge/WooCommerce-3.0+-purple.svg?logo=woocommerce)
![PHP Version](https://img.shields.io/badge/PHP-7.4+-8892BF.svg?logo=php)
![License](https://img.shields.io/badge/license-GPL--2.0+-green.svg)

A modern, powerful Gutenberg block ensemble that displays WooCommerce products in sleek, customizable layouts, including carousels, grids, and category carousels. Designed specifically for real-estate and architecture websites, it automatically integrates house specifications using Secure Custom Fields (SCF) or Advanced Custom Fields (ACF).

---

## ✨ Features

- **🧱 Three Professional Blocks**:
  - **House Products Carousel**: Sleek product slide with house specs.
  - **House Products Grid**: Responsive grid layout for property listings.
  - **Categories Carousel**: Interactive carousel for WooCommerce product categories.
- **🚀 Dynamic Gutenberg Blocks**: Easily customizable right from the WordPress block editor with intuitive sidebar controls.
- **📱 Responsive Layouts**: Powered by SplideJS for carousels; CSS Grid for listing grids.
- **🎨 Modern Aesthetic**: Beautiful card design with smooth hover animations, shadows, and accessible colors.
- **🏷️ "Best Seller" Badging**: Automatically adds badges based on WooCommerce product tags.
- **📐 Specification Rows**: Displays key property data (floors, bedrooms, bathrooms, area, dimensions) out-of-the-box.
- **⚡ Performance Optimized**: Server-side rendered for superior SEO and lightning-fast load times.
- **🗂️ Accessibility Ready**: Includes focus-visible states, reduced motion support, and proper ARIA labeling.

## 🛠️ Requirements

- **WordPress** 6.4 or higher
- **WooCommerce** 3.0 or higher
- **Secure Custom Fields (SCF)** or **Advanced Custom Fields (ACF)** for specification fields

## 📋 Custom Fields Setup

To display the property specifications on the product cards, create the following custom fields (using SCF or ACF) and assign them to WooCommerce products:

- `floors` — Number of floors
- `bedrooms` — Number of bedrooms
- `bathrooms` — Number of bathrooms
- `width` — Property width (displayed with "m" suffix)
- `length` — Property length (displayed with "m" suffix)
- `area` — Property area (displayed with "m²" suffix)

*(Note: If ACF/SCF is not installed or fields are empty, the specification row gracefully hides itself.)*

## 🚀 Installation & Usage

1. **Download/Clone** this repository and place the `house-products-carousel-block` folder into your `/wp-content/plugins/` directory.
2. **Activate** the plugin through the 'Plugins' menu in WordPress.
3. Make sure **WooCommerce** and **SCF/ACF** are installed and active.
4. Add the custom fields outlined above.
5. In the Gutenberg editor, search for **"House Products Carousel"** and add the block to your page.
6. Configure the layout, animations, and typography using the block sidebar panel!

## ❓ FAQ

**Does this work with Advanced Custom Fields (ACF) instead of Secure Custom Fields (SCF)?**
Yes! Both plugins provide the same `get_field()` API. The carousel block works seamlessly with either of them.

**How do I add the "Best Seller" badge to a house?**
Simply add a product tag with the exact slug `best-seller` to any of your WooCommerce products. The badge will appear automatically on the carousel cards.

**What happens if I don't use the custom fields?**
The carousel will continue to look great! It will simply display the product image, title, price, and ratings without the specification row.

## 📜 Changelog

### 1.2.0
- **🔥 NEW: Grid Block**: Added a full-featured "House Products Grid" block with category and count controls.
- **🔥 NEW: Categories Carousel**: Added a "Categories Carousel" block to display WooCommerce categories with images.
- **🛠️ Category Exclusion**: Added the ability to exclude specific categories from the product blocks.
- **🖼️ Image Size Control**: Improved editor image selection using `medium_large` for clearer previews.
- **📦 Multi-Block Architecture**: Refactored to a modern, multiple-block ensemble structure.

### 1.1.0
- Optimize block loading speed in the editor using `_embed` for media data.
- Align plugin, block, and package versions to 1.1.0.
- General performance improvements and code cleanup.

### 1.0.2
- Add staggered reveal animation when the carousel enters the viewport.
- Add block settings to enable/disable animation and control duration/stagger.
- Add "Overflow Visible" setting for the carousel track with layout warning.
- Improve card design with better shadow and radius variables.

### 1.0.1
- Update Splide arrows to modern icons.
- Fix arrow alignment and orientation.
- Improve UI responsiveness for carousel navigation.

### 1.0.0
- Initial release.
