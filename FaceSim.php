<?php
#   $picurl="http://mmsns.qpic.cn/mmsns/ZCvlggClXcxanQiaNLXK458w0vbu8IxobmIoukdaygH2kS6IVq6j4Yg/0";
#   $items = getSimFaceItems($picurl);
#   print_r($items);
    if(isset($argc)){
        if($argc >2){
            $key = $argv[1];
            $picurl = $argv[2];
            $items = getSimFaceItems($picurl);
            saveFaceSim($key,$items);
        }else if ($argc == 2){
            $key = $argv[1];
            $items = getFaceSim($key);
            print_r($items);
        }
    }

    function getSimFaceItems($picurl){
        $detectUrl = "http://www.pictriev.com/facedbj.php?findface&image=".$picurl;
        $api2str = file_get_contents($detectUrl);
        $api2json = json_decode($api2str, true);
        
        $items=array();
        
        if(!isset($api2json['nfaces']) || $api2json['nfaces'] == 0 )    //没有识别到
            return $items;

        $imageid = $api2json['imageid']; 
        
        if(!isset($imageid))
            return $items;
        $idx=0;
        
        $simUrl = "http://www.pictriev.com/facedbj.php?whoissim&imageid=".$imageid."&faceid=0&lang=zh";
        
        $api2str = file_get_contents($simUrl);
        $api2json = json_decode($api2str, true);
#        print_r($api2json);
        
        foreach ($api2json['attrs'] as $attr){
            $items[$idx]['name'] =  $attr[2];
            $items[$idx]['sim'] = sprintf("%.2f%%", $attr[1]*100);
            $items[$idx]['picurl']= "http://www.pictriev.com/imgj.php?facex=".$attr[3];
                        
            if(++$idx >= 10)
                break;
        }

        return $items;
    }

    function saveFaceSim($key,$items){
        include 'userData.php';
#$text = implode(",", $items);  
#        logger("save faceSimObj:".$text);
        UserData::getInstance()->$key = json_encode($items);
    }

    function getFaceSim($key){
        include 'userData.php';
        return json_decode(UserData::getInstance()->$key,true);
    }

?>
