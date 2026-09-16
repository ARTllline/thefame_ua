const dataPrefix = 'data-region-selector';
const classPrefix = 'region-selector';
const $container = document.querySelector(`[${dataPrefix}]`);

if ($container) {
    regionSelector();
}

export function regionSelector() {
    const $openButtons = document.querySelectorAll('[data-region-open]');


    $openButtons.forEach((button) => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            openModal();
        });
    });

    // Опционально: закрытие при клике вне контента (на подложку)
    $container.addEventListener('click', (e) => {
        if (e.target === $container) {
            closeModal();
        }
    });

    function openModal() {
        $container.classList.add(`${classPrefix}--active`);
    }

    function closeModal() {
        $container.classList.remove(`${classPrefix}--active`);
    }
}
