<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | {{ config('app.name', 'Forsais') }}</title>
    <style>
        :root {
            color-scheme: light;
            font-family: 'Instrument Sans', 'Segoe UI', sans-serif;
            color: #1e2925;
            background: #f2f5f1;
            font-synthesis: none;
            text-rendering: optimizeLegibility;
        }
        * { box-sizing: border-box; }
        body { min-width: 320px; min-height: 100vh; margin: 0; }
        .page-shell {
            display: grid;
            min-height: 100vh;
            grid-template-rows: auto 1fr auto;
            justify-items: center;
            padding: 30px 20px 22px;
            background: radial-gradient(ellipse at 50% 0%, #e4efe7 0, #f2f5f1 56%);
        }
        .brand, .page-footer { width: min(100%, 440px); }
        .brand { color: #174d3e; font-size: 17px; font-weight: 750; text-decoration: none; }
        .auth-panel {
            align-self: center;
            width: min(100%, 440px);
            padding: 34px;
            border: 1px solid #dce5dd;
            border-radius: 6px;
            background: #fff;
            box-shadow: 0 18px 55px rgb(30 55 42 / 8%);
        }
        h1 { margin: 0 0 8px; font-size: 29px; line-height: 1.2; letter-spacing: 0; }
        .intro { margin: 0 0 25px; color: #617069; line-height: 1.5; }
        .field { margin: 0 0 17px; }
        label { display: block; margin-bottom: 7px; font-size: 14px; font-weight: 650; }
        input {
            width: 100%;
            min-height: 45px;
            padding: 10px 12px;
            border: 1px solid #cbd7ce;
            border-radius: 4px;
            background: #fff;
            color: #1e2925;
            font: inherit;
        }
        input:focus { border-color: #28765d; outline: 3px solid rgb(40 118 93 / 15%); }
        button {
            width: 100%;
            min-height: 46px;
            border: 0;
            border-radius: 4px;
            background: #174d3e;
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }
        button:hover { background: #103c30; }
        .form-note { margin: 21px 0 0; color: #617069; font-size: 14px; text-align: center; }
        .back-link { display: inline-block; margin-top: 18px; }
        a { color: #17634b; font-weight: 650; }
        .notice { margin: 0 0 18px; padding: 11px 13px; border-radius: 4px; background: #eaf5eb; color: #205b35; }
        .errors { margin: 0 0 18px; padding: 11px 13px 11px 30px; border-radius: 4px; background: #fff0ed; color: #9d352b; font-size: 14px; }
        .page-footer { align-self: end; margin-top: 24px; color: #78847d; font-size: 12px; text-align: center; }
        @media (max-width: 480px) {
            .page-shell { padding: 22px 16px 18px; }
            .auth-panel { padding: 26px 21px; }
            h1 { font-size: 26px; }
        }
    </style>
</head>
<body>
    <main class="page-shell">
        <a class="brand" href="{{ route('home') }}">Forsais</a>
        <section class="auth-panel">
            @yield('content')
            <a class="back-link" href="{{ route('home') }}">Atpakaļ uz sākumlapu</a>
        </section>
        <footer class="page-footer">{{ config('app.name', 'Forsais') }} · {{ date('Y') }}</footer>
    </main>
</body>
</html>