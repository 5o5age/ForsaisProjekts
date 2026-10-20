<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mana PHP lapa</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            min-width: 320px;
            background: #f2f2f2;
        }

        header {
            background: #333;
            color: white;
            padding: 20px;
            text-align: center;
        }

        nav {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px 24px;
            background: #444;
            padding: 10px 16px;
        }

        nav a {
            color: white;
            text-decoration: none;
        }

        main {
            background: white;
            width: min(80%, 900px);
            margin: 30px auto;
            padding: 30px;
            text-align: center;
        }

        .auth-actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .auth-actions a {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 4px;
            background: #333;
            color: white;
            text-decoration: none;
        }

        .auth-actions form {
            margin: 0;
        }

        .auth-actions button {
            min-height: 42px;
            padding: 10px 20px;
            border: 0;
            border-radius: 4px;
            background: #333;
            color: white;
            font: inherit;
            cursor: pointer;
        }

        .auth-actions a:hover,
        .auth-actions button:hover {
            background: #555;
        }

        footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 15px;
        }

        @media (max-width: 560px) {
            main {
                width: calc(100% - 32px);
                padding: 24px 18px;
            }
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
        <h2>Sveiki!</h2>
        <p>Sveiks, cilvēk!</p>
        <p>Šī ir vienkārša PHP bāzes lapa.</p>

        @guest
            <div class="auth-actions">
                <a href="{{ route('register') }}">Reģistrēties</a>
                <a href="{{ route('login') }}">Pieslēgties</a>
            </div>
        @else
            <p>Sveiks, {{ auth()->user()->name }} {{ auth()->user()->surname }}!</p>
            <div class="auth-actions">
                <a href="{{ route('dashboard') }}">Mans konts</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Iziet no konta</button>
                </form>
            </div>
        @endguest
    </main>

    <footer>
        <p>&copy; {{ date('Y') }} Mana lapa</p>
    </footer>
</body>
</html>