<?php
    //echo getMSTranslate("hello","en","zh-CHS");
    //翻译 
    function getMSTranslate($str, $fromLanguage="en",$toLanguage="en"){
        if ($str === ""){
            return $str;
        }
        include 'HttpTranslator.php';
        include 'AccessTokenAuthentication.php';

        try {
            //Client ID of the application.
            $clientID   = "xiongyaliyu";
            //Client Secret key of the application.
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
            //$toLanguage   = "hu";
            $contentType  = 'text/plain';
            $category     = 'general';
    
            $params = "text=".urlencode($str)."&to=".$toLanguage."&from=".$fromLanguage;
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
        return $translatedStr;
    }

?>
