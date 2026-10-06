<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#cb6342">
    <title>@yield('code', 'خطأ') | @yield('title', 'خطأ') - {{ __('content.heroTitle') ?? 'نادي أمل للرياضة' }}</title>

    {{-- Favicons --}}
    <link rel="icon" type="image/png" href="{{ asset('favicon/favicon-96x96.png') }}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}" />
    <link rel="shortcut icon" href="{{ asset('favicon/favicon.ico') }}" />

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,600,700,800,900|instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

    <style>
        :root {
            --primary: #cb6342;
            --primary-rgb: 203, 99, 66;
            --primary-dark: #b54f30;
            --primary-glow: rgba(203, 99, 66, 0.35);
            --bg-dark: #090a0f;
            --card-bg: rgba(18, 20, 29, 0.85);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --text-sub: #64748b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: {!! app()->getLocale() === 'ar' ? "'Cairo', system-ui, sans-serif" : "'Instrument Sans', system-ui, sans-serif" !!};
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            position: relative;
            line-height: 1.6;
        }

        /* Gym Background Atmosphere */
        .gym-backdrop {
            position: fixed;
            inset: 0;
            z-index: 0;
            background-image: url('{{ asset("assets/gym-hero.jpg") }}');
            background-size: cover;
            background-position: center;
            filter: brightness(0.2) saturate(1.1) contrast(1.15);
            transform: scale(1.03);
            transition: transform 10s ease;
        }

        .gym-backdrop-overlay {
            position: fixed;
            inset: 0;
            z-index: 1;
            background: radial-gradient(circle at 50% 35%, rgba(203, 99, 66, 0.12) 0%, rgba(9, 10, 15, 0.88) 60%, rgba(5, 6, 9, 0.98) 100%);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        /* Subtle athletic diagonal grid texture */
        .gym-grid-pattern {
            position: fixed;
            inset: 0;
            z-index: 2;
            background-size: 40px 40px;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            pointer-events: none;
        }

        .page-content {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: 100%;
        }

        /* Header */
        .error-header {
            width: 100%;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1280px;
            margin: 0 auto;
        }

        .brand-link {
            display: inline-flex;
            align-items: center;
            gap: 0.85rem;
            text-decoration: none;
            color: #fff;
            transition: transform 0.2s ease;
        }

        .brand-link:hover {
            transform: translateY(-1px);
        }

        .brand-logo-img {
            height: 48px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.5));
        }

        .brand-name {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: {{ app()->getLocale() === 'ar' ? '0' : '0.05em' }};
            color: #ffffff;
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .brand-subtitle {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .header-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(203, 99, 66, 0.12);
            border: 1px solid rgba(203, 99, 66, 0.3);
            color: #fca5a5;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.05em;
        }

        .header-pill-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--primary);
            box-shadow: 0 0 10px var(--primary);
            animation: pulse-dot 2s infinite ease-in-out;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        /* Main Container */
        .error-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem 3rem;
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
        }

        .error-card {
            width: 100%;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 1.5rem;
            padding: 3rem 2.25rem;
            text-align: center;
            position: relative;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7), 0 0 40px -10px var(--primary-glow);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            overflow: hidden;
        }

        /* Top energetic accent bar */
        .error-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, transparent, var(--primary), #ef4444, transparent);
        }

        /* Athletic gym badge icon container */
        .gym-icon-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 86px;
            height: 86px;
            border-radius: 1.25rem;
            background: linear-gradient(135deg, rgba(203, 99, 66, 0.2) 0%, rgba(20, 22, 33, 0.8) 100%);
            border: 1px solid rgba(203, 99, 66, 0.4);
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 25px -5px var(--primary-glow);
        }

        .gym-icon-wrapper svg {
            width: 44px;
            height: 44px;
            stroke: var(--primary);
            filter: drop-shadow(0 2px 8px var(--primary-glow));
        }

        /* Big Athletic Error Code */
        .error-code-container {
            position: relative;
            line-height: 0.95;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .error-code-num {
            font-size: clamp(5rem, 14vw, 8.5rem);
            font-weight: 900;
            letter-spacing: -0.04em;
            background: linear-gradient(180deg, #ffffff 20%, #cbd5e1 55%, var(--primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            font-variant-numeric: tabular-nums;
        }

        /* Barbell ends styling around code for gym flair */
        .barbell-plate {
            display: inline-flex;
            flex-direction: column;
            gap: 4px;
            opacity: 0.25;
        }

        .barbell-plate span {
            display: block;
            width: 10px;
            border-radius: 4px;
            background: var(--primary);
        }

        .barbell-plate span:nth-child(1) { height: 42px; }
        .barbell-plate span:nth-child(2) { height: 70px; }
        .barbell-plate span:nth-child(3) { height: 95px; }

        /* Titles and taglines */
        .gym-headline {
            font-size: clamp(1.4rem, 3.2vw, 2.1rem);
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.75rem;
        }

        .gym-subheadline {
            font-size: clamp(0.95rem, 1.8vw, 1.15rem);
            color: var(--text-muted);
            max-width: 580px;
            margin: 0 auto 1.75rem;
            line-height: 1.6;
        }

        /* Explicit Message Callout Box */
        .error-message-box {
            background: rgba(24, 27, 40, 0.9);
            border: 1px solid rgba(203, 99, 66, 0.25);
            border-radius: 1rem;
            padding: 1.15rem 1.5rem;
            margin: 0 auto 2.25rem;
            max-width: 620px;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.3);
        }

        .message-icon-bubble {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            border-radius: 0.5rem;
            background: rgba(203, 99, 66, 0.15);
            border: 1px solid rgba(203, 99, 66, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            margin-top: 0.1rem;
        }

        .message-content-wrap {
            flex: 1;
            min-width: 0;
        }

        .message-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--primary);
            margin-bottom: 0.2rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .message-text {
            font-size: 0.95rem;
            font-weight: 600;
            color: #f1f5f9;
            word-break: break-word;
            line-height: 1.5;
        }

        /* Gym Action Buttons */
        .actions-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .btn-gym-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            background: linear-gradient(135deg, var(--primary) 0%, #b54f30 100%);
            color: #ffffff;
            font-size: 1rem;
            font-weight: 700;
            padding: 0.85rem 1.85rem;
            border-radius: 0.85rem;
            text-decoration: none;
            border: none;
            cursor: pointer;
            box-shadow: 0 8px 20px -4px var(--primary-glow);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-gym-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px -6px var(--primary-glow);
            background: linear-gradient(135deg, #db6e4b 0%, #cb6342 100%);
            color: #fff;
        }

        .btn-gym-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            background: rgba(255, 255, 255, 0.04);
            color: #e2e8f0;
            font-size: 1rem;
            font-weight: 600;
            padding: 0.85rem 1.65rem;
            border-radius: 0.85rem;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.12);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-gym-secondary:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.25);
            color: #ffffff;
            transform: translateY(-2px);
        }

        .btn-gym-ghost {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: transparent;
            color: var(--text-muted);
            font-size: 0.95rem;
            font-weight: 600;
            padding: 0.85rem 1.25rem;
            border-radius: 0.85rem;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-gym-ghost:hover {
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.06);
            background: rgba(255, 255, 255, 0.02);
        }

        /* Gym Support & Quick Contact Footer Inside Card */
        .gym-quick-info {
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            padding-top: 1.5rem;
            margin-top: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            font-size: 0.85rem;
            color: var(--text-sub);
        }

        .gym-info-item {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .gym-info-item svg {
            width: 15px;
            height: 15px;
            stroke: var(--primary);
            opacity: 0.85;
        }

        .gym-info-item a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.2s;
        }

        .gym-info-item a:hover {
            color: #ffffff;
        }

        /* Footer */
        .error-footer {
            width: 100%;
            padding: 1.5rem 2rem;
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-sub);
            max-width: 1280px;
            margin: 0 auto;
        }

        @media (max-width: 640px) {
            .error-card {
                padding: 2.25rem 1.25rem;
                border-radius: 1.25rem;
            }

            .error-header {
                padding: 1.25rem 1rem;
            }

            .actions-row {
                flex-direction: column;
                width: 100%;
            }

            .btn-gym-primary,
            .btn-gym-secondary,
            .btn-gym-ghost {
                width: 100%;
            }

            .gym-quick-info {
                flex-direction: column;
                gap: 0.75rem;
            }
        }
    </style>
</head>
<body>
    {{-- Atmosphere layers --}}
    <div class="gym-backdrop"></div>
    <div class="gym-backdrop-overlay"></div>
    <div class="gym-grid-pattern"></div>

    <div class="page-content">
        {{-- Header --}}
        <header class="error-header">
            <a href="{{ url('/') }}" class="brand-link" title="{{ __('content.home') ?? 'Amal Gym' }}">
                <img src="{{ asset('images/amal-gym-logo.png') }}" 
                     alt="Amal Gym Ouarzazate" 
                     class="brand-logo-img"
                     onerror="this.style.display='none'; document.getElementById('brand-text-fallback').style.display='flex';">
                
                <div id="brand-text-fallback" style="display:none; align-items:center; gap:0.5rem;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary);">
                        <path d="m6.5 6.5 11 11"/>
                        <path d="m21 21-1-1"/>
                        <path d="m3 3 1 1"/>
                        <path d="m18 22 4-4"/>
                        <path d="m2 6 4-4"/>
                        <path d="m3 10 7-7"/>
                        <path d="m14 21 7-7"/>
                    </svg>
                </div>

                <div class="brand-name">
                    <span>{{ app()->getLocale() === 'ar' ? 'نادي أمل للرياضة' : 'AMAL GYM' }}</span>
                    <span class="brand-subtitle">{{ app()->getLocale() === 'ar' ? 'ورزازات • OUARZAZATE' : 'Ouarzazate • Fitness & Martial Arts' }}</span>
                </div>
            </a>

            <div class="header-pill">
                <span class="header-pill-dot"></span>
                <span>@yield('status_badge', app()->getLocale() === 'ar' ? 'تنبيه تدريبي' : 'WORKOUT ALERT')</span>
            </div>
        </header>

        {{-- Main Error Content --}}
        <main class="error-main">
            <div class="error-card">
                {{-- Gym Themed Icon --}}
                <div class="gym-icon-wrapper">
                    @hasSection('gym_icon')
                        @yield('gym_icon')
                    @else
                        {{-- Default Dumbbell / Fitness SVG --}}
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6.5 6.5 11 11"/>
                            <path d="m21 21-1-1"/>
                            <path d="m3 3 1 1"/>
                            <path d="m18 22 4-4"/>
                            <path d="m2 6 4-4"/>
                            <path d="m3 10 7-7"/>
                            <path d="m14 21 7-7"/>
                        </svg>
                    @endif
                </div>

                {{-- Error Code with Athletic Plates --}}
                <div class="error-code-container">
                    <div class="barbell-plate" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>

                    <h1 class="error-code-num">@yield('code', '404')</h1>

                    <div class="barbell-plate" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>

                {{-- Gym Headline & Subtitle --}}
                <h2 class="gym-headline">
                    @yield('gym_headline', @view()->hasSection('title') ? @view()->yieldContent('title') : (app()->getLocale() === 'ar' ? 'تمرين خارج المسار!' : 'Workout Off Track!'))
                </h2>

                <p class="gym-subheadline">
                    @yield('gym_subheadline', app()->getLocale() === 'ar' 
                        ? 'يبدو أن هذا التمرين لم يكتمل بالشكل الصحيح أو أن الصفحة التي تبحث عنها غير متوفرة حالياً في جدول تدريبات الصالة.' 
                        : 'It looks like this repetition didn\'t count or the requested page has been moved or temporarily retired from the gym roster.')
                </p>

                {{-- The Error Message Display --}}
                @php
                    $displayMessage = null;
                    if (isset($exception) && filled($exception->getMessage())) {
                        $displayMessage = $exception->getMessage();
                    }
                @endphp

                <div class="error-message-box">
                    <div class="message-icon-bubble">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>
                    <div class="message-content-wrap">
                        <div class="message-label">
                            {{ app()->getLocale() === 'ar' ? 'رسالة الخطأ / التفاصيل' : 'Error Details / Message' }}
                        </div>
                        <div class="message-text">
                            @if ($displayMessage)
                                {{ $displayMessage }}
                            @elseif (trim($__env->yieldContent('message')))
                                @yield('message')
                            @else
                                {{ app()->getLocale() === 'ar' ? 'حدث خطأ أثناء معالجة هذا الطلب.' : 'An unexpected error occurred while processing this request.' }}
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Action Navigation Buttons --}}
                <div class="actions-row">
                    <a href="{{ url('/') }}" class="btn-gym-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                        <span>{{ app()->getLocale() === 'ar' ? 'العودة للرئيسية' : 'Back to Home' }}</span>
                    </a>

                    @auth
                        <a href="{{ url('/admin') }}" class="btn-gym-secondary">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="7" height="9" x="3" y="3" rx="1"/>
                                <rect width="7" height="5" x="14" y="3" rx="1"/>
                                <rect width="7" height="9" x="14" y="12" rx="1"/>
                                <rect width="7" height="5" x="3" y="16" rx="1"/>
                            </svg>
                            <span>{{ __('content.dashboard') ?? (app()->getLocale() === 'ar' ? 'لوحة التحكم' : 'Dashboard') }}</span>
                        </a>
                    @else
                        <a href="{{ url('/admin/login') }}" class="btn-gym-secondary">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                                <polyline points="10 17 15 12 10 7"/>
                                <line x1="15" y1="12" x2="3" y2="12"/>
                            </svg>
                            <span>{{ __('content.login') ?? (app()->getLocale() === 'ar' ? 'تسجيل الدخول' : 'Sign In') }}</span>
                        </a>
                    @endauth

                    <button type="button" onclick="window.history.length > 1 ? window.history.back() : window.location.href='{{ url('/') }}'" class="btn-gym-ghost">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="{{ app()->getLocale() === 'ar' ? 'transform: rotate(180deg);' : '' }}">
                            <line x1="19" y1="12" x2="5" y2="12"/>
                            <polyline points="12 19 5 12 12 5"/>
                        </svg>
                        <span>{{ app()->getLocale() === 'ar' ? 'خطوة للخلف' : 'Go Back' }}</span>
                    </button>
                </div>

                {{-- Gym Assistance & Contact Pill --}}
                <div class="gym-quick-info">
                    <div class="gym-info-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        <a href="tel:+212624836434" dir="ltr">+212 624 836 434</a>
                    </div>

                    <div class="gym-info-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span>{{ app()->getLocale() === 'ar' ? 'حي فدراكوم، ورزازات' : 'Hay Fedragoum, Ouarzazate' }}</span>
                    </div>

                    <div class="gym-info-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <span>{{ app()->getLocale() === 'ar' ? '08:00 - 22:00' : '08:00 - 22:00' }}</span>
                    </div>
                </div>
            </div>
        </main>

        {{-- Footer --}}
        <footer class="error-footer">
            <p>{{ __('content.copyright', ['year' => date('Y')]) ?? '© ' . date('Y') . ' Amal Gym Ouarzazate. All rights reserved.' }}</p>
        </footer>
    </div>
</body>
</html>
