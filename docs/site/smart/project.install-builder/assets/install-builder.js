// Project-owned behavior: validates, formats and copies text; it never executes commands.
(() => {
    const osLabels = new Set(['macOS', 'Linux', 'Windows PowerShell']);
    const methodExecutables = new Map([
        ['Composer', 'composer'],
        ['Composer PHAR', 'php composer.phar'],
    ]);
    const packagePattern = /^[a-z0-9_.-]+\/[a-z0-9_.-]+$/;
    const versionPattern = /^[A-Za-z0-9._^~*<>=-]+$/;

    const quote = (value) => `'${String(value).replaceAll("'", "'\"'\"'")}'`;
    const buildCommand = ({os, method, packageName, version, development, preferDist}) => {
        if (!osLabels.has(os) || !methodExecutables.has(method)) {
            return {ok: false, code: 'PROJECT_INSTALL_SELECTION_INVALID', message: 'Выберите поддерживаемые ОС и способ установки.'};
        }
        if (!packagePattern.test(packageName) || !versionPattern.test(version)) {
            return {ok: false, code: 'PROJECT_INSTALL_INPUT_INVALID', message: 'Проверьте package и version: разрешены только безопасные значения Composer.'};
        }
        const args = [methodExecutables.get(method), 'require', quote(`${packageName}:${version}`)];
        if (development) args.push('--dev');
        if (preferDist) args.push('--prefer-dist');
        return {ok: true, code: 'PROJECT_INSTALL_COMMAND_READY', command: `# ${os}\n${args.join(' ')}`};
    };
    const selectedLabel = (root, name, fallback) => {
        const control = root.querySelector(`sf-dropdown[name="${name}"]`);
        return control?.selectedOptions?.[0]?.text || control?.getAttribute('value') || fallback;
    };
    const inputValue = (root, name, fallback) => {
        const control = root.querySelector(`sf-input[name="${name}"]`);
        return String(control?.value ?? control?.querySelector('input')?.value ?? fallback).trim();
    };
    const checked = (root, name) => {
        const control = root.querySelector(`sf-checkbox[name="${name}"]`);
        return Boolean(control?.state?.checked ?? control?.querySelector('input[type="checkbox"]')?.checked ?? control?.hasAttribute('checked'));
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
        const output = root.querySelector('[data-install-command]');
        const copy = root.querySelector('[data-install-copy]');
        const status = root.querySelector('[data-install-status]');
        if (!(output instanceof HTMLElement) || !(copy instanceof HTMLButtonElement) || !(status instanceof HTMLElement)) return;
        root.querySelectorAll('sf-dropdown').forEach(enableDropdownKeyboard);
        const render = () => {
            const result = buildCommand({
                os: selectedLabel(root, 'operating-system', root.dataset.defaultOs || ''),
                method: selectedLabel(root, 'install-method', root.dataset.defaultMethod || ''),
                packageName: inputValue(root, 'package', root.dataset.defaultPackage || ''),
                version: inputValue(root, 'version', root.dataset.defaultVersion || ''),
                development: checked(root, 'development'),
                preferDist: checked(root, 'prefer-dist'),
            });
            root.dataset.state = result.ok ? 'valid' : 'invalid';
            copy.disabled = !result.ok;
            output.textContent = result.ok ? result.command : 'Команда недоступна: исправьте параметры.';
            status.textContent = result.ok
                ? 'Команда только формируется и никогда не выполняется страницей.'
                : result.message;
            return result;
        };
        root.addEventListener('input', render);
        root.addEventListener('change', render);
        root.addEventListener('sf-dropdown:change', render);
        copy.addEventListener('click', async () => {
            const result = render();
            if (!result.ok) return;
            try {
                await navigator.clipboard.writeText(result.command);
                status.textContent = 'Команда скопирована.';
            } catch {
                status.textContent = 'Выделите команду и скопируйте вручную.';
            }
        });
        Promise.all(['sf-input', 'sf-dropdown', 'sf-checkbox'].map((name) => customElements.whenDefined(name))).then(render);
        render();
    };

    globalThis.DocaraProjectDemoContracts = Object.freeze({
        ...(globalThis.DocaraProjectDemoContracts || {}),
        buildInstallCommand: buildCommand,
    });
    if (typeof document !== 'undefined') {
        document.querySelectorAll('[data-project-install-builder]').forEach(bind);
    }
})();
