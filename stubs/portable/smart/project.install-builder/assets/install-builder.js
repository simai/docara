// Project-owned behavior: formats and copies text; it never executes commands.
document.querySelectorAll('[data-project-install-builder]').forEach((root) => {
    const mode = root.querySelector('[data-install-mode]');
    const output = root.querySelector('[data-install-command]');
    const copy = root.querySelector('[data-install-copy]');
    const status = root.querySelector('[data-install-status]');
    if (!(mode instanceof HTMLSelectElement) || !(output instanceof HTMLElement) || !(copy instanceof HTMLButtonElement) || !(status instanceof HTMLElement)) return;
    const render = () => {
        const suffix = mode.value === 'development' ? ' --dev' : '';
        output.textContent = `composer require ${root.dataset.package}:${root.dataset.version}${suffix}`;
    };
    mode.addEventListener('change', render);
    copy.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(output.textContent || '');
            status.textContent = 'Команда скопирована.';
        } catch {
            status.textContent = 'Выделите команду и скопируйте вручную.';
        }
    });
    render();
});
