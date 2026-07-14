(() => {
    const explorer = document.querySelector('[data-larena-framework-explorer], [data-larena-utility-explorer]');
    if (!explorer) return;

    const controls = explorer.querySelector('[data-framework-catalog-controls]');
    const search = explorer.querySelector('[data-framework-catalog-search]');
    const kind = explorer.querySelector('[data-framework-catalog-kind]');
    const reset = explorer.querySelector('[data-framework-catalog-reset]');
    const results = explorer.querySelector('[data-framework-catalog-results]');
    const empty = explorer.querySelector('[data-framework-catalog-empty]');
    const entries = [...explorer.querySelectorAll('[data-framework-catalog-entry]')];
    if (!controls || !search || !kind || !reset || !results || !empty) return;

    controls.hidden = false;
    const update = () => {
        const query = search.value.trim().toLocaleLowerCase();
        let count = 0;
        entries.forEach((entry) => {
            const visible = (!query || entry.dataset.frameworkSearch.includes(query))
                && (!kind.value || entry.dataset.frameworkKind === kind.value);
            entry.hidden = !visible;
            if (visible) count += 1;
        });
        results.textContent = (results.dataset.frameworkResultsTemplate || ':count').replace(':count', String(count));
        empty.hidden = count !== 0;
    };
    search.addEventListener('input', update);
    kind.addEventListener('change', update);
    reset.addEventListener('click', () => {
        search.value = '';
        kind.value = '';
        update();
        search.focus();
    });

    explorer.querySelectorAll('[data-framework-utility-demo]').forEach((demo) => {
        const select = demo.querySelector('[data-framework-utility-demo-select]');
        const preview = demo.querySelector('[data-framework-utility-demo-preview]');
        const code = demo.querySelector('[data-framework-utility-demo-code]');
        const baseClasses = demo.dataset.frameworkBaseClasses;
        if (!select || !preview || !code || !baseClasses) return;

        select.addEventListener('change', () => {
            const classes = `${baseClasses} ${select.value}`;
            preview.className = `larena-framework-utility-demo__preview ${classes}`;
            code.textContent = `<div class="${classes}">…</div>`;
        });
    });
})();
