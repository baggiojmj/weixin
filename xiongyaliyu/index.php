<?php
/**
  * wechat php test
  */

//define your token
define("TOKEN", "jiangyingdan");
traceHttp();
$wechatObj = new wechatCallbackapiTest();
$wechatObj->valid();

class wechatCallbackapiTest
{
	public function valid()
    {
        $echoStr = $_GET["echostr"];
        
        //valid signature , option
        if($this->checkSignature()){
            echo $echoStr;    //for check
            $this->responseMsg();
        	exit;
        }
    }

    public function responseMsg()
    {
		//get post data, May be due to the different environments
		//$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postStr = file_get_contents("php://input");
                
      	//extract post data
		if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            switch ($postObj->MsgType)
            {
                case "text":
                    $resultStr = $this->receiveText($postObj);
                    break;
                case "event":
                    $resultStr = $this->receiveEvent($postObj);
                     break;
                case "location":
                    $resultStr = $this->receiveLocation($postObj);
                default:
                    $resultStr = $this->receiveOther($postObj);                                                                                 
            }
            echo $resultStr;
        }else {
        	echo "";
        	exit;
        }
    }
    
    //解析，处理，得到返回字符串
    private function receiveText($object){
        $keyword = trim($object->Content);
		if(!empty( $keyword ))
        {
            if($keyword == "help")
                $contentStr = "欢迎使用，输入\n[翻译text] 球哥会帮你中英翻译!\n[梦见text] 球哥帮你分析分析!\n[听歌 title #歌手 singer] 点歌,#歌手 singer可以不填,不过填了能帮助我更好地知道你要的是什么歌";
            else{
                if(substr($keyword,0,6) == "梦见"){
                    $entityName = trim(substr($keyword,6,strlen($keyword)));
                    return $this->getDream($object,$entityName);
                }
                if(substr($keyword,0,6) == "翻译"){
                    $entityName = trim(substr($keyword,6,strlen($keyword)));
                    return $this->getTranslate($object,$entityName);
                }
                if(substr($keyword,0,6) == "听歌"){
                    $singerPos = strpos($keyword,"#歌手");
                    if($singerPos != false){
                        $song = trim(substr($keyword,6,$singerPos-7));
                        $singer = trim(substr($keyword,$singerPos+7));
                    }else{
                        $song = trim(substr($keyword,6));
                        $singer ="";
                    }
                    return $this->getSong($object,$song,$singer);
                }
                else{
                    $contentStr = "输入格式有误，输入[help]获得帮助";
                }
            }
        }else{
            $contentStr = "Input something...";
        }
        $resultStr = $this->transmitText($object, $contentStr, 0);
        return $resultStr;
    }

    private function receiveLocation($object){
        $x = $object->Location_X;
        $y = $object->Location_Y;
        $scale = $object->Scale;
        $label = $object->Label;
        logger("location:   x:".$x."    y:".$y."    scale:".$scale."    lable:".$label);
    }

    private function receiveEvent($object){

    }

    private function receiveOther($object){

    }

    //翻译 
    private function getTranslate($object,$entityName){
        
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
        $fromLanguage = "en";
        //$toLanguage   = "es";
        $toLanguage   = "hu";
        //$inputStr     = $_POST["txtToTranslate"];
        $inputStr = $entityName;
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
        
    $resultStr = $this->transmitText($object,$translatedStr,0);
    return $resultStr;
        
        /*
        if ($entityName == ""){
            $contentStr = "不说，我懒得帮你!";
        }else{
            $apihost = "http://api2.sinaapp.com/";
            $apimethod = "search/translate/?";
            $apiparams = array('appkey'=>"0020120430", 'appsecert'=>"fa6095e113cd28fd", 'reqtype'=>"text");
            $apikeyword = "&keyword=".urlencode($entityName);
            $apicallurl = $apihost.$apimethod.http_build_query($apiparams).$apikeyword;
            $api2str = file_get_contents($apicallurl);
            $api2json = json_decode($api2str, true);
            $contentStr = $api2json['text']['content'];
        }
        $resultStr = $this->transmitText($object, $contentStr, 0);
        return $resultStr;
        */
    }

    private function getSong($object,$song,$singer){
        $funcFlag = 0;
        if ($song == ""){
            $contentStr = "不理你";
            $resultStr = $this->transmitText($object, $contentStr, $funcFlag);
        }else{
            logger("song:".$song);
            logger("singer".$singer);
            $reqUrl = "http://box.zhangmen.baidu.com/x?op=12&count=1&title=".urlencode($song)."$$".urlencode($singer)."$$$$";
            logger("requrl:".$requrl);
            $api2str = file_get_contents($reqUrl);
            $apiobj = simplexml_load_string($api2str);
            logger("song count:".$apiobj->count);
            if ( !isset($apiobj->count) || $apiobj->count == 0){
                $contentStr = "没有找到音乐，换首歌试试！";
                $resultStr = $this->transmitText($object, $contentStr, $funcFlag);
            }else{
                //$idx = rand(0,$apiobj->count-1);
                $idx = 0;   //其他资源比较差
                logger("song idx:".$idx);
                $encode = $apiobj->url[$idx]->encode;

                $encode = substr($encode,0,strlen($encode)-2);
                $decode = $apiobj->url[$idx]->decode;
                $pos = strpos($decode,"&mid");
                if ($pos != false) {
                    $decode = substr($decode,0,$pos);
                }

                $musicUrl = $encode."".$decode;
                logger("music url:".$musicUrl);
                $musicArray = array("title"=>$song,
                        //"description"=>$api2json['music']['description'],
                        "description"=>$singer,
                        "MusicUrl"=>$musicUrl,
                        "HQMusicUrl"=>$musicUrl);
                $resultStr = $this->transmitMusic($object, $musicArray, $funcFlag);
            }
        }
        return $resultStr;
    }

    //解梦
    private function getDream($object,$entityName){
        if ($entityName == ""){
            $contentStr = "你什么也不说，我怎么知道?";
        }else{
            $apihost = "http://api2.sinaapp.com/";
            $apimethod = "search/dream/?";
            $apiparams = array('appkey'=>"0020130430", 'appsecert'=>"fa6095e113cd28fd", 'reqtype'=>"text");
            $apikeyword = "&keyword=".urlencode($entityName);
            $apicallurl = $apihost.$apimethod.http_build_query($apiparams).$apikeyword;
            $api2str = file_get_contents($apicallurl);
            $api2json = json_decode($api2str, true);
            $contentStr = $api2json['text']['content'];
        }
        $resultStr = $this->transmitText($object, $contentStr, 0);
        logQA($entityName,$contentStr);
        return $resultStr;
    }

	private function checkSignature()
	{
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}

    private function transmitText($object, $content, $funcFlag)
    {
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            <FuncFlag><![CDATA[%s]]></FuncFlag>
            </xml>";
        $resultStr = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content, $funcFlag);
        return $resultStr;
    }

    private function transmitMusic($object, $musicArray,$funcFlag)
    {
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[music]]></MsgType>
            <Music>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <MusicUrl><![CDATA[%s]]></MusicUrl>
            <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
            </Music>
            <FuncFlag><![CDATA[%s]]></FuncFlag>
            </xml>";
        $resultStr = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $musicArray['title'], $musicArray['description'], $musicArray['MusicUrl'], $musicArray['HQMusicUrl'],$funcFlag);
        return $resultStr;
    }
}

function traceHttp()
{
    logger("REMOTE_ADDR:".$_SERVER["REMOTE_ADDR"].((strpos($_SERVER["REMOTE_ADDR"],"101.226"))===0?" From WeiXin":" Unknown IP"));
    logger("QUERY_STRING:".$_SERVER["QUERY_STRING"]);
}

function logQA($Q,$A)
{
    logger("Q:  ".$Q."A:    ".$A);
}

function logger($content)
{
    //file_put_contents("weixin.log", date('Y-m-d H:i:s    ').$content."<br>", FILE_APPEND);
    file_put_contents("log.html", date('Y-m-d H:i:s    ').$content."<br>", FILE_APPEND);
}

?>