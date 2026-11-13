/* BoardMate - comparison list (localStorage) + floating tray */
(function () {
    'use strict';

    const KEY = 'boardmate_compare';
    const MAX = 3;
    const base = (window.BM && window.BM.base) || '';

    function read() {
        try {
            const data = JSON.parse(localStorage.getItem(KEY) || '[]');
            return Array.isArray(data) ? data.filter(x => x && x.id).slice(0, MAX) : [];
        } catch (e) { return []; }
    }
    function write(list) {
        try { localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) { /* storage blocked */ }
        document.dispatchEvent(new CustomEvent('bm:compare-changed'));
    }
    function has(id) { return read().some(x => String(x.id) === String(id)); }

    function toast(message) {
        const t = document.createElement('div');
        t.className = 'toast';
        t.textContent = message;
        document.body.appendChild(t);
        requestAnimationFrame(() => t.classList.add('show'));
        setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 2600);
    }

    function add(id, name) {
        const list = read();
        if (list.some(x => String(x.id) === String(id))) return true;
        if (list.length >= MAX) {
            toast('You can compare up to ' + MAX + ' boardings. Remove one first.');
            return false;
        }
        list.push({ id: String(id), name: name || 'Boarding' });
        write(list);
        return true;
    }
    function remove(id) { write(read().filter(x => String(x.id) !== String(id))); }
    function clear() { write([]); }

    // Public API (used by the compare page in Phase 5)
    window.BMCompare = { get: read, add: add, remove: remove, clear: clear, has: has, max: MAX };

    // ---------- Floating tray ----------
    let tray;
    function buildTray() {
        tray = document.createElement('div');
        tray.className = 'compare-tray';
        tray.innerHTML =
            '<div class="tray-inner container">' +
            '  <div class="tray-title"><i class="fa-solid fa-scale-balanced"></i> <span id="trayCount"></span></div>' +
            '  <div class="tray-items" id="trayItems"></div>' +
            '  <div class="tray-actions">' +
            '    <button type="button" class="btn btn-outline btn-sm" id="trayClear">Clear</button>' +
            '    <a class="btn btn-accent btn-sm" id="trayGo" href="' + base + '/student/compare.php">Compare now</a>' +
            '  </div>' +
            '</div>';
        document.body.appendChild(tray);
        tray.querySelector('#trayClear').addEventListener('click', clear);
    }

    function render() {
        const list = read();

        // Card buttons
        document.querySelectorAll('.compare-btn').forEach(btn => {
            const active = list.some(x => String(x.id) === btn.dataset.id);
            btn.classList.toggle('active', active);
            const label = btn.querySelector('span');
            if (label) label.textContent = active ? 'Added' : 'Compare';
            const icon = btn.querySelector('i');
            if (icon) icon.className = active ? 'fa-solid fa-check' : 'fa-solid fa-scale-balanced';
        });

        // Tray
        if (!tray) buildTray();
        tray.classList.toggle('visible', list.length > 0);
        tray.querySelector('#trayCount').textContent = list.length + ' / ' + MAX + ' selected';
        const items = tray.querySelector('#trayItems');
        items.innerHTML = '';
        list.forEach(item => {
            const chip = document.createElement('span');
            chip.className = 'tray-chip';
            const name = document.createElement('span');
            name.textContent = item.name;
            const x = document.createElement('button');
            x.type = 'button';
            x.setAttribute('aria-label', 'Remove ' + item.name);
            x.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            x.addEventListener('click', () => remove(item.id));
            chip.appendChild(name);
            chip.appendChild(x);
            items.appendChild(chip);
        });
        tray.querySelector('#trayGo').classList.toggle('disabled', list.length < 2);
        document.body.classList.toggle('has-tray', list.length > 0);
    }

    document.addEventListener('click', evt => {
        const btn = evt.target.closest('.compare-btn');
        if (!btn) return;
        evt.preventDefault();
        if (has(btn.dataset.id)) {
            remove(btn.dataset.id);
        } else if (add(btn.dataset.id, btn.dataset.name)) {
            toast('Added to compare list');
        }
    });

    document.addEventListener('bm:compare-changed', render);
    window.addEventListener('storage', render); // other tabs
    document.addEventListener('DOMContentLoaded', render);
})();