<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transkriptions-E-Mail</title>
</head>

<body>
    <h1>Transkriptions-E-Mail</h1>

    <img src="https://dhnapi.cyberifyportfolio.com/assets/logo.png" alt="Profilbild"
        style="width: 150px; height: auto; border-radius: 10px; display: block; max-width: 100%;">


    <p><strong>Datum:</strong> {{ $data['date'] }}</p>
    <p><strong>Autor:</strong> {{ $data['author'] }}</p>
    <p><strong>E-Mail:</strong> {{ $data['email'] }}</p>
    <p><strong>Teilnehmer:</strong> {{ $data['participants'] }}</p>

    @if (isset($data['summary']))
        <p><strong>Zusammenfassung:</strong></p>
        <p style="white-space: break-spaces">{!! nl2br($data['summary']) !!}</p>
    @endif

    <p>Vielen Dank,</p>
</body>

</html>
