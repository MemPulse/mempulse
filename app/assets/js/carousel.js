// Simple smooth scroll controls for deck carousel
const track = document.getElementById('deck-track');
const scrollLeftBtn = document.getElementById('deck-scroll-left');
const scrollRightBtn = document.getElementById('deck-scroll-right');

if (track && scrollLeftBtn && scrollRightBtn) {
    scrollLeftBtn.addEventListener('click', () => {
        track.scrollBy({ left: -340, behavior: 'smooth' });
    });
    scrollRightBtn.addEventListener('click', () => {
        track.scrollBy({ left: 340, behavior: 'smooth' });
    });
}

// Hero carousel controller
const slides = document.querySelectorAll('.hero-slide');
const dots = document.querySelectorAll('.hero-dot');
const heroPrev = document.getElementById('hero-prev');
const heroNext = document.getElementById('hero-next');
let currentSlide = 0;
const totalSlides = slides.length;

// Auto-play Configuration
let autoPlayTimer = null;
const SLIDE_DELAY = 5000; // 5 seconds interval time

function showSlide(index) {
    currentSlide = (index + totalSlides) % totalSlides;
    slides.forEach((slide, idx) => {
        if (idx === currentSlide) {
            slide.classList.remove('opacity-0', 'pointer-events-none', 'z-0');
            slide.classList.add('opacity-100', 'z-10');
        } else {
            slide.classList.remove('opacity-100', 'z-10');
            slide.classList.add('opacity-0', 'pointer-events-none', 'z-0');
        }
    });

    dots.forEach((dot, idx) => {
        if (idx === currentSlide) {
            dot.classList.remove('bg-white/40', 'w-2.5');
            dot.classList.add('bg-cyan-400', 'w-8');
        } else {
            dot.classList.remove('bg-cyan-400', 'w-8');
            dot.classList.add('bg-white/40', 'w-2.5');
        }
    });
}

// Starts or restarts the auto-advance timer
function startAutoPlay() {
    stopAutoPlay(); // Safety clear
    autoPlayTimer = setInterval(() => {
        showSlide(currentSlide + 1);
    }, SLIDE_DELAY);
}

// Stops the timer loop cleanly
function stopAutoPlay() {
    if (autoPlayTimer) {
        clearInterval(autoPlayTimer);
    }
}

// Navigation button behaviors with user interaction reset
if (heroPrev && heroNext) {
    heroPrev.addEventListener('click', () => {
        showSlide(currentSlide - 1);
        startAutoPlay(); // Restart timer sequence on manual interaction
    });
    heroNext.addEventListener('click', () => {
        showSlide(currentSlide + 1);
        startAutoPlay(); // Restart timer sequence on manual interaction
    });
}

// Interactive pagination click event listeners
dots.forEach((dot, idx) => {
    dot.addEventListener('click', () => {
        showSlide(idx);
        startAutoPlay(); // Restart timer sequence on manual interaction
    });
});

// Initialize the slide loop on page load
startAutoPlay();