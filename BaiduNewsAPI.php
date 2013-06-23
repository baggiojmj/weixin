<?php
#getBaiduNews("NBA");
    function getBaiduNews($query){
        $apiUrl="http://news.baidu.com/ns?word=".urlencode($query)."&tn=newstitle";  
        $html = file_get_contents($apiUrl);
#       echo $html; 
        $news = parseNews($html);
#       print_r($news);
        return $news;
    }

    function parseNews($str){
        $str = preg_replace("/<\/*font[^>]*>/", "", $str);  //font tag
        $str = preg_replace("/\&nbsp;/","",$str);   //nbsp~
        $news = array();
        preg_match_all("/a href=([^ ]+)  mon[^>]+>(.+)<\/a>([^>]+)</",$str,$mat);
        $idx = 0;
        for($i=0;$i<count($mat[0]);$i++){
            try{
                $url = $mat[1][$i];
                $title = iconv("GB2312","UTF-8//ignore",$mat[2][$i]);
                $source = iconv("GB2312","UTF-8//ignore",$mat[3][$i]);
                $news[$idx]['url']=$url;
                $news[$idx]['title']=$title; 
                $news[$idx]['source']=$source;
                $idx+=1;
            }catch(Exception $e){
            }
        }
        return $news;
    }
?>
