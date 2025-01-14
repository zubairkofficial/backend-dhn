<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benachrichtigung: Kontingent fast ausgeschöpft</title>
</head>

<body>
    <h1>Benachrichtigung: Kontingent fast ausgeschöpft</h1>

    <img src="https://dhnapi.cyberifyportfolio.com/assets/logo.png" alt="Logo" style="width: 100px; height: 100px; border-radius: 50%;">

    <p>Sehr geehrter Kunde,</p>

    <p>dies ist eine automatische Benachrichtigung, dass das gebuchte Kontingent Ihrer Organisation für SDB2Excel zu <strong>90%</strong> ausgeschöpft ist. Bitte beachten Sie, dass keine weiteren SDB verarbeitet werden können, sobald das Limit überschritten ist.</p>

    <p>Details:</p>
    <ul>
        <li>Dienstname: <strong>{{ $details['serviceName'] }}</strong></li>
        <li>Aktueller Zählerstand: <strong>{{ $details['usageCount'] }}</strong></li>
        <li>Maximales Limit: <strong>{{ $details['userCounterLimit'] }}</strong></li>
    </ul>

    <p>Falls Sie SDB2Excel weiterhin nutzen möchten, kontaktieren Sie bitte Ihren Ansprechpartner bei Cretschmar, um weiteres Kontingent hinzuzufügen. Vielen Dank!</p>

    <p>Mit freundlichen Grüßen,</p>
    <p>Ihr Systemadministrator</p>
</body>

</html>
