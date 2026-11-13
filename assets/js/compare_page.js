/* BoardMate - compare page: keeps localStorage and the URL in sync */
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('compareRoot');
    const C = window.BMCompare;
    if (!root || !C) return;

    const base = (window.BM && window.BM.base) || '';
    const page = base + '/student/compare.php';
    const params = new URLSearchParams(window.location.search);

    function goWithList() {
        const ids = C.get().map(x => x.id);
        window.location.href = page + (ids.length ? '?ids=' + ids.join(',') : '');
    }

    if (params.has('ids')) {
        // URL is the truth: store what the server actually found (drops deleted boardings)
        C.clear();
        document.querySelectorAll('.ct-head').forEach(h => C.add(h.dataset.id, h.dataset.name));
    } else {
        // No ids in the URL: use the saved list
        const ids = C.get().map(x => x.id);
        if (ids.length) {
            window.location.replace(page + '?ids=' + ids.join(','));
            return;
        }
    }

    document.querySelectorAll('.ct-remove').forEach(btn => {
        btn.addEventListener('click', () => { C.remove(btn.dataset.id); goWithList(); });
    });

    const clearBtn = document.getElementById('clearCompare');
    if (clearBtn) clearBtn.addEventListener('click', () => { C.clear(); window.location.href = page; });
});