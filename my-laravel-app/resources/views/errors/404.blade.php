<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lapa nav atrasta</title>
    <style>
        * { box-sizing: border-box; }
        body {
            display: grid;
            min-width: 320px;
            min-height: 100vh;
            margin: 0;
            padding: 24px;
            place-items: center;
            background: #f2f2f2;
            color: #222;
            font-family: Arial, sans-serif;
            text-align: center;
        }
        main {
            width: min(100%, 520px);
            padding: 32px 24px;
            background: #fff;
        }
        h1 { margin-top: 0; }
        a {
            display: inline-block;
            margin-top: 12px;
            padding: 12px 18px;
            border-radius: 4px;
            background: #333;
            color: #fff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <main>
        <h1>Lapa nav atrasta</h1>
        <p>Šī adrese neeksistē vai lapa ir pārvietota.</p>
        <a href="{{ route('home') }}">Atgriezties sākumlapā</a>
    </main>
</body>
</html>