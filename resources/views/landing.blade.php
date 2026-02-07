<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Bimbel Gracia - Sistem Management Terintegrasi</title>
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
                    <p class="text-lg font-semibold">Sistem Management Terintegrasi</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <a href="#paket" class="text-sm text-white/80 hover:text-white">Paket</a>
                <a href="#keunggulan" class="text-sm text-white/80 hover:text-white">Keunggulan</a>
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
                        Bimbel privat dan kelas bersama dengan alur kerja yang rapi, cepat, dan bisa diaudit.
                    </h1>
                    <p class="text-lg text-white/80">
                        Semua data murid, guru, presensi bulanan, hingga laporan keuangan menyatu dalam satu dashboard.
                        Admin, guru, dan murid mendapatkan akses sesuai perannya.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <span class="px-4 py-2 rounded-full glass text-sm">Login role-based</span>
                        <span class="px-4 py-2 rounded-full glass text-sm">Presensi tervalidasi</span>
                        <span class="px-4 py-2 rounded-full glass text-sm">Analisis otomatis</span>
                    </div>
                </div>
                <div class="relative">
                    <div class="absolute -top-12 -right-8 w-40 h-40 bg-[color:var(--accent)]/40 blur-3xl rounded-full"></div>
                    <div class="glass rounded-3xl p-8 space-y-6 shadow-2xl float">
                        <h3 class="text-xl font-semibold">Ringkasan Sistem</h3>
                        <ul class="space-y-3 text-white/80">
                            <li>3 Role utama: Admin, Guru, Murid</li>
                            <li>Presensi bulanan dengan validasi admin</li>
                            <li>Template WhatsApp otomatis</li>
                            <li>Audit log perubahan data</li>
                        </ul>
                    </div>
                </div>
            </section>

            <section id="paket" class="py-16">
                <div class="flex items-end justify-between">
                    <h2 class="text-3xl font-semibold">Paket Belajar</h2>
                    <span class="text-sm text-white/70">Disusun fleksibel untuk kebutuhan ortu & murid</span>
                </div>
                <div class="grid md:grid-cols-3 gap-6 mt-8">
                    <div class="glass rounded-2xl p-6 space-y-4">
                        <h3 class="text-xl font-semibold">Privat Intensif</h3>
                        <p class="text-white/70">Satu murid satu tentor, fokus pada target akademik tertentu.</p>
                        <ul class="text-sm text-white/70 space-y-2">
                            <li>Jadwal fleksibel</li>
                            <li>Presensi detail per tanggal</li>
                            <li>Laporan tagihan otomatis</li>
                        </ul>
                    </div>
                    <div class="glass rounded-2xl p-6 space-y-4 border border-[color:var(--accent)]/40">
                        <h3 class="text-xl font-semibold">Privat Reguler</h3>
                        <p class="text-white/70">Pertemuan rutin mingguan dengan monitoring progres bulanan.</p>
                        <ul class="text-sm text-white/70 space-y-2">
                            <li>Validasi admin</li>
                            <li>Template WA ke ortu</li>
                            <li>Status pembayaran tercatat</li>
                        </ul>
                    </div>
                    <div class="glass rounded-2xl p-6 space-y-4">
                        <h3 class="text-xl font-semibold">Kelas Bersama</h3>
                        <p class="text-white/70">Kelas kecil dengan jadwal terstruktur dan absensi otomatis.</p>
                        <ul class="text-sm text-white/70 space-y-2">
                            <li>Jadwal per tanggal</li>
                            <li>Total hadir murid & guru</li>
                            <li>Rekap bulanan siap export</li>
                        </ul>
                    </div>
                </div>
            </section>

            <section id="keunggulan" class="py-16">
                <h2 class="text-3xl font-semibold">Keunggulan Utama</h2>
                <div class="grid md:grid-cols-2 gap-6 mt-8">
                    <div class="glass rounded-2xl p-6 space-y-3">
                        <h3 class="text-lg font-semibold text-[color:var(--teal)]">Keamanan & Akses</h3>
                        <p class="text-white/70">Password default wajib diganti, session aman, akun bisa dihibernasi & restore.</p>
                    </div>
                    <div class="glass rounded-2xl p-6 space-y-3">
                        <h3 class="text-lg font-semibold text-[color:var(--teal)]">Presensi Tervalidasi</h3>
                        <p class="text-white/70">Guru mengisi presensi bulanan, admin memvalidasi sebelum analisis.</p>
                    </div>
                    <div class="glass rounded-2xl p-6 space-y-3">
                        <h3 class="text-lg font-semibold text-[color:var(--teal)]">Analisis Otomatis</h3>
                        <p class="text-white/70">Template WA untuk ortu dan guru dibuat otomatis, lengkap dengan status bayar.</p>
                    </div>
                    <div class="glass rounded-2xl p-6 space-y-3">
                        <h3 class="text-lg font-semibold text-[color:var(--teal)]">Audit Log</h3>
                        <p class="text-white/70">Setiap perubahan data tercatat lengkap dengan before-after dan timestamp.</p>
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
                        <p class="mt-4 text-white/70">Hubungi admin untuk info paket, jadwal, atau demo sistem.</p>
                        <div class="mt-6 space-y-2 text-white/80">
                            <p>WhatsApp: 62xxxxxxxxxxx</p>
                            <p>Email: admin@bimbelgracia.com</p>
                            <p>Alamat: (isi alamat bimbel)</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="p-4 rounded-2xl bg-white/10">
                            <p class="text-sm uppercase tracking-[0.35em] text-[color:var(--teal)]">Siap mulai?</p>
                            <p class="mt-2 text-white/80">Klik login untuk masuk ke dashboard sesuai role.</p>
                        </div>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-5 py-3 rounded-full bg-[color:var(--accent)] text-[color:var(--bg-deep)] font-semibold shadow-lg">
                            Masuk Dashboard
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="max-w-6xl mx-auto px-6 py-10 text-sm text-white/60">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <p>(c) 2026 Bimbel Gracia. Sistem Management Terintegrasi.</p>
                <p>Semua data disimpan dengan kebijakan soft delete & audit log.</p>
            </div>
        </footer>
    </body>
</html>
