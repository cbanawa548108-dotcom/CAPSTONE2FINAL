<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshTrack — Sales & Spoilage Forecasting for Fruit Vendors</title>
    <meta name="description" content="FreshTrack brings sales, batch inventory, demand forecasts and spoilage risk into one workspace for fruit vendors in Davao City.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/freshtrack-app-icon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *,*::before,*::after{box-sizing:border-box}
        body{font-family:'Inter',sans-serif;-webkit-font-smoothing:antialiased;background:#FFFEFA;color:#1F2937}
        h1,h2,h3,h4{font-family:'Poppins',sans-serif}
        .g-hero{background:radial-gradient(ellipse at 82% 18%,rgba(132,204,22,.18),transparent 30%),linear-gradient(135deg,#102B21 0%,#153B2C 58%,#1F4D37 100%)}
        .g-violet{background:linear-gradient(135deg,#7C3AED,#6D28D9)}
        .hero-accent{color:#C9F27A}
        .hero-dot{background:#C9F27A}
        .hero-card{background:#fff;border:1px solid rgba(255,255,255,.3);border-radius:24px;box-shadow:0 28px 80px rgba(0,0,0,.24)}
        .feature-card{background:#fff;border:1px solid #E8E9DF;border-radius:22px;box-shadow:0 4px 18px rgba(26,52,36,.04);transition:all .25s ease}
        .feature-card:hover{transform:translateY(-5px);border-color:#C8D8B8;box-shadow:0 18px 38px rgba(26,52,36,.1)}
        .team-card{position:relative;width:min(100%,300px);padding:36px 26px 30px;background:linear-gradient(180deg,#fff 0%,#FCFBFF 100%);border:1px solid #E9DFFF;border-radius:26px;box-shadow:0 10px 30px rgba(76,29,149,.06);transition:transform .25s ease,box-shadow .25s ease,border-color .25s ease}
        .team-card::before{content:"";position:absolute;inset:0 28px auto;height:3px;border-radius:0 0 999px 999px;background:linear-gradient(90deg,#A78BFA,#6D28D9);opacity:.7}
        .team-card:hover{transform:translateY(-7px);border-color:#C4B5FD;box-shadow:0 22px 44px rgba(76,29,149,.12)}
        .team-photo-wrap{width:112px;height:112px;padding:4px;margin:0 auto 20px;border-radius:999px;background:linear-gradient(135deg,#C4B5FD,#7C3AED);box-shadow:0 10px 22px rgba(109,40,217,.2)}
        .team-photo{width:100%;height:100%;display:block;border:3px solid #fff;border-radius:inherit;object-fit:cover;object-position:center 25%}
        .team-role{display:inline-flex;align-items:center;gap:6px;margin-top:12px;padding:6px 11px;border:1px solid #E9D5FF;border-radius:999px;background:#F5F3FF;color:#6D28D9;font-size:11px;font-weight:700}
        .team-role::before{content:"";width:6px;height:6px;border-radius:50%;background:#8B5CF6}
        @media(prefers-reduced-motion:reduce){.team-card{transition:none}.team-card:hover{transform:none}}
        .workflow-card{background:#fff;border:1px solid #E8E9DF;border-radius:20px}
        .stat-card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:28px 20px}
        .btn-hero{display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border-radius:14px;font-weight:700;font-size:15px;transition:all .2s ease;cursor:pointer;border:none;font-family:'Poppins',sans-serif}
        .btn-hero:hover{transform:translateY(-2px)}
        .btn-hero:active{transform:scale(.97)}
        .btn-lime{background:#C9F27A;color:#183522;box-shadow:0 10px 28px rgba(17,34,25,.2)}
        .btn-lime:hover{background:#D7F99A}
        .eyebrow{letter-spacing:.13em}
        .risk{display:inline-flex;align-items:center;gap:5px;font-size:10px;font-weight:700;padding:3px 9px;border-radius:999px}
        .risk::before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}
        .risk-low{color:#15803D;background:#DCFCE7}
        .risk-med{color:#B45309;background:#FEF3C7}
        .risk-high{color:#B91C1C;background:#FEE2E2}
        .ripeness{height:8px;border-radius:999px;background:linear-gradient(90deg,#22C55E,#EAB308,#F97316,#DC2626)}
        @keyframes pulseDot{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(1.3)}}
        @media(max-width:640px){.hero-title{font-size:2.65rem;line-height:1.08}.hero-copy{font-size:1rem}}
    </style>
</head>
<body>

{{-- NAV --}}
<nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-xl border-b border-violet-100/50 shadow-sm">
    <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
        <a href="{{ url('/') }}" class="flex items-center">
            <img src="{{ asset('images/freshtrack-logo.svg') }}" alt="FreshTrack — Sales and Spoilage Forecasting" class="w-[190px] h-auto object-contain">
        </a>
        <div class="hidden md:flex items-center gap-8 text-[13.5px] font-semibold text-gray-600">
            <a href="#features" class="hover:text-violet-600 transition-colors">Features</a>
            <a href="#how-it-works" class="hover:text-green-700 transition-colors">How it works</a>
            <a href="#fruits" class="hover:text-green-700 transition-colors">Fruits</a>
            <a href="#about" class="hover:text-green-700 transition-colors">Team</a>
        </div>
        <div class="flex items-center gap-3">
            @guest
                <a href="{{ route('login') }}" class="hidden sm:inline-flex btn-hero bg-white border border-gray-200 text-gray-700 px-5 py-2.5 text-[13.5px]">Sign in</a>
            @endguest
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn-hero btn-lime px-5 py-2.5 text-[13.5px]">
                {{ auth()->check() ? 'Open workspace' : 'Enter workspace' }} <span aria-hidden="true">→</span>
            </a>
        </div>
    </div>
</nav>

{{-- HERO --}}
<section class="g-hero min-h-[760px] flex items-center pt-16 relative overflow-hidden">
    <div class="absolute inset-0 opacity-[.08]" style="background-image:radial-gradient(circle,rgba(255,255,255,.8) 1px,transparent 1px);background-size:34px 34px"></div>
    <div class="absolute -right-36 -top-32 w-[520px] h-[520px] rounded-full border border-white/10"></div>
    <div class="absolute -right-12 -top-8 w-[270px] h-[270px] rounded-full border border-lime-100/10"></div>
    <div class="max-w-7xl mx-auto px-6 py-20 w-full relative">
        <div class="grid grid-cols-1 lg:grid-cols-[.9fr_1.1fr] gap-12 xl:gap-20 items-center">
            <div class="max-w-xl">
                <div class="inline-flex items-center gap-2.5 bg-white/10 border border-white/15 rounded-full px-4 py-2 mb-7">
                    <span class="w-2 h-2 hero-dot rounded-full"></span>
                    <span class="text-white/85 text-[11px] font-bold uppercase eyebrow">Built for fruit vendors in Davao City</span>
                </div>
                <h1 class="hero-title text-5xl lg:text-[4.25rem] font-black text-white leading-[1.08] mb-6">
                    Know what will sell.<br>Know what will spoil.<br><span class="hero-accent">Stay ahead.</span>
                </h1>
                <p class="hero-copy text-white/75 text-[17px] leading-relaxed mb-8 max-w-lg">
                    FreshTrack brings sales, batch inventory, demand forecasts and spoilage risk into one practical workspace for market fruit vendors.
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn-hero btn-lime">
                        {{ auth()->check() ? 'Open your workspace' : 'Sign in to your workspace' }} <span aria-hidden="true">→</span>
                    </a>
                    <a href="#features" class="btn-hero bg-white/10 border border-white/20 text-white hover:bg-white/15">Explore the platform</a>
                </div>
                <div class="mt-9 flex flex-wrap gap-x-6 gap-y-3 text-white/65 text-[12px] font-medium">
                    <span class="inline-flex items-center gap-2"><span class="text-lime-300">✓</span> Batch-level stock visibility</span>
                    <span class="inline-flex items-center gap-2"><span class="text-lime-300">✓</span> Weather-aware sales forecasts</span>
                    <span class="inline-flex items-center gap-2"><span class="text-lime-300">✓</span> Low / Medium / High spoilage risk</span>
                </div>
            </div>

            <div class="relative max-w-[620px] w-full mx-auto lg:ml-auto">
                <div class="absolute -inset-5 bg-lime-200/10 rounded-[32px] blur-2xl"></div>
                <div class="hero-card relative p-4 sm:p-6">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#EAF2E3] rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#55724C]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <div>
                                <p class="font-bold text-gray-900 text-[14px]">Inventory overview</p>
                                <p class="text-gray-400 text-[11px]">Calinan Public Market · Stall preview</p>
                            </div>
                        </div>
                        <span class="hidden sm:inline-flex rounded-full bg-[#F3F4EC] px-3 py-1 text-[10px] font-bold text-[#55724C]">PRODUCT PREVIEW</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <div class="rounded-xl bg-[#F8F8F3] p-3">
                            <p class="text-[10px] uppercase tracking-wide text-gray-400 font-bold">Sales</p>
                            <p class="font-bold text-gray-800 text-[12px] mt-1">Record transactions</p>
                            <div class="mt-3 flex items-end gap-1 h-8" aria-hidden="true">
                                <span class="w-2.5 h-3 rounded-t bg-[#C7D9B7]"></span><span class="w-2.5 h-5 rounded-t bg-[#A8C391]"></span><span class="w-2.5 h-4 rounded-t bg-[#C7D9B7]"></span><span class="w-2.5 h-7 rounded-t bg-[#789C64]"></span><span class="w-2.5 h-5 rounded-t bg-[#A8C391]"></span>
                            </div>
                        </div>
                        <div class="rounded-xl bg-[#F8F8F3] p-3">
                            <p class="text-[10px] uppercase tracking-wide text-gray-400 font-bold">Inventory</p>
                            <p class="font-bold text-gray-800 text-[12px] mt-1">Follow each batch</p>
                            <div class="mt-3 h-2 rounded-full bg-[#E5E8DE] overflow-hidden"><div class="h-full w-2/3 rounded-full bg-[#92B579]"></div></div>
                            <p class="text-[10px] text-gray-400 mt-2">Stock and arrival date per batch</p>
                        </div>
                        <div class="rounded-xl bg-[#F8F8F3] p-3">
                            <p class="text-[10px] uppercase tracking-wide text-gray-400 font-bold">Planning</p>
                            <p class="font-bold text-gray-800 text-[12px] mt-1">Review demand signals</p>
                            <div class="mt-3 flex items-center gap-1 text-[#71945D]" aria-hidden="true">
                                <span class="text-xl">↗</span><span class="h-px flex-1 bg-[#A8C391]"></span><span class="w-2 h-2 rounded-full bg-[#71945D]"></span>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1">Forecast from sales + weather</p>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-[#E9E9E0] overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-3 bg-[#FAFAF7] border-b border-[#EEEEEA]">
                            <span class="text-[11px] font-bold text-gray-700">Batch watch</span>
                            <span class="text-[10px] font-semibold text-gray-400">Example data</span>
                        </div>
                        @foreach([
                            ['MG','Mango (Carabao)','MNG-1102','18 kg','med','Medium'],
                            ['DR','Durian (Puyat)','DUR-1104','24 kg','low','Low'],
                            ['BN','Banana (Lakatan)','BAN-1030','9 kg','high','High'],
                            ['PM','Pomelo','POM-1105','32 pcs','low','Low'],
                        ] as [$initials,$fruit,$batch,$qty,$riskClass,$riskLabel])
                        <div class="flex items-center gap-3 px-4 py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                            <span class="w-9 h-9 rounded-xl bg-[#F7F5E9] flex items-center justify-center text-[11px] font-bold text-[#55724C]">{{ $initials }}</span>
                            <span class="min-w-0">
                                <span class="block text-[12px] font-bold text-gray-800 truncate">{{ $fruit }}</span>
                                <span class="block text-[10px] text-gray-400">{{ $batch }} · {{ $qty }}</span>
                            </span>
                            <span class="ml-auto risk risk-{{ $riskClass }}">{{ $riskLabel }}</span>
                        </div>
                        @endforeach
                    </div>
                    <p class="text-[10px] text-gray-400 mt-3 text-center">Illustrative preview — your own stall data appears after sign-in.</p>
                </div>
                <div class="hidden sm:flex absolute -left-7 bottom-10 bg-white rounded-2xl shadow-xl px-4 py-3 items-center gap-3 border border-gray-100">
                    <span class="w-9 h-9 rounded-xl bg-[#FFF2D7] flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#B45309]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </span>
                    <span><strong class="block text-[11px] text-gray-800">Track by batch</strong><small class="text-[10px] text-gray-400">From arrival to checkout</small></span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- FEATURES --}}
<section id="features" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
            <span class="inline-flex items-center gap-2 bg-violet-50 text-violet-700 text-[12px] font-bold px-4 py-2 rounded-full border border-violet-200 mb-4">Powered by machine learning</span>
            <h2 class="text-4xl font-black text-gray-900 mb-4">Everything You Need to Run<br><span class="text-violet-600">a Smarter Fruit Business</span></h2>
            <p class="text-gray-500 text-[16px] max-w-2xl mx-auto">From batch-level inventory to sales forecasting and spoilage risk, FreshTrack gives Davao fruit vendors a clearer view of what to buy, what to sell first and what to restock.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @php
            $features = [
                ['M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z','Sales Forecasting','A SARIMAX model learns from your daily sales history and local weather from OpenWeatherMap to estimate upcoming demand per fruit.'],
                ['M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4','Batch Inventory','Every delivery is its own batch with a code, quantity and arrival date. Stock in, stock out and adjustments are all recorded.'],
                ['M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z','Spoilage Prediction','An XGBoost model estimates each batch\'s remaining shelf life and labels it Low, Medium or High risk.'],
                ['M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z','Point of Sale','Fast checkout for cashiers. Each sale deducts from stock and builds the sales history the forecasts depend on.'],
                ['M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z','Decision Support','Low-stock and high-risk alerts help you decide what to restock, what to sell first and what to discount.'],
                ['M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z','Reports & Analytics','Sales trends, stock movement and spoilage losses by day, week or month, ready to review or print.'],
            ];
            @endphp
            @foreach($features as [$iconPath,$title,$desc])
            <div class="feature-card p-7">
                <div class="w-12 h-12 bg-gray-100 border border-gray-200 rounded-2xl flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
                    </svg>
                </div>
                <h3 class="text-[17px] font-bold text-gray-900 mb-2">{{ $title }}</h3>
                <p class="text-gray-500 text-[13.5px] leading-relaxed">{{ $desc }}</p>
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="inline-flex items-center gap-1 text-violet-600 text-[12.5px] font-semibold mt-4 hover:gap-2 transition-all">
                    Explore feature
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- HOW IT WORKS (formerly STATS) --}}
<section id="how-it-works" class="py-24 g-hero relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle,rgba(255,255,255,.6) 1px,transparent 1px);background-size:32px 32px"></div>
    <div class="max-w-7xl mx-auto px-6 relative">
        <div class="text-center mb-14">
            <h2 class="text-4xl font-black text-white mb-3">What Powers FreshTrack</h2>
            <p class="text-white/70 text-[15px]">The models and records behind every forecast and alert</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach([
                ['SARIMAX','Sales Forecasting','Daily sales + weather variables'],
                ['XGBoost','Spoilage Risk','Remaining shelf life → Low / Medium / High'],
                ['Per batch','Inventory Tracking','Every delivery from arrival to checkout'],
                ['3 roles','Owner · Manager · Cashier','Each sees only what they need'],
            ] as [$v,$l,$d])
            <div class="stat-card text-center">
                <p class="text-3xl md:text-4xl font-black hero-accent mb-2">{{ $v }}</p>
                <p class="text-white font-bold text-[15px]">{{ $l }}</p>
                <p class="text-white/50 text-[12px] mt-1">{{ $d }}</p>
            </div>
            @endforeach
        </div>
        <div class="max-w-xl mx-auto mt-12">
            <div class="ripeness"></div>
            <div class="flex justify-between text-white/60 text-[11px] font-semibold mt-2">
                <span>Fresh</span><span>Ripening</span><span>Sell soon</span><span>Spoilage risk</span>
            </div>
        </div>
    </div>
</section>

{{-- FRUIT SHOWCASE --}}
<section id="fruits" class="py-20 bg-[#F5F3FF]">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-black text-gray-900 mb-3">Fruits We <span class="text-violet-600">Track &amp; Analyze</span></h2>
            <p class="text-gray-500 text-[14px]">Common varieties sold in Calinan and Magsaysay public markets</p>
        </div>
        <div class="flex flex-wrap justify-center" style="gap:16px">
            <div class="bg-white rounded-2xl text-center border border-violet-100 hover:border-violet-300 hover:shadow-lg hover:shadow-violet-100 transition-all" style="width:160px;padding:22px 16px 18px">
                <div class="mx-auto mb-3 rounded-2xl flex items-center justify-center" style="width:72px;height:72px;background:#FFF7E6">
                    <svg viewBox="0 0 48 48" width="48" height="48" role="img" aria-label="Mango"><path d="M25 12c1-3 3-5 6-6" stroke="#7C4A1E" stroke-width="2" stroke-linecap="round" fill="none"/><path d="M26 10c4-5 10-6 14-4-3 5-9 7-14 4z" fill="#16A34A"/><path d="M12 31c-3-9 3-18 13-19 9-1 14 6 12 14-2 9-10 13-17 12-4-1-7-3-8-7z" fill="#FBBF24"/><path d="M30 37c4-2 7-6 7-11 0 7-3 12-9 13z" fill="#F97316" opacity=".55"/><ellipse cx="19" cy="21" rx="4" ry="2.2" transform="rotate(-35 19 21)" fill="#FFF7D6" opacity=".8"/></svg>
                </div>
                <p class="font-bold text-gray-900 text-[14px]">Mango</p>
                <p class="text-violet-600 text-[11.5px] font-semibold">Carabao</p>
                <span class="inline-block mt-2.5 bg-violet-100 text-violet-700 text-[10px] font-bold px-3 py-1 rounded-full">Per kg</span>
            </div>
            <div class="bg-white rounded-2xl text-center border border-violet-100 hover:border-violet-300 hover:shadow-lg hover:shadow-violet-100 transition-all" style="width:160px;padding:22px 16px 18px">
                <div class="mx-auto mb-3 rounded-2xl flex items-center justify-center" style="width:72px;height:72px;background:#F1F5E4">
                    <svg viewBox="0 0 48 48" width="48" height="48" role="img" aria-label="Durian"><polygon points="24.0,10.0 26.6,13.8 30.5,11.3 31.5,15.8 36.0,15.0 35.2,19.5 39.7,20.5 37.2,24.4 41.0,27.0 37.2,29.6 39.7,33.5 35.2,34.5 36.0,39.0 31.5,38.2 30.5,42.7 26.6,40.2 24.0,44.0 21.4,40.2 17.5,42.7 16.5,38.2 12.0,39.0 12.8,34.5 8.3,33.5 10.8,29.6 7.0,27.0 10.8,24.4 8.3,20.5 12.8,19.5 12.0,15.0 16.5,15.8 17.5,11.3 21.4,13.8" fill="#6B7A26"/><circle cx="24" cy="27" r="13" fill="#8FA33A"/><path d="M15 23l3-3 3 3-3 3zM21 19l3-3 3 3-3 3zM27 23l3-3 3 3-3 3zM18 29l3-3 3 3-3 3zM24 29l3-3 3 3-3 3zM21 35l3-3 3 3-3 3z" fill="#C5D46A"/><path d="M23 10V5h3v5z" fill="#5B3A1A"/></svg>
                </div>
                <p class="font-bold text-gray-900 text-[14px]">Durian</p>
                <p class="text-violet-600 text-[11.5px] font-semibold">Puyat</p>
                <span class="inline-block mt-2.5 bg-violet-100 text-violet-700 text-[10px] font-bold px-3 py-1 rounded-full">Per kg</span>
            </div>
            <div class="bg-white rounded-2xl text-center border border-violet-100 hover:border-violet-300 hover:shadow-lg hover:shadow-violet-100 transition-all" style="width:160px;padding:22px 16px 18px">
                <div class="mx-auto mb-3 rounded-2xl flex items-center justify-center" style="width:72px;height:72px;background:#EEF9E4">
                    <svg viewBox="0 0 48 48" width="48" height="48" role="img" aria-label="Pomelo"><path d="M24 11V6" stroke="#5B3A1A" stroke-width="2" stroke-linecap="round"/><path d="M25 8c3-4 8-4 11-2-3 4-7 5-11 2z" fill="#16A34A"/><circle cx="24" cy="27" r="16" fill="#A3D977"/><circle cx="24" cy="27" r="16" fill="none" stroke="#7FB956" stroke-width="1.5"/><ellipse cx="18" cy="20" rx="5" ry="3" transform="rotate(-30 18 20)" fill="#EAFBD6" opacity=".85"/><circle cx="30" cy="31" r="1" fill="#7FB956"/><circle cx="26" cy="36" r="1" fill="#7FB956"/><circle cx="33" cy="25" r="1" fill="#7FB956"/></svg>
                </div>
                <p class="font-bold text-gray-900 text-[14px]">Pomelo</p>
                <p class="text-violet-600 text-[11.5px] font-semibold">Magallanes</p>
                <span class="inline-block mt-2.5 bg-violet-100 text-violet-700 text-[10px] font-bold px-3 py-1 rounded-full">Per piece</span>
            </div>
            <div class="bg-white rounded-2xl text-center border border-violet-100 hover:border-violet-300 hover:shadow-lg hover:shadow-violet-100 transition-all" style="width:160px;padding:22px 16px 18px">
                <div class="mx-auto mb-3 rounded-2xl flex items-center justify-center" style="width:72px;height:72px;background:#F8ECF1">
                    <svg viewBox="0 0 48 48" width="48" height="48" role="img" aria-label="Mangosteen"><circle cx="24" cy="28" r="15" fill="#6B2140"/><ellipse cx="18" cy="22" rx="4.5" ry="2.6" transform="rotate(-30 18 22)" fill="#B5527A" opacity=".7"/><path d="M24 15c-5-1-8-4-8-7 4 0 7 2 8 5 1-3 4-5 8-5 0 3-3 6-8 7z" fill="#4D7C0F"/><path d="M24 15c-3 2-7 2-9 1 2-3 6-3 9-1zm0 0c3 2 7 2 9 1-2-3-6-3-9-1z" fill="#65A30D"/><path d="M24 13V6" stroke="#4D7C0F" stroke-width="2.4" stroke-linecap="round"/></svg>
                </div>
                <p class="font-bold text-gray-900 text-[14px]">Mangosteen</p>
                <p class="text-violet-600 text-[11.5px] font-semibold">Local</p>
                <span class="inline-block mt-2.5 bg-violet-100 text-violet-700 text-[10px] font-bold px-3 py-1 rounded-full">Per kg</span>
            </div>
            <div class="bg-white rounded-2xl text-center border border-violet-100 hover:border-violet-300 hover:shadow-lg hover:shadow-violet-100 transition-all" style="width:160px;padding:22px 16px 18px">
                <div class="mx-auto mb-3 rounded-2xl flex items-center justify-center" style="width:72px;height:72px;background:#FBF6E4">
                    <svg viewBox="0 0 48 48" width="48" height="48" role="img" aria-label="Lanzones"><path d="M24 6v9M24 12l-7 5M24 12l7 5" stroke="#7C5A2A" stroke-width="1.8" stroke-linecap="round" fill="none"/><circle cx="17" cy="22" r="6" fill="#E9D48C" stroke="#C9A94F" stroke-width="1.2"/><circle cx="31" cy="22" r="6" fill="#E9D48C" stroke="#C9A94F" stroke-width="1.2"/><circle cx="24" cy="25" r="6.5" fill="#F0DC97" stroke="#C9A94F" stroke-width="1.2"/><circle cx="18" cy="34" r="6" fill="#E9D48C" stroke="#C9A94F" stroke-width="1.2"/><circle cx="30" cy="34" r="6" fill="#E9D48C" stroke="#C9A94F" stroke-width="1.2"/><circle cx="24" cy="40" r="5" fill="#F0DC97" stroke="#C9A94F" stroke-width="1.2"/><circle cx="22" cy="23" r="1.6" fill="#FFF8DE"/></svg>
                </div>
                <p class="font-bold text-gray-900 text-[14px]">Lanzones</p>
                <p class="text-violet-600 text-[11.5px] font-semibold">Local</p>
                <span class="inline-block mt-2.5 bg-violet-100 text-violet-700 text-[10px] font-bold px-3 py-1 rounded-full">Per kg</span>
            </div>
            <div class="bg-white rounded-2xl text-center border border-violet-100 hover:border-violet-300 hover:shadow-lg hover:shadow-violet-100 transition-all" style="width:160px;padding:22px 16px 18px">
                <div class="mx-auto mb-3 rounded-2xl flex items-center justify-center" style="width:72px;height:72px;background:#FEF9DC">
                    <svg viewBox="0 0 48 48" width="48" height="48" role="img" aria-label="Banana"><path d="M9 18c2 14 14 22 30 18-2 3-6 5-11 5C16 41 7 31 7 19z" fill="#EAB308"/><path d="M12 14c1 13 11 21 27 19-1 2-4 4-8 4C18 37 10 27 10 15z" fill="#FACC15"/><path d="M15 11c0 11 9 18 23 17-1 2-3 3-7 3-11 0-18-8-18-19z" fill="#FDE047"/><path d="M9 13l4-4 3 3-3 3z" fill="#5B3A1A"/><path d="M39 28l2 1-1 2" stroke="#5B3A1A" stroke-width="1.6" fill="none" stroke-linecap="round"/></svg>
                </div>
                <p class="font-bold text-gray-900 text-[14px]">Banana</p>
                <p class="text-violet-600 text-[11.5px] font-semibold">Lakatan</p>
                <span class="inline-block mt-2.5 bg-violet-100 text-violet-700 text-[10px] font-bold px-3 py-1 rounded-full">Per kg</span>
            </div>
            <div class="bg-white rounded-2xl text-center border border-violet-100 hover:border-violet-300 hover:shadow-lg hover:shadow-violet-100 transition-all" style="width:160px;padding:22px 16px 18px">
                <div class="mx-auto mb-3 rounded-2xl flex items-center justify-center" style="width:72px;height:72px;background:#FFF3E0">
                    <svg viewBox="0 0 48 48" width="48" height="48" role="img" aria-label="Pineapple"><path d="M24 18c-2-5-6-9-10-10 3 4 4 7 5 10zM24 18c2-5 6-9 10-10-3 4-4 7-5 10zM24 17c-1-5 0-10 0-13 1 3 2 8 0 13z" fill="#16A34A"/><path d="M24 17c-3-3-8-4-11-3 3 2 6 3 9 5zM24 17c3-3 8-4 11-3-3 2-6 3-9 5z" fill="#22C55E"/><ellipse cx="24" cy="31" rx="11" ry="13" fill="#F59E0B"/><path d="M16 24l16 16M14 31l12 12M19 19l15 15M32 24L16 40M34 31L22 43M29 19L14 34" stroke="#B45309" stroke-width="1.2" opacity=".6"/><ellipse cx="19" cy="25" rx="3" ry="4.5" fill="#FDE68A" opacity=".55"/></svg>
                </div>
                <p class="font-bold text-gray-900 text-[14px]">Pineapple</p>
                <p class="text-violet-600 text-[11.5px] font-semibold">Queen</p>
                <span class="inline-block mt-2.5 bg-violet-100 text-violet-700 text-[10px] font-bold px-3 py-1 rounded-full">Per piece</span>
            </div>
        </div>
    </div>
</section>

{{-- TEAM --}}
<section id="about" class="py-24 bg-gradient-to-b from-[#FCFAFF] via-white to-[#FAF8FF]">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
            <span class="inline-flex items-center gap-2 bg-violet-50 text-violet-700 text-[12px] font-bold px-4 py-2 rounded-full border border-violet-200 mb-5">The Team</span>
            <h2 class="text-4xl sm:text-5xl font-black text-gray-900 mb-5 leading-tight">Meet the People<br><span class="text-violet-600">Behind FreshTrack</span></h2>
            <p class="text-gray-500 text-[15px] leading-relaxed max-w-2xl mx-auto">We’re BSIT students from the University of Mindanao building FreshTrack to help fruit vendors in Davao City make smarter day-to-day decisions.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto">
            <article class="team-card text-center">
                <div class="team-photo-wrap"><img src="{{ asset('images/crislee1.png') }}" alt="Cris Lee Banawa" class="team-photo"></div>
                <h3 class="text-lg font-bold text-gray-900">Cris Lee Banawa</h3>
                <p class="team-role">Project Manager</p>
                <p class="text-gray-400 text-[12px] mt-3">University of Mindanao</p>
            </article>
            <article class="team-card text-center">
                <div class="team-photo-wrap"><img src="{{ asset('images/CHIRSTIAN1.png') }}" alt="Christian Torion" class="team-photo"></div>
                <h3 class="text-lg font-bold text-gray-900">Christian Torion</h3>
                <p class="team-role">UI/UX Designer</p>
                <p class="text-gray-400 text-[12px] mt-3">University of Mindanao</p>
            </article>
            <article class="team-card text-center sm:col-span-2 lg:col-span-1 sm:justify-self-center lg:justify-self-stretch">
                <div class="team-photo-wrap"><img src="{{ asset('images/RYANJAY.PNG.png') }}" alt="Ryan Vicente" class="team-photo"></div>
                <h3 class="text-lg font-bold text-gray-900">Ryan Vicente</h3>
                <p class="team-role">Backend Developer</p>
                <p class="text-gray-400 text-[12px] mt-3">University of Mindanao</p>
            </article>
        </div>
    </div>
</section>

{{-- CTA --}}
<section id="get-started" class="pb-20 bg-white">
    <div class="max-w-4xl mx-auto px-6 text-center">
        <div class="bg-gradient-to-br from-[#F5F3FF] to-[#EDE9FE] rounded-3xl p-12 border border-violet-200">
            <div class="w-16 h-16 g-violet rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-violet-300/40"><svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>
            <h2 class="text-4xl font-black text-gray-900 mb-4">Ready to Run a<br><span class="text-violet-600">Fresher Fruit Stall?</span></h2>
            <p class="text-gray-500 text-[15px] mb-8 max-w-xl mx-auto">Track every batch, forecast what will sell and catch spoilage risk early, all in one workspace built for micro-scale fruit vendors in Davao City.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn-hero g-violet text-white shadow-xl shadow-violet-300/50 text-[15px] px-8 py-4 justify-center">
                    {{ auth()->check() ? 'Open your workspace' : 'Sign in to FreshTrack' }}
                </a>
                <a href="#features" class="btn-hero bg-white border-2 border-violet-200 text-violet-700 text-[15px] px-8 py-4 justify-center">Explore features</a>
            </div>
        </div>
    </div>
</section>

{{-- FOOTER --}}
<footer class="bg-gray-900 text-white py-12">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-10">
            <div class="md:col-span-2">
                <div class="inline-flex items-center bg-white rounded-xl p-2 mb-4">
                    <img src="{{ asset('images/freshtrack-logo.svg') }}" alt="FreshTrack — Sales and Spoilage Forecasting" class="w-[190px] h-auto object-contain">
                </div>
                <p class="text-gray-400 text-[13px] leading-relaxed max-w-sm">Capstone project — Sales and Spoilage Forecasting with Inventory Management for Fruit Vendors in Davao City.</p>
            </div>
            @foreach([
                ['Platform',[['Features','#features'],['How it works','#how-it-works'],['Fruits','#fruits'],['Team','#about']]],
                ['Account',[[auth()->check() ? 'Dashboard' : 'Sign in', auth()->check() ? route('dashboard') : route('login')]]],
            ] as [$g,$links])
            <div>
                <h4 class="font-bold text-[13px] text-gray-300 uppercase tracking-wider mb-4">{{ $g }}</h4>
                <ul class="space-y-2">
                    @foreach($links as [$label,$href])
                    <li><a href="{{ $href }}" class="text-gray-500 hover:text-violet-400 text-[13px] transition-colors">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
        <div class="border-t border-gray-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-gray-500 text-[12.5px]">© {{ date('Y') }} FreshTrack — Capstone Project · University of Mindanao · Davao City, Philippines</p>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 hero-dot rounded-full" style="animation:pulseDot 2.2s infinite"></span>
                <span class="hero-accent text-[12px] font-semibold">BSIT Capstone</span>
            </div>
        </div>
    </div>
</footer>

</body>
</html>
