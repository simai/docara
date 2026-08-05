// Local-only UI state: no fetch, order, payment or command side effect.
document.querySelectorAll('[data-project-product-configurator]').forEach((root) => {
    const total = root.querySelector('[data-config-total]');
    if (!(total instanceof HTMLElement)) return;
    const options = [...root.querySelectorAll('sf-checkbox[name="features"]')];
    const render = () => {
        let value = Number(root.dataset.basePrice || 0);
        options.forEach((option) => {
            if (option instanceof HTMLElement && (option.hasAttribute('checked') || option.getAttribute('aria-checked') === 'true')) {
                value += Number(option.getAttribute('value') || 0);
            }
        });
        total.textContent = `${new Intl.NumberFormat(document.documentElement.lang || 'ru').format(value)} ${root.dataset.currency || ''}`.trim();
    };
    options.forEach((option) => {
        option.addEventListener('change', render);
        option.addEventListener('click', () => queueMicrotask(render));
    });
    render();
});
