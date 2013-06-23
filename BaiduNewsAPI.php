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
        preg_match_all("/a href=([^ ]+)  mon[^>]+>([^<]+)<\/a>([^>]+)</",$str,$mat);
        $idx = 0;
        for($i=0;$i<count($mat[0]);$i++){
            $news[$idx]['url']=$mat[1][$i];
            $news[$idx]['title']=iconv("GB2312", "UTF-8",$mat[2][$i]); 
            $news[$idx]['source']=iconv("GB2312", "UTF-8", $mat[3][$i]);
            $idx+=1;
        }
        return $news;
    }

    //not used
    function  html_entity_decode_utf8($string){
        static $trans_tbl;

        $string = preg_replace('~&#x([0-9a-f]+);~ei', 'code2utf(hexdec("\\1"))', $string);
        $string = preg_replace('~&#([0-9]+);~e', 'code2utf(\\1)', $string);

        if (!isset($trans_tbl)){
            $trans_tbl = array();

            foreach (get_html_translation_table(HTML_ENTITIES) as $val=>$key)
                $trans_tbl[$key] = utf8_encode($val);
        }
        return strtr($string, $trans_tbl);
    }
 
    function code2utf($num){
        if ($num < 128) return chr($num);
        if ($num < 2048) return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
        if ($num < 65536) return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
        if ($num < 2097152) return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
        return '';
    }
 
    function utf8_urldecode($str){
        $str = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($str));
        return html_entity_decode($str,null,'UTF-8');
    }
?>
