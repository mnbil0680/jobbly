<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = !empty($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>

<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Jobbly | Unified Career Gallery</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "primary-fixed": "#cee5ff",
                        "outline": "#717881",
                        "error": "#ba1a1a",
                        "on-secondary-fixed-variant": "#394857",
                        "on-tertiary-fixed-variant": "#004f4f",
                        "on-secondary-fixed": "#0d1d2a",
                        "surface-tint": "#00639b",
                        "tertiary-fixed-dim": "#84d4d3",
                        "on-tertiary-container": "#97e7e6",
                        "on-primary-fixed-variant": "#004a76",
                        "primary": "#004a76",
                        "on-error-container": "#93000a",
                        "inverse-primary": "#96cbff",
                        "on-surface-variant": "#40474f",
                        "on-error": "#ffffff",
                        "surface-container": "#efeded",
                        "on-primary-container": "#bcdcff",
                        "background": "#fbf9f8",
                        "secondary-fixed": "#d4e4f6",
                        "outline-variant": "#c0c7d1",
                        "surface-container-low": "#f5f3f3",
                        "surface-dim": "#dbdad9",
                        "surface-container-highest": "#e4e2e2",
                        "on-secondary-container": "#576675",
                        "on-background": "#1b1c1c",
                        "surface-container-lowest": "#ffffff",
                        "on-secondary": "#ffffff",
                        "tertiary-fixed": "#a0f0f0",
                        "secondary-container": "#d4e4f6",
                        "on-tertiary": "#ffffff",
                        "surface": "#fbf9f8",
                        "tertiary": "#005050",
                        "on-primary": "#ffffff",
                        "inverse-on-surface": "#f2f0f0",
                        "error-container": "#ffdad6",
                        "secondary": "#51606f",
                        "inverse-surface": "#303030",
                        "tertiary-container": "#006a6a",
                        "secondary-fixed-dim": "#b9c8da",
                        "on-primary-fixed": "#001d33",
                        "surface-variant": "#e4e2e2",
                        "surface-bright": "#fbf9f8",
                        "primary-container": "#00639b",
                        "on-surface": "#1b1c1c",
                        "surface-container-high": "#e9e8e7",
                        "on-tertiary-fixed": "#002020",
                        "primary-fixed-dim": "#96cbff"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.125rem",
                        "lg": "0.25rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "fontFamily": {
                        "headline": ["Manrope"],
                        "body": ["Inter"],
                        "label": ["Inter"]
                    }
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #fbf9f8;
        }
        .font-headline { font-family: 'Manrope', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .tonal-transition { transition: background-color 0.2s ease; }
        .glass-header {
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .signature-gradient { background: linear-gradient(135deg, #004a76 0%, #00639b 100%); }
        .ghost-border { border: 1px solid rgba(113, 120, 129, 0.15); }
        .ghost-border:hover { border-color: #004a76; }
    </style>
</head>

<body class="bg-surface text-on-background antialiased">
    <!-- TopNavBar -->
    <header class="fixed top-0 w-full z-50 bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border-b border-outline-variant/20 font-['Manrope'] antialiased">
        <div class="flex justify-between items-center w-full px-6 h-16 max-w-screen-2xl mx-auto">
            <div class="flex items-center gap-8">
                <a class="text-xl font-black tracking-tight text-sky-900 dark:text-sky-100" href="index.php">Jobbly</a>
                <nav class="hidden md:flex items-center space-x-6">
                    <?php if ($isLoggedIn): ?>
                        <a class="text-slate-600 dark:text-slate-400 hover:text-sky-600 transition-colors text-sm" href="jobs.php?view=saved">Saved</a>
                        <a class="text-slate-600 dark:text-slate-400 hover:text-sky-600 transition-colors text-sm" href="profile.php">Dashboard</a>
                    <?php else: ?>
                        <a class="text-slate-600 dark:text-slate-400 hover:text-sky-600 transition-colors text-sm" href="login.php?redirect=jobs.php?view=saved">Saved</a>
                    <?php endif; ?>
                </nav>
            </div>
            <div class="flex items-center space-x-3">
                <?php if ($isLoggedIn): ?>
                    <span class="text-slate-600 font-semibold text-sm"><?php echo htmlspecialchars($userName); ?></span>
                    <button onclick="logout()" class="text-slate-600 dark:text-slate-400 hover:text-sky-600 transition-colors text-xs font-bold uppercase tracking-wider">Sign Out</button>
                <?php else: ?>
                    <a href="login.php" class="text-slate-600 dark:text-slate-400 hover:text-sky-600 transition-colors text-xs font-bold uppercase tracking-wider">Sign In</a>
                    <a href="signup.php" class="signature-gradient text-white px-5 py-2 rounded-lg text-xs font-bold transition-transform active:scale-95">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="pt-16">
        <!-- Hero Section -->
        <section class="relative py-16 md:py-24 flex items-center justify-center overflow-hidden bg-surface border-b border-outline-variant/10">
            <div class="absolute inset-0 z-0">
                <div class="absolute top-[-20%] right-[-10%] w-[400px] h-[400px] bg-primary-fixed/20 rounded-full blur-[100px]"></div>
                <div class="absolute bottom-[-20%] left-[-10%] w-[400px] h-[400px] bg-tertiary-fixed/15 rounded-full blur-[80px]"></div>
            </div>
            <div class="relative z-10 max-w-5xl mx-auto px-6 text-center">
                <h1 class="font-headline text-5xl md:text-7xl font-extrabold tracking-tighter text-on-background mb-4 leading-none">
                    The Curated <span class="text-primary">Career Gallery.</span>
                </h1>
                <p class="text-on-surface-variant text-base md:text-lg max-w-xl mx-auto mb-10 font-body opacity-90">
                    A unified search engine for high-intent roles from global leaders and boutique firms.
                </p>
                <!-- Search Bar -->
                <form action="jobs.php" method="GET">
                    <input type="hidden" name="view" value="explore">
                    <div class="bg-surface-container-lowest p-1.5 rounded-xl shadow-xl shadow-on-surface/5 flex flex-col md:flex-row items-center gap-1 max-w-3xl mx-auto border border-outline-variant/30">
                        <div class="flex-1 flex items-center px-4 w-full h-12 border-b md:border-b-0 md:border-r border-outline-variant/20">
                            <span class="material-symbols-outlined text-outline text-xl mr-2">work</span>
                            <input name="search" class="w-full h-full bg-transparent border-none focus:ring-0 text-on-surface text-sm font-medium placeholder:text-outline/50" placeholder="Job title or company" type="text" />
                        </div>
                        <div class="flex-1 flex items-center px-4 w-full h-12">
                            <span class="material-symbols-outlined text-outline text-xl mr-2">location_on</span>
                            <input class="w-full h-full bg-transparent border-none focus:ring-0 text-on-surface text-sm font-medium placeholder:text-outline/50" placeholder="City or Remote" type="text" />
                        </div>
                        <button type="submit" class="signature-gradient text-white px-8 py-3 rounded-lg font-bold text-sm w-full md:w-auto hover:brightness-110 transition-all">
                            Search
                        </button>
                    </div>
                </form>
                <div class="mt-8 flex flex-wrap justify-center items-center gap-6 opacity-50">
                    <span class="font-headline font-bold text-[10px] tracking-[0.2em] uppercase text-outline">Aggregating from</span>
                    <div class="flex gap-6 items-center grayscale">
                        <span class="font-bold text-base">LinkedIn</span>
                        <span class="font-bold text-base">Indeed</span>
                        <span class="font-bold text-base">Greenhouse</span>
                        <span class="font-bold text-base">Lever</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Methodology -->
        <section class="py-16 px-6 max-w-screen-2xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <div class="lg:col-span-5 space-y-6">
                    <div class="inline-block px-3 py-1 bg-primary-fixed text-primary text-[10px] font-bold tracking-widest uppercase rounded">Our Methodology</div>
                    <h2 class="font-headline text-4xl font-extrabold text-on-background leading-tight">
                        One Lens. <br />Infinite Opportunities.
                    </h2>
                    <p class="text-on-surface-variant text-base leading-relaxed">
                        Jobbly cleanses data from 400+ portals, ensuring you see the clearest version of the market without duplicates.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div class="flex gap-3 items-start p-3 rounded-lg hover:bg-surface-container-low transition-colors">
                            <span class="material-symbols-outlined text-primary bg-primary-fixed p-2 rounded-lg text-lg">hub</span>
                            <div>
                                <h4 class="font-bold text-sm text-on-background leading-none mb-1">Unified Aggregation</h4>
                                <p class="text-on-surface-variant text-[11px] leading-tight">Real-time sync with 50+ ATS platforms.</p>
                            </div>
                        </div>
                        <div class="flex gap-3 items-start p-3 rounded-lg hover:bg-surface-container-low transition-colors">
                            <span class="material-symbols-outlined text-secondary bg-secondary-fixed p-2 rounded-lg text-lg">auto_awesome</span>
                            <div>
                                <h4 class="font-bold text-sm text-on-background leading-none mb-1">Editorial Clarity</h4>
                                <p class="text-on-surface-variant text-[11px] leading-tight">AI-driven normalization of job titles.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-7">
                    <div class="bg-surface-container-low rounded-2xl aspect-video overflow-hidden relative shadow-inner">
                        <img class="w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBuK9Ode7-hAYktDLu79oMiDz64nqUSVDls0xC15Ihx5D6dkHhBIoYfk1GijpAPCNBuDgSpHYBK_7y4dN5nzR7tN7SprRrvvcgg9DDyFjAhBRbuTa3pCVnadVWyKGtBPC4KO1Pj0jEwfQwh1V23AndshNur3hhO2yXn5ffFw1eqBokWDm7_sEgomELS0oXd_RsLwXgowMxqBqAyQTKnpFrr1n_cSrBx2IqCcglMWt-VMJgYKYtejyKjPM6I3B40JPca5ntXdHopDE4a" alt="Office" />
                        <div class="absolute bottom-4 left-4 right-4 md:right-auto md:max-w-xs bg-white/95 backdrop-blur-sm p-4 rounded-xl shadow-lg border border-outline-variant/10">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="material-symbols-outlined text-primary text-base">verified</span>
                                <span class="text-[9px] font-bold uppercase tracking-widest text-outline">Verified Source</span>
                            </div>
                            <p class="text-xs font-medium text-on-background italic">"Linear Dynamics found their Lead Designer via Jobbly in 12 days."</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Trending Roles -->
        <section class="py-16 bg-surface-container-low/50">
            <div class="max-w-screen-2xl mx-auto px-6">
                <div class="flex justify-between items-center mb-10">
                    <div>
                        <h2 class="font-headline text-3xl font-extrabold">Trending Roles</h2>
                        <p class="text-xs text-outline font-medium mt-1 uppercase tracking-widest">Hand-picked selections</p>
                    </div>
                    <a href="jobs.php" class="ghost-border px-4 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-all">
                        View Gallery <span class="material-symbols-outlined text-xs">arrow_forward</span>
                    </a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-surface-container-lowest p-5 rounded-xl border border-outline-variant/10 tonal-transition hover:shadow-md hover:border-primary/30 group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-10 h-10 rounded-lg bg-slate-50 flex items-center justify-center border border-outline-variant/5">
                                <span class="material-symbols-outlined text-xl text-slate-400">filter_drama</span>
                            </div>
                            <span class="bg-secondary-container text-on-secondary-container px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider">Full-time</span>
                        </div>
                        <h3 class="font-headline text-base font-bold text-on-background group-hover:text-primary transition-colors leading-tight mb-1">Senior Product Designer</h3>
                        <p class="text-on-surface-variant text-[12px] mb-4">Stripe • SF (Remote Friendly)</p>
                        <div class="flex items-center justify-between text-[10px] font-semibold text-outline border-t border-outline-variant/10 pt-3">
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">payments</span> $160k - $220k</span>
                            <span>2d ago</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-lowest p-5 rounded-xl border border-outline-variant/10 tonal-transition hover:shadow-md hover:border-primary/30 group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-10 h-10 rounded-lg bg-slate-50 flex items-center justify-center border border-outline-variant/5">
                                <span class="material-symbols-outlined text-xl text-slate-400">token</span>
                            </div>
                            <span class="bg-secondary-container text-on-secondary-container px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider">Contract</span>
                        </div>
                        <h3 class="font-headline text-base font-bold text-on-background group-hover:text-primary transition-colors leading-tight mb-1">Backend Engineer (Go)</h3>
                        <p class="text-on-surface-variant text-[12px] mb-4">Vercel • Remote</p>
                        <div class="flex items-center justify-between text-[10px] font-semibold text-outline border-t border-outline-variant/10 pt-3">
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">payments</span> $140k - $190k</span>
                            <span>5h ago</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-lowest p-5 rounded-xl border border-outline-variant/10 tonal-transition hover:shadow-md hover:border-primary/30 group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-10 h-10 rounded-lg bg-slate-50 flex items-center justify-center border border-outline-variant/5">
                                <span class="material-symbols-outlined text-xl text-slate-400">landscape</span>
                            </div>
                            <span class="bg-secondary-container text-on-secondary-container px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider">Hybrid</span>
                        </div>
                        <h3 class="font-headline text-base font-bold text-on-background group-hover:text-primary transition-colors leading-tight mb-1">Growth Marketing Lead</h3>
                        <p class="text-on-surface-variant text-[12px] mb-4">Airbnb • London, UK</p>
                        <div class="flex items-center justify-between text-[10px] font-semibold text-outline border-t border-outline-variant/10 pt-3">
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">payments</span> £90k - £130k</span>
                            <span>1d ago</span>
                        </div>
                    </div>
                    <div class="bg-primary rounded-xl overflow-hidden relative p-6 flex flex-col justify-end">
                        <img class="absolute inset-0 h-full w-full object-cover opacity-30 mix-blend-overlay"
                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuA8ljeBnqVfhpQlsLbs9rh_KD4obShAi76Mles5XaroCezYb_6F-dRF4BFUbp5I9zrKzE_bN2DHSqbXvvt0sDMseRrhKqnGotiUMKesFTF8tMIdhkblX6ZH6MZXVHumLzfyo84tc25IpPCcdRujsldrVVrRs2Bt2qVokkD3m1U3Wv61vla46j1TWTZKsn_OLkZL4r3qIBP5dVC_m7o6uveyI1jp_CGtkd7uBK6bg40BWRmNX3w0x_5xzEpFxwO55w-yGQEr9WyPQ3l-" alt="Spotlight" />
                        <div class="relative z-10">
                            <p class="text-primary-fixed text-[9px] font-bold uppercase tracking-widest mb-1">Employer Spotlight</p>
                            <h3 class="font-headline text-lg font-extrabold text-white leading-tight mb-3">OpenAI is hiring 14+ roles.</h3>
                            <a href="jobs.php" class="bg-white text-primary px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-primary-fixed transition-colors">Apply Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA & Newsletter -->
        <section class="py-16 px-6">
            <div class="max-w-4xl mx-auto flex flex-col md:flex-row gap-12 items-center bg-surface-container-lowest p-8 md:p-12 rounded-2xl border border-outline-variant/20 shadow-sm">
                <div class="flex-1 text-center md:text-left">
                    <h2 class="font-headline text-3xl font-extrabold mb-4 tracking-tight leading-tight">Your career, <br />curated.</h2>
                    <div class="flex flex-wrap justify-center md:justify-start gap-3">
                        <a href="jobs.php" class="signature-gradient text-white px-6 py-3 rounded-xl text-sm font-bold shadow-lg shadow-primary/20">Explore Jobs</a>
                        <button class="ghost-border px-6 py-3 rounded-xl text-sm font-bold">Post a Role</button>
                    </div>
                </div>
                <div class="w-full md:w-80 space-y-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="material-symbols-outlined text-tertiary">mail</span>
                        <h3 class="font-bold text-sm">Gallery Alerts</h3>
                    </div>
                    <div class="flex flex-col gap-2">
                        <input class="w-full bg-surface-container-low border border-outline-variant/30 rounded-lg py-2.5 px-3 text-on-surface placeholder:text-outline/40 text-xs focus:ring-1 focus:ring-primary" placeholder="email@address.com" type="email" />
                        <button class="w-full bg-primary text-white font-bold py-2.5 rounded-lg text-xs">Subscribe</button>
                    </div>
                    <p class="text-[10px] text-outline leading-tight">Curated matches delivered every Monday.</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="w-full py-10 bg-white dark:bg-slate-900 border-t border-outline-variant/10">
        <div class="max-w-screen-2xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="text-xl font-black text-sky-900 dark:text-white">Jobbly</div>
            <div class="flex flex-wrap justify-center gap-6 font-['Inter'] text-[10px] uppercase tracking-widest font-semibold">
                <a class="text-slate-400 hover:text-sky-500 transition-colors" href="#">Privacy</a>
                <a class="text-slate-400 hover:text-sky-500 transition-colors" href="#">Terms</a>
                <a class="text-slate-400 hover:text-sky-500 transition-colors" href="#">Cookies</a>
                <a class="text-slate-400 hover:text-sky-500 transition-colors" href="#">Contact</a>
            </div>
            <p class="text-slate-400 text-[10px] font-medium tracking-wide">© <?php echo date('Y'); ?> Jobbly. Editorial Career Discovery.</p>
        </div>
    </footer>

    <script>
        async function logout() {
            const res = await fetch('API_Ops.php?action=logout');
            const data = await res.json();
            if (data.success) window.location.href = 'index.php';
        }
    </script>
</body>
</html>
