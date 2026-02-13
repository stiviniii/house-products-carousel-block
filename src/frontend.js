/**
 * House Products Carousel — Frontend SplideJS Initializer
 *
 * Runs on the frontend only. Finds all carousel instances
 * and initializes Splide with the data-attribute options.
 *
 * Splide is loaded as a global via wp_enqueue_script (not bundled),
 * so we reference it from the window scope.
 */

/* global Splide */

document.addEventListener('DOMContentLoaded', () => {
    const wrappers = document.querySelectorAll('.hpc-carousel-wrapper');

    if (!wrappers.length) {
        return;
    }

    if (typeof Splide === 'undefined') {
        // eslint-disable-next-line no-console
        console.warn(
            'House Products Carousel: Splide library not loaded. Carousel will not initialize.'
        );
        return;
    }

    wrappers.forEach((wrapper) => {
        const splideEl = wrapper.querySelector('.splide');
        if (!splideEl) {
            return;
        }

        // Prevent double-initialization.
        if (splideEl.classList.contains('is-initialized')) {
            return;
        }

        let options = {};
        try {
            const raw = wrapper.getAttribute('data-splide-options');
            if (raw) {
                options = JSON.parse(raw);
            }
        } catch (e) {
            // eslint-disable-next-line no-console
            console.warn(
                'House Products Carousel: Invalid Splide options JSON.',
                e
            );
        }

        try {
            const splide = new Splide(splideEl, options);
            splide.mount();
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error(
                'House Products Carousel: Failed to initialize Splide.',
                e
            );
        }
    });
});
