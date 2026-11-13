/* BoardMate - add/edit boarding form helpers */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('boardingForm');
    if (!form) return;

    const maxImages = parseInt(form.dataset.maxImages, 10) || 8;
    const maxBytes = parseInt(form.dataset.maxBytes, 10) || 2097152;
    const allowed = ['image/jpeg', 'image/png', 'image/webp'];

    // ---------- Description counter ----------
    const desc = document.getElementById('description');
    const count = document.getElementById('descCount');
    if (desc && count) {
        const update = () => { count.textContent = desc.value.length; };
        desc.addEventListener('input', update);
        update();
    }

    // ---------- Photo preview + client-side checks (server re-checks everything) ----------
    const input = document.getElementById('images');
    const preview = document.getElementById('newPreview');
    const msg = document.getElementById('imgMsg');

    function existingKept() {
        const all = document.querySelectorAll('#existingImages .img-tile').length;
        const removed = document.querySelectorAll('#existingImages input:checked').length;
        return all - removed;
    }

    function checkImages() {
        if (!input) return;
        preview.innerHTML = '';
        msg.textContent = '';
        input.setCustomValidity('');
        const files = Array.from(input.files);
        let problem = '';

        files.forEach(f => {
            if (!allowed.includes(f.type)) problem = '"' + f.name + '" is not a JPG, PNG or WEBP image.';
            else if (f.size > maxBytes) problem = '"' + f.name + '" is larger than ' + (maxBytes / 1048576) + ' MB.';
        });
        if (!problem && existingKept() + files.length > maxImages) {
            problem = 'You can have up to ' + maxImages + ' photos in total.';
        }

        if (problem) {
            msg.textContent = problem;
            input.setCustomValidity(problem);
            return;
        }

        files.forEach(f => {
            const tile = document.createElement('div');
            tile.className = 'img-tile';
            const img = document.createElement('img');
            img.src = URL.createObjectURL(f);
            img.alt = 'New photo preview';
            img.onload = () => URL.revokeObjectURL(img.src);
            const cap = document.createElement('span');
            cap.className = 'img-remove';
            cap.textContent = 'New';
            tile.appendChild(img);
            tile.appendChild(cap);
            preview.appendChild(tile);
        });
    }

    if (input) input.addEventListener('change', checkImages);

    // Highlight photos marked for removal
    document.querySelectorAll('#existingImages input[type=checkbox]').forEach(cb => {
        cb.addEventListener('change', () => {
            cb.closest('.img-tile').classList.toggle('marked', cb.checked);
            checkImages();
        });
    });
});