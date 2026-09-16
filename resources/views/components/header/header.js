import { initBannerSlider } from "../swiper/swiper";

const classPrefix = 'header';
const dataPrefix = 'data-header';
const $container = document.querySelector(`[${dataPrefix}]`);

if ($container) {
    header();
}

export function header() {
    const $mobileMenuToggle = $container.querySelector(`[${dataPrefix}-menu-open]`);
    const menuList = $container.querySelector(`.${classPrefix}__menu-list`);
    const menuItems = Array.from($container.querySelectorAll(`.${classPrefix}__menu-list-item`));
    const closeBtns = Array.from($container.querySelectorAll(`[${dataPrefix}-body-menu-close]`));
    const anchorLinks = $container.querySelectorAll(`a[href^="#"]`);

    const threshold = $container.offsetHeight;
    let isScrollingFromClick = false;

    let isTicking = false;
    let lastScrollTop = window.pageYOffset || document.documentElement.scrollTop;

    init();

    function init() {
        window.addEventListener('scroll', handleHeader);

        if ($mobileMenuToggle) {
            $mobileMenuToggle.addEventListener('click', toggleMobileMenu);
        }

        anchorLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const targetId = link.getAttribute('href');
                if (targetId !== '#' && targetId.length > 1) {
                    isScrollingFromClick = true;
                    closeMobileMenu();

                    setTimeout(() => {
                        isScrollingFromClick = false;
                    }, 800);
                }
            });
        });
    }

    function toggleMobileMenu() {
        if ($container.classList.contains(`${classPrefix}--open`)) {
            closeMobileMenu();
        } else {
            openMobileMenu();
        }
    }

    function openMobileMenu() {
        $container.classList.add(`${classPrefix}--open`);
        document.documentElement.classList.add('no-scroll');
    }

    function closeMobileMenu() {
        $container.classList.remove(`${classPrefix}--open`);
        document.documentElement.classList.remove('no-scroll');
        closeAllSubmenus();
    }

    function closeAllSubmenus() {
        menuItems.forEach(item => {
            item.querySelector(`.${classPrefix}__menu-list-item-body--active`)?.classList.remove(`${classPrefix}__menu-list-item-body--active`);
            item.querySelector(`.${classPrefix}__menu-list-item-title--active`)?.classList.remove(`${classPrefix}__menu-list-item-title--active`);
        });
    }

    menuItems.forEach(item => {
        const title = item.querySelector(`.${classPrefix}__menu-list-item-title`);
        const submenu = item.querySelector(`.${classPrefix}__menu-list-item-body`);
        if (!title || !submenu) return;

        title.addEventListener('click', e => {
            e.stopPropagation();
            const isActive = submenu.classList.contains(`${classPrefix}__menu-list-item-body--active`);
            closeAllSubmenus();

            if (!isActive) {
                title.classList.add(`${classPrefix}__menu-list-item-title--active`);
                submenu.classList.add(`${classPrefix}__menu-list-item-body--active`);
            }
        });
    });

    closeBtns.forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            closeAllSubmenus();
        });
    });

    document.addEventListener('click', e => {
        if (!$container.contains(e.target)) {
            closeAllSubmenus();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 767 && $container.classList.contains(`${classPrefix}--open`)) {
            closeMobileMenu();
        }
    });

    function handleHeader() {
        // Получаем текущий скролл, игнорируя отрицательные значения iOS (Math.max)
        const scrollTop = Math.max(0, window.pageYOffset || document.documentElement.scrollTop);

        if (!isTicking) {
            window.requestAnimationFrame(() => {
                // Если мобильное меню открыто, блокируем скрытие хедера
                if ($container.classList.contains(`${classPrefix}--open`)) {
                    isTicking = false;
                    return;
                }

                // Логика состояния "scrolled" (фон и тень)
                if (scrollTop > 10) {
                    $container.classList.add(`${classPrefix}--scrolled`);
                } else {
                    $container.classList.remove(`${classPrefix}--scrolled`);
                }

                // Логика скрытия хедера при скролле вниз
                if (scrollTop > threshold && scrollTop > lastScrollTop) {
                    $container.classList.add(`${classPrefix}--hidden`);
                } else if (scrollTop < lastScrollTop || scrollTop <= threshold) {
                    $container.classList.remove(`${classPrefix}--hidden`);
                }

                lastScrollTop = scrollTop;
                isTicking = false;
            });
            isTicking = true;
        }
    }
}
