<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

         <title>{{ config('app.name', 'Library Management System') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        <style>
            /* ! tailwindcss v3.4.1 | MIT License | https://tailwindcss.com */*,::after,::before{box-sizing:border-box;border-width:0;border-style:solid;border-color:#e5e7eb}::after,::before{--tw-content:''}:host,html{line-height:1.5;-webkit-text-size-adjust:100%;-moz-tab-size:4;tab-size:4;font-family:Figtree, ui-sans-serif, system-ui, sans-serif, Apple Color Emoji, Segoe UI Emoji, Segoe UI Symbol, Noto Color Emoji;font-feature-settings:normal;font-variation-settings:normal;-webkit-tap-highlight-color:transparent}body{margin:0;line-height:inherit}hr{height:0;color:inherit;border-top-width:1px}abbr:where([title]){-webkit-text-decoration:underline dotted;text-decoration:underline dotted}h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}a{color:inherit;text-decoration:inherit}b,strong{font-weight:bolder}code,kbd,pre,samp{font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;font-feature-settings:normal;font-variation-settings:normal;font-size:1em}small{font-size:80%}sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}sub{bottom:-.25em}sup{top:-.5em}table{text-indent:0;border-color:inherit;border-collapse:collapse}button,input,optgroup,select,textarea{font-family:inherit;font-feature-settings:inherit;font-variation-settings:inherit;font-size:100%;font-weight:inherit;line-height:inherit;color:inherit;margin:0;padding:0}button,select{text-transform:none}[type=button],[type=reset],[type=submit],button{-webkit-appearance:button;background-color:transparent;background-image:none}:-moz-focusring{outline:auto}:-moz-ui-invalid{box-shadow:none}progress{vertical-align:baseline}::-webkit-inner-spin-button,::-webkit-outer-spin-button{height:auto}[type=search]{-webkit-appearance:textfield;outline-offset:-2px}::-webkit-search-decoration{-webkit-appearance:none}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}summary{display:list-item}blockquote,dd,dl,figure,h1,h2,h3,h4,h5,h6,hr,p,pre{margin:0}fieldset{margin:0;padding:0}legend{padding:0}menu,ol,ul{list-style:none;margin:0;padding:0}dialog{padding:0}textarea{resize:vertical}input::placeholder,textarea::placeholder{opacity:1;color:#9ca3af}[role=button],button{cursor:pointer}:disabled{cursor:default}audio,canvas,embed,iframe,img,object,svg,video{display:block;vertical-align:middle}img,video{max-width:100%;height:auto}[hidden]{display:none}*, ::before, ::after{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;--tw-rotate:0;--tw-skew-x:0;--tw-skew-y:0;--tw-scale-x:1;--tw-scale-y:1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness:proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:rgb(59 130 246 / 0.5);--tw-ring-offset-shadow:0 0 #0000;--tw-ring-shadow:0 0 #0000;--tw-shadow:0 0 #0000;--tw-shadow-colored:0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: }::backdrop{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;--tw-rotate:0;--tw-skew-x:0;--tw-skew-y:0;--tw-scale-x:1;--tw-scale-y:1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness:proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:rgb(59 130 246 / 0.5);--tw-ring-offset-shadow:0 0 #0000;--tw-ring-shadow:0 0 #0000;--tw-shadow:0 0 #0000;--tw-shadow-colored:0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: }.absolute{position:absolute}.relative{position:relative}.-left-20{left:-5rem}.top-0{top:0px}.-bottom-16{bottom:-4rem}.-left-16{left:-4rem}. -mx-3{margin-left:-0.75rem;margin-right:-0.75rem}.mt-4{margin-top:1rem}.mt-6{margin-top:1.5rem}.flex{display:flex}.grid{display:grid}.hidden{display:none}.aspect-video{aspect-ratio:16 / 9}.size-12{width:3rem;height:3rem}.size-5{width:1.25rem;height:1.25rem}.size-6{width:1.5rem;height:1.5rem}.h-12{height:3rem}.h-40{height:10rem}.h-full{height:100%}.min-h-screen{min-height:100vh}.w-full{width:100%}.w-\[calc\(100\%\+8rem\)\]{width:calc(100% + 8rem)}.w-auto{width:auto}.max-w-\[877px\]{max-width:877px}.max-w-2xl{max-width:42rem}.flex-1{flex:1 1 0%}.shrink-0{flex-shrink:0}.grid-cols-2{grid-template-columns:repeat(2, minmax(0, 1fr))}.flex-col{flex-direction:column}.items-start{align-items:flex-start}.items-center{align-items:center}.items-stretch{align-items:stretch}.justify-end{justify-content:flex-end}.justify-center{justify-content:center}.gap-2{gap:0.5rem}.gap-4{gap:1rem}.gap-6{gap:1.5rem}.self-center{align-self:center}.overflow-hidden{overflow:hidden}.rounded-\[10px\]{border-radius:10px}.rounded-full{border-radius:9999px}.rounded-lg{border-radius:0.5rem}.rounded-md{border-radius:0.375rem}.rounded-sm{border-radius:0.125rem}.bg-\[\#FF2D20\]\/10{background-color:rgb(255 45 32 / 0.1)}.bg-white{--tw-bg-opacity:1;background-color:rgb(255 255 255 / var(--tw-bg-opacity))}.bg-gradient-to-b{background-image:linear-gradient(to bottom, var(--tw-gradient-stops))}.from-transparent{--tw-gradient-from:transparent var(--tw-gradient-from-position);--tw-gradient-to:rgb(0 0 0 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.via-white{--tw-gradient-to:rgb(255 255 255 / 0)  var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), #fff var(--tw-gradient-via-position), var(--tw-gradient-to)}.to-white{--tw-gradient-to:#fff var(--tw-gradient-to-position)}.stroke-\[\#FF2D20\]{stroke:#FF2D20}.object-cover{object-fit:cover}.object-top{object-position:top}.p-6{padding:1.5rem}.px-6{padding-left:1.5rem;padding-right:1.5rem}.py-10{padding-top:2.5rem;padding-bottom:2.5rem}.px-3{padding-left:0.75rem;padding-right:0.75rem}.py-16{padding-top:4rem;padding-bottom:4rem}.py-2{padding-top:0.5rem;padding-bottom:0.5rem}.pt-3{padding-top:0.75rem}.text-center{text-align:center}.font-sans{font-family:Figtree, ui-sans-serif, system-ui, sans-serif, Apple Color Emoji, Segoe UI Emoji, Segoe UI Symbol, Noto Color Emoji}.text-sm{font-size:0.875rem;line-height:1.25rem}.text-sm\/relaxed{font-size:0.875rem;line-height:1.625}.text-xl{font-size:1.25rem;line-height:1.75rem}.font-semibold{font-weight:600}.text-black{--tw-text-opacity:1;color:rgb(0 0 0 / var(--tw-text-opacity))}.text-white{--tw-text-opacity:1;color:rgb(255 255 255 / var(--tw-text-opacity))}.underline{-webkit-text-decoration-line:underline;text-decoration-line:underline}.antialiased{-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}.shadow-\[0px_14px_34px_0px_rgba\(0\2c 0\2c 0\2c 0\.08\)\]{--tw-shadow:0px 14px 34px 0px rgba(0,0,0,0.08);--tw-shadow-colored:0px 14px 34px 0px var(--tw-shadow-color);box-shadow:var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow)}.ring-1{--tw-ring-offset-shadow:var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);--tw-ring-shadow:var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color);box-shadow:var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000)}.ring-transparent{--tw-ring-color:transparent}.ring-white\/\[0\.05\]{--tw-ring-color:rgb(255 255 255 / 0.05)}.drop-shadow-\[0px_4px_34px_rgba\(0\2c 0\2c 0\2c 0\.06\)\]{--tw-drop-shadow:drop-shadow(0px 4px 34px rgba(0,0,0,0.06));filter:var(--tw-blur) var(--tw-brightness) var(--tw-contrast) var(--tw-grayscale) var(--tw-hue-rotate) var(--tw-invert) var(--tw-saturate) var(--tw-sepia) var(--tw-drop-shadow)}.drop-shadow-\[0px_4px_34px_rgba\(0\2c 0\2c 0\2c 0\.25\)\]{--tw-drop-shadow:drop-shadow(0px 4px 34px rgba(0,0,0,0.25));filter:var(--tw-blur) var(--tw-brightness) var(--tw-contrast) var(--tw-grayscale) var(--tw-hue-rotate) var(--tw-invert) var(--tw-saturate) var(--tw-sepia) var(--tw-drop-shadow)}.transition{transition-property:color, background-color, border-color, fill, stroke, opacity, box-shadow, transform, filter, -webkit-text-decoration-color, -webkit-backdrop-filter;transition-property:color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;transition-property:color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter, -webkit-text-decoration-color, -webkit-backdrop-filter;transition-timing-function:cubic-bezier(0.4, 0, 0.2, 1);transition-duration:150ms}.duration-300{transition-duration:300ms}.selection\:bg-\[\#FF2D20\] *::selection{--tw-bg-opacity:1;background-color:rgb(255 45 32 / var(--tw-bg-opacity))}.selection\:text-white *::selection{--tw-text-opacity:1;color:rgb(255 255 255 / var(--tw-text-opacity))}.selection\:bg-\[\#FF2D20\]::selection{--tw-bg-opacity:1;background-color:rgb(255 45 32 / var(--tw-bg-opacity))}.selection\:text-white::selection{--tw-text-opacity:1;color:rgb(255 255 255 / var(--tw-text-opacity))}.hover\:text-black:hover{--tw-text-opacity:1;color:rgb(0 0 0 / var(--tw-text-opacity))}.hover\:text-black\/70:hover{color:rgb(0 0 0 / 0.7)}.hover\:ring-black\/20:hover{--tw-ring-color:rgb(0 0 0 / 0.2)}.focus\:outline-none:focus{outline:2px solid transparent;outline-offset:2px}.focus-visible\:ring-1:focus-visible{--tw-ring-offset-shadow:var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);--tw-ring-shadow:var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color);box-shadow:var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000)}.focus-visible\:ring-\[\#FF2D20\]:focus-visible{--tw-ring-opacity:1;--tw-ring-color:rgb(255 45 32 / var(--tw-ring-opacity))}@media (min-width: 640px){.sm\:size-16{width:4rem;height:4rem}.sm\:size-6{width:1.5rem;height:1.5rem}.sm\:pt-5{padding-top:1.25rem}}@media (min-width: 768px){.md\:row-span-3{grid-row:span 3 / span 3}}@media (min-width: 1024px){.lg\:col-start-2{grid-column-start:2}.lg\:h-16{height:4rem}.lg\:max-w-7xl{max-width:80rem}.lg\:grid-cols-3{grid-template-columns:repeat(3, minmax(0, 1fr))}.lg\:grid-cols-2{grid-template-columns:repeat(2, minmax(0, 1fr))}.lg\:flex-col{flex-direction:column}.lg\:items-end{align-items:flex-end}.lg\:justify-center{justify-content:center}.lg\:gap-8{gap:2rem}.lg\:p-10{padding:2.5rem}.lg\:pb-10{padding-bottom:2.5rem}.lg\:pt-0{padding-top:0px}.lg\:text-\[\#FF2D20\]{--tw-text-opacity:1;color:rgb(255 45 32 / var(--tw-text-opacity))}}@media (prefers-color-scheme: dark){.dark\:block{display:block}.dark\:hidden{display:none}.dark\:bg-black{--tw-bg-opacity:1;background-color:rgb(0 0 0 / var(--tw-bg-opacity))}.dark\:bg-zinc-900{--tw-bg-opacity:1;background-color:rgb(24 24 27 / var(--tw-bg-opacity))}.dark\:via-zinc-900{--tw-gradient-to:rgb(24 24 27 / 0)  var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), #18181b var(--tw-gradient-via-position), var(--tw-gradient-to)}.dark\:to-zinc-900{--tw-gradient-to:#18181b var(--tw-gradient-to-position)}.dark\:text-white\/50{color:rgb(255 255 255 / 0.5)}.dark\:text-white{--tw-text-opacity:1;color:rgb(255 255 255 / var(--tw-text-opacity))}.dark\:text-white\/70{color:rgb(255 255 255 / 0.7)}.dark\:ring-zinc-800{--tw-ring-opacity:1;--tw-ring-color:rgb(39 39 42 / var(--tw-ring-opacity))}.dark\:hover\:text-white:hover{--tw-text-opacity:1;color:rgb(255 255 255 / var(--tw-text-opacity))}.dark\:hover\:text-white\/70:hover{color:rgb(255 255 255 / 0.7)}.dark\:hover\:text-white\/80:hover{color:rgb(255 255 255 / 0.8)}.dark\:hover\:ring-zinc-700:hover{--tw-ring-opacity:1;--tw-ring-color:rgb(63 63 70 / var(--tw-ring-opacity))}.dark\:focus-visible\:ring-\[\#FF2D20\]:focus-visible{--tw-ring-opacity:1;--tw-ring-color:rgb(255 45 32 / var(--tw-ring-opacity))}.dark\:focus-visible\:ring-white:focus-visible{--tw-ring-opacity:1;--tw-ring-color:rgb(255 255 255 / var(--tw-ring-opacity))}}
        </style>

        <script src="https://cdn.tailwindcss.com"></script>
    </head>
     <body class="font-sans antialiased bg-white text-zinc-900 selection:bg-emerald-600 selection:text-white dark:bg-zinc-950 dark:text-zinc-100">
         <div class="relative min-h-screen overflow-hidden">
             <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                 <div class="absolute -top-40 left-1/2 h-[520px] w-[520px] -translate-x-1/2 rounded-full bg-emerald-500/25 blur-3xl"></div>
                 <div class="absolute -bottom-48 left-0 h-[520px] w-[520px] rounded-full bg-red-500/15 blur-3xl"></div>
                 <div class="absolute -bottom-48 right-0 h-[520px] w-[520px] rounded-full bg-sky-500/15 blur-3xl"></div>
             </div>

             <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-6">
                 @php($isAdmin = auth()->check() && auth()->user()->role?->name === 'admin')
                 <header class="flex items-center justify-between py-6">
                     <a href="{{ url('/') }}" class="group inline-flex items-center gap-3">
                         <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-900 text-white shadow-sm ring-1 ring-zinc-900/10 dark:bg-white dark:text-zinc-900 dark:ring-white/10">
                             <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                 <path d="M4.5 6.25C4.5 5.2835 5.2835 4.5 6.25 4.5H18.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                 <path d="M4.5 17.75C4.5 18.7165 5.2835 19.5 6.25 19.5H18.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                 <path d="M6.25 4.5V19.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                 <path d="M9 7.25H19.25C20.2165 7.25 21 8.0335 21 9V15C21 15.9665 20.2165 16.75 19.25 16.75H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                             </svg>
                         </span>
                         <div class="leading-tight">
                             <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ config('app.name', 'Library Management System') }}</div>
                             <div class="text-xs text-zinc-600 dark:text-zinc-400">Powered by Filament</div>
                         </div>
                     </a>

                     <nav class="flex items-center gap-2">
                         <a href="{{ url('/staff') }}" class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-200 transition hover:bg-emerald-50 dark:text-emerald-300 dark:ring-emerald-900/70 dark:hover:bg-emerald-950/40">Staff Portal</a>
                         @if ($isAdmin)
                             <a href="{{ url('/admin') }}" class="inline-flex items-center justify-center rounded-xl bg-zinc-900 px-4 py-2 text-sm font-semibold text-white shadow-sm ring-1 ring-zinc-900/10 transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:ring-white/10 dark:hover:bg-zinc-200">Admin Portal</a>
                         @endif
                     </nav>
                 </header>

                 <main class="flex-1">
                     <section class="grid items-center gap-10 py-10 lg:grid-cols-2 lg:py-16">
                         <div>
                             <div class="inline-flex items-center gap-2 rounded-full bg-white/70 px-3 py-1 text-xs font-semibold text-zinc-700 ring-1 ring-zinc-200 backdrop-blur dark:bg-zinc-900/60 dark:text-zinc-300 dark:ring-zinc-800">
                                 <span class="inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                 <span>Library operations, simplified</span>
                             </div>

                             <h1 class="mt-5 text-balance text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">
                                 Manage books, members, and circulation in one place.
                             </h1>

                             <p class="mt-5 max-w-xl text-pretty text-base leading-relaxed text-zinc-600 dark:text-zinc-300">
                                 A modern Library Management System built with Laravel + Filament. Track inventory, issue/return workflows, staff actions, and reporting—fast, secure, and easy to use.
                             </p>

                             <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center">
                                 <a href="{{ url('/staff') }}" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm ring-1 ring-emerald-600/20 transition hover:bg-emerald-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/60">
                                     Enter Staff Portal
                                 </a>
                                 @if ($isAdmin)
                                     <a href="{{ url('/admin') }}" class="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-semibold text-zinc-900 ring-1 ring-zinc-200 transition hover:bg-white/70 dark:text-zinc-100 dark:ring-zinc-800 dark:hover:bg-zinc-900/40">
                                         Go to Admin Dashboard
                                     </a>
                                 @endif
                                 <a href="{{ url('/staff/register') }}" class="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-semibold text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                                     Create staff account
                                 </a>
                             </div>

                             <dl class="mt-10 grid grid-cols-3 gap-4">
                                 <div class="rounded-2xl bg-white/70 p-4 ring-1 ring-zinc-200 backdrop-blur dark:bg-zinc-900/60 dark:ring-zinc-800">
                                     <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Catalog</dt>
                                     <dd class="mt-1 text-lg font-bold text-zinc-900 dark:text-white">Books</dd>
                                 </div>
                                 <div class="rounded-2xl bg-white/70 p-4 ring-1 ring-zinc-200 backdrop-blur dark:bg-zinc-900/60 dark:ring-zinc-800">
                                     <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Circulation</dt>
                                     <dd class="mt-1 text-lg font-bold text-zinc-900 dark:text-white">Issue/Return</dd>
                                 </div>
                                 <div class="rounded-2xl bg-white/70 p-4 ring-1 ring-zinc-200 backdrop-blur dark:bg-zinc-900/60 dark:ring-zinc-800">
                                     <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Insights</dt>
                                     <dd class="mt-1 text-lg font-bold text-zinc-900 dark:text-white">Reports</dd>
                                 </div>
                             </dl>
                         </div>

                         <div class="relative">
                             <div class="absolute inset-0 -z-10 rounded-3xl bg-gradient-to-br from-emerald-600/20 via-transparent to-red-600/20 blur-2xl"></div>
                             <div class="overflow-hidden rounded-3xl bg-white/70 ring-1 ring-zinc-200 backdrop-blur dark:bg-zinc-900/60 dark:ring-zinc-800">
                                 <div class="border-b border-zinc-200/70 px-6 py-4 dark:border-zinc-800">
                                     <div class="flex items-center justify-between">
                                         <div class="text-sm font-semibold text-zinc-900 dark:text-white">Quick actions</div>
                                         <div class="flex items-center gap-1.5">
                                             <span class="h-2 w-2 rounded-full bg-red-500/70"></span>
                                             <span class="h-2 w-2 rounded-full bg-yellow-500/70"></span>
                                             <span class="h-2 w-2 rounded-full bg-emerald-500/70"></span>
                                         </div>
                                     </div>
                                 </div>
                                 <div class="grid gap-4 p-6 sm:grid-cols-2">
                                     <a href="{{ url('/staff') }}" class="group rounded-2xl border border-zinc-200/70 bg-white p-5 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-sm dark:border-zinc-800 dark:bg-zinc-950/30 dark:hover:border-emerald-900/60">
                                         <div class="flex items-start justify-between gap-4">
                                             <div>
                                                 <div class="text-sm font-semibold text-zinc-900 dark:text-white">Staff workbench</div>
                                                 <div class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">Issue books, handle returns, manage members.</div>
                                             </div>
                                             <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 transition group-hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900/60">
                                                 <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                     <path d="M7 7H17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                                     <path d="M7 12H14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                                     <path d="M7 17H12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                                     <path d="M5 4.75C5 3.7835 5.7835 3 6.75 3H17.25C18.2165 3 19 3.7835 19 4.75V19.25C19 20.2165 18.2165 21 17.25 21H6.75C5.7835 21 5 20.2165 5 19.25V4.75Z" stroke="currentColor" stroke-width="1.8"/>
                                                 </svg>
                                             </span>
                                         </div>
                                     </a>

                                     @if ($isAdmin)
                                         <a href="{{ url('/admin') }}" class="group rounded-2xl border border-zinc-200/70 bg-white p-5 transition hover:-translate-y-0.5 hover:border-zinc-300 hover:shadow-sm dark:border-zinc-800 dark:bg-zinc-950/30 dark:hover:border-zinc-700">
                                             <div class="flex items-start justify-between gap-4">
                                                 <div>
                                                     <div class="text-sm font-semibold text-zinc-900 dark:text-white">Admin control</div>
                                                     <div class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">Configure settings, manage users, view analytics.</div>
                                                 </div>
                                                 <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-zinc-900 text-white ring-1 ring-zinc-900/10 transition group-hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:ring-white/10 dark:hover:bg-zinc-200">
                                                     <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                         <path d="M12 15.5C13.933 15.5 15.5 13.933 15.5 12C15.5 10.067 13.933 8.5 12 8.5C10.067 8.5 8.5 10.067 8.5 12C8.5 13.933 10.067 15.5 12 15.5Z" stroke="currentColor" stroke-width="1.8"/>
                                                         <path d="M19.4 15.2L21 12L19.4 8.8L15.9 8.3L14 5.3L10 5.3L8.1 8.3L4.6 8.8L3 12L4.6 15.2L8.1 15.7L10 18.7H14L15.9 15.7L19.4 15.2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                                     </svg>
                                                 </span>
                                             </div>
                                         </a>
                                     @endif

                                     <div class="rounded-2xl border border-zinc-200/70 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-950/30">
                                         <div class="text-sm font-semibold text-zinc-900 dark:text-white">Built-in security</div>
                                         <div class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                                             Role-based access and panel separation for staff and admin workflows.
                                         </div>
                                     </div>

                                     <div class="rounded-2xl border border-zinc-200/70 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-950/30">
                                         <div class="text-sm font-semibold text-zinc-900 dark:text-white">Fast workflows</div>
                                         <div class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                                             Search, filter, and manage records with Filament-powered tables.
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </section>

                     <section class="py-10 lg:py-14">
                         <div class="grid gap-6 lg:grid-cols-3">
                             <div class="rounded-3xl bg-white/70 p-6 ring-1 ring-zinc-200 backdrop-blur dark:bg-zinc-900/60 dark:ring-zinc-800">
                                 <div class="text-sm font-semibold text-zinc-900 dark:text-white">Inventory control</div>
                                 <div class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">Track titles, copies, availability, and categories with a clean catalog structure.</div>
                             </div>
                             <div class="rounded-3xl bg-white/70 p-6 ring-1 ring-zinc-200 backdrop-blur dark:bg-zinc-900/60 dark:ring-zinc-800">
                                 <div class="text-sm font-semibold text-zinc-900 dark:text-white">Member records</div>
                                 <div class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">Maintain member profiles, history, and borrowing status for quick service.</div>
                             </div>
                             <div class="rounded-3xl bg-white/70 p-6 ring-1 ring-zinc-200 backdrop-blur dark:bg-zinc-900/60 dark:ring-zinc-800">
                                 <div class="text-sm font-semibold text-zinc-900 dark:text-white">Clear reporting</div>
                                 <div class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">Get actionable insights with dashboards and downloadable documents.</div>
                             </div>
                         </div>
                     </section>

                     <section class="py-10 lg:py-16">
                         <div class="rounded-3xl bg-gradient-to-br from-emerald-600 to-emerald-700 p-8 text-white shadow-sm ring-1 ring-emerald-700/30 lg:p-10">
                             <div class="grid gap-6 lg:grid-cols-3 lg:items-center">
                                 <div class="lg:col-span-2">
                                     <div class="text-sm font-semibold text-white/90">Get started</div>
                                     <h2 class="mt-2 text-balance text-2xl font-bold tracking-tight sm:text-3xl">Choose your portal and start managing today.</h2>
                                     <p class="mt-2 max-w-2xl text-sm leading-relaxed text-white/90">Use the Staff Portal for daily operations. Use the Admin Portal for configuration and oversight.</p>
                                 </div>
                                 <div class="flex flex-col gap-3 sm:flex-row lg:justify-end">
                                     <a href="{{ url('/staff') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-emerald-700 shadow-sm ring-1 ring-white/20 transition hover:bg-white/90">Staff Portal</a>
                                     @if ($isAdmin)
                                         <a href="{{ url('/admin') }}" class="inline-flex items-center justify-center rounded-xl bg-zinc-900/20 px-5 py-3 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-zinc-900/30">Admin Portal</a>
                                     @endif
                                 </div>
                             </div>
                         </div>
                     </section>
                 </main>

                 <footer class="py-10 text-sm text-zinc-500 dark:text-zinc-400">
                     <div class="flex flex-col gap-3 border-t border-zinc-200/70 pt-6 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between">
                         <div class="font-medium">{{ config('app.name', 'Library Management System') }}</div>
                         <div>© {{ date('Y') }}. Built with Laravel & Filament.</div>
                     </div>
                 </footer>
             </div>
         </div>
     </body>
</html>
