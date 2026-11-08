{{--
    pages/home.blade.php
    Daet Listens landing page - Official Complaint Portal
    Mode: Persuade - the visitor decides and acts.
    Stack: Laravel Blade + Tailwind CSS v4
    Visual world: navy/gold civic system, Cormorant Garamond headings,
    DM Sans body. Code-led (no comp generator available).
    Craft-floor: no em dashes, no emojis, inline SVG icons only,
    contrast ≥4.5:1 body text, type consistency, no system display face,
    no thick coloured border-left, no bounce easing, no unicode glyph icons.
--}}
@extends('layouts.app')
@section('title', __('home.title'))

@section('content')

<main class="home-root relative min-h-screen overflow-x-clip bg-navy text-white px-4 pt-[var(--navbar-height)] pb-12">

    {{-- ═══════════════════════════════════════════════════════════════════════════
       HERO
       ══════════════════════════════════════════════════════════════════════════ --}}
    <section class="max-w-7xl mx-auto">
        <div class="relative grid md:grid-cols-2 gap-8 lg:gap-16 items-center px-4 z-10">

            <div class="space-y-6">
                

                <h1 class="text-[clamp(2rem,5vw,3.5rem)] font-display font-bold leading-[1.1] tracking-tight">
                    {{ __('home.hero.title_line_1') }}<br>
                    <span class="text-gold italic block">{{ __('home.hero.title_line_2') }}</span>
                </h1>

                <p class="text-base sm:text-lg leading-relaxed text-white/90 max-w-[52ch]">
                    {{ __('home.hero.description') }}
                </p>

                <div class="flex flex-wrap gap-3 pt-6">
                    <a href="{{ route('complaints.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-6 bg-gold text-navy font-bold text-sm uppercase tracking-tight px-6 py-3 shadow-[0_4px_24px_rgba(201,168,76,0.32)] hover:shadow-[0_10px_32px_rgba(201,168,76,0.48)] hover:-translate-y-1 transition-all duration-200 group">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-1 transition-transform duration-200"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        {{ __('home.hero.file_complaint') }}
                    </a>
                    <a href="{{ route('complaints.track') }}" wire:navigate class="inline-flex items-center gap-2 rounded-6 border border-white/20 text-white/90 font-bold text-sm uppercase tracking-tight px-6 py-3 hover:bg-white/5 hover:border-white/30 hover:-translate-y-1 transition-all duration-200 group">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-1 transition-transform duration-200"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        {{ __('home.hero.track_complaint') }}
                    </a>
                </div>

                <!-- Trust row below CTAs -->
                <div class="flex flex-wrap gap-4 pt-6 text-xs text-white/60">
                    <div class="flex items-center gap-2">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2c3 0 5.5 2.25 5.5 5v1a1 1 0 0 0 1 1h3a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1h-16a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h3a1 1 0 0 1 1-1V7a1 1 0 0 0 1-1c0-2.75 2.25-5 5-5z"/></svg>
                        {{ __('home.hero.identity_protected') }}
                    </div>
                    <div class="flex items-center gap-2">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        {{ __('home.hero.trackable') }}
                    </div>
                    <div class="flex items-center gap-2">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
                        {{ __('home.hero.foi_compliant') }}
                    </div>
                </div>
            </div>

            <!-- Status card (right) -->
            <div class="relative z-10">
                <div class="bg-white rounded-[16px] p-6 shadow-[0_20px_60px_rgba(0,0,0,0.25)] border border-white/10">
                    <div class="flex items-start justify-between mb-4">
                        <span class="text-xs font-bold uppercase text-navy tracking-tighter">{{ __('home.status_card.tracking_number') }}</span>
                        <span class="text-xs font-mono text-navy">DAET-2026-00123</span>
                    </div>

                    <div class="relative mb-6">
                        <div class="absolute left-1/2 top-0 bottom-0 w-[1px] bg-gold/20"></div>
                        <div class="relative z-10">
                            <div class="flex justify-between mb-2">
                                <span class="text-xs font-semibold text-navy">{{ __('home.status_card.received') }}</span>
                                <span class="text-xs font-semibold text-gold">{{ __('home.status_card.resolved') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-white border-2 border-gold flex items-center justify-center">
                                        <span class="text-[10px] text-white font-bold">1</span>
                                    </div>
                                    <span class="text-sm text-navy">Jan 12</span>
                                </div>
                                <div class="flex items-center gap-2 text-right">
                                    <div class="w-6 h-6 rounded-full bg-white border-2 border-gold flex items-center justify-center">
                                        <span class="text-[10px] text-white font-bold">4</span>
                                    </div>
                                    <span class="text-sm text-navy">Jan 21</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gold/10 px-3 py-1.5 text-xs font-bold text-gold border border-gold/20">
                                <span class="w-2 h-2 rounded-full bg-gold"></span>
                                {{ __('home.status_card.resolved') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="absolute inset-0 -z-10 rounded-[20px] blur-[80px] bg-gold/5 opacity-50 animate-[spin_10s_linear_infinite]"></div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════════════════
       STATS STRIP
       ═══════════════════════════════════════════════════════════════════════════ --}}
    <section class="relative bg-navy py-10 sm:py-14 lg:py-16 px-4 sm:px-6">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-white">
                <div class="group relative p-6 rounded-[12px] border border-[#C9A84C]/20 bg-[#142D4F]/50 hover:bg-gold/5 transition-colors duration-300">
                    <span class="text-[clamp(1.5rem,4vw,3rem)] font-bold text-gold leading-none tabular-nums" id="stat-complaints" style="font-family: 'IBM Plex Mono', 'Numeric', ui-monospace, monospace;">0</span>
                    <span class="text-xs font-bold uppercase tracking-tighter text-white/60 mt-1 block">{{ __('home.stats.complaints_filed') }}</span>
                </div>
                <div class="group relative p-6 rounded-[12px] border border-[#C9A84C]/20 bg-[#142D4F]/50 hover:bg-gold/5 transition-colors duration-300">
                    <span class="text-[clamp(1.5rem,4vw,3rem)] font-bold text-gold leading-none tabular-nums" id="stat-resolved" style="font-family: 'IBM Plex Mono', 'Numeric', ui-monospace, monospace;">0</span>
                    <span class="text-xs font-bold uppercase tracking-tighter text-white/60 mt-1 block">{{ __('home.stats.resolved') }}</span>
                </div>
                <div class="group relative p-6 rounded-[12px] border border-[#C9A84C]/20 bg-[#142D4F]/50 hover:bg-gold/5 transition-colors duration-300">
                    <span class="text-[clamp(1.5rem,4vw,3rem)] font-bold text-gold leading-none tabular-nums" id="stat-avg" style="font-family: 'IBM Plex Mono', 'Numeric', ui-monospace, monospace;">0</span>
                    <span class="text-xs font-bold uppercase tracking-tighter text-white/60 mt-1 block">{{ __('home.stats.avg_response') }}</span>
                </div>
                <div class="group relative p-6 rounded-[12px] border border-[#C9A84C]/20 bg-[#142D4F]/50 hover:bg-gold/5 transition-colors duration-300">
                    <span class="text-[clamp(1.5rem,4vw,3rem)] font-bold text-gold leading-none tabular-nums" id="stat-barangays" style="font-family: 'IBM Plex Mono', 'Numeric', ui-monospace, monospace;">0</span>
                    <span class="text-xs font-bold uppercase tracking-tighter text-white/60 mt-1 block">{{ __('home.stats.barangays_served') }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════════════════════════════════════
       HOW IT WORKS
       ═══════════════════════════════════════════════════════════════════════════ --}}
    <section class="relative bg-[#F5F0E8] py-24 px-4 overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="text-center max-w-[56ch] mx-auto mb-16">
                <h2 class="font-display text-[clamp(1.5rem,4vw,2.25rem)] font-bold text-navy leading-[1.15] mb-3 sm:mb-4">{{ __('home.how.title') }}</h2>
                <p class="text-base sm:text-lg text-navy/70 max-w-[44ch] mx-auto">{{ __('home.how.subtitle') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="relative group">
                    <!-- Connector line -->
                    <div class="absolute left-1/2 top-[24px] bottom-[24px] w-[1px] bg-gold/20"></div>
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 rounded-full bg-white border-2 border-gold text-gold font-bold text-xl flex items-center justify-center" style="font-family: 'IBM Plex Mono', 'Numeric', ui-monospace, monospace;">01</div>
                        <div class="flex-1">
                            <h3 class="font-display font-bold text-navy text-lg mb-2">{{ __('home.how.step1_title') }}</h3>
                            <p class="text-sm text-navy/60 leading-1.6">{{ __('home.how.step1_body') }}</p>
                        </div>
                    </div>
                </div>
                <div class="relative group">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 rounded-full bg-white border-2 border-gold text-gold font-bold text-xl flex items-center justify-center" style="font-family: 'IBM Plex Mono', 'Numeric', ui-monospace, monospace;">02</div>
                        <div class="flex-1">
                            <h3 class="font-display font-bold text-navy text-lg mb-2">{{ __('home.how.step2_title') }}</h3>
                            <p class="text-sm text-navy/60 leading-1.6">{{ __('home.how.step2_body') }}</p>
                        </div>
                    </div>
                </div>
                <div class="relative group">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 rounded-full bg-white border-2 border-gold text-gold font-bold text-xl flex items-center justify-center" style="font-family: 'IBM Plex Mono', 'Numeric', ui-monospace, monospace;">03</div>
                        <div class="flex-1">
                            <h3 class="font-display font-bold text-navy text-lg mb-2">{{ __('home.how.step3_title') }}</h3>
                            <p class="text-sm text-navy/60 leading-1.6">{{ __('home.how.step3_body') }}</p>
                        </div>
                    </div>
                </div>
                <div class="relative group">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 rounded-full bg-white border-2 border-gold text-gold font-bold text-xl flex items-center justify-center" style="font-family: 'IBM Plex Mono', 'Numeric', ui-monospace, monospace;">04</div>
                        <div class="flex-1">
                            <h3 class="font-display font-bold text-navy text-lg mb-2">{{ __('home.how.step4_title') }}</h3>
                            <p class="text-sm text-navy/60 leading-1.6">{{ __('home.how.step4_body') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════════════════
       FEATURES
       ═══════════════════════════════════════════════════════════════════════════ --}}
    <section class="relative bg-slate-50 py-12 sm:py-16 lg:py-24 px-4 sm:px-6 overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="text-center max-w-[56ch] mx-auto mb-8 sm:mb-12 lg:mb-16">
                <h2 class="font-display text-[clamp(1.5rem,4vw,2.25rem)] font-bold text-navy leading-[1.15] mb-3 sm:mb-4">{{ __('home.features.title') }}</h2>
                <p class="text-base sm:text-lg text-navy/70 max-w-[44ch] mx-auto">{{ __('home.features.subtitle') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Feature 1 -->
                <div class="group flex items-start gap-4 border-b border-[#C9A84C]/20 py-4 first:pt-0 last:border-0 last:pb-0 hover:bg-white/5 transition-colors duration-300">
                    <div class="w-10 h-10 rounded-lg bg-gold/10 text-gold flex items-center justify-center mb-0 group-hover:bg-gold group-hover:text-white transition-colors duration-300 flex-shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2c3 0 5.5 2.25 5.5 5v1a1 1 0 0 0 1 1h3a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1h-16a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h3a1 1 0 0 1 1-1V7a1 1 0 0 0 1-1c0-2.75 2.25-5 5-5z"/></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-display font-bold text-navy text-lg mb-1">{{ __('home.features.anonymous_title') }}</h3>
                        <p class="text-sm text-navy/60 leading-1.6">{{ __('home.features.anonymous_body') }}</p>
                    </div>
                </div>
                <!-- Feature 2 -->
                <div class="group flex items-start gap-4 border-b border-[#C9A84C]/20 py-4 first:pt-0 last:border-0 last:pb-0 hover:bg-white/5 transition-colors duration-300">
                    <div class="w-10 h-10 rounded-lg bg-gold/10 text-gold flex items-center justify-center mb-0 group-hover:bg-gold group-hover:text-white transition-colors duration-300 flex-shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 18 3 12"/></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-display font-bold text-navy text-lg mb-1">{{ __('home.features.tracking_title') }}</h3>
                        <p class="text-sm text-navy/60 leading-1.6">{{ __('home.features.tracking_body') }}</p>
                    </div>
                </div>
                <!-- Feature 3 -->
                <div class="group flex items-start gap-4 border-b border-[#C9A84C]/20 py-4 first:pt-0 last:border-0 last:pb-0 hover:bg-white/5 transition-colors duration-300">
                    <div class="w-10 h-10 rounded-lg bg-gold/10 text-gold flex items-center justify-center mb-0 group-hover:bg-gold group-hover:text-white transition-colors duration-300 flex-shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-display font-bold text-navy text-lg mb-1">{{ __('home.features.photos_title') }}</h3>
                        <p class="text-sm text-navy/60 leading-1.6">{{ __('home.features.photos_body') }}</p>
                    </div>
                </div>
                <!-- Feature 4 -->
                <div class="group flex items-start gap-4 border-b border-[#C9A84C]/20 py-4 first:pt-0 last:border-0 last:pb-0 hover:bg-white/5 transition-colors duration-300">
                    <div class="w-10 h-10 rounded-lg bg-gold/10 text-gold flex items-center justify-center mb-0 group-hover:bg-gold group-hover:text-white transition-colors duration-300 flex-shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-display font-bold text-navy text-lg mb-1">{{ __('home.features.routing_title') }}</h3>
                        <p class="text-sm text-navy/60 leading-1.6">{{ __('home.features.routing_body') }}</p>
                    </div>
                </div>
                <!-- Feature 5 -->
                <div class="group flex items-start gap-4 border-b border-[#C9A84C]/20 py-4 first:pt-0 last:border-0 last:pb-0 hover:bg-white/5 transition-colors duration-300">
                    <div class="w-10 h-10 rounded-lg bg-gold/10 text-gold flex items-center justify-center mb-0 group-hover:bg-gold group-hover:text-white transition-colors duration-300 flex-shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 18 12 12"/></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-display font-bold text-navy text-lg mb-1">{{ __('home.features.status_title') }}</h3>
                        <p class="text-sm text-navy/60 leading-1.6">{{ __('home.features.status_body') }}</p>
                    </div>
                </div>
                <!-- Feature 6 -->
                <div class="group flex items-start gap-4 py-4 first:pt-0 last:border-0 last:pb-0 hover:bg-white/5 transition-colors duration-300">
                    <div class="w-10 h-10 rounded-lg bg-gold/10 text-gold flex items-center justify-center mb-0 group-hover:bg-gold group-hover:text-white transition-colors duration-300 flex-shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-display font-bold text-navy text-lg mb-1">{{ __('home.features.feedback_title') }}</h3>
                        <p class="text-sm text-navy/60 leading-1.6">{{ __('home.features.feedback_body') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════════════════
       PRIVACY &amp; TRANSPARENCY
       ═══════════════════════════════════════════════════════════════════════════ --}}
    <section class="relative bg-navy text-white py-12 sm:py-16 lg:py-24 px-4 sm:px-6 overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-8 lg:gap-12 items-center">
                <div>
                    <h2 class="font-display text-[clamp(1.5rem,4vw,2.25rem)] font-bold text-white mb-4 sm:mb-6">{{ __('home.privacy.title') }}</h2>
                    <ul class="space-y-3 sm:space-y-4 text-base sm:text-lg text-white/90 leading-relaxed">
                        <li class="flex items-start gap-3">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 text-gold mt-0.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"/></svg>
                            <span>{{ __('home.privacy.identity') }}</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 text-gold mt-0.5"><polyline points="22 12 18 12 15 21 9 18 3 12"/></svg>
                            <span>{{ __('home.privacy.alias') }}</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 text-gold mt-0.5"><path d="M20 14H2v2h18v-2z"/><path d="M8 15h8"/></svg>
                            <span>{{ __('home.privacy.data') }}</span>
                        </li>
                    </ul>
                </div>
                <div class="bg-[#142D4F] border border-[#C9A84C]/30 rounded-[16px] p-8 shadow-2xl shadow-navy/40">
                    <div class="flex items-center justify-between mb-6">
                        <span class="text-xs font-bold uppercase text-gold tracking-tighter">{{ __('home.privacy.visibility_settings') }}</span>
                        <div class="flex gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#142D4F] border border-white"></span>
                            <span class="text-xs text-white/50">{{ __('home.privacy.private') }}</span>
                            <span class="text-xs text-white/50">{{ __('home.privacy.public') }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="radio" name="visibility" id="vis-private" class="w-4 h-4 rounded-full border-2 border-gold checked:bg-gold checked:border-gold text-gold focus:ring-2 focus:ring-gold/50" checked>
                        <label class="text-sm text-white/90 cursor-pointer">{{ __('home.privacy.private_option') }}</label>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="radio" name="visibility" id="vis-anonymous" class="w-4 h-4 rounded-full border-2 border-gold checked:bg-gold checked:border-gold text-gold focus:ring-2 focus:ring-gold/50">
                        <label class="text-sm text-white/90 cursor-pointer">{{ __('home.privacy.anonymous_option') }}</label>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="radio" name="visibility" id="vis-public" class="w-4 h-4 rounded-full border-2 border-gold checked:bg-gold checked:border-gold text-gold focus:ring-2 focus:ring-gold/50">
                        <label class="text-sm text-white/90 cursor-pointer">{{ __('home.privacy.public_option') }}</label>
                    </div>
                    <p class="mt-4 text-sm text-white/50">{{ __('home.privacy.note') }}</p>
                    <a href="{{ route('transparency') }}" wire:navigate class="mt-6 inline-flex items-center gap-2 text-sm font-bold text-gold hover:text-white transition-colors">
                        {{ __('home.privacy.view_register') }} &rarr;
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════════════════
       FAQ
       ═══════════════════════════════════════════════════════════════════════════ --}}
    <section class="relative bg-white py-12 sm:py-16 lg:py-24 px-4 sm:px-6 overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="text-center max-w-[56ch] mx-auto mb-8 sm:mb-12 lg:mb-16">
                <h2 class="font-display text-[clamp(1.5rem,4vw,2.25rem)] font-bold text-navy leading-[1.15] mb-3 sm:mb-4">{{ __('home.faq.title') }}</h2>
                <p class="text-base sm:text-lg text-navy/70 max-w-[44ch] mx-auto">{{ __('home.faq.subtitle') }}</p>
            </div>

            <div class="max-w-[56ch] mx-auto space-y-3">
                <details class="border border-[#C9A84C]/20 rounded-[12px] bg-white overflow-hidden">
                    <summary class="flex items-center justify-between p-4 cursor-pointer list-none" role="button" tabindex="0">
                        <span class="font-bold text-navy">{{ __('home.faq.q1') }}</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                    </div>
                    <div class="px-4 pb-4 text-sm text-navy/70 leading-1.6">
                        {{ __('home.faq.a1') }}
                    </p>
                </details>
                <div class="border border-[#C9A84C]/20 rounded-[12px] bg-white overflow-hidden">
                    <summary class="flex items-center justify-between p-4 cursor-pointer list-none" role="button" tabindex="0">
                        <span class="font-bold text-navy">{{ __('home.faq.q2') }}</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                    </summary>
                    <div class="px-4 pb-4 text-sm text-navy/70 leading-1.6">
                        {{ __('home.faq.a2') }}
                    </p>
                </details>
                <div class="border border-[#C9A84C]/20 rounded-[12px] bg-white overflow-hidden">
                    <summary class="flex items-center justify-between p-4 cursor-pointer list-none" role="button" tabindex="0">
                        <span class="font-bold text-navy">{{ __('home.faq.q3') }}</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                    </summary>
                    <div class="px-4 pb-4 text-sm text-navy/70 leading-1.6">
                        {{ __('home.faq.a3') }}
                    </p>
                </details>
                <div class="border border-[#C9A84C]/20 rounded-[12px] bg-white overflow-hidden">
                    <summary class="flex items-center justify-between p-4 cursor-pointer list-none" role="button" tabindex="0">
                        <span class="font-bold text-navy">{{ __('home.faq.q4') }}</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                    </summary>
                    <div class="px-4 pb-4 text-sm text-navy/70 leading-1.6">
                        {{ __('home.faq.a4') }}
                    </p>
                </details>
                <div class="border border-[#C9A84C]/20 rounded-[12px] bg-white overflow-hidden">
                    <summary class="flex items-center justify-between p-4 cursor-pointer list-none" role="button" tabindex="0">
                        <span class="font-bold text-navy">{{ __('home.faq.q5') }}</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                    </summary>
                    <div class="px-4 pb-4 text-sm text-navy/70 leading-1.6">
                        {{ __('home.faq.a5') }}
                    </p>
                </details>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════════════════
       FINAL CTA
       ═══════════════════════════════════════════════════════════════════════════ --}}
    <section class="relative bg-navy py-12 sm:py-16 lg:py-20 px-4 sm:px-6 overflow-hidden">
        <div class="absolute inset-0 -z-10 opacity-20 overflow-hidden">
            <div class="absolute -top-[10%] -right-[10%] w-[80vw] h-[80vw] max-w-[700px] max-h-[700px] rounded-full blur-[100px] bg-gold/10 animate-[spin_14s_linear_infinite]"></div>
        </div>

        <div class="max-w-7xl mx-auto relative z-10">
            <div class="text-center max-w-[56ch] mx-auto">
                <h2 class="font-display text-[clamp(1.5rem,4vw,2.25rem)] font-bold text-white leading-[1.15] mb-3 sm:mb-4">{{ __('home.cta.title') }}</h2>
                <p class="text-base sm:text-lg text-white/70 max-w-[44ch] mx-auto">{{ __('home.cta.subtitle') }}</p>
            </div>
            <div class="mt-6 sm:mt-8 lg:mt-10">
                <a href="{{ route('complaints.create') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-6 bg-gold text-navy font-bold text-sm uppercase tracking-tight px-8 py-4 shadow-[0_4px_24px_rgba(201,168,76,0.32)] hover:shadow-[0_10px_32px_rgba(201,168,76,0.48)] hover:-translate-y-1 transition-all duration-200 group">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-1 transition-transform duration-200"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    {{ __('home.cta.button') }}
                </a>
            </div>
        </div>
    </section>

</main>

<style>
    .stat-value {
        transition: all 0.5s ease-out;
    }
    .glow-pulse {
        animation: pulse-glow 6s ease-in-out infinite;
    }
    @keyframes pulse-glow {
        0%, 100% { opacity: 0.6; transform: scale(1); }
        50% { opacity: 0.8; transform: scale(1.05); }
    }
    details[open] summary {
        border-bottom-color: var(--color-gold);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Stats counter animation
        const stats = [
            { id: 'stat-complaints', target: 1247 },
            { id: 'stat-resolved', target: 891 },
            { id: 'stat-avg', target: 7.2, suffix: @json(' ' . __('home.stats.days')) },
            { id: 'stat-barangays', target: 25 }
        ];

        const duration = 2000;
        const startTime = performance.now();

        function animateStats(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const ease = 1 - Math.pow(1 - progress, 3);

            stats.forEach(stat => {
                const el = document.getElementById(stat.id);
                if (!el) return;
                const current = Math.floor(stat.target * ease);
                el.textContent = stat.suffix ? current + stat.suffix : current;
            });

            if (progress < 1) {
                requestAnimationFrame(animateStats);
            }
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    requestAnimationFrame(animateStats);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.2 });

        const statsSection = document.querySelector('.bg-navy > div');
        if (statsSection) {
            observer.observe(statsSection);
        }

        // FAQ accordion accessibility
        document.querySelectorAll('details summary').forEach(btn => {
            btn.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    const detail = this.parentElement;
                    if (detail) detail.open = !detail.open;
                }
            });
        });
    });
</script>
@endsection