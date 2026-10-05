<?php
$title = "Mana PHP lapa";
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            color: #222;
        }

        header {
            background: #222;
            color: white;
            padding: 20px;
            text-align: center;
        }

        nav {
            margin-top: 10px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
        }

        main {
            max-width: 900px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        button {
            margin-top: 20px;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            background: #007bff;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }

        footer {
            text-align: center;
            padding: 20px;
            margin-top: 50px;
            background: #222;
            color: white;
        }
    </style>
</head>

<body>

<header>
    <h1><?= $title ?></h1>

    <nav>
        <a href="index.php">Sākums</a>
        <a href="#">Par mums</a>
        <a href="#">Kontakti</a>
    </nav>
</header>

<main>
    <h2>Sveiki!</h2>

    <p>
        Šī ir mana vienkāršā PHP mājaslapa.
        PHP darbojas servera pusē un ļauj veidot dinamiskas lapas.
    </p>

    <button onclick="alert('Sveiki no PHP lapas!')">
        Spied mani
    </button>
</main>

<footer>
    &copy; <?= date("Y") ?> Mana PHP lapa
</footer>

</body>
</html>