<?php

if('POST' == $_SERVER['REQUEST_METHOD'])
{
    include 'HttpTranslator.php';
    include 'AccessTokenAuthentication.php';

    try {
        //Client ID of the application.
        //$clientID       = "fc9827b7-1512-44d3-93b4-5a088ad4d4b9";
        $clientID   = "xiongyaliyu";
        //Client Secret key of the application.
        //$clientSecret = "f4xHZtBN7o9g6V5Uuyfy0mtAzpWDRjMTpXEkMjwu1Fg=";
        $clientSecret = "MeD+7YJsG+e06K+ZJseOOpSb6NwbYY2nu5nRAboVdsw=";
        //OAuth Url.
        $authUrl      = "https://datamarket.accesscontrol.windows.net/v2/OAuth2-13/";
        //Application Scope Url
        $scopeUrl     = "http://api.microsofttranslator.com";
        //Application grant type
        $grantType    = "client_credentials";

        //Create the AccessTokenAuthentication object.
        $authObj      = new AccessTokenAuthentication();
        //Get the Access token.
        $accessToken  = $authObj->getTokens($grantType, $scopeUrl, $clientID, $clientSecret, $authUrl);
        //Create the authorization Header string.
        $authHeader = "Authorization: Bearer ". $accessToken;

        //Set the params.//
        //$fromLanguage = "en";
        //$toLanguage   = "es";
        $toLanguage   = "hu";
        $inputStr     = $_POST["txtToTranslate"];
        $contentType  = 'text/plain';
        $category     = 'general';
    
        $params = "text=".urlencode($inputStr)."&to=".$toLanguage."&from=".$fromLanguage;
        $translateUrl = "http://api.microsofttranslator.com/v2/Http.svc/Translate?$params";
    
        //Create the Translator Object.
        $translatorObj = new HTTPTranslator();
    
        //Get the curlResponse.
        $curlResponse = $translatorObj->curlRequest($translateUrl, $authHeader);
    
        //Interprets a string of XML into an object.
        $xmlObj = simplexml_load_string($curlResponse);
        foreach((array)$xmlObj[0] as $val){
            $translatedStr = $val;
        }

    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . PHP_EOL;
    }
}

?>

<!DOCTYPE html>

<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title></title>
    </head>
    <body>
        <form action="translate.php" method="post">
            Text to translate: <input type="text" name="txtToTranslate" value="<?php echo($inputStr); ?>"  />
            <input type="submit" value="Translate">
            
        </form>
        <p>Translated Text: <?php echo($translatedStr); ?></p>
    </body>
</html>
