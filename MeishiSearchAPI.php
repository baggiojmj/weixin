<?php
    include 'simple_html_dom.php';
#   $query="西红柿 鸡蛋";
#   $res = getRecipeItems($query);
#   print_r($res);
    function getRecipeItems($query){

        $url = "http://home.meishichina.com/search2/".$query."/";
#echo $url."\n";

        $html = file_get_html($url);
    
        $items = array();
        $idx = 0;
        $m6list = $html->find('div[class=m6list]',0);
        if( isset($m6list)){
            foreach($m6list->find('li') as $li){
                $cover = $li->find('div[class=cover]',0);
                if( !isset($cover))
                    continue;
                $a = $cover->find("a",0);
                if(isset($a)){
                    $items[$idx]['title']=$a->title;
                    $items[$idx]['url']=$a->href;
                    $items[$idx]['picurl']=$a->find("img",0)->src;
                }else{
                    continue;
                }
                $p = $li->find('div[class=detail]',0)->find('p',0);
                if(isset($p))
                    $items[$idx]['detail']=$p->innertext;
                if( ++$idx >= 10)
                    break;
            }
        }
        return $items;
    }

?>
