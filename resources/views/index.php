<?php

declare(strict_types=1);

use Core\Foundation\Application;

$version = Application::VERSION;
?>
<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Flipa is a lightweight, educational full-stack micro-framework built from scratch with PHP 8.3+.">
    <title>Flipa &mdash; A lightweight PHP micro-framework</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="min-h-screen bg-linear-to-b from-emerald-50 via-white to-emerald-50 font-sans text-slate-800 antialiased">
    <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -right-32 -top-40 h-96 w-96 rounded-full bg-emerald-200/50 blur-3xl"></div>
        <div class="absolute -bottom-40 -left-32 h-96 w-96 rounded-full bg-green-200/40 blur-3xl"></div>
    </div>

    <main class="relative mx-auto flex min-h-screen w-full max-w-3xl flex-col items-center justify-center px-6 py-16">
        <section class="w-full rounded-3xl border border-emerald-100 bg-white/80 p-8 text-center shadow-xl shadow-emerald-900/5 backdrop-blur sm:p-12">
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-linear-to-br from-emerald-400 to-green-600 shadow-lg shadow-emerald-500/30">
                <span class="text-3xl font-black text-white">F</span>
            </div>

            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-emerald-700">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                v<?= e($version) ?>
            </span>

            <h1 class="mt-5 text-5xl font-black tracking-tight text-slate-900 sm:text-6xl">
                Flipa
            </h1>

            <p class="mx-auto mt-4 max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg">
                A lightweight, educational full-stack micro-framework built from scratch with
                <span class="font-semibold text-emerald-700">PHP 8.3+</span>.
                No magic, no hidden layers &mdash; just clean, readable code you can learn from.
            </p>

            <div class="mt-7 flex flex-wrap items-center justify-center gap-2 text-xs font-medium text-slate-500">
                <span class="rounded-full bg-slate-100 px-3 py-1">PHP 8.3+</span>
                <span class="rounded-full bg-slate-100 px-3 py-1">PSR-4</span>
                <span class="rounded-full bg-slate-100 px-3 py-1">IoC Container</span>
                <span class="rounded-full bg-slate-100 px-3 py-1">Active Record</span>
                <span class="rounded-full bg-slate-100 px-3 py-1">Tailwind CSS</span>
            </div>

            <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="https://github.com/oric28/flipa"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 sm:w-auto">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0 1 12 6.844a9.59 9.59 0 0 1 2.504.337c1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.02 10.02 0 0 0 22 12.017C22 6.484 17.522 2 12 2Z" clip-rule="evenodd"/>
                    </svg>
                    View on GitHub
                </a>

                <a href="https://github.com/oric28/flipa#getting-started"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 sm:w-auto">
                    Get started
                </a>
            </div>
        </section>

        <footer class="mt-8 text-center text-xs text-slate-500">
            Built with <span class="font-semibold text-emerald-700">PHP</span> &amp;
            <span class="font-semibold text-emerald-700">Tailwind CSS</span>
            <span class="mx-2 text-slate-300">&bull;</span>
            MIT License
        </footer>
    </main>
</body>
</html>
