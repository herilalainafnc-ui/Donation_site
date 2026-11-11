<?php /* Illustration de la bannière d'accueil : cadeaux, cœurs et diplôme — « donner pour l'éducation ». */ ?>
<svg class="gift-art" viewBox="0 0 500 500" role="img" aria-labelledby="gift-art-title">
    <title id="gift-art-title"><?= e(__('Illustration : des cadeaux d\'où s\'élèvent des cœurs et un diplôme')) ?></title>
    <defs>
        <radialGradient id="ga-glow" cx="50%" cy="55%" r="50%">
            <stop offset="0" stop-color="#e8c66a" stop-opacity=".55"/>
            <stop offset=".55" stop-color="#8e6a1c" stop-opacity=".18"/>
            <stop offset="1" stop-color="#8e6a1c" stop-opacity="0"/>
        </radialGradient>
        <linearGradient id="ga-beam" x1="0" y1="1" x2="0" y2="0">
            <stop offset="0" stop-color="#fff3c4" stop-opacity=".75"/>
            <stop offset="1" stop-color="#fff3c4" stop-opacity="0"/>
        </linearGradient>
        <linearGradient id="ga-box" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#3b67c2"/>
            <stop offset="1" stop-color="#1d3b78"/>
        </linearGradient>
        <linearGradient id="ga-lid" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#4675d1"/>
            <stop offset="1" stop-color="#2a4f9c"/>
        </linearGradient>
        <linearGradient id="ga-gold" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#fff3c4"/>
            <stop offset=".5" stop-color="#d9b25a"/>
            <stop offset="1" stop-color="#8e6a1c"/>
        </linearGradient>
        <linearGradient id="ga-gold-dark" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#b8892e"/>
            <stop offset="1" stop-color="#7a5a12"/>
        </linearGradient>
        <linearGradient id="ga-cream" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#ffffff"/>
            <stop offset="1" stop-color="#dfe5f2"/>
        </linearGradient>
        <path id="ga-heart" d="M0 -6 C0 -14 -12 -16 -12 -6 C-12 2 0 10 0 14 C0 10 12 2 12 -6 C12 -16 0 -14 0 -6 Z"/>
        <path id="ga-spark" d="M0 -12 L2.6 -2.6 L12 0 L2.6 2.6 L0 12 L-2.6 2.6 L-12 0 L-2.6 -2.6 Z"/>
    </defs>

    <!-- Halo et ombre au sol -->
    <circle cx="250" cy="275" r="230" fill="url(#ga-glow)"/>
    <ellipse class="ga-shadow" cx="250" cy="446" rx="190" ry="16" fill="#000" opacity=".28"/>

    <!-- Plan arrière : rayon de lumière, cœurs, diplôme, étincelles -->
    <g class="ga-back">
        <polygon class="ga-beam" points="168,262 332,262 400,70 100,70" fill="url(#ga-beam)"/>
        <g class="ga-cap" transform="translate(300 128)">
            <polygon points="-38,0 0,-15 38,0 0,15" fill="#0b1834" stroke="#e8c66a" stroke-width="1.5"/>
            <path d="M-22 7 V20 C-22 28 22 28 22 20 V7" fill="#183060" stroke="#e8c66a" stroke-width="1.5"/>
            <path d="M0 0 L30 6 V26" fill="none" stroke="#e8c66a" stroke-width="2.5" stroke-linecap="round"/>
            <circle cx="30" cy="29" r="4" fill="#e8c66a"/>
        </g>
        <use class="ga-heart h1" href="#ga-heart" transform="translate(236 178) scale(2.4)" fill="url(#ga-gold)"/>
        <use class="ga-heart h2" href="#ga-heart" transform="translate(184 130) scale(1.6)" fill="#ffffff"/>
        <use class="ga-heart h3" href="#ga-heart" transform="translate(268 100) scale(1.8)" fill="#e8c66a"/>
        <use class="ga-heart h4" href="#ga-heart" transform="translate(206 72) scale(1.3)" fill="#d9b25a"/>
        <use class="ga-spark s1" href="#ga-spark" transform="translate(120 150)" fill="#fff3c4"/>
        <use class="ga-spark s2" href="#ga-spark" transform="translate(392 196) scale(.8)" fill="#ffffff"/>
        <use class="ga-spark s3" href="#ga-spark" transform="translate(356 70) scale(.6)" fill="#e8c66a"/>
        <use class="ga-spark s4" href="#ga-spark" transform="translate(150 60) scale(.55)" fill="#ffffff"/>
    </g>

    <!-- Plan avant : les cadeaux -->
    <g class="ga-front">
        <!-- Petit cadeau doré (gauche) -->
        <g class="ga-gift ga-left">
            <rect x="58" y="342" width="104" height="96" rx="9" fill="url(#ga-gold)"/>
            <rect x="110" y="342" width="52" height="96" rx="0" fill="#7a5a12" opacity=".18"/>
            <rect x="101" y="342" width="18" height="96" fill="#183060"/>
            <rect x="50" y="320" width="120" height="28" rx="7" fill="url(#ga-gold-dark)"/>
            <rect x="101" y="320" width="18" height="28" fill="#0b1834"/>
            <path d="M110 320 C92 296 72 306 88 320 Z M110 320 C128 296 148 306 132 320 Z" fill="#183060"/>
            <circle cx="110" cy="318" r="6" fill="#0b1834"/>
        </g>

        <!-- Grand cadeau qui s'ouvre (centre) -->
        <g class="ga-gift ga-main">
            <rect x="150" y="262" width="200" height="176" rx="12" fill="url(#ga-box)"/>
            <rect x="250" y="262" width="100" height="176" fill="#0b1834" opacity=".22"/>
            <rect x="231" y="262" width="38" height="176" fill="url(#ga-gold)"/>
            <rect x="150" y="262" width="200" height="12" fill="#0b1834" opacity=".25"/>
            <g class="ga-lid">
                <rect x="136" y="214" width="228" height="54" rx="11" fill="url(#ga-lid)"/>
                <rect x="250" y="214" width="114" height="54" rx="0" fill="#0b1834" opacity=".15"/>
                <rect x="231" y="214" width="38" height="54" fill="url(#ga-gold)"/>
                <path d="M250 214 C214 166 168 180 198 214 Z" fill="url(#ga-gold)"/>
                <path d="M250 214 C286 166 332 180 302 214 Z" fill="url(#ga-gold-dark)"/>
                <path d="M244 214 C232 236 222 246 210 252 M256 214 C268 236 278 246 290 252" stroke="#d9b25a" stroke-width="9" stroke-linecap="round" fill="none"/>
                <circle cx="250" cy="211" r="13" fill="url(#ga-gold-dark)"/>
            </g>
        </g>

        <!-- Cadeau blanc (droite) -->
        <g class="ga-gift ga-right">
            <rect x="340" y="356" width="112" height="82" rx="9" fill="url(#ga-cream)"/>
            <rect x="396" y="356" width="56" height="82" fill="#183060" opacity=".08"/>
            <rect x="387" y="356" width="18" height="82" fill="url(#ga-gold)"/>
            <rect x="332" y="334" width="128" height="28" rx="7" fill="#ffffff"/>
            <rect x="387" y="334" width="18" height="28" fill="url(#ga-gold-dark)"/>
            <path d="M396 334 C378 310 358 320 374 334 Z M396 334 C414 310 434 320 418 334 Z" fill="url(#ga-gold)"/>
            <circle cx="396" cy="332" r="6" fill="#8e6a1c"/>
        </g>

        <!-- Pièces -->
        <g class="ga-coins">
            <ellipse cx="190" cy="448" rx="22" ry="7" fill="#7a5a12"/>
            <ellipse cx="190" cy="442" rx="22" ry="7" fill="url(#ga-gold)"/>
            <ellipse cx="318" cy="452" rx="18" ry="6" fill="#7a5a12"/>
            <ellipse cx="318" cy="447" rx="18" ry="6" fill="url(#ga-gold)"/>
        </g>
    </g>
</svg>
