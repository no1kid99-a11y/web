<?php /** @var string $title */ ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(\Core\Csrf::token()) ?>">
<title><?= e(($title ?? '') ? $title . ' | ' : '') ?>PetCare Home</title>
<link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Lexend:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  /* Bảng màu dạng biến: trang khách dùng xanh đen + vàng; khu vực quản trị (.admin-theme) dùng xám ấm + cam */
  :root {
    /* Giao diện khách hàng: nâu ấm + cam, nền kem */
    --ink-50: 251 247 241; --ink-100: 244 236 225; --ink-200: 231 217 198; --ink-300: 205 183 155; --ink-400: 166 139 108;
    --ink-500: 130 103 74; --ink-600: 99 76 53;    --ink-700: 74 56 38;    --ink-800: 58 43 29;    --ink-900: 43 31 20;
    --tag-50: 255 245 232; --tag-100: 255 232 199; --tag-200: 255 208 143; --tag-300: 253 178 78;  --tag-400: 247 148 29;
    --tag-500: 233 126 12; --tag-600: 201 101 7;   --tag-700: 158 78 10;
  }
  .admin-theme {
    --ink-50: 250 248 245; --ink-100: 243 239 233; --ink-200: 230 223 213; --ink-300: 203 192 177; --ink-400: 163 150 133;
    --ink-500: 125 112 98; --ink-600: 94 83 72;    --ink-700: 70 61 53;    --ink-800: 51 44 38;    --ink-900: 36 31 27;
    --tag-50: 255 247 234; --tag-100: 254 236 205; --tag-200: 253 215 153; --tag-300: 251 188 92;  --tag-400: 247 164 44;
    --tag-500: 238 143 18; --tag-600: 208 118 10;  --tag-700: 166 91 11;
  }
</style>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = (() => {
const v = (n) => `rgb(var(--${n}) / <alpha-value>)`;
const scale = (p, steps) => Object.fromEntries(steps.map(s => [s, v(`${p}-${s}`)]));
return {
  theme: {
    extend: {
      fontFamily: {
        sans: ['"Be Vietnam Pro"', 'system-ui', 'sans-serif'],
        display: ['"Be Vietnam Pro"', 'system-ui', 'sans-serif'],
        hand: ['"Baloo 2"', '"Be Vietnam Pro"', 'sans-serif'],
        lexend: ['Lexend', '"Be Vietnam Pro"', 'system-ui', 'sans-serif'],
      },
      colors: {
        ink: scale('ink', [50, 100, 200, 300, 400, 500, 600, 700, 800, 900]),
        tag: scale('tag', [50, 100, 200, 300, 400, 500, 600, 700]),
        mint: { 50:'#EEFBF4', 100:'#D6F5E4', 200:'#AEEACB', 300:'#7DDAAB', 400:'#45C385', 500:'#22A867', 600:'#178852', 700:'#146B43' },
        forest: { 50:'#EAF6F0', 100:'#CDEBDD', 200:'#9FD6BD', 400:'#3FA77A', 500:'#1E8A5F', 600:'#13714C', 700:'#0F5B3E', 800:'#0C4A33' },
        paper: '#FBF6EE',
      },
    }
  }
};
})();
</script>
<style type="text/tailwindcss">
  @layer components {
    .btn { @apply inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-tag-400 focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed; }
    .btn-sm { @apply px-3 py-1.5 text-xs; }
    .btn-primary { @apply bg-ink-900 text-white hover:bg-ink-700; }
    .btn-accent { @apply bg-gradient-to-r from-tag-500 to-tag-600 text-white shadow-md shadow-tag-500/25 hover:from-tag-600 hover:to-tag-700; }
    .btn-outline { @apply border border-ink-200 bg-white text-ink-800 hover:bg-ink-50; }
    .btn-ghost { @apply text-ink-700 hover:bg-ink-100; }
    .btn-danger { @apply bg-red-600 text-white hover:bg-red-700; }
    .btn-danger-outline { @apply border border-red-200 bg-white text-red-700 hover:bg-red-50; }
    .label { @apply mb-1 block text-sm font-medium text-ink-800; }
    .hint { @apply mt-1 text-xs text-ink-500; }
    .input { @apply block w-full rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm text-ink-900 placeholder:text-ink-300 focus:border-ink-500 focus:outline-none focus:ring-2 focus:ring-tag-300; }
    .panel { @apply rounded-2xl border border-ink-100 bg-white shadow-[0_1px_3px_rgba(80,50,20,0.05)]; }
    .panel-head { @apply flex items-center justify-between gap-3 border-b border-ink-100 px-5 py-3.5; }
    .panel-title { @apply font-display text-lg font-semibold text-ink-900; }
    .tbl { @apply w-full text-left text-sm; }
    .tbl th { @apply whitespace-nowrap border-b border-ink-100 bg-ink-50/60 px-4 py-2.5 text-xs font-semibold text-ink-500; }
    .tbl td { @apply border-b border-ink-100 px-4 py-3 align-top text-ink-800; }
    .tbl tr:last-child td { @apply border-b-0; }
    .chip { @apply inline-flex items-center gap-1 rounded-full bg-ink-100 px-2.5 py-0.5 text-xs font-medium text-ink-700; }
    .link { @apply font-medium text-ink-900 underline decoration-tag-400 decoration-2 underline-offset-4 hover:decoration-ink-900; }
    .page-title { @apply font-display text-3xl font-extrabold leading-tight tracking-tight text-ink-900; }
    .eyebrow { @apply inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.12em] text-tag-600; }
    .section-title { @apply font-display text-2xl font-extrabold tracking-tight text-ink-900 sm:text-3xl; }

    /* ---- Khu vực quản trị và nhân viên ---- */
    .admin-theme .btn { @apply rounded-xl; }
    .admin-theme .btn-primary,
    .admin-theme .btn-accent { @apply bg-tag-400 text-ink-900 shadow-sm shadow-tag-400/30 hover:bg-tag-300; }
    .admin-theme .btn-outline { @apply border-ink-100 shadow-sm; }
    .admin-theme .input { @apply rounded-xl border-ink-100 bg-ink-50/60 focus:bg-white; }
    .admin-theme .panel { @apply rounded-2xl border-ink-100 shadow-[0_1px_2px_rgba(60,40,10,0.04)]; }
    .admin-theme .panel-head { @apply px-6 py-4; }
    .admin-theme .panel-title { @apply font-lexend text-base font-semibold; }
    .admin-theme .page-title { @apply font-lexend text-2xl font-semibold tracking-tight; }
    .admin-theme .tbl th { @apply bg-ink-50 px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-ink-500; }
    .admin-theme .tbl td { @apply px-4 py-4; }
    .admin-theme .chip { @apply bg-ink-50 ring-1 ring-inset ring-ink-100; }
    .admin-theme .link { @apply text-tag-600 no-underline hover:underline; }
    .stat-card { @apply flex items-start justify-between gap-3 rounded-2xl border border-ink-100 bg-white p-5 shadow-[0_1px_2px_rgba(60,40,10,0.04)]; }
    .stat-label { @apply text-[11px] font-semibold uppercase tracking-wider text-ink-500; }
    .stat-value { @apply mt-2 font-lexend text-4xl font-semibold leading-none tabular-nums; }
    .stat-icon { @apply grid h-11 w-11 shrink-0 place-items-center rounded-xl; }
    .pill-tab { @apply inline-flex items-center gap-1.5 rounded-xl border border-ink-100 bg-white px-3.5 py-1.5 text-sm font-medium text-ink-700 hover:border-ink-300; }
    .pill-tab-active { @apply border-tag-400 bg-tag-400 text-ink-900 hover:border-tag-400; }
    .pill-count { @apply rounded-full px-1.5 text-[11px] font-semibold leading-5; }
  }
  [x-cloak] { display: none !important; }
  /* Ô lưới không bị nội dung rộng (bảng) kéo giãn; bảng tự cuộn ngang trong thẻ */
  main .grid > * { min-width: 0; }
  /* Vùng cuộn ngang phải là khung định vị, nếu không nhãn ẩn (sr-only) trong bảng sẽ tràn ra ngoài trang */
  .overflow-x-auto { position: relative; }
  @media (prefers-reduced-motion: reduce) { * { transition: none !important; animation: none !important; } }
</style>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<script defer src="<?= asset('js/photos.js') ?>"></script>
