/* BoardMate - browse page helpers */
document.addEventListener('DOMContentLoaded', () => {
    // Mobile: show/hide the filter panel
    const toggle = document.getElementById('filterToggle');
    const panel = document.getElementById('filters');
    if (toggle && panel) {
        toggle.addEventListener('click', () => {
            const open = panel.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // Changing the sort order reloads the results straight away
    document.querySelectorAll('[data-autosubmit]').forEach(el => {
        el.addEventListener('change', () => el.form.submit());
    });
});