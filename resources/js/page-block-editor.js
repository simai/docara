(() => {
    'use strict';
    const editor = document.querySelector('[data-page-block-editor]');
    if (!editor) return;
    const list = editor.querySelector('[data-block-list]');
    const empty = editor.querySelector('[data-block-empty]');
    const type = editor.querySelector('[data-block-type] sf-dropdown');
    if (!list || !empty) return;

    const instanceId = () => {
        const bytes = new Uint8Array(10);
        crypto.getRandomValues(bytes);
        return 'block_' + Array.from(bytes, value => value.toString(16).padStart(2, '0')).join('');
    };
    const cards = () => Array.from(list.querySelectorAll('[data-block-card]'));
    const renumber = () => {
        cards().forEach((card, index) => {
            card.querySelectorAll('[name]').forEach(control => {
                const name = control.getAttribute('name').replace(/blocks\[[^\]]+\]/, `blocks[${index}]`);
                control.setAttribute('name', name);
            });
            const sort = card.querySelector('[data-block-sort]');
            const position = card.querySelector('[data-block-position]');
            if (sort) sort.value = String((index + 1) * 100);
            if (position) position.textContent = position.dataset.label.replace(':number', String(index + 1));
        });
        empty.hidden = cards().length !== 0;
    };
    const move = (card, direction) => {
        const sibling = direction < 0 ? card.previousElementSibling : card.nextElementSibling;
        if (!sibling) return;
        if (direction < 0) list.insertBefore(card, sibling); else list.insertBefore(sibling, card);
        renumber();
        card.querySelector('sf-button')?.focus();
    };

    const addBlock = () => {
        const dropdownValue = type?.value;
        const selectedType = typeof dropdownValue === 'string'
            ? dropdownValue
            : String(dropdownValue?.value || type?.getAttribute('value') || '');
        const template = selectedType
            ? editor.querySelector(`template[data-block-template="${CSS.escape(selectedType)}"]`)
            : editor.querySelector('template[data-block-template]');
        if (!template) return;
        const index = cards().length;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(index)).replaceAll('__INSTANCE__', instanceId()).replaceAll('__POSITION__', String(index + 1));
        const card = wrapper.firstElementChild;
        if (!card) return;
        list.append(card);
        renumber();
        card.querySelector('sf-input,sf-textarea,sf-dropdown,sf-checkbox')?.focus();
    };
    editor.addEventListener('click', event => {
        if (event.target.closest('[data-add-block]')) addBlock();
    });
    list.addEventListener('click', event => {
        const button = event.target.closest('sf-button');
        const card = event.target.closest('[data-block-card]');
        if (!button || !card) return;
        if (button.closest('[data-remove-block]')) { card.remove(); renumber(); }
        if (button.closest('[data-move-up]')) move(card, -1);
        if (button.closest('[data-move-down]')) move(card, 1);
    });
    renumber();
})();
