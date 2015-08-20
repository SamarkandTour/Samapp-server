<?php
    // Curl Json data from Food&Drink Category
    $en_foodndrink_query = "http://localhost/w/api.php?action=query&gcmtitle=Category:Food%26Drink&generator=categorymembers&format=json&continue=&prop=revisions&rvprop=content";
    $result= curl_http_get($en_foodndrink_query);
        
    // Pull out "DataInputForm" from the Json data
    pullout_datainputform($result);
    
    function curl_http_get($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    
    function pullout_datainputform($result){
        $data = json_decode($result);
        
        if($data){
            $pages = $data->query->pages;
            $array_pages = (array)$pages;
            $cnt_pages = count($array_pages);
            foreach ($array_pages as $item){
                $datainputform = $item->revisions[0]->{'*'};
                
                // Parse it and Encode to GeoJson and Write the GeoJson to the file in download folder
                $temp = explode('|', $datainputform);
                print_r($temp); 
            }
        }
        else 
            print_r("pullout_datainputform():json_decode error");
    }
?>