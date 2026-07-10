(() => {
    'use strict';
    const editor = document.querySelector('[data-page-block-editor]');
    if (!editor) return;
    const list = editor.querySelector('[data-block-list]');
    const empty = editor.querySelector('[data-block-empty]');
    const type = editor.querySelector('[data-block-type]');
    const add = editor.querySelector('[data-add-block]');
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
                control.name = control.name.replace(/blocks\[[^\]]+\]/, `blocks[${index}]`);
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
        card.querySelector('button')?.focus();
    };

    add?.addEventListener('click', () => {
        const template = editor.querySelector(`template[data-block-template="${CSS.escape(type.value)}"]`);
        if (!template) return;
        const index = cards().length;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(index)).replaceAll('__INSTANCE__', instanceId()).replaceAll('__POSITION__', String(index + 1));
        const card = wrapper.firstElementChild;
        if (!card) return;
        list.append(card);
        renumber();
        card.querySelector('input:not([type=hidden]),textarea,select')?.focus();
    });
    list.addEventListener('click', event => {
        const button = event.target.closest('button');
        const card = event.target.closest('[data-block-card]');
        if (!button || !card) return;
        if (button.matches('[data-remove-block]')) { card.remove(); renumber(); }
        if (button.matches('[data-move-up]')) move(card, -1);
        if (button.matches('[data-move-down]')) move(card, 1);
    });
    renumber();
})();
