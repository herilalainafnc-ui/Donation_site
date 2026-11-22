/* UAZ — interactions et animations
   GSAP + ScrollTrigger + Lenis (défilement fluide). Sans eux, le site reste entièrement utilisable. */
(function () {
    'use strict';

    var root = document.documentElement;
    var body = document.body;
    var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
    var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };
    var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    var hasGsap = !!(window.gsap && window.ScrollTrigger);
    var animate = root.classList.contains('anim') && hasGsap;

    if (!animate) root.classList.remove('anim');
    root.classList.add('anim-ready');

    /* ------------------------------------------------------------------
       Découpage des textes
       ------------------------------------------------------------------ */
    // Titres : chaque mot dans un masque (.w > span) pour le faire « monter ».
    function splitWords(el) {
        if (el.classList.contains('is-split')) return;
        var walk = function (node) {
            Array.prototype.slice.call(node.childNodes).forEach(function (child) {
                if (child.nodeType === 3) {
                    var parts = child.textContent.split(/(\s+)/);
                    var frag = document.createDocumentFragment();
                    parts.forEach(function (part) {
                        if (part === '') return;
                        if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(' ')); return; }
                        var w = document.createElement('span');
                        w.className = 'w';
                        var inner = document.createElement('span');
                        inner.textContent = part;
                        w.appendChild(inner);
                        frag.appendChild(w);
                    });
                    node.replaceChild(frag, child);
                } else if (child.nodeType === 1 && !child.classList.contains('w')) {
                    walk(child);
                }
            });
        };
        walk(el);
        el.classList.add('is-split');
    }
    // Paragraphes : chaque mot dans un span (.wd) pour l'illuminer progressivement.
    function splitPlainWords(el) {
        var words = el.textContent.trim().split(/\s+/);
        el.innerHTML = words.map(function (w) { return '<span class="wd">' + w.replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</span>'; }).join(' ');
    }
    $$('[data-split]').forEach(splitWords);
    $$('[data-words], [data-words-scrub]').forEach(splitPlainWords);

    /* ------------------------------------------------------------------
       Défilement fluide (Lenis)
       ------------------------------------------------------------------ */
    var lenis = null;
    if (animate && window.Lenis) {
        lenis = new window.Lenis({ lerp: 0.09, wheelMultiplier: 1, smoothWheel: true });
        lenis.on('scroll', window.ScrollTrigger.update);
        gsap.ticker.add(function (t) { lenis.raf(t * 1000); });
        gsap.ticker.lagSmoothing(0);
    }
    function scrollToTarget(target) {
        if (lenis) lenis.scrollTo(target, { offset: -90, duration: 1.4 });
        else if (typeof target === 'number') window.scrollTo({ top: target, behavior: 'smooth' });
        else target.scrollIntoView({ behavior: 'smooth' });
    }
    $$('a[href^="#"]').forEach(function (a) {
        a.addEventListener('click', function (ev) {
            var id = a.getAttribute('href');
            if (id === '#top') { ev.preventDefault(); scrollToTarget(0); return; }
            var target = id.length > 1 && document.getElementById(id.slice(1));
            if (target) { ev.preventDefault(); scrollToTarget(target); }
        });
    });

    /* ------------------------------------------------------------------
       En-tête : fond au défilement, masqué en descendant, clair/sombre selon la section
       ------------------------------------------------------------------ */
    var header = $('.site-header');
    var progress = $('.scroll-progress');
    var toTop = $('.to-top');
    var darkZones = $$('[data-header="dark"]');
    // Masquage avec seuils : on ne réagit qu'à un vrai changement de sens (pas aux micro-mouvements
    // du défilement fluide), et jamais dans la première moitié de l'écran.
    var anchorY = window.scrollY;
    var lastY = anchorY;
    var goingDown = false;
    function onScroll() {
        var y = window.scrollY;
        var max = root.scrollHeight - window.innerHeight;
        header.classList.toggle('is-scrolled', y > 40);
        var down = y > lastY;
        if (down !== goingDown) { goingDown = down; anchorY = lastY; }
        if (!body.classList.contains('nav-open')) {
            if (y < window.innerHeight * .5) header.classList.remove('is-hidden');
            else if (goingDown && y - anchorY > 24) header.classList.add('is-hidden');
            else if (!goingDown && anchorY - y > 12) header.classList.remove('is-hidden');
        }
        lastY = y;
        var probe = 36;
        header.classList.toggle('on-dark', darkZones.some(function (z) {
            var r = z.getBoundingClientRect();
            return r.top <= probe && r.bottom >= probe;
        }));
        if (progress) progress.style.setProperty('--progress', max > 0 ? (y / max).toFixed(4) : 0);
        if (toTop) toTop.classList.toggle('show', y > 900);
    }
    if (lenis) lenis.on('scroll', onScroll); else window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    /* ------------------------------------------------------------------
       Menu mobile plein écran
       ------------------------------------------------------------------ */
    var toggle = $('.nav-toggle');
    function setMenu(open) {
        body.classList.toggle('nav-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', toggle.getAttribute(open ? 'data-label-close' : 'data-label-open'));
        header.classList.remove('is-hidden');
        if (lenis) { if (open) lenis.stop(); else lenis.start(); }
        if (open && animate) gsap.fromTo('.main-nav li, .nav-cta', { y: 60, opacity: 0 }, { y: 0, opacity: 1, duration: .9, stagger: .06, ease: 'expo.out', delay: .25 });
    }
    if (toggle) {
        toggle.addEventListener('click', function () { setMenu(!body.classList.contains('nav-open')); });
        document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && body.classList.contains('nav-open')) setMenu(false); });
    }

    // Sous-menu « À propos » : survol sur ordinateur, clic (écrans tactiles) et clavier
    $$('.has-sub').forEach(function (item) {
        var btn = item.querySelector('.sub-toggle');
        var set = function (open) {
            item.classList.toggle('open', open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        btn.addEventListener('click', function (ev) { ev.stopPropagation(); set(!item.classList.contains('open')); });
        document.addEventListener('click', function (ev) { if (!item.contains(ev.target)) set(false); });
        document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') set(false); });
    });

    // Menu des langues de l'en-tête : clic, Échap, clic à l'extérieur, flèches au clavier
    $$('.lang-menu').forEach(function (menu) {
        var btn = menu.querySelector('.lang-toggle');
        var links = $$('.lang-list a', menu);
        var set = function (open) {
            menu.classList.toggle('open', open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        btn.addEventListener('click', function (ev) {
            ev.stopPropagation();
            var open = !menu.classList.contains('open');
            set(open);
            if (open && links.length) (menu.querySelector('.lang-list a.is-active') || links[0]).focus();
        });
        document.addEventListener('click', function (ev) { if (!menu.contains(ev.target)) set(false); });
        menu.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') { set(false); btn.focus(); return; }
            var i = links.indexOf(document.activeElement);
            if (i < 0 || (ev.key !== 'ArrowDown' && ev.key !== 'ArrowUp')) return;
            ev.preventDefault();
            links[(i + (ev.key === 'ArrowDown' ? 1 : links.length - 1)) % links.length].focus();
        });
    });

    /* ------------------------------------------------------------------
       Transitions entre pages (rideau)
       ------------------------------------------------------------------ */
    var curtain = $('.curtain');
    if (animate && curtain) {
        // GSAP prend la main sur la position du rideau (couvrant si l'intro est déjà passée).
        gsap.set(curtain, { y: 0, yPercent: root.classList.contains('no-intro') ? 0 : 100 });
        $$('a[href]').forEach(function (a) {
            a.addEventListener('click', function (ev) {
                var href = a.getAttribute('href');
                if (ev.defaultPrevented || !href || href.charAt(0) === '#' || a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-lightbox')) return;
                if (ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey || ev.button !== 0) return;
                var url = new URL(a.href, location.href);
                if (url.origin !== location.origin || /\/admin(\/|$)/.test(url.pathname)) return;
                if (url.pathname === location.pathname && url.search === location.search && url.hash) return;
                ev.preventDefault();
                if (body.classList.contains('nav-open')) setMenu(false);
                gsap.fromTo(curtain, { yPercent: 100 }, { yPercent: 0, duration: .65, ease: 'expo.inOut', onComplete: function () { location.href = url.href; } });
            });
        });
        window.addEventListener('pageshow', function (ev) { if (ev.persisted) gsap.set(curtain, { yPercent: 100 }); });
    }

    /* ------------------------------------------------------------------
       Animations GSAP
       ------------------------------------------------------------------ */
    if (animate) {
        gsap.registerPlugin(ScrollTrigger);
        var ease = 'expo.out';

        // --- Intro (première visite de la session) puis entrée de la bannière
        var intro = $('.intro');
        var tl = gsap.timeline();
        if (!root.classList.contains('no-intro') && intro) {
            tl.from('.intro img', { scale: .7, opacity: 0, duration: .9, ease: ease })
              .to('.intro-bar span', { scaleX: 1, duration: .8, ease: 'power2.inOut' }, '-=.5')
              .to(intro, { yPercent: -100, duration: 1, ease: 'expo.inOut' })
              .set(intro, { display: 'none' });
            try { sessionStorage.setItem('uaz-intro', '1'); } catch (e) { /* stockage indisponible */ }
        } else if (curtain) {
            tl.to(curtain, { yPercent: -100, duration: .9, ease: 'expo.inOut' }).set(curtain, { yPercent: 100 });
        }

        var heroText = $('[data-hero-text]');
        if (heroText) {
            var start = tl.duration() - .45;
            tl.fromTo($$('.eyebrow, .back-link', heroText), { opacity: 0, y: 20 }, { opacity: 1, y: 0, duration: 1, ease: ease }, start)
              .from($$('[data-split] .w > span', heroText), { yPercent: 115, rotate: 4, duration: 1.3, stagger: .055, ease: ease }, start + .05)
              .fromTo($$('[data-fade]', heroText.parentNode), { opacity: 0, y: 40 }, { opacity: 1, y: 0, duration: 1.2, stagger: .12, ease: ease }, start + .45);
            var visual = $('.hero-visual');
            if (visual) tl.fromTo(visual, { opacity: 0, scale: .85, rotate: -8 }, { opacity: 1, scale: 1, rotate: 0, duration: 1.8, ease: ease }, start + .1);
        }

        // --- Bannière : parallaxe au défilement + suivi de la souris
        var hero = $('.hero, .page-hero');
        if (hero) {
            gsap.to(hero.querySelector('[data-hero-text]'), { yPercent: -18, opacity: .25, ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } });
            gsap.to(hero.querySelectorAll('.aurora, .hero-seal'), { yPercent: 25, ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } });
        }

        // --- Titres de section : mots qui montent à l'apparition
        $$('[data-split]').forEach(function (el) {
            if (el.closest('[data-hero-text]')) return;
            gsap.from(el.querySelectorAll('.w > span'), { yPercent: 115, rotate: 3, duration: 1.2, stagger: .05, ease: ease, scrollTrigger: { trigger: el, start: 'top 85%' } });
        });
        $$('[data-fade], .eyebrow').forEach(function (el) {
            if (el.closest('[data-hero-text]') || (heroText && heroText.parentNode.contains(el))) return;
            gsap.fromTo(el, { opacity: 0, y: 30 }, { opacity: 1, y: 0, duration: 1.2, ease: ease, scrollTrigger: { trigger: el, start: 'top 88%' } });
        });

        // --- Blocs : apparition en cascade
        gsap.set('.reveal', { y: 60 });
        ScrollTrigger.batch('.reveal', {
            start: 'top 90%',
            onEnter: function (batch) { gsap.to(batch, { opacity: 1, y: 0, duration: 1.2, stagger: .12, ease: ease, overwrite: true }); }
        });

        // --- Images : dévoilement par le bas + léger dézoom
        $$('.img-reveal, .split-media').forEach(function (box) {
            var media = box.querySelector('img, .media-placeholder');
            var t = gsap.timeline({ scrollTrigger: { trigger: box, start: 'top 82%' } });
            t.fromTo(box, { clipPath: 'inset(100% 0% 0% 0% round 32px)' }, { clipPath: 'inset(0% 0% 0% 0% round 32px)', duration: 1.6, ease: 'expo.inOut' });
            if (media) t.from(media, { scale: 1.35, duration: 2, ease: ease }, 0);
        });

        // --- Paragraphe d'introduction : mots qui apparaissent
        $$('[data-words]').forEach(function (el) {
            gsap.from(el.querySelectorAll('.wd'), { opacity: .12, duration: .6, stagger: .02, ease: 'none', scrollTrigger: { trigger: el, start: 'top 80%', end: 'bottom 55%', scrub: 1 } });
        });
        // --- Verset : s'illumine mot par mot au défilement
        $$('[data-words-scrub]').forEach(function (el) {
            gsap.to(el.querySelectorAll('.wd'), { opacity: 1, stagger: .1, ease: 'none', scrollTrigger: { trigger: el, start: 'top 80%', end: 'bottom 45%', scrub: 1 } });
        });

        // --- Compteurs
        $$('[data-count]').forEach(function (el) {
            var target = parseInt(el.getAttribute('data-count'), 10) || 0;
            var obj = { v: 0 };
            el.textContent = '0';
            gsap.to(obj, { v: target, duration: 2.2, ease: 'power3.out', scrollTrigger: { trigger: el, start: 'top 90%' }, onUpdate: function () { el.textContent = Math.round(obj.v).toLocaleString(root.lang || 'en'); } });
        });

        // --- Anneau de progression du tableau de bord d'un projet
        $$('.ring-fill').forEach(function (ring) {
            gsap.fromTo(ring, { attr: { 'stroke-dashoffset': ring.getAttribute('data-circ') } }, {
                attr: { 'stroke-dashoffset': ring.getAttribute('stroke-dashoffset') },
                duration: 2.2, ease: 'power3.out', scrollTrigger: { trigger: ring, start: 'top 90%' }
            });
        });

        // --- Barres de financement
        $$('.progress > span').forEach(function (bar) {
            gsap.from(bar, { width: 0, duration: 2, ease: 'power3.out', scrollTrigger: { trigger: bar, start: 'top 95%' } });
        });

        // --- Étapes : ligne de progression + étape active
        $$('[data-steps]').forEach(function (list) {
            var line = list.querySelector('.steps-line');
            ScrollTrigger.create({ trigger: list, start: 'top 65%', end: 'bottom 65%', scrub: true, onUpdate: function (self) { line.style.setProperty('--steps', self.progress.toFixed(3)); } });
            $$('.step', list).forEach(function (step) {
                ScrollTrigger.create({ trigger: step, start: 'top 68%', end: 'bottom 40%', toggleClass: 'is-active' });
            });
        });

        // --- Appel au don : le cercle s'ouvre au défilement
        $$('[data-cta]').forEach(function (cta) {
            var circle = cta.querySelector('.cta-circle');
            gsap.fromTo(circle, { '--r': '12%' }, { '--r': '150%', ease: 'none', scrollTrigger: { trigger: cta, start: 'top 90%', end: 'top 10%', scrub: true } });
            gsap.fromTo(cta.querySelector('.cta-seal'), { '--rot': '-40deg' }, { '--rot': '40deg', ease: 'none', scrollTrigger: { trigger: cta, start: 'top bottom', end: 'bottom top', scrub: true } });
        });

        // --- Boutons magnétiques
        if (finePointer) {
            $$('.magnetic').forEach(function (el) {
                var xTo = gsap.quickTo(el, 'x', { duration: .6, ease: 'power3' });
                var yTo = gsap.quickTo(el, 'y', { duration: .6, ease: 'power3' });
                el.addEventListener('mousemove', function (ev) {
                    var r = el.getBoundingClientRect();
                    xTo((ev.clientX - r.left - r.width / 2) * .3);
                    yTo((ev.clientY - r.top - r.height / 2) * .4);
                });
                el.addEventListener('mouseleave', function () { gsap.to(el, { x: 0, y: 0, duration: 1, ease: 'elastic.out(1, .4)' }); });
            });

            // --- Cartes inclinables avec reflet
            $$('.tilt').forEach(function (card) {
                var glare = document.createElement('span');
                glare.className = 'tilt-glare';
                card.appendChild(glare);
                card.addEventListener('mousemove', function (ev) {
                    var r = card.getBoundingClientRect();
                    var px = (ev.clientX - r.left) / r.width;
                    var py = (ev.clientY - r.top) / r.height;
                    gsap.to(card, { rotateY: (px - .5) * 8, rotateX: (.5 - py) * 8, y: -8, transformPerspective: 1000, duration: .6, ease: 'power3.out' });
                    card.style.setProperty('--gx', (px * 100) + '%');
                    card.style.setProperty('--gy', (py * 100) + '%');
                });
                card.addEventListener('mouseleave', function () { gsap.to(card, { rotateY: 0, rotateX: 0, y: 0, duration: 1, ease: 'elastic.out(1, .5)' }); });
            });
        }

        window.addEventListener('load', function () { ScrollTrigger.refresh(); });
    }

    /* ------------------------------------------------------------------
       Carrousel des témoignages
       ------------------------------------------------------------------ */
    $$('[data-slider]').forEach(function (slider) {
        var slides = $$('.t-slide', slider);
        var dots = $$('.t-dot', slider);
        if (slides.length < 2) return;
        var index = 0;
        var delay = 7000;
        var timer = null;
        slider.style.setProperty('--t-delay', delay + 'ms');
        function show(i) {
            index = (i + slides.length) % slides.length;
            slides.forEach(function (s, k) { s.classList.toggle('is-active', k === index); });
            dots.forEach(function (d, k) {
                d.classList.remove('is-active');
                if (k === index) { void d.offsetWidth; d.classList.add('is-active'); }
            });
            if (animate) gsap.fromTo(slides[index].querySelector('blockquote'), { y: 40, opacity: 0 }, { y: 0, opacity: 1, duration: 1.2, ease: 'expo.out' });
            restart();
        }
        function restart() {
            clearTimeout(timer);
            if (!slider.classList.contains('is-paused')) timer = setTimeout(function () { show(index + 1); }, delay);
        }
        slider.querySelector('[data-next]').addEventListener('click', function () { show(index + 1); });
        slider.querySelector('[data-prev]').addEventListener('click', function () { show(index - 1); });
        dots.forEach(function (d, k) { d.addEventListener('click', function () { show(k); }); });
        slider.addEventListener('mouseenter', function () { slider.classList.add('is-paused'); clearTimeout(timer); });
        slider.addEventListener('mouseleave', function () { slider.classList.remove('is-paused'); restart(); });
        restart();
    });

    /* ------------------------------------------------------------------
       Boutons « copier » (coordonnées bancaires, Mobile Money)
       ------------------------------------------------------------------ */
    $$('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var text = btn.getAttribute('data-copy');
            var done = function () {
                btn.classList.add('copied');
                setTimeout(function () { btn.classList.remove('copied'); }, 1500);
            };
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(done);
            } else {
                var ta = document.createElement('textarea');
                ta.value = text;
                body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                ta.remove();
                done();
            }
        });
    });

    /* ------------------------------------------------------------------
       Visionneuse de la galerie
       ------------------------------------------------------------------ */
    var lb = $('.lightbox');
    if (lb) {
        // Visionneuse : photo plein écran, navigation (flèches, clavier, balayage), compteur, fermeture claire
        document.body.appendChild(lb); // hors de <main> : aucun parent ne peut décaler la position fixe
        lb.setAttribute('role', 'dialog');
        lb.setAttribute('aria-modal', 'true');
        var img = lb.querySelector('img');
        var cap = lb.querySelector('figcaption');
        var closeBtn = lb.querySelector('.lightbox-close');
        var all = $$('[data-lightbox]');
        var items = all; // photos du groupe ouvert (une étape d'avancement, la galerie…)
        var index = 0;
        var lastFocus = null;
        var label = function (key, fallback) { return lb.getAttribute('data-' + key) || fallback; };
        var mk = function (cls, html, aria) {
            var b = document.createElement('button');
            b.type = 'button'; b.className = cls; b.innerHTML = html; b.setAttribute('aria-label', aria);
            lb.appendChild(b); return b;
        };
        var chevron = '<svg width="26" height="26" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        var prevBtn = mk('lightbox-nav prev', chevron, label('prev', 'Photo précédente'));
        var nextBtn = mk('lightbox-nav next', chevron, label('next', 'Photo suivante'));
        var counter = document.createElement('span');
        counter.className = 'lightbox-counter';
        lb.appendChild(counter);

        var show = function (i) {
            index = (i + items.length) % items.length;
            var a = items[index];
            var text = a.getAttribute('data-caption') || '';
            img.src = a.getAttribute('href');
            img.alt = text || (a.querySelector('img') ? a.querySelector('img').alt : '');
            cap.textContent = text;
            cap.hidden = !text;
            counter.textContent = items.length > 1 ? (index + 1) + ' / ' + items.length : '';
            if (animate) gsap.fromTo(img, { scale: .96, opacity: 0 }, { scale: 1, opacity: 1, duration: .5, ease: 'expo.out' });
        };
        var open = function (i) {
            lastFocus = document.activeElement;
            lb.classList.toggle('single', items.length < 2);
            show(i);
            lb.hidden = false;
            root.classList.add('lightbox-open');
            if (lenis) lenis.stop();
            closeBtn.focus();
        };
        var close = function () {
            lb.hidden = true;
            img.removeAttribute('src');
            root.classList.remove('lightbox-open');
            if (lenis) lenis.start();
            if (lastFocus) lastFocus.focus();
        };

        all.forEach(function (a) {
            a.addEventListener('click', function (ev) {
                ev.preventDefault(); ev.stopPropagation();
                var g = a.getAttribute('data-lightbox') || '';
                items = all.filter(function (x) { return (x.getAttribute('data-lightbox') || '') === g; });
                open(items.indexOf(a));
            });
        });
        closeBtn.addEventListener('click', close);
        prevBtn.addEventListener('click', function () { show(index - 1); });
        nextBtn.addEventListener('click', function () { show(index + 1); });
        // Clic sur le fond (pas sur la photo ni les boutons) : fermeture
        lb.addEventListener('click', function (ev) { if (ev.target === lb || ev.target.tagName === 'FIGURE') close(); });
        document.addEventListener('keydown', function (ev) {
            if (lb.hidden) return;
            if (ev.key === 'Escape') close();
            else if (ev.key === 'ArrowRight' && items.length > 1) show(index + 1);
            else if (ev.key === 'ArrowLeft' && items.length > 1) show(index - 1);
            else if (ev.key === 'Tab') { // garde le focus dans la visionneuse
                var f = [closeBtn, prevBtn, nextBtn].filter(function (b) { return b.offsetParent !== null; });
                var pos = f.indexOf(document.activeElement);
                ev.preventDefault();
                f[(pos + (ev.shiftKey ? f.length - 1 : 1)) % f.length].focus();
            }
        });
        // Balayage sur écran tactile
        var sx = null, sy = 0;
        lb.addEventListener('touchstart', function (ev) { sx = ev.touches[0].clientX; sy = ev.touches[0].clientY; }, { passive: true });
        lb.addEventListener('touchend', function (ev) {
            if (sx === null || items.length < 2) return;
            var dx = ev.changedTouches[0].clientX - sx, dy = ev.changedTouches[0].clientY - sy;
            if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) show(index + (dx < 0 ? 1 : -1));
            sx = null;
        });
    }
})();

/* ------------------------------------------------------------------
   Consentement aux cookies (Google Analytics chargé seulement après accord)
   et barre « Faire un don » sur mobile
   ------------------------------------------------------------------ */
(function () {
    'use strict';
    var banner = document.getElementById('cookie-banner');
    var COOKIE = 'uaz_consent';
    var read = function () { var m = document.cookie.match(/(?:^|;\s*)uaz_consent=(granted|denied)/); return m ? m[1] : null; };
    var save = function (v) {
        document.cookie = COOKIE + '=' + v + ';path=/;max-age=' + (60 * 60 * 24 * 182) + ';SameSite=Lax' + (location.protocol === 'https:' ? ';Secure' : '');
    };
    var gaLoaded = false;
    var loadGa = function (id) {
        if (gaLoaded || !id) return;
        gaLoaded = true;
        window.dataLayer = window.dataLayer || [];
        window.gtag = function () { window.dataLayer.push(arguments); };
        window.gtag('js', new Date());
        window.gtag('config', id, { anonymize_ip: true });
        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
        document.head.appendChild(s);
    };

    if (banner) {
        var ga = banner.getAttribute('data-ga');
        var choice = read();
        if (choice === 'granted') loadGa(ga);
        if (!choice) banner.hidden = false;
        banner.addEventListener('click', function (ev) {
            var btn = ev.target.closest('[data-consent]');
            if (!btn) return;
            var v = btn.getAttribute('data-consent');
            var before = read();
            save(v);
            banner.hidden = true;
            if (v === 'granted') loadGa(ga);
            // Retrait du consentement : on recharge pour ne plus exécuter Google Analytics
            else if (before === 'granted' && gaLoaded) location.reload();
        });
        Array.prototype.forEach.call(document.querySelectorAll('[data-cookie-open]'), function (b) {
            b.addEventListener('click', function () { banner.hidden = false; var f = banner.querySelector('button'); if (f) f.focus(); });
        });
    }

    var bar = document.querySelector('[data-mobile-give]');
    if (bar) {
        document.body.classList.add('has-mobile-give');
        var onScroll = function () { bar.classList.toggle('is-visible', window.scrollY > 320); };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }
})();

/* ------------------------------------------------------------------
   Validation des formulaires : message clair sous chaque champ erroné
   ------------------------------------------------------------------ */
(function () {
    'use strict';
    Array.prototype.forEach.call(document.querySelectorAll('form[data-validate]'), function (form) {
        var msg = {};
        try { msg = JSON.parse(form.getAttribute('data-validate')) || {}; } catch (e) {}
        var fields = form.querySelectorAll('input:not([type=hidden]):not([readonly]), textarea, select');

        var messageFor = function (f) {
            var v = f.validity;
            if (v.valueMissing) return msg.required;
            if (v.typeMismatch && f.type === 'email') return msg.email;
            if (v.patternMismatch) return f.type === 'tel' ? msg.tel : (f.name === 'amount' ? msg.amount : msg.required);
            if (v.tooShort) return msg.short;
            return f.validationMessage;
        };
        var show = function (f) {
            if (f.closest('.hp')) return true;
            var label = f.closest('label') || f.parentNode;
            var err = label.querySelector('.field-error');
            var ok = f.checkValidity();
            f.setAttribute('aria-invalid', ok ? 'false' : 'true');
            if (ok) { if (err) err.remove(); return true; }
            if (!err) {
                err = document.createElement('span');
                err.className = 'field-error';
                err.id = 'err-' + Math.random().toString(36).slice(2, 9);
                label.appendChild(err);
                f.setAttribute('aria-describedby', err.id);
            }
            err.textContent = messageFor(f);
            return false;
        };

        Array.prototype.forEach.call(fields, function (f) {
            f.addEventListener('blur', function () { if (f.value !== '' || f.getAttribute('aria-invalid') === 'true') show(f); });
            f.addEventListener('input', function () { if (f.getAttribute('aria-invalid') === 'true') show(f); });
        });
        form.addEventListener('submit', function (ev) {
            var first = null;
            Array.prototype.forEach.call(fields, function (f) { if (!show(f) && !first) first = f; });
            if (first) {
                ev.preventDefault();
                first.focus();
                return;
            }
            var btn = form.querySelector('button[type=submit]');
            if (btn) { btn.disabled = true; btn.setAttribute('aria-busy', 'true'); }
        });
    });
})();
// Retour arrière (cache du navigateur) : réactive les boutons d'envoi
window.addEventListener('pageshow', function () {
    Array.prototype.forEach.call(document.querySelectorAll('form[data-validate] button[aria-busy]'), function (b) { b.disabled = false; b.removeAttribute('aria-busy'); });
});

/* ------------------------------------------------------------------
   Carrousel des projets (accueil) : défilement natif + flèches,
   glisser à la souris, pavé tactile / Maj + molette, flèches du clavier
   ------------------------------------------------------------------ */
(function () {
    'use strict';
    Array.prototype.forEach.call(document.querySelectorAll('[data-carousel]'), function (vp) {
        var section = vp.closest('section') || document;
        var prev = section.querySelector('[data-carousel-prev]');
        var next = section.querySelector('[data-carousel-next]');
        var bar = section.querySelector('.h-progress span');
        var max = function () { return Math.max(0, vp.scrollWidth - vp.clientWidth); };
        var step = function () {
            var card = vp.querySelector('.project-card');
            return card ? card.getBoundingClientRect().width + 28 : vp.clientWidth * .8;
        };
        var go = function (dir) { vp.scrollBy({ left: dir * step(), behavior: 'smooth' }); };
        var update = function () {
            var m = max();
            if (prev) prev.disabled = vp.scrollLeft <= 2;
            if (next) next.disabled = vp.scrollLeft >= m - 2;
            if (bar) {
                var w = m ? vp.clientWidth / vp.scrollWidth : 1;
                bar.style.setProperty('--w', (w * 100).toFixed(1) + '%');
                bar.style.setProperty('--x', (m ? vp.scrollLeft / m * (1 - w) * 100 : 0).toFixed(1) + '%');
            }
        };
        if (prev) prev.addEventListener('click', function () { go(-1); });
        if (next) next.addEventListener('click', function () { go(1); });
        vp.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        vp.addEventListener('keydown', function (ev) {
            if (ev.key === 'ArrowRight') { ev.preventDefault(); go(1); }
            if (ev.key === 'ArrowLeft') { ev.preventDefault(); go(-1); }
        });

        // Glisser à la souris (le tactile utilise le défilement natif)
        var down = false, moved = false, startX = 0, startLeft = 0;
        vp.addEventListener('pointerdown', function (ev) {
            if (ev.pointerType !== 'mouse' || ev.button !== 0) return;
            down = true; moved = false; startX = ev.clientX; startLeft = vp.scrollLeft;
        });
        window.addEventListener('pointermove', function (ev) {
            if (!down) return;
            var dx = ev.clientX - startX;
            if (!moved && Math.abs(dx) > 6) { moved = true; vp.classList.add('is-dragging'); }
            if (moved) vp.scrollLeft = startLeft - dx;
        });
        window.addEventListener('pointerup', function () {
            if (!down) return;
            down = false;
            if (moved) setTimeout(function () { vp.classList.remove('is-dragging'); }, 0);
        });
        // Un glisser ne doit pas ouvrir la carte sous le pointeur
        vp.addEventListener('click', function (ev) { if (moved) { ev.preventDefault(); ev.stopPropagation(); moved = false; } }, true);
        vp.addEventListener('dragstart', function (ev) { ev.preventDefault(); });

        update();
    });
})();

// Promesse de don : la devise affichée à côté du montant suit le projet choisi
(function () {
    var label = document.querySelector('[data-pledge-currency]');
    var select = label && label.closest('form') ? label.closest('form').querySelector('select[name=project_id]') : null;
    if (!select) return;
    var sync = function () {
        var opt = select.options[select.selectedIndex];
        label.textContent = (opt && opt.getAttribute('data-currency')) || label.getAttribute('data-default');
    };
    select.addEventListener('change', sync);
    sync();
})();
