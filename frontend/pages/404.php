<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>404 - Page Not Found</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        :root {
            --primary: #06B6D4;
            --primary-dark: #0891B2;
            --bg: #f8fafc;
            --text: #0f172a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #06B6D4, #0ea5e9);
            color: white;
        }

        .container {
            text-align: center;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(12px);
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 90%;
        }

        .code {
            font-size: 90px;
            font-weight: 800;
            letter-spacing: 5px;
            margin-bottom: 10px;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        p {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 30px;
        }

        a {
            display: inline-block;
            text-decoration: none;
            background: white;
            color: var(--primary);
            padding: 12px 24px;
            border-radius: 999px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        a:hover {
            background: var(--primary-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .container {
            animation: float 4s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }
    </style>
</head>

<body>
    <?php $BASE_URL = '/jasaan-tourism'; ?>
    <div class="container">
        <div class="code">404</div>
        <h1>Page Not Found</h1>
        <p>The page you're looking for doesn’t exist or has been moved.</p>
        <a href="<?= $BASE_URL ?>/explore">Go back to Explore</a>
    </div>
</body>

</html>