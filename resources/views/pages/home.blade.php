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
@section('title', 'Daet Listens - File a Complaint')

@section('content')

<main class="home-root relative min-h-screen overflow-x-clip bg-navy text-white px-4 pt-[var(--navbar-height)] pb-12">

    {{-- ═══════════════════════════════════════════════════════════════════════════
       HERO
       ══════════════════════════════════════════════════════════════════════════ --}}
    <section class="max-w-7xl mx-auto">
        <div class="relative grid md:grid-cols-2 gap-8 lg:gap-16 items-center px-4 z-10">

            <div class="space-y-6">
                

                <h1 class="text-[clamp(3rem,6vw,5rem)] font-display font-bold leading-[1.05] tracking-tight">
                    Your Voice.<br>
                    <span class="text-gold italic block">Our Accountability.</span>
                </h1>

                <p class="text-lg leading-1.85 text-white/90 max-w-[52ch]">
                    A direct, secure, and trackable channel for every Daet resident
                    to raise concerns with the local government. Your identity is
                    protected &mdash; you choose what the public sees.
                </p>

                <div class="flex flex-wrap gap-3 pt-6">
                    <a href="{{ route('complaints.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-6 bg-gold text-navy font-bold text-sm uppercase tracking-tight px-6 py-3 shadow-[0_4px_24px_rgba(201,168,76,0.32)] hover:shadow-[0_10px_32px_rgba(201,168,76,0.48)] hover:-translate-y-1 transition-all duration-200 group">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-1 transition-transform duration-200"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        File a Complaint
                    </a>
                    <a href="{{ route('complaints.track') }}" wire:navigate class="inline-flex items-center gap-2 rounded-6 border border-white/20 text-white/90 font-bold text-sm uppercase tracking-tight px-6 py-3 hover:bg-white/5 hover:border-white/30 hover:-translate-y-1 transition-all duration-200 group">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-1 transition-transform duration-200"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Track My Complaint
                    </a>
                </div>

                <!-- Trust row below CTAs -->
                <div class="flex flex-wrap gap-4 pt-6 text-xs text-white/60">
                    <div class="flex items-center gap-2">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2c3 0 5.5 2.25 5.5 5v1a1 1 0 0 0 1 1h3a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1h-16a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h3a1 1 0 0 1 1-1V7a1 1 0 0 0 1-1c0-2.75 2.25-5 5-5z"/></svg>
                        Identity protected
                    </div>
                    <div class="flex items-center gap-2">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Trackable
                    </div>
                    <div class="flex items-center gap-2">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
                        RA 6713 &middot; FOI compliant
                    </div>
                </div>
            </div>

            <!-- Status card (right) -->
            <div class="relative z-10">
                <div class="bg-white rounded-[16px] p-6 shadow-[0_20px_60px_rgba(0,0,0,0.25)] border border-white/10">
                    <div class="flex items-start justify-between mb-4">
                        <span class="text-xs font-bold uppercase text-navy tracking-tighter">Tracking number</span>
                        <span class="text-xs font-mono text-navy">DAET-2026-00123</span>
                    </div>

                    <div class="relative mb-6">
                        <div class="absolute left-1/2 top-0 bottom-0 w-[1px] bg-gold/20"></div>
                        <div class="relative z-10">
                            <div class="flex justify-between mb-2">
                                <span class="text-xs font-semibold text-navy">Received</span>
                                <span class="text-xs font-semibold text-gold">Resolved</span>
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
                                Resolved
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
    <section class="relative bg-navy py-16 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-white">
                <div class="group relative p-6 rounded-[12px] border border-[#C9A84C]/20 bg-[#0B1F3A]/50 hover:bg-gold/5 transition-colors duration-300">
                    <span class="text-[clamp(1.5rem,4vw,3rem)] font-display font-bold text-gold leading-none" id="stat-complaints">0</span>
                    <span class="text-xs font-bold uppercase tracking-tighter text-white/60 mt-1 block">Complaints Filed</span>
                </div>
                <div class="group relative p-6 rounded-[12px] border border-[#C9A84C]/20 bg-[#0B1F3A]/50 hover:bg-gold/5 transition-colors duration-300">
                    <span class="text-[clamp(1.5rem,4vw,3rem)] font-display font-bold text-gold leading-none" id="stat-resolved">0</span>
                    <span class="text-xs font-bold uppercase tracking-tighter text-white/60 mt-1 block">Resolved</span>
                </div>
                <div class="group relative p-6 rounded-[12px] border border-[#C9A84C]/20 bg-[#0B1F3A]/50 hover:bg-gold/5 transition-colors duration-300">
                    <span class="text-[clamp(1.5rem,4vw,3rem)] font-display font-bold text-gold leading-none" id="stat-avg">0</span>
                    <span class="text-xs font-bold uppercase tracking-tighter text-white/60 mt-1 block">Avg. Response Time</span>
                </div>
                <div class="group relative p-6 rounded-[12px] border border-[#C9A84C]/20 bg-[#0B1F3A]/50 hover:bg-gold/5 transition-colors duration-300">
                    <span class="text-[clamp(1.5rem,4vw,3rem)] font-display font-bold text-gold leading-none" id="stat-barangays">0</span>
                    <span class="text-xs font-bold uppercase tracking-tighter text-white/60 mt-1 block">Barangays Served</span>
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
                <h2 class="font-display text-[clamp(2rem,5vw,3rem)] font-bold text-navy leading-[1.1] mb-4">How it works</h2>
                <p class="text-lg text-navy/70 max-w-[44ch] mx-auto">Filing is straightforward and transparent. Every concern receives proper attention.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="relative group">
                    <!-- Connector line -->
                    <div class="absolute left-1/2 top-[24px] bottom-[24px] w-[1px] bg-gold/20"></div>
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 rounded-full bg-white border-2 border-gold text-gold font-display font-bold text-xl flex items-center justify-center">01</div>
                        <div class="flex-1">
                            <h3 class="font-display font-bold text-navy text-lg mb-2">Submit your complaint</h3>
                            <p class="text-sm text-navy/60 leading-1.6">Complete the form with your concern, the relevant department, and any supporting documents.</p>
                        </div>
                    </div>
                </div>
                <div class="relative group">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 rounded-full bg-white border-2 border-gold text-gold font-display font-bold text-xl flex items-center justify-center">02</div>
                        <div class="flex-1">
                            <h3 class="font-display font-bold text-navy text-lg mb-2">Receive a reference</h3>
                            <p class="text-sm text-navy/60 leading-1.6">A unique tracking number is issued instantly so you can monitor your complaint's status at any time.</p>
                        </div>
                    </div>
                </div>
                <div class="relative group">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 rounded-full bg-white border-2 border-gold text-gold font-display font-bold text-xl flex items-center justify-center">03</div>
                        <div class="flex-1">
                            <h3 class="font-display font-bold text-navy text-lg mb-2">LGU reviews &amp; acts</h3>
                            <p class="text-sm text-navy/60 leading-1.6">The appropriate office investigates and takes corrective action within the mandated timeframe.</p>
                        </div>
                    </div>
                </div>
                <div class="relative group">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 rounded-full bg-white border-2 border-gold text-gold font-display font-bold text-xl flex items-center justify-center">04</div>
                        <div class="flex-1">
                            <h3 class="font-display font-bold text-navy text-lg mb-2">Resolution &amp; feedback</h3>
                            <p class="text-sm text-navy/60 leading-1.6">You are notified once resolved. Your feedback helps improve public service delivery.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════════════════
       FEATURES
       ═══════════════════════════════════════════════════════════════════════════ --}}
    <section class="relative bg-white py-24 px-4 overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="text-center max-w-[56ch] mx-auto mb-16">
                <h2 class="font-display text-[clamp(2rem,5vw,3rem)] font-bold text-navy leading-[1.1] mb-4">Features</h2>
                <p class="text-lg text-navy/70 max-w-[44ch] mx-auto">Everything you need to file, track, and trust the process.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Feature 1 -->
                <div class="group flex items-start gap-4 border-b border-[#C9A84C]/20 py-4 first:pt-0 last:border-0 last:pb-0 hover:bg-white/5 transition-colors duration-300">
                    <div class="w-10 h-10 rounded-lg bg-gold/10 text-gold flex items-center justify-center mb-0 group-hover:bg-gold group-hover:text-white transition-colors duration-300 flex-shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2c3 0 5.5 2.25 5.5 5v1a1 1 0 0 0 1 1h3a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1h-16a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h3a1 1 0 0 1 1-1V7a1 1 0 0 0 1-1c0-2.75 2.25-5 5-5z"/></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-display font-bold text-navy text-lg mb-1">Anonymous or named filing</h3>
                        <p class="text-sm text-navy/60 leading-1.6">Choose whether to file under your name or a display alias. Your identity stays protected.</p>
                    </div>
                </div>
                <!-- Feature 2 -->
                <div class="group flex items-start gap-4 border-b border-[#C9A84C]/20 py-4 first:pt-0 last:border-0 last:pb-0 hover:bg-white/5 transition-colors duration-300">
                    <div class="w-10 h-10 rounded-lg bg-gold/10 text-gold flex items-center justify-center mb-0 group-hover:bg-gold group-hover:text-white transition-colors duration-300 flex-shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 18 3 12"/></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-display font-bold text-navy text-lg mb-1">Unique tracking number</h3>
                        <p class="text-sm text-navy/60 leading-1.6">Every complaint gets a tracking number so you can follow its progress at any time.</p>
                    </div>
                </div>
                <!-- Feature 3 -->
                <div class="group flex items-start gap-4 border-b border-[#C9A84C]/20 py-4 first:pt-0 last:border-0 last:pb-0 hover:bg-white/5 transition-colors duration-300">
                    <div class="w-10 h-10 rounded-lg bg-gold/10 text-gold flex items-center justify-center mb-0 group-hover:bg-gold group-hover:text-white transition-colors duration-300 flex-shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-display font-bold text-navy text-lg mb-1">Attach photos &amp; documents</h3>
                        <p class="text-sm text-navy/60 leading-1.6">Upload photos and supporting documents to strengthen your complaint.</p>
                    </div>
                </div>
                <!-- Feature 4 -->
                <div class="group flex items-start gap-4 border-b border-[#C9A84C]/20 py-4 first:pt-0 last:border-0 last:pb-0 hover:bg-white/5 transition-colors duration-300">
                    <div class="w-10 h-10 rounded-lg bg-gold/10 text-gold flex items-center justify-center mb-0 group-hover:bg-gold group-hover:text-white transition-colors duration-300 flex-shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-display font-bold text-navy text-lg mb-1">Department routing</h3>
                        <p class="text-sm text-navy/60 leading-1.6">Complaints are automatically directed to the correct LGU department.</p>
                    </div>
                </div>
                <!-- Feature 5 -->
                <div class="group flex items-start gap-4 border-b border-[#C9A84C]/20 py-4 first:pt-0 last:border-0 last:pb-0 hover:bg-white/5 transition-colors duration-300">
                    <div class="w-10 h-10 rounded-lg bg-gold/10 text-gold flex items-center justify-center mb-0 group-hover:bg-gold group-hover:text-white transition-colors duration-300 flex-shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 18 12 12"/></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-display font-bold text-navy text-lg mb-1">Real-time status updates</h3>
                        <p class="text-sm text-navy/60 leading-1.6">Follow your complaint from submission through investigation to resolution.</p>
                    </div>
                </div>
                <!-- Feature 6 -->
                <div class="group flex items-start gap-4 py-4 first:pt-0 last:border-0 last:pb-0 hover:bg-white/5 transition-colors duration-300">
                    <div class="w-10 h-10 rounded-lg bg-gold/10 text-gold flex items-center justify-center mb-0 group-hover:bg-gold group-hover:text-white transition-colors duration-300 flex-shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-display font-bold text-navy text-lg mb-1">Feedback &amp; rating</h3>
                        <p class="text-sm text-navy/60 leading-1.6">Rate your experience after resolution to help improve public service.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════════════════
       PRIVACY &amp; TRANSPARENCY
       ═══════════════════════════════════════════════════════════════════════════ --}}
    <section class="relative bg-navy text-white py-24 px-4 overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="font-display text-[clamp(2rem,5vw,3rem)] font-bold text-white mb-6">You control what the public sees</h2>
                    <ul class="space-y-4 text-lg text-white/90 leading-1.7">
                        <li class="flex items-start gap-3">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 text-gold mt-0.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"/></svg>
                            <span>Your identity is protected. Reports are handled confidentially, and you choose what the public sees.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 text-gold mt-0.5"><polyline points="22 12 18 12 15 21 9 18 3 12"/></svg>
                            <span>File under a display alias instead of your real name.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 text-gold mt-0.5"><path d="M20 14H2v2h18v-2z"/><path d="M8 15h8"/></svg>
                            <span>Resolution data is published publicly, holding each office accountable to transparent performance.</span>
                        </li>
                    </ul>
                </div>
                <div class="bg-[#0B1F3A] border border-[#C9A84C]/30 rounded-[16px] p-8 shadow-2xl shadow-navy/40">
                    <div class="flex items-center justify-between mb-6">
                        <span class="text-xs font-bold uppercase text-gold tracking-tighter">Visibility settings</span>
                        <div class="flex gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#0B1F3A] border border-white"></span>
                            <span class="text-xs text-white/50">Private</span>
                            <span class="text-xs text-white/50">Public</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="radio" name="visibility" id="vis-private" class="w-4 h-4 rounded-full border-2 border-gold checked:bg-gold checked:border-gold text-gold focus:ring-2 focus:ring-gold/50" checked>
                        <label class="text-sm text-white/90 cursor-pointer">Private - only you and the department</label>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="radio" name="visibility" id="vis-anonymous" class="w-4 h-4 rounded-full border-2 border-gold checked:bg-gold checked:border-gold text-gold focus:ring-2 focus:ring-gold/50">
                        <label class="text-sm text-white/90 cursor-pointer">Anonymous - barangay alias, no real name</label>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="radio" name="visibility" id="vis-public" class="w-4 h-4 rounded-full border-2 border-gold checked:bg-gold checked:border-gold text-gold focus:ring-2 focus:ring-gold/50">
                        <label class="text-sm text-white/90 cursor-pointer">Public - visible on the transparency register</label>
                    </div>
                    <p class="mt-4 text-sm text-white/50">You can change your visibility choice at any time. Department moderation cannot override your decision.</p>
                    <a href="{{ route('transparency') }}" wire:navigate class="mt-6 inline-flex items-center gap-2 text-sm font-bold text-gold hover:text-white transition-colors">
                        View the transparency register &rarr;
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════════════════
       FAQ
       ═══════════════════════════════════════════════════════════════════════════ --}}
    <section class="relative bg-[#F5F0E8] py-24 px-4 overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="text-center max-w-[56ch] mx-auto mb-16">
                <h2 class="font-display text-[clamp(2rem,5vw,3rem)] font-bold text-navy leading-[1.1] mb-4">Frequently asked questions</h2>
                <p class="text-lg text-navy/70 max-w-[44ch] mx-auto">Common questions about filing and tracking complaints with the LGU Daet.</p>
            </div>

            <div class="max-w-[56ch] mx-auto space-y-3">
                <details class="border border-[#C9A84C]/20 rounded-[12px] bg-white overflow-hidden">
                    <summary class="flex items-center justify-between p-4 cursor-pointer list-none" role="button" tabindex="0">
                        <span class="font-bold text-navy">Who can file a complaint?</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                    </div>
                    <div class="px-4 pb-4 text-sm text-navy/70 leading-1.6">
                        Any resident of Daet, Camarines Norte can file a complaint about municipal services, infrastructure, or public utilities. No identification is required if you file anonymously.
                    </p>
                </details>
                <div class="border border-[#C9A84C]/20 rounded-[12px] bg-white overflow-hidden">
                    <summary class="flex items-center justify-between p-4 cursor-pointer list-none" role="button" tabindex="0">
                        <span class="font-bold text-navy">Is it really anonymous?</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                    </summary>
                    <div class="px-4 pb-4 text-sm text-navy/70 leading-1.6">
                        Yes. When you file anonymously, we use a display alias instead of your real name. Your contact details are never shown on the public transparency feed.
                    </p>
                </details>
                <div class="border border-[#C9A84C]/20 rounded-[12px] bg-white overflow-hidden">
                    <summary class="flex items-center justify-between p-4 cursor-pointer list-none" role="button" tabindex="0">
                        <span class="font-bold text-navy">How long until I get a response?</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                    </summary>
                    <div class="px-4 pb-4 text-sm text-navy/70 leading-1.6">
                        The department reviews your complaint and typically responds within the mandated timeframe. You will receive updates through the tracking system.
                    </p>
                </details>
                <div class="border border-[#C9A84C]/20 rounded-[12px] bg-white overflow-hidden">
                    <summary class="flex items-center justify-between p-4 cursor-pointer list-none" role="button" tabindex="0">
                        <span class="font-bold text-navy">What can I complain about?</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                    </summary>
                    <div class="px-4 pb-4 text-sm text-navy/70 leading-1.6">
                        Road damage, drainage, street lighting, waste collection, and other municipal services. If it affects the public, you can report it.
                    </p>
                </details>
                <div class="border border-[#C9A84C]/20 rounded-[12px] bg-white overflow-hidden">
                    <summary class="flex items-center justify-between p-4 cursor-pointer list-none" role="button" tabindex="0">
                        <span class="font-bold text-navy">How do I track my complaint?</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                    </summary>
                    <div class="px-4 pb-4 text-sm text-navy/70 leading-1.6">
                        Use the tracking number you received when you filed. Enter it on the transparency page to see the current status of your complaint.
                    </p>
                </details>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════════════════
       FINAL CTA
       ═══════════════════════════════════════════════════════════════════════════ --}}
    <section class="relative bg-navy py-20 px-4 overflow-hidden">
        <div class="absolute inset-0 -z-10 opacity-20 overflow-hidden">
            <div class="absolute -top-[10%] -right-[10%] w-[80vw] h-[80vw] max-w-[700px] max-h-[700px] rounded-full blur-[100px] bg-gold/10 animate-[spin_14s_linear_infinite]"></div>
        </div>

        <div class="max-w-7xl mx-auto relative z-10">
            <div class="text-center max-w-[56ch] mx-auto">
                <h2 class="font-display text-[clamp(2rem,5vw,3rem)] font-bold text-white leading-[1.1] mb-4">Ready to file a complaint?</h2>
                <p class="text-lg text-white/70 max-w-[44ch] mx-auto">Your voice matters. File it today and hold your local government to account.</p>
            </div>
            <div class="mt-10">
                <a href="{{ route('complaints.create') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-6 bg-gold text-navy font-bold text-sm uppercase tracking-tight px-8 py-4 shadow-[0_4px_24px_rgba(201,168,76,0.32)] hover:shadow-[0_10px_32px_rgba(201,168,76,0.48)] hover:-translate-y-1 transition-all duration-200 group">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-1 transition-transform duration-200"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    File a Complaint Now
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
            { id: 'stat-avg', target: 7.2, suffix: ' days' },
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