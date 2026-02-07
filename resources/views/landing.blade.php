<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Bimbel Gracia - Bimbingan Belajar Privat & Kelas</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&family=IBM+Plex+Sans:wght@400;500;600&display=swap');

            :root {
                --bg-deep: #0b1d1a;
                --bg-mid: #0f2b27;
                --accent: #ff7a59;
                --accent-soft: #ffd2c4;
                --teal: #3fe2c5;
                --sand: #f2e8dc;
            }

            body {
                font-family: 'IBM Plex Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
                background: radial-gradient(1200px 600px at 10% -10%, rgba(63, 226, 197, 0.25), transparent),
                    radial-gradient(900px 500px at 95% 5%, rgba(255, 122, 89, 0.22), transparent),
                    linear-gradient(160deg, var(--bg-deep), var(--bg-mid));
                color: #fef7f1;
            }

            h1, h2, h3 {
                font-family: 'Space Grotesk', ui-sans-serif, system-ui, -apple-system, sans-serif;
                letter-spacing: -0.02em;
            }

            .glass {
                background: rgba(255, 255, 255, 0.08);
                border: 1px solid rgba(255, 255, 255, 0.12);
                backdrop-filter: blur(18px);
            }

            .float {
                animation: float 8s ease-in-out infinite;
            }

            @keyframes float {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-12px); }
            }

            .reveal {
                animation: fadeUp 0.9s ease both;
            }

            @keyframes fadeUp {
                from { opacity: 0; transform: translateY(12px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>
    </head>
    <body class="min-h-screen">
        <header class="max-w-6xl mx-auto px-6 py-10 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-[color:var(--accent)] text-[color:var(--bg-deep)] font-bold flex items-center justify-center shadow-lg">
                    BG
                </div>
                <div>
                    <p class="text-sm uppercase tracking-[0.35em] text-[color:var(--teal)]">Bimbel Gracia</p>
                    <p class="text-lg font-semibold">Bimbingan Belajar Privat & Kelas</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <a href="#program" class="text-sm text-white/80 hover:text-white">Program</a>
                <a href="#metode" class="text-sm text-white/80 hover:text-white">Metode</a>
                <a href="#testimoni" class="text-sm text-white/80 hover:text-white">Testimoni</a>
                <a href="#kontak" class="text-sm text-white/80 hover:text-white">Kontak</a>
                <a href="{{ route('login') }}" class="px-4 py-2 rounded-full bg-white text-[color:var(--bg-deep)] font-semibold shadow-lg">Login</a>
            </div>
        </header>

        <main class="max-w-6xl mx-auto px-6">
            <section class="grid md:grid-cols-[1.1fr_0.9fr] gap-10 items-center py-16">
                <div class="space-y-6 reveal">
                    <p class="text-sm uppercase tracking-[0.45em] text-[color:var(--teal)]">Profil Bimbel</p>
                    <h1 class="text-4xl md:text-5xl font-semibold leading-tight">
                        Belajar lebih fokus dengan bimbel yang hangat, terarah, dan hasilnya terukur.
                    </h1>
                    <p class="text-lg text-white/80">
                        Bimbel Gracia menghadirkan bimbingan privat dan kelas kecil dengan tentor yang dipilih sesuai
                        kebutuhan murid. Kami menyesuaikan strategi belajar, ritme, dan target agar progres terasa nyata.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <span class="px-4 py-2 rounded-full glass text-sm">Privat 1:1 fokus target</span>
                        <span class="px-4 py-2 rounded-full glass text-sm">Kelas kecil, interaktif</span>
                        <span class="px-4 py-2 rounded-full glass text-sm">Laporan progres rutin</span>
                    </div>
                </div>
                <div class="relative">
                    <div class="absolute -top-12 -right-8 w-40 h-40 bg-[color:var(--accent)]/40 blur-3xl rounded-full"></div>
                    <div class="glass rounded-3xl p-8 space-y-6 shadow-2xl float">
                        <h3 class="text-xl font-semibold">Kenapa Gracia?</h3>
                        <ul class="space-y-3 text-white/80">
                            <li>Diagnosa awal untuk pemetaan kebutuhan</li>
                            <li>Rencana belajar personal per murid</li>
                            <li>Jadwal fleksibel dan bisa dinegosiasi</li>
                            <li>Update progres belajar tiap bulan</li>
                        </ul>
                    </div>
                </div>
            </section>

            <section id="program" class="py-16">
                <div class="flex items-end justify-between">
                    <h2 class="text-3xl font-semibold">Program Belajar</h2>
                    <span class="text-sm text-white/70">Fleksibel untuk target akademik dan ujian</span>
                </div>
                <div class="grid md:grid-cols-3 gap-6 mt-8">
                    <div class="glass rounded-2xl p-6 space-y-4">
                        <h3 class="text-xl font-semibold">Privat Intensif</h3>
                        <p class="text-white/70">Satu murid satu tentor, fokus pada target akademik tertentu.</p>
                        <ul class="text-sm text-white/70 space-y-2">
                            <li>Jadwal fleksibel</li>
                            <li>Target mingguan terarah</li>
                            <li>Evaluasi progres rutin</li>
                        </ul>
                    </div>
                    <div class="glass rounded-2xl p-6 space-y-4 border border-[color:var(--accent)]/40">
                        <h3 class="text-xl font-semibold">Privat Reguler</h3>
                        <p class="text-white/70">Pertemuan rutin mingguan dengan monitoring progres bulanan.</p>
                        <ul class="text-sm text-white/70 space-y-2">
                            <li>Pendampingan konsep dasar</li>
                            <li>Latihan soal terukur</li>
                            <li>Ringkasan progres ke orang tua</li>
                        </ul>
                    </div>
                    <div class="glass rounded-2xl p-6 space-y-4">
                        <h3 class="text-xl font-semibold">Kelas Bersama</h3>
                        <p class="text-white/70">Kelas kecil dengan interaksi aktif dan diskusi intensif.</p>
                        <ul class="text-sm text-white/70 space-y-2">
                            <li>Materi terstruktur</li>
                            <li>Diskusi kelompok kecil</li>
                            <li>Latihan bersama & review</li>
                        </ul>
                    </div>
                </div>
            </section>

            <section id="metode" class="py-16">
                <h2 class="text-3xl font-semibold">Metode Belajar di Gracia</h2>
                <div class="grid md:grid-cols-2 gap-6 mt-8">
                    <div class="glass rounded-2xl p-6 space-y-3">
                        <h3 class="text-lg font-semibold text-[color:var(--teal)]">Diagnosa Awal</h3>
                        <p class="text-white/70">Pemetaan gaya belajar, kekuatan, dan gap materi agar strategi tepat sasaran.</p>
                    </div>
                    <div class="glass rounded-2xl p-6 space-y-3">
                        <h3 class="text-lg font-semibold text-[color:var(--teal)]">Rencana Personal</h3>
                        <p class="text-white/70">Rencana belajar disesuaikan target, ritme, dan kebutuhan murid.</p>
                    </div>
                    <div class="glass rounded-2xl p-6 space-y-3">
                        <h3 class="text-lg font-semibold text-[color:var(--teal)]">Latihan & Review</h3>
                        <p class="text-white/70">Latihan soal bertahap, review kesalahan, dan penguatan konsep inti.</p>
                    </div>
                    <div class="glass rounded-2xl p-6 space-y-3">
                        <h3 class="text-lg font-semibold text-[color:var(--teal)]">Progress Check</h3>
                        <p class="text-white/70">Laporan rutin untuk orang tua agar perkembangan mudah dipantau.</p>
                    </div>
                </div>
            </section>

            <section id="testimoni" class="py-16">
                <h2 class="text-3xl font-semibold">Testimoni</h2>
                <div class="grid md:grid-cols-3 gap-6 mt-8">
                    <div class="glass rounded-2xl p-6">
                        <p class="text-white/70">"Admin jadi cepat validasi presensi dan kirim tagihan ke ortu. Semua rapi!"</p>
                        <p class="mt-4 text-sm text-[color:var(--accent-soft)]">- Dina, Admin Bimbel</p>
                    </div>
                    <div class="glass rounded-2xl p-6">
                        <p class="text-white/70">"Guru bisa cek proyeksi gaji bulanan tanpa tanya manual."</p>
                        <p class="mt-4 text-sm text-[color:var(--accent-soft)]">- Arief, Guru Matematika</p>
                    </div>
                    <div class="glass rounded-2xl p-6">
                        <p class="text-white/70">"Ortu dapat rekap jelas lewat WA, tagihan jadi transparan."</p>
                        <p class="mt-4 text-sm text-[color:var(--accent-soft)]">- Rina, Orang Tua Murid</p>
                    </div>
                </div>
            </section>

            <section id="kontak" class="py-16">
                <div class="glass rounded-3xl p-8 md:p-12 grid md:grid-cols-[1.2fr_0.8fr] gap-8">
                    <div>
                        <h2 class="text-3xl font-semibold">Kontak & Konsultasi</h2>
                        <p class="mt-4 text-white/70">Hubungi admin untuk info paket, jadwal, atau konsultasi belajar.</p>
                        <div class="mt-6 space-y-2 text-white/80">
                            <p>WhatsApp: 62 817-0302-7942</p>
                            <p>Email: admin@bimbelgracia.com</p>
                            <p>Alamat: (isi alamat bimbel)</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="p-4 rounded-2xl bg-white/10">
                            <p class="text-sm uppercase tracking-[0.35em] text-[color:var(--teal)]">Siap mulai?</p>
                            <p class="mt-2 text-white/80">Konsultasi gratis untuk menentukan program terbaik.</p>
                        </div>
                        <a href="https://wa.me/6281703027942" class="inline-flex items-center justify-center px-5 py-3 rounded-full bg-[color:var(--accent)] text-[color:var(--bg-deep)] font-semibold shadow-lg">
                            Chat WhatsApp
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="max-w-6xl mx-auto px-6 py-10 text-sm text-white/60">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <p>(c) 2026 Bimbel Gracia. Bimbingan Belajar Privat & Kelas.</p>
                <p>Belajar terarah, progres terukur, hasil lebih percaya diri.</p>
            </div>
        </footer>
    </body>
</html>
