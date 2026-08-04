// Local-only UI state: no fetch, order, payment or command side effect.
document.querySelectorAll('[data-project-product-configurator]').forEach((root) => {
    const total = root.querySelector('[data-config-total]');
    if (!(total instanceof HTMLElement)) return;
    const render = () => {
        let value = Number(root.dataset.basePrice || 0);
        root.querySelectorAll('[data-config-option]:checked').forEach((option) => {
            if (option instanceof HTMLInputElement) value += Number(option.value || 0);
        });
        total.textContent = `${new Intl.NumberFormat(document.documentElement.lang || 'ru').format(value)} ${root.dataset.currency || ''}`.trim();
    };
    root.querySelectorAll('[data-config-option]').forEach((option) => option.addEventListener('change', render));
    render();
});
