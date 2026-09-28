<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error</title>
    <style>
        body {
            text-align: center;
            padding: 50px;
            font-family: "Helvetica", "Arial", sans-serif;
        }
        h1 {
            font-size: 50px;
        }
        body {
            font: 20px Helvetica, sans-serif;
            color: #333;
        }
        article {
            display: block;
            text-align: left;
            width: 650px;
            margin: 0 auto;
        }
        a {
            color: #dc8100;
            text-decoration: none;
        }
        a:hover {
            color: #333;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <article>
        <h1>Oops!</h1>
        <div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <p>Terjadi kesalahan pada server kami. Silakan coba lagi nanti. atau klik <button type="submit" class="btn btn-link p-0 align-baseline">disini</button> untuk logout dulu!</p>
            </form>
            <p>&mdash; Tim Support</p>
        </div>
    </article>
</body>
</html>
