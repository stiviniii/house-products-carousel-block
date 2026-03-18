/**
 * Custom webpack configuration for House Products Carousel.
 *
 * Extends the default @wordpress/scripts config to add
 * the frontend.js entry point for Splide initialization.
 */

const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        ...defaultConfig.entry(),
        'hpc-frontend': path.resolve(__dirname, 'src', 'common', 'frontend.js'),
    },
};
