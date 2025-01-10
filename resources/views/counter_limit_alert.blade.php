<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benachrichtigung: Werkzeug-Limit erreicht</title>
</head>

<body>
    <h1>Benachrichtigung: Werkzeug-Limit erreicht</h1>

    <img src="https://dhnapi.cyberifyportfolio.com/assets/logo.png" alt="Logo" style="width: 100px; height: 100px; border-radius: 50%;">

    <p>Sehr geehrtes Team,</p>

    <p>Dies ist eine automatische Benachrichtigung, dass das Werkzeug-Kontingent Ihrer Organisation <strong>90%</strong> der maximal erlaubten Grenze erreicht hat. Bitte beachten Sie, dass keine weiteren Werkzeuge hinzugefügt werden können, sobald das Limit überschritten ist.</p>
    
    <p>Details:</p>
    <ul>
        <li>Dienstname: <strong>{{ $details['serviceName'] }}</strong></li>
        <li>Aktueller Zählerstand: <strong>{{ $details['usageCount'] }}</strong></li>
        <li>Maximales Limit: <strong>{{ $details['userCounterLimit'] }}</strong></li>
    </ul>
    
    <p>Bitte unternehmen Sie rechtzeitig Maßnahmen, um sicherzustellen, dass der Betrieb Ihrer Organisation nicht beeinträchtigt wird.</p>

    <p>Vielen Dank für Ihre Aufmerksamkeit.</p>

    <p>Mit freundlichen Grüßen,</p>
    <p>Ihr Systemadministrator</p>
</body>

</html>
