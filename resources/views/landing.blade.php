<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Bimbel Gracia - Bimbingan Belajar Privat & Kelas</title>
        <link rel="icon" type="image/jpeg" href="{{ asset('storage/website/logo_bimbel.jpg') }}" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&family=IBM+Plex+Sans:wght@400;500;600&display=swap');

            :root {
                /* Brand colors from logo */
                --color-primary:        #6997eb;
                --color-primary-hover:  #5078c8;
                --color-primary-light:  #dce8fc;

                --color-secondary:       #98c73f;
                --color-secondary-hover: #7aa82e;
                --color-secondary-light: #dff3c8;

                /* Warm orange-oranye — replaces the old alarm-red accent */
                --color-accent:         #d97b3f;
                --color-accent-hover:   #c0622a;
                --color-accent-light:   #fdeee7;

                --color-danger:         #dc2817;

                /* Dark section background */
                --color-dark-bg:        #51946e;
                --color-dark-bg-mid:    #5a9e78;
                --color-dark-bg-deep:   #40735a;

                /* Warm / muted tones */
                --color-warm-soft:      #db9e8b;
                --color-warm-soft-light: #fdf0ed;
                --color-muted:          #8397b4;
                --color-muted-light:    #eef1f7;

                /* Neutrals */
                --bg-main:             #FFFFFF;
                --bg-neutral:          #eaece5;
                --surface:             #FFFFFF;
                --text-main:           #173331;
                --text-secondary:      #667A77;
                --text-muted:          #8fa8a5;
                --border:              #DCE8E5;
                --border-light:        #edf3f1;

                /* Shadows & radii */
                --shadow-sm: 0 1px 3px rgba(7, 43, 40, 0.06);
                --shadow-md: 0 4px 12px rgba(7, 43, 40, 0.08);
                --shadow-lg: 0 8px 24px rgba(7, 43, 40, 0.1);
                --radius: 16px;
                --radius-sm: 10px;
            }

            * { box-sizing: border-box; }

            body {
                font-family: 'IBM Plex Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
                background: var(--bg-main);
                color: var(--text-main);
                margin: 0;
                -webkit-font-smoothing: antialiased;
            }

            /* Full-bleed section — extends edge to edge */
            .section-bleed {
                margin-left: -1.5rem;
                margin-right: -1.5rem;
            }

            /* Colored section wrapper — gives rounded corners to full-bleed bg */
            .section-rounded {
                border-radius: 1.5rem;
                padding-top: 4rem;
                padding-bottom: 4rem;
            }

            h1, h2, h3 {
                font-family: 'Space Grotesk', ui-sans-serif, system-ui, -apple-system, sans-serif;
                letter-spacing: -0.02em;
                color: var(--text-main);
            }

            .float { animation: float 8s ease-in-out infinite; }
            @keyframes float {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-12px); }
            }

            .reveal { animation: fadeUp 0.9s ease both; }
            @keyframes fadeUp {
                from { opacity: 0; transform: translateY(12px); }
                to   { opacity: 1; transform: translateY(0); }
            }

            ::-webkit-scrollbar { width: 6px; }
            ::-webkit-scrollbar-track { background: var(--bg-main); }
            ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

            /* Base card */
            .card {
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: var(--radius);
                box-shadow: var(--shadow-sm);
                transition: box-shadow 0.2s;
            }
            .card:hover { box-shadow: var(--shadow-md); }

            /* Badge variants */
            .badge-primary {
                background: var(--color-primary-light);
                color: var(--color-primary);
            }
            .badge-secondary {
                background: var(--color-secondary-light);
                color: var(--color-secondary-hover);
            }
            .badge-accent {
                background: var(--color-accent-light);
                color: var(--color-accent);
            }
            .badge-warm {
                background: var(--color-warm-soft-light);
                color: var(--color-warm-soft);
            }
            .badge-muted {
                background: var(--color-muted-light);
                color: var(--color-muted);
            }

            /* CTA — WhatsApp / accent */
            .btn-coral {
                background: var(--color-accent);
                color: #fff;
                border-radius: 999px;
                padding: 0.65rem 1.75rem;
                font-weight: 600;
                font-size: 0.95rem;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                text-decoration: none;
                transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
                box-shadow: 0 4px 14px rgba(217, 123, 63, 0.3);
            }
            .btn-coral:hover {
                background: var(--color-accent-hover);
                transform: translateY(-1px);
                box-shadow: 0 6px 20px rgba(217, 123, 63, 0.35);
            }

            /* Outline button — primary */
            .btn-outline {
                border: 1.5px solid var(--color-primary);
                color: var(--color-primary);
                border-radius: 999px;
                padding: 0.5rem 1.25rem;
                font-weight: 600;
                font-size: 0.875rem;
                text-decoration: none;
                transition: background 0.2s, color 0.2s;
                display: inline-flex;
                align-items: center;
            }
            .btn-outline:hover {
                background: var(--color-primary);
                color: #fff;
            }

            .tag {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.4rem 0.9rem;
                border-radius: 999px;
                font-size: 0.8rem;
                font-weight: 500;
                background: var(--surface);
                border: 1px solid var(--border);
                color: var(--text-secondary);
            }

            .section-label {
                font-size: 0.7rem;
                font-weight: 700;
                letter-spacing: 0.4em;
                text-transform: uppercase;
            }

            /* Step number bubbles for Metode — solid for maximum contrast */
            .step-primary {
                background: var(--color-primary);
                color: #fff;
            }
            .step-secondary {
                background: var(--color-secondary);
                color: #fff;
            }

            /* Floating card in dark-bg sections (Metode, Kontak) */
            .card-dark {
                background: rgba(255, 255, 255, 0.09);
                border: 1px solid rgba(255, 255, 255, 0.14);
                border-radius: var(--radius);
            }
        </style>
    </head>
    <body class="min-h-screen">

        <!-- ========== NAVBAR ========== -->
        <header style="background: var(--surface); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 50;">
            <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl overflow-hidden shadow-sm flex items-center justify-center bg-white">
                        <img src="{{ asset('storage/website/logo_bimbel.jpg') }}" alt="Bimbel Gracia" class="w-full h-full object-contain" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-[0.4em] font-bold" style="color: var(--color-primary);">Bimbel Gracia</p>
                        <p class="text-sm font-semibold leading-tight" style="color: var(--text-main);">Bimbingan Belajar Privat & Kelas</p>
                    </div>
                </div>

                <button id="mobile-menu-btn" class="md:hidden p-2 rounded-lg" style="color: var(--text-main);" aria-label="Menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <nav id="mobile-menu" class="hidden md:flex flex-col md:flex-row items-start md:items-center gap-1 md:gap-2 absolute md:relative top-full left-0 right-0 md:top-auto bg-white md:bg-transparent border-b md:border-b-0" style="border-color: var(--border); padding: 1rem; box-shadow: var(--shadow-md); z-index: 49;">
                    <a href="#program"   class="px-3 py-2 rounded-lg text-sm font-medium" style="color: var(--text-secondary);" onmouseover="this.style.color='var(--color-primary)';this.style.background='var(--color-primary-light)'" onmouseout="this.style.color='var(--text-secondary)';this.style.background='transparent'">Program</a>
                    <a href="#pricelist" class="px-3 py-2 rounded-lg text-sm font-medium" style="color: var(--text-secondary);" onmouseover="this.style.color='var(--color-primary)';this.style.background='var(--color-primary-light)'" onmouseout="this.style.color='var(--text-secondary)';this.style.background='transparent'">Harga</a>
                    <a href="#metode"    class="px-3 py-2 rounded-lg text-sm font-medium" style="color: var(--text-secondary);" onmouseover="this.style.color='var(--color-primary)';this.style.background='var(--color-primary-light)'" onmouseout="this.style.color='var(--text-secondary)';this.style.background='transparent'">Metode</a>
                    <a href="#teachers"  class="px-3 py-2 rounded-lg text-sm font-medium" style="color: var(--text-secondary);" onmouseover="this.style.color='var(--color-primary)';this.style.background='var(--color-primary-light)'" onmouseout="this.style.color='var(--text-secondary)';this.style.background='transparent'">Teachers</a>
                    <a href="#testimoni" class="px-3 py-2 rounded-lg text-sm font-medium" style="color: var(--text-secondary);" onmouseover="this.style.color='var(--color-primary)';this.style.background='var(--color-primary-light)'" onmouseout="this.style.color='var(--text-secondary)';this.style.background='transparent'">Testimoni</a>
                    <a href="#kontak"    class="px-3 py-2 rounded-lg text-sm font-medium" style="color: var(--text-secondary);" onmouseover="this.style.color='var(--color-primary)';this.style.background='var(--color-primary-light)'" onmouseout="this.style.color='var(--text-secondary)';this.style.background='transparent'">Kontak</a>
                    <a href="{{ route('login') }}" class="mt-2 md:mt-0 ml-0 md:ml-3 px-5 py-2 rounded-full text-sm font-semibold text-white transition-all" style="background: var(--color-primary);" onmouseover="this.style.background='var(--color-primary-hover)';this.style.transform='translateY(-1px)'" onmouseout="this.style.background='var(--color-primary)';this.style.transform='translateY(0)'">Login</a>
                </nav>
            </div>
        </header>

        <main class="max-w-6xl mx-auto px-6">

            <!-- ========== HERO ========== -->
            <section class="grid md:grid-cols-[1.1fr_0.9fr] gap-10 items-center py-16 md:py-20">
                <div class="space-y-6 reveal">
                    <p class="section-label" style="color: var(--color-primary);">Profil Bimbel</p>
                    <h1 class="text-4xl md:text-5xl font-semibold leading-tight" style="color: var(--text-main);">
                        Belajar lebih fokus dengan bimbel yang <span style="color: var(--color-primary);">hangat</span>, <span style="color: var(--color-secondary);">terarah</span>, dan hasilnya terukur.
                    </h1>
                    <p class="text-base md:text-lg leading-relaxed" style="color: var(--text-secondary);">
                        Bimbel Gracia menghadirkan bimbingan privat dan kelas kecil dengan tentor yang dipilih sesuai
                        kebutuhan murid. Kami menyesuaikan strategi belajar, ritme, dan target agar progres terasa nyata.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <span class="tag">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" style="color: var(--color-primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Privat 1:1 fokus target
                        </span>
                        <span class="tag">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" style="color: var(--color-secondary);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Kelas kecil, interaktif
                        </span>
                        <span class="tag">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" style="color: var(--color-warm-soft);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Laporan progres rutin
                        </span>
                    </div>
                </div>
                <div class="relative">
                    <div class="absolute -top-12 -right-8 w-40 h-40 rounded-full opacity-20" style="background: var(--color-primary); filter: blur(60px);"></div>
                    <div class="card rounded-3xl p-8 space-y-5 shadow-lg float border-t-4" style="border-top-color: var(--color-primary);">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full" style="background: var(--color-secondary);"></div>
                            <h3 class="text-lg font-semibold" style="color: var(--text-main);">Kenapa Gracia?</h3>
                        </div>
                        <ul class="space-y-3" style="color: var(--text-secondary);">
                            <li class="flex items-start gap-2.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mt-0.5 flex-shrink-0" style="color: var(--color-primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Diagnosa awal untuk pemetaan kebutuhan
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mt-0.5 flex-shrink-0" style="color: var(--color-primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Rencana belajar personal per murid
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mt-0.5 flex-shrink-0" style="color: var(--color-primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Jadwal fleksibel dan bisa dinegosiasi
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mt-0.5 flex-shrink-0" style="color: var(--color-primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Update progres belajar tiap bulan
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- ========== PROGRAM BELAJAR (beige — full-bleed + rounded) ========== -->
            <div class="section-bleed">
                <div class="section-rounded" style="background: var(--bg-neutral);">
                    <div class="max-w-6xl mx-auto px-6">

                        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-8">
                            <h2 class="text-3xl font-semibold" style="color: var(--text-main);">Program Belajar</h2>
                            <span class="text-sm" style="color: var(--text-muted);">Fleksibel untuk target akademik dan ujian</span>
                        </div>

                        <div class="grid md:grid-cols-3 gap-6">

                            <!-- Privat Intensif — primary blue -->
                            <div class="card rounded-2xl p-6 space-y-4" style="border-top: 3px solid var(--color-primary);">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: var(--color-primary-light);">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color: var(--color-primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                    <span class="badge-primary px-2.5 py-0.5 rounded-full text-xs font-semibold">Intensif</span>
                                </div>
                                <h3 class="text-xl font-semibold" style="color: var(--text-main);">Privat Intensif</h3>
                                <p class="text-sm leading-relaxed" style="color: var(--text-secondary);">Satu murid satu tentor, fokus pada target akademik tertentu.</p>
                                <ul class="space-y-2 text-sm" style="color: var(--text-secondary);">
                                    <li class="flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background: var(--color-primary);"></span>
                                        Jadwal fleksibel
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background: var(--color-primary);"></span>
                                        Target mingguan terarah
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background: var(--color-primary);"></span>
                                        Evaluasi progres rutin
                                    </li>
                                </ul>
                            </div>

                            <!-- Privat Reguler — secondary green -->
                            <div class="card rounded-2xl p-6 space-y-4" style="border-top: 3px solid var(--color-secondary);">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: var(--color-secondary-light);">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color: var(--color-secondary);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                    </div>
                                    <span class="badge-secondary px-2.5 py-0.5 rounded-full text-xs font-semibold">Reguler</span>
                                </div>
                                <h3 class="text-xl font-semibold" style="color: var(--text-main);">Privat Reguler</h3>
                                <p class="text-sm leading-relaxed" style="color: var(--text-secondary);">Pertemuan rutin mingguan dengan monitoring progres bulanan.</p>
                                <ul class="space-y-2 text-sm" style="color: var(--text-secondary);">
                                    <li class="flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background: var(--color-secondary);"></span>
                                        Pendampingan konsep dasar
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background: var(--color-secondary);"></span>
                                        Latihan soal terukur
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background: var(--color-secondary);"></span>
                                        Ringkasan progres ke orang tua
                                    </li>
                                </ul>
                            </div>

                            <!-- Kelas Bersama — accent orange-oranye -->
                            <div class="card rounded-2xl p-6 space-y-4" style="border-top: 3px solid var(--color-accent);">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: var(--color-accent-light);">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color: var(--color-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    </div>
                                    <span class="badge-accent px-2.5 py-0.5 rounded-full text-xs font-semibold">Kelas</span>
                                </div>
                                <h3 class="text-xl font-semibold" style="color: var(--text-main);">Kelas Bersama</h3>
                                <p class="text-sm leading-relaxed" style="color: var(--text-secondary);">Kelas kecil dengan interaksi aktif dan diskusi intensif.</p>
                                <ul class="space-y-2 text-sm" style="color: var(--text-secondary);">
                                    <li class="flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background: var(--color-accent);"></span>
                                        Materi terstruktur
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background: var(--color-accent);"></span>
                                        Diskusi kelompok kecil
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background: var(--color-accent);"></span>
                                        Latihan bersama & review
                                    </li>
                                </ul>
                            </div>

                        </div>

                    </div>
                </div>
            </div>

            <!-- ========== METODE BELAJAR (dark green — full-bleed + rounded) ========== -->
            <div class="section-bleed" style="margin-top: 1.5rem;">
                <div class="section-rounded" style="background: var(--color-dark-bg);">
                    <div class="max-w-6xl mx-auto px-6" id="metode">
                        <div class="mb-8">
                            <p class="section-label mb-2" style="color: rgba(152,199,63,0.9);">Metode</p>
                            <h2 class="text-3xl font-semibold text-white">Metode Belajar di Gracia</h2>
                            <p class="mt-2 text-sm" style="color: rgba(255,255,255,0.92);">Empat langkah terstruktur untuk memastikan setiap murid berkembang</p>
                        </div>
                        <div class="grid md:grid-cols-2 gap-5">
                            <!-- Step 1 — primary -->
                            <div class="card-dark p-6 space-y-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg step-primary">1</div>
                                    <h3 class="text-lg font-semibold text-white">Diagnosa Awal</h3>
                                </div>
                                <p class="text-sm leading-relaxed" style="color: rgba(255,255,255,0.92);">Pemetaan gaya belajar, kekuatan, dan gap materi agar strategi tepat sasaran.</p>
                            </div>
                            <!-- Step 2 — secondary -->
                            <div class="card-dark p-6 space-y-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg step-secondary">2</div>
                                    <h3 class="text-lg font-semibold text-white">Rencana Personal</h3>
                                </div>
                                <p class="text-sm leading-relaxed" style="color: rgba(255,255,255,0.92);">Rencana belajar disesuaikan target, ritme, dan kebutuhan murid.</p>
                            </div>
                            <!-- Step 3 — primary -->
                            <div class="card-dark p-6 space-y-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg step-primary">3</div>
                                    <h3 class="text-lg font-semibold text-white">Latihan & Review</h3>
                                </div>
                                <p class="text-sm leading-relaxed" style="color: rgba(255,255,255,0.92);">Latihan soal bertahap, review kesalahan, dan penguatan konsep inti.</p>
                            </div>
                            <!-- Step 4 — secondary -->
                            <div class="card-dark p-6 space-y-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg step-secondary">4</div>
                                    <h3 class="text-lg font-semibold text-white">Progress Check</h3>
                                </div>
                                <p class="text-sm leading-relaxed" style="color: rgba(255,255,255,0.92);">Laporan rutin untuk orang tua agar perkembangan mudah dipantau.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== HARGA PROGRAM ========== -->
            <section id="pricelist" class="py-16">
                <div class="text-center mb-10">
                    <p class="section-label mb-2" style="color: var(--color-primary);">Biaya Belajar</p>
                    <h2 class="text-3xl font-semibold" style="color: var(--text-main);">Harga Program</h2>
                    <p class="mt-3 text-sm" style="color: var(--text-muted);">Biaya belajar per pertemuan berdasarkan divisi dan jenis les</p>
                </div>

                @php
                    $programs = \App\Models\Program::query()
                        ->where('status', 'active')
                        ->orderBy('division')
                        ->orderBy('type')
                        ->get()
                        ->groupBy('division');
                    $divisions = ['TK', 'SD', 'SMP', 'SMA', 'UTBK'];
                @endphp

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse ($divisions as $div)
                        @php $divPrograms = $programs->get($div, collect()); @endphp
                        <div class="card rounded-2xl p-6 space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm" style="background: var(--color-primary-light); color: var(--color-primary);">
                                    {{ $div }}
                                </div>
                                <h3 class="text-base font-semibold" style="color: var(--text-main);">Divisi {{ $div }}</h3>
                            </div>
                            @if ($divPrograms->isNotEmpty())
                                <div class="space-y-3">
                                    @foreach ($divPrograms as $program)
                                        <div class="flex items-center justify-between text-sm">
                                            <span style="color: var(--text-secondary);">{{ $program->name }}</span>
                                            <span class="font-semibold" style="color: var(--text-main);">
                                                @if ($program->default_parent_rate)
                                                    Rp{{ number_format($program->default_parent_rate, 0, ',', '.') }}
                                                @else
                                                    <span style="color: var(--text-muted);">Hubungi</span>
                                                @endif
                                            </span>
                                        </div>
                                        @if ($program->description)
                                            <p class="text-xs -mt-2" style="color: var(--text-muted);">{{ $program->description }}</p>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm" style="color: var(--text-muted);">Hubungi admin untuk info harga</p>
                            @endif
                        </div>
                    @empty
                        <div class="col-span-full text-center" style="color: var(--text-muted);">
                            <p>Belum ada data program.</p>
                        </div>
                    @endforelse
                </div>
                <p class="mt-6 text-center text-xs" style="color: var(--text-muted);">* Harga dapat berubah. Hubungi admin untuk informasi terbaru.</p>
            </section>

            <!-- ========== OUR TEACHERS ========== -->
            <section id="teachers" style="background: var(--surface); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
                <div class="text-center mb-10">
                    <p class="section-label mb-2" style="color: var(--color-primary);">Tim Kami</p>
                    <h2 class="text-3xl font-semibold" style="color: var(--text-main);">Our Teachers</h2>
                    <p class="mt-2 text-sm" style="color: var(--text-muted);">Kenali guru-guru profesional kami</p>
                </div>

                @php
                    $founders = \App\Models\Teacher::query()
                        ->where('status', 'active')
                        ->where('is_founder', true)
                        ->whereNotNull('profile_photo_path')
                        ->where('profile_photo_approved', true)
                        ->orderBy('name')
                        ->get();

                    $approvedTeachers = \App\Models\Teacher::query()
                        ->where('status', 'active')
                        ->where('profile_photo_approved', true)
                        ->whereNotNull('profile_photo_path')
                        ->where(function ($q) {
                            $q->where('is_founder', false)->orWhereNull('is_founder');
                        })
                        ->orderBy('name')
                        ->get();
                @endphp

                <!-- Tier 1: Co-Founder cards (border-top accent) -->
                @if ($founders->isNotEmpty())
                    <div class="grid md:grid-cols-2 gap-6 max-w-2xl mx-auto mb-10">
                        @foreach ($founders as $founder)
                            <div class="card rounded-2xl p-6 text-center" style="border-top: 3px solid var(--color-primary);">
                                <img src="{{ $founder->profile_photo_url }}" alt="{{ $founder->name }}"
                                    class="w-20 h-20 rounded-full object-cover mx-auto shadow-md"
                                    style="border: 2px solid var(--color-primary-light);">
                                <h3 class="mt-3 font-semibold text-base" style="color: var(--text-main);">{{ $founder->name }}</h3>
                                @if ($founder->major)
                                    <p class="text-xs mt-0.5" style="color: var(--text-muted);">{{ $founder->major }}</p>
                                @endif
                                @if ($founder->founder_description)
                                    <p class="text-sm mt-2 leading-relaxed" style="color: var(--text-secondary);">{{ $founder->founder_description }}</p>
                                @endif
                                <div class="mt-3 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold" style="background: var(--color-primary-light); color: var(--color-primary);">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                    Co-Founder
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Tier 2: Regular teacher cards (polished, no border-top accent) -->
                @if ($approvedTeachers->isNotEmpty())
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        @foreach ($approvedTeachers as $teacher)
                            <div class="card rounded-2xl p-4 text-center">
                                <img src="{{ $teacher->profile_photo_url }}" alt="{{ $teacher->name }}"
                                    class="w-16 h-16 rounded-full object-cover mx-auto shadow-sm"
                                    style="border: 2px solid var(--border-light);">
                                <h3 class="mt-2.5 font-semibold text-sm" style="color: var(--text-main);">{{ $teacher->name }}</h3>
                                @if ($teacher->major)
                                    <p class="text-xs mt-0.5" style="color: var(--text-muted);">{{ $teacher->major }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-center" style="color: var(--text-muted);">Belum ada data guru yang ditampilkan.</p>
                @endif
            </section>

            <!-- ========== TESTIMONI (white background — no beige wrap) ========== -->
            <section id="testimoni" class="py-16">
                <div class="mb-8">
                    <p class="section-label mb-2" style="color: var(--color-primary);">Kepercayaan</p>
                    <h2 class="text-3xl font-semibold" style="color: var(--text-main);">Testimoni</h2>
                </div>
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Card 1 — primary blue left border -->
                    <div class="card rounded-2xl p-6 space-y-4" style="border-left: 3px solid var(--color-primary);">
                        <div class="flex items-center gap-1">
                            @for ($i = 0; $i < 5; $i++)
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color: var(--color-primary);" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h4v10h-10z"/></svg>
                            @endfor
                        </div>
                        <p class="text-sm leading-relaxed" style="color: var(--text-secondary);">"Admin jadi cepat validasi presensi dan kirim tagihan ke ortu. Semua rapi!"</p>
                        <p class="text-sm font-semibold" style="color: var(--color-primary);">— Dina, Admin Bimbel</p>
                    </div>
                    <!-- Card 2 — secondary green left border -->
                    <div class="card rounded-2xl p-6 space-y-4" style="border-left: 3px solid var(--color-secondary);">
                        <div class="flex items-center gap-1">
                            @for ($i = 0; $i < 5; $i++)
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color: var(--color-secondary);" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h4v10h-10z"/></svg>
                            @endfor
                        </div>
                        <p class="text-sm leading-relaxed" style="color: var(--text-secondary);">"Guru bisa cek proyeksi gaji bulanan tanpa tanya manual."</p>
                        <p class="text-sm font-semibold" style="color: var(--color-secondary);">— Bref, Guru Matematika</p>
                    </div>
                    <!-- Card 3 — accent orange-oranye left border -->
                    <div class="card rounded-2xl p-6 space-y-4" style="border-left: 3px solid var(--color-accent);">
                        <div class="flex items-center gap-1">
                            @for ($i = 0; $i < 5; $i++)
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color: var(--color-accent);" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h4v10h-10z"/></svg>
                            @endfor
                        </div>
                        <p class="text-sm leading-relaxed" style="color: var(--text-secondary);">"Ortu dapat rekap jelas lewat WA, tagihan jadi transparan."</p>
                        <p class="text-sm font-semibold" style="color: var(--color-accent);">— Rina, Orang Tua Murid</p>
                    </div>
                </div>
            </section>

            <!-- ========== KONTAK & KONSULTASI (dark green — full-bleed + rounded) ========== -->
            <div class="section-bleed" style="margin-top: 1.5rem;">
                <div class="section-rounded" style="background: var(--color-dark-bg);">
                    <div class="max-w-6xl mx-auto px-6" id="kontak">
                        <div class="grid md:grid-cols-[1.2fr_0.8fr] gap-10 items-center">
                            <div>
                                <!-- FIX 1: label now readable on dark bg -->
                                <p class="section-label mb-2" style="color: rgba(255,255,255,0.9);">Hubungi Kami</p>
                                <h2 class="text-3xl font-semibold text-white">Kontak &amp; Konsultasi</h2>
                                <p class="mt-4 text-sm leading-relaxed" style="color: rgba(255,255,255,0.92);">Hubungi admin untuk info paket, jadwal, atau konsultasi belajar. Tim kami siap membantu.</p>
                                <div class="mt-6 space-y-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(105,151,235,0.15);">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color: var(--color-primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        </div>
                                        <span class="text-sm" style="color: rgba(255,255,255,0.8);">WhatsApp: 62 817-0302-7942</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(152,199,63,0.15);">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color: var(--color-secondary);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        </div>
                                        <span class="text-sm" style="color: rgba(255,255,255,0.8);">Email: admin@bimbelgracia.com</span>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(219,158,139,0.15);">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color: var(--color-warm-soft);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </div>
                                        <div>
                                            <span class="text-sm" style="color: rgba(255,255,255,0.8);">Jl. Karanglo Indah Blk. M No.4, Balearjosari, Kec. Blimbing, Kota Malang, Jawa Timur 65126</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 rounded-xl overflow-hidden" style="border: 1px solid rgba(255,255,255,0.14);">
                                    <iframe
                                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3951.7861111073735!2d112.64284727538515!3d-7.917397278815161!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd62a21c463bd93%3A0x3b76e1c36612f6c6!2sTempat%20Les%2C%20Tempat%20Kursus%2C%20Bimbel%20Gracia%20Karanglo%20Indah!5e0!3m2!1sid!2sid!4v1787153657474!5m2!1sid!2sid"
                                        width="100%"
                                        height="250"
                                        style="border:0; display: block;"
                                        allowfullscreen=""
                                        loading="lazy"
                                        referrerpolicy="strict-origin-when-cross-origin">
                                    </iframe>
                                </div>
                            </div>
                            <div class="space-y-5">
                                <!-- FIX 1: "Siap mulai?" label now readable on dark bg -->
                                <div class="card-dark p-5">
                                    <p class="text-xs font-bold uppercase tracking-[0.3em] mb-1" style="color: rgba(255,255,255,0.9);">Siap mulai?</p>
                                    <p class="text-sm leading-relaxed" style="color: rgba(255,255,255,0.92);">Konsultasi gratis untuk menentukan program terbaik bagi kebutuhan belajar Anda.</p>
                                </div>
                                <a href="https://wa.me/6281703027942"
                                   class="btn-coral w-full justify-center"
                                   style="font-size: 1rem; padding: 0.85rem 2rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    Chat WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>

        <!-- ========== FOOTER ========== -->
        <footer style="background: var(--color-dark-bg); border-top: 1px solid rgba(255,255,255,0.08); margin-top: 1.5rem;">
            <div class="max-w-6xl mx-auto px-6 py-8">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg overflow-hidden flex items-center justify-center" style="background: rgba(255,255,255,0.1);">
                            <img src="{{ asset('storage/website/logo_bimbel.jpg') }}" alt="Bimbel Gracia" class="w-full h-full object-contain" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Bimbel Gracia</p>
                            <p class="text-xs" style="color: rgba(255,255,255,0.4);">Bimbingan Belajar Privat & Kelas</p>
                        </div>
                    </div>
                    <p class="text-xs" style="color: rgba(255,255,255,0.4);">© 2026 Bimbel Gracia. Belajar terarah, progres terukur, hasil lebih percaya diri.</p>
                </div>
            </div>
        </footer>

        <script>
            document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
                const menu = document.getElementById('mobile-menu');
                menu.classList.toggle('hidden');
            });
        </script>
    </body>
</html>
