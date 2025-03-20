<!DOCTYPE html>
<html>

<head>
    <title>Verarbeitete Datei</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
        }

        .email-container {
            width: 600px;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <p>Hallo {{ $user->name }},</p>

        <p>im Anhang finden Sie die Excel-Tabelle mit den extrahierten Informationen der SDB. Dies ist eine automatisch
            generierte E-Mail. Bei Rückfragen wenden Sie sich bitte direkt an <a
                href="mailto:denny.steude@cretschmar.de">denny.steude@cretschmar.de</a>. Weitere Informationen finden Sie
            auf unserer Homepage <a href="https://www.cretschmar.de">www.cretschmar.de</a>.</p>

        <p>Mit freundlichen Grüßen,<br>
            Ihr Cretschmar-Team</p>
    </div>
</body>

</html>
