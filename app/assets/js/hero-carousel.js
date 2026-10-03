/**
 * Hero Carousel Management Module
 * Standardized module for managing slide transitions, autoplay timers,
 * keyboard accessibility, and wayfinder dot state synchronization.
 */
(($) => {
    'use strict';

    class HeroCarousel {
        constructor(carouselElement, autoPlayDelay = 7000) {
            if (!carouselElement) return;

            this.carousel = carouselElement;
            this.delay = autoPlayDelay;
            this.slides = Array.from(this.carousel.querySelectorAll('.hero-slide'));
            this.dotGroups = this.slides.map(slide =>
                Array.from(slide.querySelectorAll('.hero-dot'))
            );

            this.currentIndex = 0;
            this.timer = null;

            this.init();
        }

        init() {
            if (this.slides.length === 0) return;

            this.bindEvents();
            this.goToSlide(0, false);
            this.startAutoplay();
        }

        goToSlide(index, resetTimer = true) {
            this.currentIndex = (index + this.slides.length) % this.slides.length;

            // Update slide visibilities & accessibility attributes
            this.slides.forEach((slide, i) => {
                const isActive = i === this.currentIndex;
                slide.classList.toggle('opacity-100', isActive);
                slide.classList.toggle('z-10', isActive);
                slide.classList.toggle('opacity-0', !isActive);
                slide.classList.toggle('pointer-events-none', !isActive);
                slide.classList.toggle('z-0', !isActive);

                slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            });

            // Update wayfinder dot indicators across all duplicated slide footers
            this.dotGroups.forEach((group) => {
                group.forEach((dot, dotIdx) => {
                    const isActive = dotIdx === this.currentIndex;
                    dot.classList.toggle('bg-cyan-400', isActive);
                    dot.classList.toggle('w-6', isActive);
                    dot.classList.toggle('bg-white/30', !isActive);
                    dot.classList.toggle('w-2', !isActive);

                    dot.setAttribute('aria-current', isActive ? 'true' : 'false');
                });
            });

            if (resetTimer) {
                this.startAutoplay();
            }
        }

        startAutoplay() {
            this.stopAutoplay();
            this.timer = setInterval(() => {
                this.goToSlide(this.currentIndex + 1, false);
            }, this.delay);
        }

        stopAutoplay() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        }

        bindEvents() {
            // Click handlers on wayfinder dots
            this.dotGroups.forEach((group) => {
                group.forEach((dot) => {
                    dot.addEventListener('click', (e) => {
                        e.preventDefault();
                        const targetIndex = parseInt(dot.dataset.dotIndex, 10);
                        if (!isNaN(targetIndex)) {
                            this.goToSlide(targetIndex);
                        }
                    });
                });
            });

            // Accessibility: Pause autoplay on hover and focus
            this.carousel.addEventListener('mouseenter', () => this.stopAutoplay());
            this.carousel.addEventListener('mouseleave', () => this.startAutoplay());
            this.carousel.addEventListener('focusin', () => this.stopAutoplay());
            this.carousel.addEventListener('focusout', (e) => {
                if (!this.carousel.contains(e.relatedTarget)) {
                    this.startAutoplay();
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const carouselEl = document.getElementById('hero-carousel');
        if (carouselEl) {
            new HeroCarousel(carouselEl, 7000);
        }
    });
})(window);