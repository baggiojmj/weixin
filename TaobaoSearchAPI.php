<?php
    include 'simple_html_dom.php';
#   $query="大嘴猴";
#   $items = getTaobaoItems($query);    
#   print_r($items);
    function getTaobaoItems($query,$shop="lovemuyan"){
        $str_gbk=urlencode(mb_convert_encoding($query, "gb2312", "utf-8"));
        $url = "http://".$shop.".taobao.com/search.htm?q=".$str_gbk."&searcy_type=item&s_from=newHeader&source=&ssid=s5-e&search=y";
        $html = file_get_html($url);
        $items = array();
        $notfound = $html->find('p[class=item-not-found]');
        if(!isset($notfound) || count($notfound) != 0)
            return $items;
        $idx = 0;
        foreach($html->find('dl[class=item]') as $dl){
            $dt = $dl->find('dt[class=photo]',0);
            $a = $dt->find("a",0);
#           echo $a->href."\n";
            $img = $a->find("img",0);
            $alt = "alt";
            $imgdata = "data-ks-lazyload";
#           echo conv($img->$alt)."\n";
#           echo conv($img->$imgdata)."\n";
            $dd = $dl->find('dd[class=detail]',0);
            $count = $dd->find("div[class=attribute]",0)->find("div[class=sale-area]",0)->find("span[class=sale-num]",0);
                
#           echo $count->outertext."\n";
#           echo $count->innertext."\n";

            $items[$idx]['url']=$a->href;
            $items[$idx]['title']=conv($img->$alt);
            $items[$idx]['picurl']=$img->$imgdata;
            $items[$idx]['salecount']=$count->innertext;
            $idx++;
            if($idx == 10)  //max count = 10
                break;
        }
        return $items;
    }

    function conv($str){
        $str = preg_replace("/<\/*span[^>]*>/", "", $str);  //font tag 
        return iconv("GB2312","UTF-8//ignore",$str);
    }

?>
