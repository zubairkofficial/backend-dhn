<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Willkommen</title>
    <style>
        /* Global Styles */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f7f7f7;
        }

        h2 {
            color: #567BD9;
            font-size: 24px;
            margin: 20px 0;
            text-align: center;
        }

        p {
            color: #333;
            font-size: 16px;
            line-height: 1.5;
            margin: 0;
        }

        a {
            color: #567BD9;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        /* Main Container */
        .container {
            width: 100%;
            height: 100vh;
            background-image: url('http://127.0.0.1:8000/images/wave-bg.png');
            background-position: center;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .content {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            padding: 30px;
            box-sizing: border-box;
            text-align: center;
        }

        .logo {
            height: 100px;
            margin-bottom: 20px;
        }

        .message {
            text-align: left;
            margin-top: 20px;
            padding: 0 20px;
        }

        .footer {
            font-size: 14px;
            color: #555;
            margin-top: 30px;
        }

        .footer a {
            color: #567BD9;
        }

        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <div>
                <img src="https://dhnapi.cyberifyportfolio.com/logos/1727182162.svg" class="logo" alt="Cretschmar Logo">
            </div>
            <h2>Willkommen {{ $data->name }}</h2>
            <div class="message">
                <p>
                    Ihre Registrierung war erfolgreich! Sie können sich nun mit Ihren Angaben anmelden.
                </p>
                <p>
                    Bitte beachten Sie, dass Sie in der DEMO-Version das Ergebnis nur an die von Ihnen hinterlegte Email senden können, 
                    und diese Datei nur einen exemplarischen Teil der Gefahrgutinformationen enthält. In der Vollversion können Sie die kompletten Daten direkt auf Ihr Gerät herunterladen.
                </p>
            </div>
            <div class="footer">
                <p>Bei Fragen wenden Sie sich bitte an 
                    <a href="mailto:denny.steude@cretschmar.de">denny.steude@cretschmar.de</a>.
                </p>
                <p>Ihr Cretschmar-Team</p>
            </div>
        </div>
    </div>
</body>
</html>
