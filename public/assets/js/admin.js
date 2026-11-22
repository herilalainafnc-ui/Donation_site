(function () {
    'use strict';
    var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };
    var store = {
        get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
        set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
    };

    // Confirmation avant suppression (formulaire entier, ou bouton précis)
    $$('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            if (!window.confirm(form.getAttribute('data-confirm'))) ev.preventDefault();
        });
    });
    $$('[data-confirm-click]').forEach(function (btn) {
        btn.addEventListener('click', function (ev) {
            if (!window.confirm(btn.getAttribute('data-confirm-click'))) { ev.preventDefault(); return; }
            var form = btn.form;
            if (form) form.setAttribute('data-submitting', '1');
        });
    });

    // Aperçu de l'image choisie avant envoi
    $$('input[type=file][data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            var prev = input.parentNode.querySelector('.img-preview');
            if (!input.files || !input.files[0]) { if (prev) prev.remove(); return; }
            if (!prev) {
                prev = document.createElement('img');
                prev.className = 'img-preview';
                prev.alt = '';
                input.insertAdjacentElement('afterend', prev);
            }
            prev.src = URL.createObjectURL(input.files[0]);
        });
    });

    // Zone d'ajout de photos : nom des fichiers choisis, survol pendant un glisser-déposer
    $$('[data-dropzone]').forEach(function (input) {
        var zone = input.closest('.dropzone');
        var list = zone.querySelector('[data-dropzone-files]');
        input.addEventListener('change', function () {
            var n = input.files ? input.files.length : 0;
            list.textContent = n ? n + ' photo(s) prête(s) à être envoyée(s) : ' + Array.prototype.map.call(input.files, function (f) { return f.name; }).join(', ') : '';
        });
        ['dragenter', 'dragover'].forEach(function (t) { zone.addEventListener(t, function () { zone.classList.add('is-over'); }); });
        ['dragleave', 'drop'].forEach(function (t) { zone.addEventListener(t, function () { zone.classList.remove('is-over'); }); });
    });

    // Modifications non enregistrées : avertissement en quittant la page + mention près du bouton
    $$('form[data-dirty-check]').forEach(function (form) {
        var dirty = false;
        var notes = $$('[data-dirty-note]');
        var mark = function () {
            if (dirty) return;
            dirty = true;
            notes.forEach(function (n) { n.hidden = false; });
            document.body.classList.add('is-dirty');
        };
        form.addEventListener('input', mark);
        form.addEventListener('change', mark);
        form.addEventListener('submit', function () { dirty = false; });
        window.addEventListener('beforeunload', function (ev) {
            if (dirty && !form.getAttribute('data-submitting')) { ev.preventDefault(); ev.returnValue = ''; }
        });
    });

    // Onglets Français / English : n'affichent qu'une langue à la fois ; choix mémorisé
    $$('[data-lang-tabs]').forEach(function (bar) {
        var scope = bar.closest('form') || document;
        var buttons = $$('[data-lang]', bar);
        var missing = bar.querySelector('[data-en-missing]');
        var countMissing = function () {
            var n = 0;
            $$('[data-i18n="en"]', scope).forEach(function (wrap) {
                var en = wrap.querySelector('input, textarea');
                var frWrap = scope.querySelector('#' + (wrap.getAttribute('data-i18n-of') || '')) ;
                var fr = frWrap ? frWrap : null;
                if (en && fr && fr.value.trim() !== '' && en.value.trim() === '') n++;
            });
            if (missing) {
                missing.hidden = n === 0;
                missing.textContent = n;
                missing.title = n + ' texte(s) à traduire';
            }
        };
        var show = function (lang) {
            scope.setAttribute('data-show-lang', lang);
            buttons.forEach(function (b) { b.setAttribute('aria-selected', b.getAttribute('data-lang') === lang ? 'true' : 'false'); });
            store.set('uaz-admin-lang', lang);
        };
        buttons.forEach(function (b) { b.addEventListener('click', function () { show(b.getAttribute('data-lang')); }); });
        scope.addEventListener('input', countMissing);
        show(store.get('uaz-admin-lang') === 'en' ? 'en' : 'fr');
        countMissing();
    });

    // Lignes de tableau cliquables (sauf sur les liens et boutons)
    $$('tr[data-href]').forEach(function (tr) {
        tr.addEventListener('click', function (ev) {
            if (ev.target.closest('a, button, form, input, select, label')) return;
            window.location.href = tr.getAttribute('data-href');
        });
    });

    // Menu latéral mobile
    var sidebar = document.getElementById('sidebar');
    var toggle = document.querySelector('[data-sidebar-toggle]');
    var backdrop = document.querySelector('[data-sidebar-close]');
    var setMenu = function (open) {
        if (!sidebar) return;
        sidebar.classList.toggle('open', open);
        if (backdrop) backdrop.hidden = !open;
        if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    if (toggle) toggle.addEventListener('click', function () { setMenu(!sidebar.classList.contains('open')); });
    if (backdrop) backdrop.addEventListener('click', function () { setMenu(false); });
    document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') setMenu(false); });

    // Menu du compte : fermeture en cliquant ailleurs
    var userMenu = document.querySelector('.user-menu');
    document.addEventListener('click', function (ev) {
        if (userMenu && userMenu.open && !userMenu.contains(ev.target)) userMenu.open = false;
    });

    // Notifications : fermeture manuelle, disparition automatique des confirmations
    $$('.toast').forEach(function (t) {
        var close = function () { t.classList.add('is-leaving'); setTimeout(function () { t.remove(); }, 300); };
        t.querySelector('.toast-close').addEventListener('click', close);
        if (t.classList.contains('toast-success')) setTimeout(close, 5000);
    });
})();

// Devise d'un projet : le suffixe des champs de montant suit la devise saisie (sinon la devise générale)
(function () {
    var input = document.getElementById('f_currency');
    var suffixes = document.querySelectorAll('[data-currency-suffix]');
    if (!input || !suffixes.length) return;
    var sync = function () {
        var v = input.value.trim();
        Array.prototype.forEach.call(suffixes, function (s) { s.textContent = v || s.getAttribute('data-default'); });
    };
    input.addEventListener('input', sync);
    sync();
})();
