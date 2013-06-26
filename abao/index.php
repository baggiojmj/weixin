<?php

//define your token
define("TOKEN", "abao");
traceHttp();
$wechatObj = new wechatCallbackapiTest();
$wechatObj->valid();

class wechatCallbackapiTest
{
	public function valid()
    {
        //$echoStr = $_GET["echostr"];
        
        //valid signature , option
        if($this->checkSignature()){
            #echo $echoStr;  //认证开这里
            include '../Tools.php';
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
                $contentStr = "欢迎使用,输入 买XX 搜索店铺商品";
            else{
                if(substr($keyword,0,3) == "买"){
                    $entityName = trim(substr($keyword,3,strlen($keyword)));
                    return $this->getShopItems($object,$entityName);
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

    private function getShopItems($object,$entityName){
        include '../TaobaoSearchAPI.php';
        $items = getTaobaoItems($entityName);
        if( $items == "" || count($items)==0)
            return $this->transmitText($object,"没货",0);
        $itemsStr = $this->transmitShopItems($items);
        return $this->transmitArticles($object,count($items),$itemsStr,0);         
    }

    private function transmitShopItems($items){
        $itemsStr = "";
        foreach($items as $item){
            $itemStr = "<item>
                <Title><![CDATA[".$item['title']."]]></Title>
                <Description></Description>
                <PicUrl>".$item['picurl']."</PicUrl>
                <Url>".$item['url']."</Url>
                </item>";
            $itemsStr = $itemsStr.$itemStr;
        }
        return $itemsStr; 
    }

    private function receiveEvent($object){
        logger("event".$object->Event); 
        if($object->Event == "subscribe"){
            return $this->transmitText($object, "亲，感谢您关注慕妍品牌专营店。尽享购物优惠资讯！爱慕妍，更贴心的享受！后续会有最新的精彩内容第一时间发送给您！输入[买+关键词]对您需要的宝贝进行搜索。如，输入[买慕妍睡衣]搜索慕妍品牌睡衣。更多功能开发中~",0);
        }
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

    private function transmitArticles($object, $count, $itemsStr, $funcFlag){
        $articleTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[news]]></MsgType>
            <Content><![CDATA[result]]></Content>
            <ArticleCount><![CDATA[%s]]></ArticleCount>
            <Articles>
            %s
            </Articles>
            <FuncFlag><![CDATA[%s]]></FuncFlag>
            </xml>";
        $resultStr = sprintf($articleTpl, $object->FromUserName, $object->ToUserName, time(), $count, $itemsStr, $funcFlag);
        return $resultStr;
    }

    private function transmitText($object, $content, $funcFlag){
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            <FuncFlag><![CDATA[%s]]></FuncFlag>
            </xml>";
        $resultStr = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content, $funcFlag);
        logger("resultStr:".$resultStr);
        return $resultStr;
    }

    private function transmitMusic($object, $musicArray,$funcFlag){
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
