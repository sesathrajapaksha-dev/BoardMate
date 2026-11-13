/* BoardMate - boarding details photo gallery + lightbox */
document.addEventListener('DOMContentLoaded', () => {
    const gallery = document.getElementById('gallery');
    if (!gallery) return;

    let images = [];
    try { images = JSON.parse(gallery.dataset.images || '[]'); } catch (e) { images = []; }
    if (!images.length) return;

    const main = document.getElementById('galleryMain');
    const counter = document.getElementById('gCount');
    const thumbs = gallery.querySelectorAll('.g-thumb');
    let index = 0;

    function show(i) {
        index = (i + images.length) % images.length;
        main.src = images[index];
        thumbs.forEach((t, n) => t.classList.toggle('active', n === index));
        if (counter && images.length > 1) counter.textContent = (index + 1) + ' / ' + images.length;
        if (lightboxImg) lightboxImg.src = images[index];
    }

    const prev = document.getElementById('gPrev');
    const next = document.getElementById('gNext');
    if (prev) prev.addEventListener('click', e => { e.stopPropagation(); show(index - 1); });
    if (next) next.addEventListener('click', e => { e.stopPropagation(); show(index + 1); });
    thumbs.forEach(t => t.addEventListener('click', () => show(parseInt(t.dataset.index, 10))));

    // ---------- Lightbox ----------
    let box = null, lightboxImg = null;
    function openBox() {
        box = document.createElement('div');
        box.className = 'lightbox';
        box.innerHTML =
            '<button type="button" class="lb-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>' +
            (images.length > 1
                ? '<button type="button" class="lb-nav lb-prev" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>' +
                  '<button type="button" class="lb-nav lb-next" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>'
                : '') +
            '<img alt="Boarding photo">';
        document.body.appendChild(box);
        lightboxImg = box.querySelector('img');
        lightboxImg.src = images[index];
        document.body.style.overflow = 'hidden';

        box.addEventListener('click', e => {
            if (e.target.closest('.lb-prev')) show(index - 1);
            else if (e.target.closest('.lb-next')) show(index + 1);
            else if (e.target.tagName !== 'IMG') closeBox();
        });
    }
    function closeBox() {
        if (!box) return;
        box.remove();
        box = null;
        lightboxImg = null;
        document.body.style.overflow = '';
    }

    main.addEventListener('click', openBox);
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeBox();
        if (e.key === 'ArrowLeft' && images.length > 1) show(index - 1);
        if (e.key === 'ArrowRight' && images.length > 1) show(index + 1);
    });

    show(0);
});