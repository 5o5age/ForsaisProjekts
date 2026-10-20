<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} | Mana PHP lapa</title>
    <style>
        * { box-sizing: border-box; }
        body {
            min-width: 320px;
            margin: 0;
            background: #f2f2f2;
            font-family: Arial, sans-serif;
        }
        header {
            padding: 20px;
            background: #333;
            color: white;
            text-align: center;
        }
        nav {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px 24px;
            padding: 10px 16px;
            background: #444;
        }
        nav a { color: white; text-decoration: none; }
        main {
            width: min(80%, 900px);
            margin: 30px auto;
            padding: 30px;
            background: white;
            text-align: center;
        }
        .home-link {
            display: inline-block;
            margin-top: 12px;
            padding: 12px 20px;
            border-radius: 4px;
            background: #333;
            color: white;
            text-decoration: none;
        }
        footer {
            padding: 15px;
            background: #333;
            color: white;
            text-align: center;
        }
        @media (max-width: 560px) {
            main { width: calc(100% - 32px); padding: 24px 18px; }
        }
    </style>
</head>
<body>
    <header>
        <h1>Mana PHP mājaslapa</h1>
    </header>
    <nav>
        <a href="{{ route('home') }}">Sākums</a>
        <a href="{{ route('about') }}">Par mums</a>
        <a href="{{ route('contact') }}">Kontakti</a>
    </nav>
    <main>
        <h2>{{ $title }}</h2>
        <p>{{ $description }}</p>
        <a class="home-link" href="{{ route('home') }}">Atgriezties sākumā</a>
    </main>
    <footer>
        <p>&copy; {{ date('Y') }} Mana lapa</p>
    </footer>
</body>
</html>