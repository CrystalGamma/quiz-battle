<?php
class ContentNegotation{
	private static function parse_Accept($accept){ //Funktion zum parsen des Accepts
		$formatPreferences=explode(",",$accept);
		$preference=[];
		foreach($formatPreferences as $format){
			$temp=explode(";",$format);
			if(count($temp)==1){
				$preference[$temp[0]]=1;
			}else{
				$number=trim(explode("=",$temp[1])[1]);
				if(preg_match("/^[0-9]+(.[0-9]+)?$/",$number)){ //Kontrolle das als Präferenz nur eine Zahl angegeben ist
					$preference[trim($temp[0])]=$number;
				}else{
					http_response_code(406);
				}
			}
		}
		arsort($preference);
		return $preference;
	}
    public static function getContent($accept, $server_Accept){ //Funktion zum ContentNegotation zwischen zwei Accepts
        $client=ContentNegotation::parse_Accept($accept);
        $server=ContentNegotation::parse_Accept($server_Accept);
        $content=[];
        if(!empty($accept)){
            foreach($client as $client_String => $client_Content){
                foreach($server as $server_String => $server_Content){
                    if($client_String == $server_String){
                        $content[$client_String] = ($client_Content*$server_Content); //Multiplizieren der Präferenzen
                    }
                }
            }
            if(array_key_exists("*/*",$client) && count($content)!= count($server)){ //Berücksichtigung des beliebigen Contents
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
		return key($content); //Rückgabe des Contents mit der höchsten Präferenz
    }
}
?>
