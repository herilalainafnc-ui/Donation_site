(function () {
    'use strict';

    // Confirmation avant suppression
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            if (!window.confirm(form.getAttribute('data-confirm'))) ev.preventDefault();
        });
    });

    // Aperçu de l'image choisie avant envoi
    document.querySelectorAll('input[type=file][data-preview]').forEach(function (input) {
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

    // Prévenir la perte de modifications non enregistrées
    document.querySelectorAll('form[data-dirty-check]').forEach(function (form) {
        var dirty = false;
        form.addEventListener('input', function () { dirty = true; });
        form.addEventListener('change', function () { dirty = true; });
        form.addEventListener('submit', function () { dirty = false; });
        window.addEventListener('beforeunload', function (ev) {
            if (dirty) { ev.preventDefault(); ev.returnValue = ''; }
        });
    });

    // Fermer le menu latéral mobile en cliquant à côté
    var sidebar = document.getElementById('sidebar');
    document.addEventListener('click', function (ev) {
        if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(ev.target) && !ev.target.closest('.sb-toggle')) {
            sidebar.classList.remove('open');
        }
    });
})();
