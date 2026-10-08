<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mana PHP lapa</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f2f2f2;
        }

        header {
            background: #333;
            color: white;
            padding: 20px;
            text-align: center;
        }

        nav {
            background: #444;
            padding: 10px;
            text-align: center;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
        }

        main {
            background: white;
            width: 80%;
            margin: 30px auto;
            padding: 30px;
            text-align: center;
        }

        footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 15px;
        }
    </style>
</head>

<body>

<header>
    <h1>Mana PHP mājaslapa</h1>
</header>

<nav>
    <a href="index.php">Sākums</a>
    <a href="#">Par mums</a>
    <a href="#">Kontakti</a>
</nav>

<main>
    <h2>Sveiki!</h2>

    <?php
        $vards = "Haralds";
        echo "<p>Sveiks, $vards!</p>";
    ?>

    <p>Šī ir vienkārša PHP bāzes lapa.</p>
</main>

<footer>
    <p>&copy; <?php echo date("Y"); ?> Mana lapa</p>
</footer>

</body>
</html>