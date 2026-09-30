<script src="./assets/js/app.js"></script>
<script src="./assets/js/sidebar.js"></script>
<script src="./assets/js/carousel.js"></script>
<script src="./assets/js/home.js"></script>
<script>
       

        // Function to update the top navigation header dynamically
        function updateHeaderData(userData) {
            const headerStreak = document.getElementById('header-streak-count');
            const headerProfileImg = document.getElementById('header-profile-img');

            // 1. Update Streak Count Display Node smoothly
            if (headerStreak) {
                headerStreak.textContent = userData.currentStreak + (userData.currentStreak === 1 ? ' Day' : ' Days');
            }

            // 2. Update Image Path Assets dynamically
            if (headerProfileImg && userData.profileImageUrl) {
                headerProfileImg.src = userData.profileImageUrl;
            }
        }

        // Example Integration Test Payload
        document.addEventListener("DOMContentLoaded", () => {
            const userPayload = {
                currentStreak: 24,
                profileImageUrl: "./assets/images/profile-picture.webp.jpg"
            };

            // Run the updater sequence
            // updateHeaderData(userPayload);
        });

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
    </script>
    <script>
        /**
     * Global Dashboard Manager Module
     * Coordinates active dynamic interface state tracking for MemPulse
     */
        const MemPulseDashboard = {
            // 1. Core State Updater Method
            updateAll(appPayload) {
                if (!appPayload) return;

                this.syncHeader(appPayload.user);
                this.syncSidebarProfile(appPayload.user);
                this.syncDatabaseMetrics(appPayload.stats);
                this.syncStreakMetrics(appPayload.stats);
            },

            // 2. Synchronize Top Fixed Navigation Header Elements
            syncHeader(user) {
                if (!user) return;
                const headerStreak = document.getElementById('header-streak-count');
                const headerImg = document.getElementById('header-profile-img');

                if (headerStreak) {
                    headerStreak.textContent = user.currentStreak + (user.currentStreak === 1 ? ' Day' : ' Days');
                }
                if (headerImg && user.profileImageUrl) {
                    headerImg.src = user.profileImageUrl;
                }
            },

            // 3. Synchronize Bottom Expanded/Collapsed Sidebar Components
            syncSidebarProfile(user) {
                if (!user) return;
                const sidebarName = document.getElementById('sidebar-profile-name');
                const sidebarRole = document.getElementById('sidebar-profile-role');
                const sidebarImg = document.getElementById('sidebar-profile-img');

                if (sidebarName) sidebarName.textContent = user.displayName;
                if (sidebarRole) sidebarRole.textContent = user.displayRole;
                if (sidebarImg && user.profileImageUrl) {
                    sidebarImg.src = user.profileImageUrl;
                }
            },

            // 4. Update the Spaced-Repetition Core Database Analytics Rows
            syncDatabaseMetrics(stats) {
                if (!stats) return;
                const cardsDue = document.getElementById('stat-cards-due');
                const mastered = document.getElementById('stat-mastered');
                const accuracy = document.getElementById('stat-accuracy');
                const tough = document.getElementById('stat-tough');

                if (cardsDue) cardsDue.textContent = stats.cardsDue ?? '--';
                if (mastered) mastered.textContent = stats.masteredCards ?? '--';
                if (accuracy) accuracy.textContent = (stats.lifetimeAccuracy !== undefined) ? `${stats.lifetimeAccuracy}%` : '--%';
                if (tough) tough.textContent = stats.toughCards ?? '--';
            },

            // 5. Update Streak and Performance Calculation Cards
            syncStreakMetrics(stats) {
                if (!stats) return;
                const currentStreak = document.getElementById('stat-current-streak');
                const bestStreak = document.getElementById('stat-best-streak');
                const streakAccuracy = document.getElementById('stat-streak-accuracy');

                if (currentStreak) currentStreak.textContent = stats.currentStreak ?? '--';
                if (bestStreak) bestStreak.textContent = stats.bestStreak ?? '--';
                if (streakAccuracy) streakAccuracy.textContent = stats.streakAccuracy ?? '--';
            }
        };

        // ─── INITIALIZATION / LIVE SQL DATABASE RUNNER ───
        document.addEventListener("DOMContentLoaded", () => {
            // This section at the bottom of your file now instantly matches your dynamic PHP calculations!
            const incomingAppPayload = {
                user: {
                    displayName: <?php echo json_encode($display_name); ?>,
                    displayRole: "Active Learner",
                    currentStreak: <?php echo intval($current_streak); ?>,
                    profileImageUrl: "./assets/images/profile-picture.webp.jpg" 
                },
                stats: {
                    cardsDue: <?php echo intval($cards_due); ?>,
                    masteredCards: <?php echo intval($mastered_cards); ?>,
                    lifetimeAccuracy: <?php echo floatval($avg_accuracy); ?>,
                    toughCards: <?php echo intval($tough_cards); ?>,
                    currentStreak: <?php echo intval($current_streak); ?>,
                    bestStreak: <?php echo intval($best_streak); ?>,
                    streakAccuracy: <?php echo floatval($streak_accuracy); ?>
                }
            };

            // Default the heatmap to the most recent study days
            document.addEventListener("DOMContentLoaded", () => {
            const heatmapScroller = document.getElementById("heatmap-scroll");

            if (heatmapScroller) {
                requestAnimationFrame(() => {
                    heatmapScroller.scrollLeft =
                        heatmapScroller.scrollWidth - heatmapScroller.clientWidth;
                });
            }
        });

            document.querySelectorAll("#heatmap-scroll .heatmap-cell").forEach(cell => {
                cell.addEventListener("mouseenter", () => {
                    const rect = cell.getBoundingClientRect();

                    cell.style.setProperty("--tooltip-left", `${rect.left + rect.width / 2}px`);
                    cell.style.setProperty("--tooltip-top", `${rect.top - 8}px`);
                });
            });

            // Run deep script synchronization across Header, Sidebar, and Stats Row
            MemPulseDashboard.updateAll(incomingAppPayload);
        });

    </script>