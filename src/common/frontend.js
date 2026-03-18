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
    // Both carousel and grid use this class for animations.
    const revealWrappers = document.querySelectorAll('.hpc-animate-reveal');
    // For Splide initialization, we specifically need the carousel wrappers.
    const carouselWrappers = document.querySelectorAll('.hpc-carousel-wrapper');

    if (!revealWrappers.length && !carouselWrappers.length) {
        return;
    }

    // Intersection Observer for scroll reveal animation
    const revealObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    revealObserver.unobserve(entry.target);
                }
            });
        },
        {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px',
        }
    );

    // Observe all revealable elements
    revealWrappers.forEach((wrapper) => {
        revealObserver.observe(wrapper);
    });

    // Initialize Splide for carousels
    carouselWrappers.forEach((wrapper) => {
        const splideEl = wrapper.querySelector('.splide');
        if (!splideEl) {
            return;
        }

        if (typeof Splide === 'undefined') {
            console.warn('House Products Carousel: Splide library not loaded.');
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
            console.warn('House Products Carousel: Invalid Splide options JSON.', e);
        }

        try {
            const splide = new Splide(splideEl, options);
            splide.mount();
        } catch (e) {
            console.error('House Products Carousel: Failed to initialize Splide.', e);
        }
    });
});

