(() => {
    'use strict';

    const App = {
        config: {
            storageThemeKey: 'vertexgrad_theme',
            storageLangKey: 'vertexgrad_lang',
            defaultTheme: document.documentElement.dataset.theme || 'dark',
            defaultLang: document.documentElement.lang || 'en',
            revealSelector: '.fade-up, .scale-in',
            navbarSelector: '.navbar',
            mobileToggleSelector: '[data-mobile-toggle]',
            mobileMenuSelector: '[data-mobile-menu]',
            themeToggleSelector: '[data-theme-toggle]',
            langToggleSelector: '[data-lang-toggle]',
            headerScrolledClass: 'is-scrolled',
        },

        state: {
            theme: 'dark',
            lang: 'en',
            mobileMenuOpen: false,
        },

        network: {
            canvas: null,
            ctx: null,
            points: [],
            animationId: null,
            mouse: { x: null, y: null, active: false },
            dpr: Math.min(window.devicePixelRatio || 1, 2),
        },

        init() {
            this.cache();
            this.restorePreferences();
            this.bindEvents();
            this.initReveal();
            this.handleInitialHeader();
            this.syncDirection();
            this.initNetworkBackground();
            this.markReady();
        },

        cache() {
            this.html = document.documentElement;
            this.body = document.body;
            this.navbar = document.querySelector(this.config.navbarSelector);
            this.mobileToggle = document.querySelector(this.config.mobileToggleSelector);
            this.mobileMenu = document.querySelector(this.config.mobileMenuSelector);
            this.themeToggles = document.querySelectorAll(this.config.themeToggleSelector);
            this.langToggles = document.querySelectorAll(this.config.langToggleSelector);
            this.revealItems = document.querySelectorAll(this.config.revealSelector);
        },

        restorePreferences() {
            const savedTheme = localStorage.getItem(this.config.storageThemeKey);
            const savedLang = localStorage.getItem(this.config.storageLangKey);

            this.state.theme = savedTheme || this.config.defaultTheme || 'dark';
            this.state.lang = savedLang || this.config.defaultLang || 'en';

            this.setTheme(this.state.theme, false);
            this.setLanguage(this.state.lang, false);
        },

        bindEvents() {
            window.addEventListener('scroll', this.handleScroll.bind(this), { passive: true });
            window.addEventListener('resize', this.handleResize.bind(this));

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && this.state.mobileMenuOpen) {
                    this.closeMobileMenu();
                }
            });

            if (this.mobileToggle) {
                this.mobileToggle.addEventListener('click', () => {
                    this.toggleMobileMenu();
                });
            }

            if (this.mobileMenu) {
                this.mobileMenu.querySelectorAll('a').forEach((link) => {
                    link.addEventListener('click', () => this.closeMobileMenu());
                });
            }

            this.themeToggles.forEach((toggle) => {
                toggle.addEventListener('click', () => {
                    const nextTheme = this.state.theme === 'dark' ? 'light' : 'dark';
                    this.setTheme(nextTheme, true);
                });
            });

            this.langToggles.forEach((toggle) => {
                toggle.addEventListener('click', () => {
                    const nextLang = this.state.lang === 'ar' ? 'en' : 'ar';
                    this.setLanguage(nextLang, true);
                });
            });

            document.querySelectorAll('[data-set-theme]').forEach((button) => {
                button.addEventListener('click', () => {
                    const theme = button.dataset.setTheme;
                    if (theme) this.setTheme(theme, true);
                });
            });

            document.querySelectorAll('[data-set-lang]').forEach((button) => {
                button.addEventListener('click', () => {
                    const lang = button.dataset.setLang;
                    if (lang) this.setLanguage(lang, true);
                });
            });
        },

        setTheme(theme, persist = true) {
            this.state.theme = theme;
            this.html.setAttribute('data-theme', theme);
            this.body.setAttribute('data-theme', theme);

            if (persist) {
                localStorage.setItem(this.config.storageThemeKey, theme);
            }

            this.themeToggles.forEach((toggle) => {
                toggle.setAttribute('aria-pressed', String(theme === 'dark'));
                toggle.dataset.activeTheme = theme;
            });

            document.dispatchEvent(
                new CustomEvent('vertexgrad:theme-changed', {
                    detail: { theme },
                })
            );
        },

        setLanguage(lang, persist = true) {
            this.state.lang = lang;
            this.html.setAttribute('lang', lang);

            const dir = lang === 'ar' ? 'rtl' : 'ltr';
            this.html.setAttribute('dir', dir);
            this.body.setAttribute('dir', dir);

            if (persist) {
                localStorage.setItem(this.config.storageLangKey, lang);
            }

            this.langToggles.forEach((toggle) => {
                toggle.setAttribute('data-active-lang', lang);
                toggle.setAttribute('aria-label', `Current language: ${lang}`);
            });

            document.querySelectorAll('[data-i18n]').forEach((element) => {
                const text = element.dataset[`i18n${lang.charAt(0).toUpperCase() + lang.slice(1)}`];
                if (text) {
                    element.textContent = text;
                }
            });

            document.querySelectorAll('[data-i18n-placeholder]').forEach((element) => {
                const placeholder =
                    element.dataset[`i18nPlaceholder${lang.charAt(0).toUpperCase() + lang.slice(1)}`];
                if (placeholder) {
                    element.setAttribute('placeholder', placeholder);
                }
            });

            this.syncDirection();

            document.dispatchEvent(
                new CustomEvent('vertexgrad:language-changed', {
                    detail: { lang, dir },
                })
            );
        },

        syncDirection() {
            const isRTL = this.state.lang === 'ar' || this.html.getAttribute('dir') === 'rtl';
            this.body.classList.toggle('rtl', isRTL);
            this.body.classList.toggle('ltr', !isRTL);
        },

        toggleMobileMenu() {
            if (this.state.mobileMenuOpen) {
                this.closeMobileMenu();
            } else {
                this.openMobileMenu();
            }
        },

        openMobileMenu() {
            if (!this.mobileMenu || !this.mobileToggle) return;

            this.state.mobileMenuOpen = true;
            this.mobileMenu.hidden = false;
            this.mobileMenu.classList.add('is-open');
            this.mobileToggle.classList.add('is-active');
            this.mobileToggle.setAttribute('aria-expanded', 'true');
            this.body.classList.add('menu-open');
        },

        closeMobileMenu() {
            if (!this.mobileMenu || !this.mobileToggle) return;

            this.state.mobileMenuOpen = false;
            this.mobileMenu.classList.remove('is-open');
            this.mobileToggle.classList.remove('is-active');
            this.mobileToggle.setAttribute('aria-expanded', 'false');
            this.body.classList.remove('menu-open');

            window.setTimeout(() => {
                if (!this.state.mobileMenuOpen) {
                    this.mobileMenu.hidden = true;
                }
            }, 220);
        },

        handleScroll() {
            this.handleHeaderScrolled();
        },

        handleResize() {
            if (window.innerWidth > 768 && this.state.mobileMenuOpen) {
                this.closeMobileMenu();
            }

            if (this.network.canvas) {
                this.setupNetworkCanvas();
                this.createNetworkPoints();
            }
        },

        handleInitialHeader() {
            this.handleHeaderScrolled();
        },

        handleHeaderScrolled() {
            if (!this.navbar) return;

            const scrolled = window.scrollY > 12;
            this.navbar.classList.toggle(this.config.headerScrolledClass, scrolled);
        },

        initReveal() {
            if (!('IntersectionObserver' in window)) {
                this.revealItems.forEach((item) => item.classList.add('is-visible'));
                return;
            }

            const observer = new IntersectionObserver(
                (entries, obs) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            obs.unobserve(entry.target);
                        }
                    });
                },
                {
                    threshold: 0.14,
                    rootMargin: '0px 0px -40px 0px',
                }
            );

            this.revealItems.forEach((item) => observer.observe(item));
        },

        initNetworkBackground() {
            const canvas = document.getElementById('networkCanvas');
            if (!canvas) return;

            this.network.canvas = canvas;
            this.network.ctx = canvas.getContext('2d');

            if (!this.network.ctx) return;

            this.setupNetworkCanvas();
            this.createNetworkPoints();
            this.bindNetworkEvents();
            this.animateNetwork();

            document.addEventListener('vertexgrad:theme-changed', () => {
                // redraw theme colors immediately
                if (this.network.ctx) {
                    this.drawNetwork();
                }
            });
        },

        setupNetworkCanvas() {
            const { canvas, dpr } = this.network;
            if (!canvas) return;

            const width = window.innerWidth;
            const height = window.innerHeight;

            canvas.width = Math.floor(width * dpr);
            canvas.height = Math.floor(height * dpr);
            canvas.style.width = `${width}px`;
            canvas.style.height = `${height}px`;

            this.network.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        },

        createNetworkPoints() {
            const width = window.innerWidth;
            const height = window.innerHeight;

            let count = 48;
            if (width < 1200) count = 36;
            if (width < 768) count = 24;

            this.network.points = Array.from({ length: count }, () => ({
                x: Math.random() * width,
                y: Math.random() * height,
                vx: (Math.random() - 0.5) * 0.28,
                vy: (Math.random() - 0.5) * 0.28,
                r: Math.random() * 1.8 + 1.1,
            }));
        },

        bindNetworkEvents() {
            window.addEventListener(
                'mousemove',
                (event) => {
                    this.network.mouse.x = event.clientX;
                    this.network.mouse.y = event.clientY;
                    this.network.mouse.active = true;
                },
                { passive: true }
            );

            window.addEventListener('mouseleave', () => {
                this.network.mouse.x = null;
                this.network.mouse.y = null;
                this.network.mouse.active = false;
            });
        },

        getNetworkColors() {
            const theme = document.documentElement.getAttribute('data-theme') || 'dark';

            if (theme === 'light') {
                return {
                    dot: 'rgba(11, 191, 224, 0.62)',
                    line: 'rgba(47, 104, 232, 0.16)',
                    nearLine: 'rgba(11, 191, 224, 0.22)',
                    glow: 'rgba(11, 191, 224, 0.08)',
                };
            }

            return {
                dot: 'rgba(20, 216, 255, 0.64)',
                line: 'rgba(120, 170, 255, 0.12)',
                nearLine: 'rgba(20, 216, 255, 0.22)',
                glow: 'rgba(20, 216, 255, 0.10)',
            };
        },

        updateNetworkPoints() {
            const width = window.innerWidth;
            const height = window.innerHeight;
            const { points, mouse } = this.network;

            points.forEach((point) => {
                point.x += point.vx;
                point.y += point.vy;

                if (point.x <= 0 || point.x >= width) point.vx *= -1;
                if (point.y <= 0 || point.y >= height) point.vy *= -1;

                point.x = Math.max(0, Math.min(width, point.x));
                point.y = Math.max(0, Math.min(height, point.y));

                if (mouse.active && mouse.x !== null && mouse.y !== null) {
                    const dx = point.x - mouse.x;
                    const dy = point.y - mouse.y;
                    const distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < 120 && distance > 0) {
                        const force = (120 - distance) / 120;
                        point.x += (dx / distance) * force * 0.8;
                        point.y += (dy / distance) * force * 0.8;
                    }
                }
            });
        },

        drawNetwork() {
            const { ctx, points } = this.network;
            if (!ctx) return;

            const width = window.innerWidth;
            const height = window.innerHeight;
            const colors = this.getNetworkColors();

            ctx.clearRect(0, 0, width, height);

            // connection lines
            for (let i = 0; i < points.length; i += 1) {
                for (let j = i + 1; j < points.length; j += 1) {
                    const a = points[i];
                    const b = points[j];

                    const dx = a.x - b.x;
                    const dy = a.y - b.y;
                    const distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < 150) {
                        const opacity = 1 - distance / 150;
                        ctx.beginPath();
                        ctx.moveTo(a.x, a.y);
                        ctx.lineTo(b.x, b.y);
                        ctx.strokeStyle = distance < 90
                            ? colors.nearLine.replace(/[\d.]+\)$/, `${(opacity * 0.9).toFixed(3)})`)
                            : colors.line.replace(/[\d.]+\)$/, `${(opacity * 0.75).toFixed(3)})`);
                        ctx.lineWidth = distance < 90 ? 1.1 : 0.8;
                        ctx.stroke();
                    }
                }
            }

            // points
            points.forEach((point) => {
                ctx.beginPath();
                ctx.arc(point.x, point.y, point.r + 2.4, 0, Math.PI * 2);
                ctx.fillStyle = colors.glow;
                ctx.fill();

                ctx.beginPath();
                ctx.arc(point.x, point.y, point.r, 0, Math.PI * 2);
                ctx.fillStyle = colors.dot;
                ctx.fill();
            });
        },

        animateNetwork() {
            const loop = () => {
                this.updateNetworkPoints();
                this.drawNetwork();
                this.network.animationId = window.requestAnimationFrame(loop);
            };

            loop();
        },

        markReady() {
            this.body.classList.add('app-ready');
        },
    };

    window.VertexGradApp = App;
    document.addEventListener('DOMContentLoaded', () => App.init());
})();