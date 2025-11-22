<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-['Arial',sans-serif] min-h-screen" dir="rtl">
        <section x-data class="relative flex h-screen items-center justify-center overflow-hidden">
            <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6 not-has-[nav]:hidden absolute top-0 left-0 right-0 z-20 px-4 py-6">
                    <nav class="flex items-center justify-end gap-4">
                        @auth
                            <a
                                href="{{ url('/admin/dashboard') }}"
                                class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal"
                            >
                                {{ __('content.dashboard') }}
                            </a>
                        @else
                            <a
                                href="/admin/login"
                                class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] text-[#1b1b18] border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
                            >
                                {{ __('content.login') }}
                            </a>
    
                            <a
                                href="/admin/register"
                                class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                                {{ __('content.register') }}
                            </a>
                        @endauth
                    </nav>
            </header> 
            {{-- Language Toggle Placeholder --}}
            <div class="absolute right-6 top-6 z-20 ltr:right-6 rtl:left-6">
                {{-- Include your language toggle blade component here --}}
                {{-- @include('components.language-toggle') --}}
            </div>

            {{-- Background Image with Overlay --}}
            <div class="absolute inset-0">
                <img src="{{ asset('assets/gym-hero.jpg') }}" alt="AmalGym training facility in Ouarzazate"
                    class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-black/60"></div>
            </div>

            {{-- Content --}}
            <div class="relative z-10 px-4 text-center">
                <h1 class="mb-4 font-arabic text-5xl font-bold text-white md:text-7xl">
                    {{ __('content.heroTitle') }}
                </h1>

                <p class="mx-auto mb-3 max-w-2xl text-xl text-gray-200 md:text-2xl">
                    {{ __('content.heroTagline') }}
                </p>

                <p class="mb-8 text-lg text-gray-300">
                    {{ __('content.heroLocation') }}
                </p>

                <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <button class="rounded-lg bg-primary px-8 py-3 text-lg text-white transition hover:bg-primary/90"
                        @click="document.getElementById('signup')?.scrollIntoView({ behavior: 'smooth' })">
                        {{ __('content.joinNow') }}
                    </button>

                    <button
                        class="rounded-lg border border-white px-8 py-3 text-lg text-white transition hover:bg-white hover:text-black"
                        @click="document.getElementById('contact')?.scrollIntoView({ behavior: 'smooth' })">
                        {{ __('content.contactUs') }}
                    </button>
                </div>
            </div>
        </section>
        <section id="services" class="@if (app()->getLocale() === 'ar') font-arabic @endif bg-gray-50 py-20" x-data>
            <div class="mx-auto max-w-7xl px-4">

                {{-- Section Title --}}
                <div class="mb-12 text-center">
                    <h2 class="mb-3 text-3xl font-bold text-gray-900 md:text-5xl">
                        {{ __('content.servicesTitle') }}
                    </h2>

                    <p class="mx-auto max-w-3xl text-lg text-gray-600">
                        {{ __('content.servicesSubtitle') }}
                    </p>
                </div>

                {{-- Services Grid --}}
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">

                    @php
                        $services = [
                            [
                                'key' => 'karate',
                                'desc' => 'karateDesc',
                                'image' => 'assets/karate.jpg',
                                'price' => 250,
                            ],
                            [
                                'key' => 'boxing',
                                'desc' => 'boxingDesc',
                                'image' => 'assets/boxing.jpg',
                                'price' => 300,
                            ],
                            [
                                'key' => 'fitness',
                                'desc' => 'fitnessDesc',
                                'image' => 'assets/fitness.jpg',
                                'price' => 200,
                            ],
                        ];
                    @endphp

                    @foreach ($services as $service)
                        <div
                            class="group overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition hover:shadow-lg">
                            {{-- Image --}}
                            <div class="relative h-48 overflow-hidden">
                                <img src="{{ asset($service['image']) }}"
                                    alt="{{ __('content.' . $service['key']) . ' training at AmalGym' }}"
                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                            </div>

                            {{-- Content --}}
                            <div class="p-5">
                                <h3 class="mb-2 text-2xl font-semibold text-primary">
                                    {{ __('content.' . $service['key']) }}
                                </h3>

                                <p class="mb-4 text-sm leading-relaxed text-gray-600">
                                    {{ __('content.' . $service['desc']) }}
                                </p>

                                <p class="mb-4 text-lg font-semibold text-gray-900">
                                    {{ __('content.fromPerMonth', ['price' => $service['price']]) }}
                                </p>

                                <button
                                    class="w-full rounded-lg bg-primary py-2 text-white transition hover:bg-primary/90"
                                    @click="document.getElementById('signup')?.scrollIntoView({ behavior: 'smooth' })">
                                    {{ __('content.signupFor', ['service' => __('content.' . $service['key'])]) }}
                                </button>
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>
        </section>
        <section id="shop" class="@if (app()->getLocale() === 'ar') font-arabic @endif bg-white py-20" x-data>
            <div class="mx-auto max-w-7xl px-4">

                {{-- Title --}}
                <div class="mb-12 text-center">
                    <h2 class="mb-3 text-3xl font-bold text-gray-900 md:text-5xl">
                        {{ __('content.shopTitle') }}
                    </h2>

                    <p class="mx-auto max-w-3xl text-lg text-gray-600">
                        {{ __('content.shopSubtitle') }}
                    </p>
                </div>

                {{-- Grid --}}
                <div class="mb-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">

                    @php
                        $shopItems = [
                            [
                                'icon' => 'lucide-shirt',
                                'name' => 'trainingGear',
                                'desc' => 'trainingGearDesc',
                            ],
                            [
                                'icon' => 'lucide-dumbbell',
                                'name' => 'equipment',
                                'desc' => 'equipmentDesc',
                            ],
                            [
                                'icon' => 'lucide-backpack',
                                'name' => 'gymBags',
                                'desc' => 'gymBagsDesc',
                            ],
                            [
                                'icon' => 'lucide-shopping-bag',
                                'name' => 'supplements',
                                'desc' => 'supplementsDesc',
                            ],
                        ];
                    @endphp

                    @foreach ($shopItems as $item)
                        <div
                            class="rounded-lg border border-gray-200 bg-gray-50 p-5 text-center shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                            <div
                                class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-primary/10">
                                {{-- @include('components.icons.' . $item['icon'], ['class' => 'w-7 h-7 text-primary']) --}}
                                {{-- <{{ $item['icon'] }} class="w-7 h-7 text-primary" /> --}}
                                <x-dynamic-component :component="$item['icon']" class="h-7 w-7 text-primary" />
                            </div>

                            <h3 class="mb-1 text-lg font-semibold">
                                {{ __('content.' . $item['name']) }}
                            </h3>

                            <p class="text-xs text-gray-600">
                                {{ __('content.' . $item['desc']) }}
                            </p>
                        </div>
                    @endforeach
                </div>

                {{-- Button --}}
                <div class="text-center">
                    <button class="rounded-lg bg-primary px-8 py-3 text-lg text-white transition hover:bg-primary/90">
                        {{ __('content.visitStore') }}
                    </button>
                </div>

            </div>
        </section>
        @props(['language' => app()->getLocale()])

        <section id="signup" class="section-padding {{ $language === 'ar' ? 'font-arabic' : '' }} bg-background">
            <div class="container-custom">
                <div class="mx-auto max-w-2xl animate-fade-in">
                    {{-- Section Header --}}
                    <div class="mb-10 text-center">
                        <h2 class="mb-3 text-3xl font-bold text-foreground md:text-5xl">
                            {{ __('content.signupTitle') }}
                        </h2>
                        <p class="text-lg text-muted-foreground">
                            {{ __('content.signupSubtitle') }}
                        </p>
                    </div>

                    {{-- Form --}}
                    <form action="/register" method="POST"
                        class="space-y-5 rounded-lg border border-border bg-card p-6">
                        @csrf

                        {{-- Full Name --}}
                        <div class="space-y-2">
                            <label for="fullName"
                                class="block text-sm font-medium text-foreground">{{ __('content.fullName') }}</label>
                            <input type="text" id="fullName" name="fullName"
                                placeholder="{{ __('content.fullNamePlaceholder') }}" value="{{ old('fullName') }}"
                                class="w-full rounded-md border border-border bg-background px-4 py-2 text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                required />
                        </div>

                        {{-- Phone --}}
                        <div class="space-y-2">
                            <label for="phone"
                                class="block text-sm font-medium text-foreground">{{ __('content.phoneNumber') }}</label>
                            <input type="tel" id="phone" name="phone"
                                placeholder="{{ __('content.phonePlaceholder') }}" value="{{ old('phone') }}"
                                class="w-full rounded-md border border-border bg-background px-4 py-2 text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                required />
                        </div>

                        {{-- Sport Selection --}}
                        <div class="space-y-2">
                            <label for="sport"
                                class="block text-sm font-medium text-foreground">{{ __('content.sportService') }}</label>
                            <select id="sport" name="sport"
                                class="w-full rounded-md border border-border bg-background px-4 py-2 text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                required>
                                <option value="">{{ __('content.chooseSport') }}</option>
                                <option value="karate">{{ __('content.karate') }}</option>
                                <option value="boxing">{{ __('content.boxing') }}</option>
                                <option value="fitness">{{ __('content.fitness') }}</option>
                                <option value="multiple">{{ __('content.multipleSports') }}</option>
                            </select>
                        </div>

                        {{-- Submit Button --}}
                        <button type="submit"
                            class="w-full rounded-lg bg-primary px-6 py-3 font-semibold text-primary-foreground transition-colors duration-200 hover:bg-primary/90">
                            {{ __('content.submitRegistration') }}
                        </button>

                    </form>
                </div>
            </div>
        </section>
        @props(['language' => app()->getLocale()])

        {{-- /* --------------------------- contact us section --------------------------- */ --}}
        <section id="contact" class="section-padding {{ $language === 'ar' ? 'font-arabic' : '' }} bg-card">
            <div class="container-custom">
                {{-- Section Header --}}
                <div class="mb-12 animate-fade-in text-center">
                    <h2 class="mb-3 text-3xl font-bold text-foreground md:text-5xl">{{ __('content.contactTitle') }}
                    </h2>
                    <p class="mx-auto max-w-3xl text-lg text-muted-foreground">{{ __('content.contactSubtitle') }}</p>
                </div>

                <div class="grid gap-10 lg:grid-cols-2">

                    {{-- Contact Form --}}
                    <div class="animate-fade-in">
                        <form action="contact.store" method="POST" class="space-y-5">
                            @csrf

                            {{-- Name --}}
                            <div class="space-y-2">
                                <label for="name"
                                    class="block text-sm font-medium text-foreground">{{ __('content.name') }}</label>
                                <input type="text" id="name" name="name"
                                    placeholder="{{ __('content.namePlaceholder') }}" value="{{ old('name') }}"
                                    class="w-full rounded-md border border-border bg-background px-4 py-2 text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                    required />
                            </div>

                            {{-- Contact (Email/Phone) --}}
                            <div class="space-y-2">
                                <label for="contact"
                                    class="block text-sm font-medium text-foreground">{{ __('content.emailPhone') }}</label>
                                <input type="text" id="contact" name="contact"
                                    placeholder="{{ __('content.emailPhonePlaceholder') }}"
                                    value="{{ old('contact') }}"
                                    class="w-full rounded-md border border-border bg-background px-4 py-2 text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                    required />
                            </div>

                            {{-- Message --}}
                            <div class="space-y-2">
                                <label for="message"
                                    class="block text-sm font-medium text-foreground">{{ __('content.message') }}</label>
                                <textarea id="message" name="message" rows="5" placeholder="{{ __('content.messagePlaceholder') }}"
                                    class="w-full rounded-md border border-border bg-background px-4 py-2 text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                    required>{{ old('message') }}</textarea>
                            </div>

                            {{-- Submit --}}
                            <button type="submit"
                                class="w-full rounded-lg bg-primary px-6 py-3 font-semibold text-primary-foreground transition-colors duration-200 hover:bg-primary/90">
                                {{ __('content.sendMessage') }}
                            </button>
                        </form>
                    </div>

                    {{-- Contact Info & Quick Links --}}
                    <div class="animate-fade-in space-y-6">
                        <div>
                            <h3 class="mb-5 text-2xl font-semibold text-primary">
                                {{ __('content.contactInformation') }}</h3>

                            <div class="space-y-5">

                                {{-- Phone --}}
                                <div class="flex gap-3">
                                    <div
                                        class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-primary/10">
                                        <svg class="h-5 w-5 text-primary">
                                            <x-lucide-phone class="h-5 w-5 text-primary" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="mb-1 text-base font-semibold">{{ __('content.phoneWhatsApp') }}
                                        </h4>
                                        <a href="tel:+212600000000"
                                            class="text-sm text-muted-foreground transition-colors hover:text-primary">
                                            +212 6XX XXX XXX
                                        </a>
                                    </div>
                                </div>

                                {{-- Email --}}
                                <div class="flex gap-3">
                                    <div
                                        class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-primary/10">
                                        <svg class="h-5 w-5 text-primary">
                                            <x-lucide-mail class="h-5 w-5 text-primary" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="mb-1 text-base font-semibold">{{ __('content.email') }}</h4>
                                        <a href="mailto:contact@amalgym.ma"
                                            class="text-sm text-muted-foreground transition-colors hover:text-primary">
                                            contact@amalgym.ma
                                        </a>
                                    </div>
                                </div>

                                {{-- Address --}}
                                <div class="flex gap-3">
                                    <div
                                        class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-primary/10">
                                        <svg class="h-5 w-5 text-primary">
                                            <x-lucide-map-pin class="h-5 w-5 text-primary" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="mb-1 text-base font-semibold">{{ __('content.address') }}</h4>
                                        <p class="text-sm text-muted-foreground">
                                            Centre Ville<br />
                                            Ouarzazate, Morocco
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Quick Contact --}}
                        <div class="rounded-lg border border-border bg-background p-5">
                            <h4 class="mb-3 text-lg font-semibold">{{ __('content.quickContact') }}</h4>
                            <p class="mb-4 text-sm text-muted-foreground">{{ __('content.quickContactText') }}</p>
                            <a href="https://wa.me/212600000000" target="_blank"
                                class="inline-block w-full rounded-lg border border-primary px-6 py-3 text-center text-primary transition-colors duration-200 hover:bg-primary/10">
                                {{ __('content.messageWhatsApp') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @props(['language' => app()->getLocale()])

        <footer class="{{ $language === 'ar' ? 'font-arabic' : '' }} border-t border-border bg-background">
            <div class="container-custom py-10">

                {{-- Top Grid --}}
                <div class="mb-8 grid gap-8 md:grid-cols-3">

                    {{-- About / Hero --}}
                    <div>
                        <h3 class="mb-3 text-2xl font-bold">{{ __('content.heroTitle') }}</h3>
                        <p class="text-sm text-muted-foreground">{{ __('content.footerTagline') }}</p>
                    </div>

                    {{-- Opening Hours --}}
                    <div>
                        <h4 class="mb-3 text-lg font-semibold">{{ __('content.openingHours') }}</h4>
                        <div class="space-y-1 text-sm text-muted-foreground">
                            <p>{{ __('content.mondayFriday') }} 6:00 - 22:00</p>
                            <p>{{ __('content.saturday') }} 8:00 - 20:00</p>
                            <p>{{ __('content.sunday') }} 9:00 - 18:00</p>
                        </div>
                    </div>

                    {{-- Contact --}}
                    <div>
                        <h4 class="mb-3 text-lg font-semibold">{{ __('content.contact') }}</h4>
                        <div class="space-y-1 text-sm text-muted-foreground">
                            <p>+212 6XX XXX XXX</p>
                            <p>contact@amalgym.ma</p>
                            <p>{{ __('content.heroLocation') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Bottom Section --}}
                <div class="flex flex-col items-center justify-between gap-4 border-t border-border pt-6 md:flex-row">

                    {{-- Copyright --}}
                    <p class="text-xs text-muted-foreground">
                        {{ __('content.copyright', ['year' => now()->year]) }}
                    </p>

                    {{-- Social Icons --}}
                    <div class="flex gap-3">
                        <a href="https://facebook.com" target="_blank" rel="noopener noreferrer"
                            class="flex h-9 w-9 items-center justify-center rounded-full border border-border bg-card transition-colors hover:border-primary hover:bg-primary hover:text-primary-foreground"
                            aria-label="Facebook">
                            <x-lucide-facebook class="h-4 w-4" />
                        </a>

                        <a href="https://instagram.com" target="_blank" rel="noopener noreferrer"
                            class="flex h-9 w-9 items-center justify-center rounded-full border border-border bg-card transition-colors hover:border-primary hover:bg-primary hover:text-primary-foreground"
                            aria-label="Instagram">
                            <x-lucide-instagram class="h-4 w-4" />
                        </a>

                        <a href="https://tiktok.com" target="_blank" rel="noopener noreferrer"
                            class="flex h-9 w-9 items-center justify-center rounded-full border border-border bg-card transition-colors hover:border-primary hover:bg-primary hover:text-primary-foreground"
                            aria-label="TikTok">
                            <x-lucide-music class="h-4 w-4" />
                        </a>
                    </div>

                </div>
            </div>
        </footer>

    </body>

</html>
