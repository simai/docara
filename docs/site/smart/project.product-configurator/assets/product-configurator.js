// Local-only UI state: no fetch, order, payment or command side effect.
(() => {
    const variantKeys = new Map([
        ['Стартовый', 'starter'],
        ['Командный', 'team'],
        ['Бизнес', 'business'],
    ]);
    const calculateConfiguration = ({variant, prices, options}) => {
        const key = variantKeys.get(variant);
        const base = Number(key ? prices[key] : Number.NaN);
        if (!key || !Number.isFinite(base) || base < 0) {
            return {ok: false, code: 'PROJECT_CONFIG_VARIANT_INVALID', message: 'Выберите поддерживаемый вариант продукта.'};
        }
        let total = base;
        const selected = [];
        for (const option of options) {
            const value = Number(option.value);
            if (!Number.isFinite(value) || value < 0) {
                return {ok: false, code: 'PROJECT_CONFIG_OPTION_INVALID', message: 'Параметры конфигурации повреждены.'};
            }
            if (option.checked) {
                total += value;
                selected.push(option.label);
            }
        }
        return {ok: true, code: 'PROJECT_CONFIG_READY', variant, total, selected};
    };
    const enableDropdownKeyboard = (dropdown) => {
        dropdown.addEventListener('keydown', (event) => {
            const trigger = event.target?.closest?.('.sf-dropdown-field');
            if (trigger && ['Enter', ' ', 'ArrowDown'].includes(event.key)) {
                event.preventDefault();
                dropdown.openDropdown?.();
                requestAnimationFrame(() => dropdown.querySelector('.sf-list-item:not(.disabled)')?.focus());
            } else if (event.key === 'Escape') {
                dropdown.closeDropdown?.();
                requestAnimationFrame(() => dropdown.querySelector('.sf-dropdown-field input[readonly]')?.focus());
            }
        });
    };
    const bind = (root) => {
        const total = root.querySelector('[data-config-total]');
        const summary = root.querySelector('[data-config-summary]');
        const dropdown = root.querySelector('sf-dropdown[name="product"]');
        const controls = [...root.querySelectorAll('sf-checkbox[name="features"]')];
        if (!(total instanceof HTMLElement) || !(summary instanceof HTMLElement) || !(dropdown instanceof HTMLElement)) return;
        enableDropdownKeyboard(dropdown);
        let selectedVariant = root.dataset.defaultVariant || '';
        const readChecked = (control) => Boolean(control?.state?.checked ?? control?.querySelector('input[type="checkbox"]')?.checked ?? control?.hasAttribute('checked'));
        const render = () => {
            selectedVariant = dropdown.selectedOptions?.[0]?.text || dropdown.getAttribute('value') || selectedVariant;
            const result = calculateConfiguration({
                variant: selectedVariant,
                prices: {
                    starter: root.dataset.starterPrice,
                    team: root.dataset.teamPrice,
                    business: root.dataset.businessPrice,
                },
                options: controls.map((control) => ({
                    label: control.getAttribute('label') || '',
                    value: control.getAttribute('value') || '',
                    checked: readChecked(control),
                })),
            });
            root.dataset.state = result.ok ? 'valid' : 'invalid';
            if (!result.ok) {
                total.textContent = 'Недоступно';
                summary.textContent = result.message;
                return result;
            }
            total.textContent = `${new Intl.NumberFormat(document.documentElement.lang || 'ru').format(result.total)} ${root.dataset.currency || ''}`.trim();
            summary.textContent = `${result.variant} — ${result.selected.length ? result.selected.join(', ') : 'без дополнительных возможностей'}`;
            return result;
        };
        dropdown.addEventListener('sf-dropdown:change', (event) => {
            selectedVariant = event.detail?.label || event.detail?.value || '';
            queueMicrotask(render);
        });
        root.addEventListener('change', render);
        root.addEventListener('click', () => queueMicrotask(render));
        Promise.all(['sf-dropdown', 'sf-checkbox'].map((name) => customElements.whenDefined(name))).then(render);
        render();
    };

    globalThis.DocaraProjectDemoContracts = Object.freeze({
        ...(globalThis.DocaraProjectDemoContracts || {}),
        calculateProductConfiguration: calculateConfiguration,
    });
    if (typeof document !== 'undefined') {
        document.querySelectorAll('[data-project-product-configurator]').forEach(bind);
    }
})();
