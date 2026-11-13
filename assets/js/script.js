/* BoardMate - main script */
document.addEventListener('DOMContentLoaded', () => {

    // ---------- Mobile navigation ----------
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            const open = navMenu.classList.toggle('open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            navToggle.innerHTML = open ? '<i class="fa-solid fa-xmark"></i>' : '<i class="fa-solid fa-bars"></i>';
        });
    }

    // ---------- Flash messages: close button + auto-hide for success ----------
    document.querySelectorAll('.alert').forEach(alertBox => {
        const close = alertBox.querySelector('.alert-close');
        if (close) close.addEventListener('click', () => alertBox.remove());
        if (alertBox.classList.contains('alert-success')) {
            setTimeout(() => alertBox.remove(), 6000);
        }
    });

    // ---------- Show / hide password ----------
    document.querySelectorAll('[data-toggle-password]').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.togglePassword);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.innerHTML = show ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
        });
    });

    // ---------- Registration form: strength meter + confirm check ----------
    const pw = document.getElementById('password');
    const confirmPw = document.getElementById('confirm_password');
    const bar = document.getElementById('pwBar');
    const pwText = document.getElementById('pwText');
    const confirmMsg = document.getElementById('confirmMsg');

    if (pw && bar && pwText) {
        pw.addEventListener('input', () => {
            const v = pw.value;
            let score = 0;
            if (v.length >= 8) score++;
            if (/[A-Za-z]/.test(v) && /\d/.test(v)) score++;
            if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v) || v.length >= 12) score++;

            const levels = [
                { w: '8%',   c: '#DC2626', t: 'Too weak' },
                { w: '30%',  c: '#DC2626', t: 'Weak' },
                { w: '55%',  c: '#D97706', t: 'Fair' },
                { w: '80%',  c: '#16A34A', t: 'Good' },
                { w: '100%', c: '#16A34A', t: 'Strong' }
            ];
            const lvl = v.length ? levels[score] : { w: '0', c: '#DC2626', t: 'Use letters and numbers.' };
            bar.style.width = lvl.w;
            bar.style.background = lvl.c;
            pwText.textContent = lvl.t;
            checkConfirm();
        });
    }

    function checkConfirm() {
        if (!confirmPw || !confirmMsg) return;
        if (!confirmPw.value) {
            confirmMsg.textContent = '';
            confirmMsg.className = 'hint';
            confirmPw.setCustomValidity('');
            return;
        }
        if (confirmPw.value === pw.value) {
            confirmMsg.textContent = 'Passwords match';
            confirmMsg.className = 'hint ok';
            confirmPw.setCustomValidity('');
        } else {
            confirmMsg.textContent = 'Passwords do not match';
            confirmMsg.className = 'hint bad';
            confirmPw.setCustomValidity('Passwords do not match');
        }
    }
    if (confirmPw) confirmPw.addEventListener('input', checkConfirm);

    // ---------- Use the browser's built-in validation bubbles on forms ----------
    // (the forms use novalidate so we trigger it ourselves; the server still re-validates everything)
    document.querySelectorAll('form[novalidate]').forEach(form => {
        form.addEventListener('submit', evt => {
            if (!form.checkValidity()) {
                evt.preventDefault();
                form.reportValidity();
            }
        });
    });
});
