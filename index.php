<?php

//define your token
define("TOKEN", "baggiojmj");
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
            include 'Tools.php'; 
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
                    break;
                case "image":
                    $resultStr = $this->receiveImage($postObj);
                    break;
                default:
                    $resultStr = $this->receiveOther($postObj);                                                                                 
            }
            echo $resultStr;
        }else {
        	echo "";
        	exit;
        }
    }
    
    //文本消息处理
    private function receiveText($object){
        $keyword = trim($object->Content);
		if(!empty( $keyword ))
        {
            if($keyword == "help")
                $contentStr = "欢迎使用，输入\n[翻译text] 帮你中英翻译!\n[梦见text] 帮你分析分析!\n[听歌 title #歌手 singer] 点歌,#歌手 singer可以不填\n\n新功能\n6.22 查询周边:\n先发送位置(点对话框的+按钮，再点位置,发送);\n然后输入[找text]可查附近服务信息(例如，找银行)。\n\n6.23 看新闻:\n输入[新闻text] 查相关新闻(例如 新闻习大大)。\n\n6.29 菜谱:\n输入[菜谱 text] 查找食材能做些什么(例如 菜谱 黄瓜 冷面)\n\n6.30 名人脸:\n发送正脸自拍照，等待后，输入 要脸 返回相似名人脸。";
            else{
                if(substr($keyword,0,6) == "梦见"){
                    $entityName = trim(substr($keyword,6,strlen($keyword)));
                    return $this->getDream($object,$entityName);
                }
                if(substr($keyword,0,6) == "翻译"){
                    $entityName = trim(substr($keyword,6,strlen($keyword)));
                    return $this->getTranslate($object,$entityName);
                }
                if(substr($keyword,0,6) == "菜谱"){
                    $entityName = trim(substr($keyword,6,strlen($keyword)));
                    return $this->getRecipes($object,$entityName);
                }
                if(substr($keyword,0,6) == "新闻"){
                    $entityName = trim(substr($keyword,6,strlen($keyword)));
                    return $this->getNews($object,$entityName);
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
                if(substr($keyword,0,3) == "找"){
                    $entityName = trim(substr($keyword,3,strlen($keyword)));
                    return $this->getNearBy($object,$entityName);
                }
                if(substr($keyword,0,3) == "淘"){
                    $entityName = trim(substr($keyword,3,strlen($keyword)));
                    return $this->getShopItems($object,$entityName);
                }
                if($keyword == "要脸"){
                    return $this->getFaceSimItems($object);
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

    //位置消息处理
    private function receiveLocation($object){
        $x = $object->Location_X;
        $y = $object->Location_Y;
        $scale = $object->Scale;
        $label = $object->Label;
        logger("location:   x:".$x."    y:".$y."    scale:".$scale."    lable:".$label);
        $this->saveLocation($object);  //save user location
    }

    private function saveLocation($locObj){
        include 'userData.php';
        $key = $this->getLocKey($locObj);    
        $loc = array('x'=>$locObj->Location_X, 'y'=>$locObj->Location_Y,'r'=>$locObj->Scale,'l'=>$locObj->Label);
        $text = implode(",", $loc);  
        logger("save locObj:".$text);
        
        UserData::getInstance()->$key = json_encode($loc);
    }

    private function getLocation($queryObject){
        $key = $this->getLocKey($queryObject);
        include 'userData.php';
        return json_decode(UserData::getInstance()->$key,true);
    }


    //查询周边
    private function getNearBy($queryObject,$entityName){
        if ($entityName == "")
            return $this->transmitText($queryObject, "你没说要找什么", 0);
        $locObj = $this->getLocation($queryObject);
        if (!isset($locObj) || $locObj == "")
            return $this->transmitText($queryObject, "亲，我还不知道你在哪", 0);
        $apihost = "http://api.map.baidu.com/place/v2/";
        $apimethod = "search?";
        $apiparams = array('query'=>urlencode($entityName), 'location'=>"39.915,116.404",'radius'=>"2000", 'ak'=>"1339018708476b8ef2de89a1cff77ef0",'output'=>"xml");
        $apiTpl = "http://api.map.baidu.com/place/v2/search?query=%s&location=%s,%s&radius=%s&ak=1339018708476b8ef2de89a1cff77ef0&output=json";
        $apicallurl = sprintf($apiTpl, urlencode($entityName), $locObj['x'][0], $locObj['y'][0], $locObj['r'][0]*100);
        
        $api2str = file_get_contents($apicallurl);
        $api2json = json_decode($api2str, true);
        if($api2json['status'] != "0")
            return $this->transmitText($queryObject, "查询失败了 T_T", 0);
        $res=$api2json['results'];
        if(count($res) == 0 )
            return $this->transmitText($queryObject, "附近没有找到结果 T_T", 0);
        $itemStr = $this->transmitLocationItems($res);
        return $this->transmitArticles($queryObject,count($res),$itemStr,0);
    }

    //查询店铺消息
    private function getShopItems($object,$entityName){
        include 'TaobaoSearchAPI.php';
        $items = getTaobaoItems($entityName,"lovemuyan");
        if( $items == "" || count($items)==0)
            return $this->transmitText($object,"没货",0);
        $itemsStr = $this->transmitShopItems($items);
        return $this->transmitArticles($object,count($items),$itemsStr,0);         
    }

    //事件处理
    private function receiveEvent($object){
        logger("event".$object->Event); 
        if($object->Event == "subscribe"){
            return $this->transmitText($object, "哈哈，又多了位新朋友，欢迎关注球哥，输入[help]获得帮助",0);
        }
    }

    //图片处理
    private function receiveImage($object){
        $key = $this->getFaceKey($object);
        logger("key: ".$key." image: ".$object->PicUrl);
        $cmd = "php FaceSim.php ".$key." ".$object->PicUrl;
        logger("cmd: ".$cmd);
        system("{$cmd} > /dev/null &");
        return $this->transmitText($object,"图片处理耗时较长，请等待大约30秒后，输入 要脸 查看结果",0);
    }
    
    //获得相似脸
    private function getFaceSimItems($object){
        $key = $this->getFaceKey($object);
        include 'FaceSim.php';
        $items = getFaceSim($key);
        if (!isset($items) || $items == "" || count($items) == 0)
            return $this->transmitText($object,"没有找到相似的明星脸",0);
        $itemsStr = $this->transmitFaceSimItems($items);
        return $this->transmitArticles($object,count($items),$itemsStr,0);         
    }

    //TODO
    private function receiveOther($object){

    }

    //翻译
    private function getTranslate($object,$entityName){
        if ($entityName == ""){
            $contentStr = "不说，我懒得帮你!";
        }else{
            include "xiongyaliyu/MicroSoftTranslateAPI.php";
            if( hasChinese($entityName)){
                $contentStr = getMSTranslate($entityName,"zh-CHS","en");
            }else{
                $contentStr = getMSTranslate($entityName,"en","zh-CHS");
            }
        }
        return $this->transmitText($object, $contentStr, 0);
    }

    //菜谱
    private function getRecipes($object,$entityName){
        if ($entityName == ""){
            $contentStr = "巧妇难为无米之炊";
        }else{
            include "MeishiSearchAPI.php";
            $items = getRecipeItems($entityName);
            if( $items == "" || count($items)==0)
                return $this->transmitText($object,"这几个球哥也不会做",0);
            $itemsStr = $this->transmitRecipeItems($items);
            return $this->transmitArticles($object,count($items),$itemsStr,0);         
        }
        return $this->transmitText($object, $contentStr, 0);
    }

    //查询新闻
    private function getNews($object,$entityName){
        if ($entityName == ""){
            return $this->transmitText($object,"请输入新闻关键字呀",0);
        }else{
            include 'BaiduNewsAPI.php';
            $news = getBaiduNews($entityName);
            if( $news == "" || count($news)==0)
                return $this->transmitText($object,"没啥大事",0);
            $newsStr = $this->transmitNewsItems($news);
            return $this->transmitArticles($object,count($news),$newsStr,0);
        }
    }

    //组织新闻items
    private function transmitNewsItems($news){
        $newsStr = "";
        foreach($news as $item){
            $itemStr = "<item>
                <Title><![CDATA[".$item['title']."\n".$item['source']."]]></Title>
                <Description></Description>
                <PicUrl></PicUrl>
                <Url>".$item['url']."</Url>
                </item>";
            $newsStr = $newsStr.$itemStr;
        }
        return $newsStr; 
    }

    //组织店铺items
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
    
    //组织食谱items
    private function transmitRecipeItems($items){
        $itemsStr = "";
        foreach($items as $item){
            $itemStr = "<item>
                <Title><![CDATA[".$item['title']."\n".$item['detail']."]]></Title>
                <Description></Description>
                <PicUrl>".$item['picurl']."</PicUrl>
                <Url>".$item['url']."</Url>
                </item>";
            $itemsStr = $itemsStr.$itemStr;
        }
        return $itemsStr; 
    }
    
    //组织食谱items
    private function transmitFaceSimItems($items){
        $itemsStr = "";
        foreach($items as $item){
            $itemStr = "<item>
                <Title><![CDATA[".$item['name']."\n相似度 ".$item['sim']."]]></Title>
                <Description></Description>
                <PicUrl>".$item['picurl']."</PicUrl>
                <Url>".$item['picurl']."</Url>
                </item>";
            $itemsStr = $itemsStr.$itemStr;
        }
        return $itemsStr; 
    }
    
    //组织附近信息items
    private function transmitLocationItems($items){
        $itemsStr = "";
        foreach($items as $item){
            $itemStr = "<item>
                <Title><![CDATA[".$item['name']."\n".$item['address']."]]></Title>
                <Description></Description>
                <PicUrl></PicUrl>
                <Url></Url>
                </item>";
            $itemsStr = $itemsStr.$itemStr;
        }
        return $itemsStr; 
    }
    
    //获得歌曲
    private function getSong($object,$song,$singer){
        $funcFlag = 0;
        if ($song == ""){
            $contentStr = "不理你";
            $resultStr = $this->transmitText($object, $contentStr, $funcFlag);
        }else{
            logger("song:".$song);
            logger("singer".$singer);
            $reqUrl = "http://box.zhangmen.baidu.com/x?op=12&count=1&title=".urlencode($song)."$$".urlencode($singer)."$$$$";
            logger("requrl:".$reqUrl);
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

    //组织图文返回消息
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

    //组织文本返回消息
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

    //组织音频返回消息
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

    private function getLocKey($obj){
        return $obj->FromUserName."_loc"; 
    }

    private function getFaceKey($obj){
        return $obj->FromUserName."_face"; 
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
