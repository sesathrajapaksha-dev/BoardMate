/* BoardMate - confirmation before destructive actions */
document.addEventListener('submit', evt => {
    const form = evt.target.closest('form[data-confirm]');
    if (form && !window.confirm(form.dataset.confirm)) {
        evt.preventDefault();
    }
});