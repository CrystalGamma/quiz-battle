<?php
class ContentNegotation{
	private static function parse_Accept($accept){
		$formatPreferences=explode(",",$accept);
		$preference=[];
		foreach($formatPreferences as $format){
			$temp=explode(";",$format);
			if(count($temp)==1){
				$preference[$temp[0]]=1;
			}else{
				$preference[$temp[0]]=explode("=",$temp[1])[1];
			}
		}
		arsort($preference);
		return $preference;
	}
    public static function getContent($accept, $server_Accept){
        $client=ContentNegotation::parse_Accept($accept);
        $server=ContentNegotation::parse_Accept($server_Accept);
        $content=[];
        if(!empty($accept)){
            foreach($client as $client_String => $client_Content){
                foreach($server as $server_String => $server_Content){
                    if($client_String == $server_String){
                        $content[$client_String] = ($client_Content*$server_Content);
                    }
                }
            }
            if(array_key_exists("*/*",$client) && count($content)!= count($server)){
                $q;
                if($client["*/*"]==1){
                    $q=0.1;
                }else{
                    $q=$client["*/*"];
                }
                foreach($server as $key => $value){
                    if(!array_key_exists($key, $content)){
                        $content[$key]=($value*$q);
                    }
                }
            }
        }else{
            $content=$server;
        }
        if(empty($content)){
            http_response_code(406);
        }
        arsort($content);
		return $content;
    }
}
?>