document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-larena-confirm]');
    if (form === null) {
        return;
    }

    if (!window.confirm(form.dataset.larenaConfirm || '')) {
        event.preventDefault();
    }
});
