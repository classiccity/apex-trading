/**
 * Theme front-end helpers + mobile submenu fix.
 */
(function () {

    // ============================================================
    //  FIXED HEADER
    // ============================================================
    const fixedHeaderSelectors = {
        container: '.wp-site-blocks',
        header: '.wp-site-blocks > header',
        main: '.wp-site-blocks > main',
    };

    const adjustMainPaddingForHeader = () => {
        const header = document.querySelector(fixedHeaderSelectors.header);
        const main = document.querySelector(fixedHeaderSelectors.main);

        if (!header || !main) return;

        const headerHeight = header.getBoundingClientRect().height;
        main.style.paddingTop = `${headerHeight}px`;
    };

    const initFixedHeaderSpacing = () => {
        const container = document.querySelector(fixedHeaderSelectors.container);
        if (!container) return;

        adjustMainPaddingForHeader();
        window.addEventListener('resize', adjustMainPaddingForHeader);
        window.addEventListener('orientationchange', adjustMainPaddingForHeader);
    };

    // ============================================================
    // FAQ ACCORDION
    // ============================================================
    const faqSelectors = {
        block: '.wp-block-yoast-faq-block',
        section: '.schema-faq-section',
        question: '.schema-faq-question',
        answer: '.schema-faq-answer',
    };

    const generateId = (prefix) => `${prefix}-${Math.random().toString(36).slice(2, 11)}`;

    const toggleFaqSection = (section, question, answer) => {
        const willOpen = !section.classList.contains('is-open');
        const cleanUpListener = (listener) => {
            answer.removeEventListener('transitionend', listener);
        };

        if (willOpen) {
            section.classList.add('is-open');
            question.setAttribute('aria-expanded', 'true');
            answer.hidden = false;

            const targetHeight = answer.scrollHeight;
            answer.style.maxHeight = '0px';
            answer.style.opacity = '0';

            requestAnimationFrame(() => {
                answer.style.maxHeight = `${targetHeight}px`;
                answer.style.opacity = '1';
            });

            const onOpenTransitionEnd = (event) => {
                if (event.propertyName !== 'max-height') return;

                answer.style.maxHeight = 'none';
                answer.style.removeProperty('opacity');
                cleanUpListener(onOpenTransitionEnd);
            };

            answer.addEventListener('transitionend', onOpenTransitionEnd);

        } else {
            section.classList.remove('is-open');
            question.setAttribute('aria-expanded', 'false');

            const startHeight = answer.scrollHeight;
            answer.style.maxHeight = `${startHeight}px`;
            answer.style.opacity = '1';

            requestAnimationFrame(() => {
                answer.style.maxHeight = '0px';
                answer.style.opacity = '0';
            });

            const onCloseTransitionEnd = (event) => {
                if (event.propertyName !== 'max-height') return;

                answer.hidden = true;
                answer.style.removeProperty('max-height');
                answer.style.removeProperty('opacity');
                cleanUpListener(onCloseTransitionEnd);
            };

            answer.addEventListener('transitionend', onCloseTransitionEnd);
        }
    };

    const initFaqAccordions = () => {
        const faqBlocks = document.querySelectorAll(faqSelectors.block);
        if (!faqBlocks.length) return;

        faqBlocks.forEach((block) => {
            const sections = block.querySelectorAll(faqSelectors.section);

            sections.forEach((section) => {
                const question = section.querySelector(faqSelectors.question);
                const answer = section.querySelector(faqSelectors.answer);
                if (!question || !answer) return;

                const startOpen = section.classList.contains('is-open');
                answer.hidden = !startOpen;

                if (startOpen) {
                    answer.style.maxHeight = 'none';
                    answer.style.opacity = '1';
                } else {
                    answer.style.maxHeight = '0px';
                    answer.style.opacity = '0';
                }

                if (!answer.id) {
                    answer.id = generateId('faq-answer');
                }

                question.setAttribute('role', 'button');
                question.setAttribute('tabindex', '0');
                question.setAttribute('aria-expanded', String(startOpen));
                question.setAttribute('aria-controls', answer.id);

                const handler = () => toggleFaqSection(section, question, answer);

                if (!question.dataset.accordionBound) {
                    question.addEventListener('click', handler);
                    question.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            handler();
                        }
                    });

                    question.dataset.accordionBound = 'true';
                }
            });
        });
    };

    // ============================================================
    // MOBILE SUBMENU FIX
    // ============================================================
    const mobileNav = {
        container: '.wp-block-navigation__responsive-container',
        parent: '.wp-block-navigation-submenu',
        toggle: '.wp-block-navigation-submenu__toggle',
        submenu: '.wp-block-navigation__submenu-container',
    };

    const initMobileSubmenus = () => {
        const menu = document.querySelector(mobileNav.container);
        if (!menu) return;

        const parents = menu.querySelectorAll(mobileNav.parent);

        parents.forEach(parent => {
            const toggle = parent.querySelector(mobileNav.toggle);
            const submenu = parent.querySelector(mobileNav.submenu);

            // Ensure toggle is visible
            if (toggle) {
                toggle.style.display = 'inline-flex';
                toggle.style.visibility = 'visible';
                toggle.style.opacity = '1';
                toggle.style.pointerEvents = 'auto';
                toggle.style.cursor = 'pointer';
            }

            // Force submenu closed on load
            parent.classList.remove('is-menu-open');

            if (submenu) {
                submenu.style.display = 'none';
                submenu.style.maxHeight = '0';
                submenu.style.overflow = 'hidden';
            }

            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        });

        // CLICK HANDLER
        menu.addEventListener('click', (e) => {
            const toggle = e.target.closest(mobileNav.toggle);
            if (!toggle) return;

            e.preventDefault();
            const parent = toggle.closest(mobileNav.parent);
            const submenu = parent.querySelector(mobileNav.submenu);
            const isOpen = parent.classList.contains('is-menu-open');

            if (!submenu) return;

            if (isOpen) {
                parent.classList.remove('is-menu-open');
                submenu.style.display = 'none';
                submenu.style.maxHeight = '0';
                submenu.style.overflow = 'hidden';
                toggle.setAttribute('aria-expanded', 'false');
            } else {
                parent.classList.add('is-menu-open');
                submenu.style.display = 'block';
                submenu.style.maxHeight = '500px';
                submenu.style.overflow = 'visible';
                toggle.setAttribute('aria-expanded', 'true');
            }
        });
    };

    // ============================================================
    // INIT
    // ============================================================
    const init = () => {
        initFixedHeaderSpacing();
        initFaqAccordions();
        initMobileSubmenus();
    };

    if (document.readyState !== 'loading') {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})();
