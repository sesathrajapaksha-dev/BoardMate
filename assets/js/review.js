/* BoardMate - review form helpers */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('reviewForm');
    if (!form) return;

    // Star label text
    const names = { 1: 'Poor', 2: 'Fair', 3: 'Good', 4: 'Very good', 5: 'Excellent' };
    const text = document.getElementById('ratingText');
    const radios = form.querySelectorAll('input[name="rating"]');
    function showRating() {
        const sel = form.querySelector('input[name="rating"]:checked');
        text.textContent = sel ? sel.value + ' / 5 - ' + names[sel.value] : 'Select a rating';
    }
    radios.forEach(r => r.addEventListener('change', showRating));
    showRating();

    // Character counter
    const area = document.getElementById('review_text');
    const count = document.getElementById('reviewCount');
    if (area && count) {
        const update = () => { count.textContent = area.value.length; };
        area.addEventListener('input', update);
        update();
    }
});