<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error</title>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f8f9fa;
            color: #333;
        }

        .container {
            text-align: center;
        }

        h1 {
            font-size: 6rem;
            margin: 0;
            color: #dc3545;
        }

        p {
            font-size: 1.5rem;
            color: #6c757d;
        }

        a {
            color: #0d6efd;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>500</h1>
        <?php
        /** @var string $message */
        ?>
        <p><?= e($message) ?></p>
        <a href="/">Back</a>
    </div>
</body>

</html>