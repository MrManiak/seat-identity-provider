<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SAML SSO</title>
</head>
<body onload="document.forms[0].submit();">
    <noscript>
        <p>JavaScript is required. Please click the button below to continue.</p>
    </noscript>
    <form method="POST" action="{{ $destination }}">
        <input type="hidden" name="SAMLResponse" value="{{ $samlResponse }}">
        @if($relayState)
            <input type="hidden" name="RelayState" value="{{ $relayState }}">
        @endif
        <noscript>
            <button type="submit">Continue</button>
        </noscript>
    </form>
</body>
</html>
