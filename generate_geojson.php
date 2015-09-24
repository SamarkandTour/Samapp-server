<?php
    require __DIR__ . '/vendor/autoload.php';
    require __DIR__ . 'vote.php';

    generate_ByCategroy("http://uz.samarkandtour.org", "Ovqatlanish");
    //generate_ByCategroy("http://en.samarkandtour.org", "Food%26Drink");
    //generate_ByCategroy("http://uz.localhost", "Food%26Drink");


    function generate_ByCategroy($samtour_url, $category){
        // Curl JSON data from Category
        $PageInfoQuery = $samtour_url . "/api.php?action=query&prop=revisions&rvprop=content&format=json&generator=categorymembers&gcmtitle=Category:" . $category;
        $result= curl_http_get($PageInfoQuery);

        $propertiesArray = parse_queryData($samtour_url, $result);
        $featureElements = encode_geojson_features($propertiesArray);
        $geojson = encode_geojson_FeatureCollection($featureElements);
        write_file_inDownloadDir($samtour_url, $category, $geojson);
    }


    function curl_http_get($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        // if $result is false
        assert('$result', "curl_http_get():curl at $url failed!");

        curl_close($ch);
        return $result;
    }


    function parse_queryData($samtour_url, $result){

        // Parse the JSON format of  "DataInputForm Template"
        $data = json_decode($result);
        //print_r($data);

        $pages = $data->query->pages;
        $array_pages = (array)$pages;

        foreach ($array_pages as $item){
            $pageId = $item->pageid;
            $pageTitle = $item->title;
            $dataInputForm = $item->revisions[0]->{'*'};

            $temp = explode('|', $dataInputForm);
            //print_r($temp);

            $photoPath = find_imagePath($samtour_url, $pageTitle);
            $rating = find_rating($pageId);
            $name = $pageTitle;
            $desc = trim(explode('=',$temp[8])[1]);
            $type = trim(explode('=',$temp[9])[1]);
            $price = trim(explode('=',$temp[10])[1]);
            $wifi = trim(explode('=',$temp[11])[1]);
            $open = trim(explode('=',$temp[12])[1]);
            $addr = trim(explode('=',$temp[13])[1]);
            $tel = trim(explode('=',$temp[14])[1]);
            $url = trim(explode('=',$temp[15])[1]);
            $gps = trim(explode('=',$temp[16])[1]);
            $gps = trim(explode("}}", $gps)[0]); // Remove "}}" from $gps

            $properties['photoExt'] = strrchr($photoPath, '.');
            $encoded_image = encode_ImageToBase64($photoPath);
            $properties['photo'] = $encoded_image;
            $properties['rating'] = $rating;
            $properties['name'] = $name;
            $properties['desc'] = $desc;
            $properties['type'] = $type;
            $properties['price'] = $price;
            $properties['wifi'] = $wifi;
            $properties['open'] = $open;
            $properties['addr'] = $addr;
            $properties['tel'] = $tel;
            $properties['url'] = $url;

            //Decompose $gps for Point format
            $lat = $long = 0;
            if($gps != null) {
                $fields = explode(",", $gps);
                if ($fields[0] != null) {
                    $long = (float)trim($fields[0]);
                }
                if ($fields[1] != null) {
                    $lat = (float)trim($fields[1]);
                }
            }

            $featureElements[] = array('properties'=>$properties, 'long'=>$long, 'lat'=>$lat);
        }

        return $featureElements;
    }


    function find_imagePath($samtour_url, $title) {
        // Create a image of size of 528px
        $ImageCreateQuery = $samtour_url . "/api.php?action=query&prop=pageimages&format=json&piprop=thumbnail&pithumbsize=528&titles=" . $title;
        $result = curl_http_get($ImageCreateQuery);

        //Parse JSON and Extract ImagePath
        $data = json_decode($result);
        //print_r($data);

        $pages = $data->query->pages;
        $array_pages = (array)$pages;
        foreach ($array_pages as $item){
            $source = $item->thumbnail->source;
        }
        $imagePath = parse_url($source)['path'];
        $imagePath = "w" . $imagePath; // Because all images are stored in uz domin, add physically directory path from this files

        return $imagePath;
    }


    function encode_ImageToBase64($imagePath){
        $image_data=file_get_contents($imagePath);
        $encoded_image=base64_encode($image_data);

        return $encoded_image;
    }


    function encode_geojson_features($featureElements){

        $count = count($featureElements);
        for ($i = 0; $i < $count; $i++) {
            // Generate GeoJSON format
            $point = new \GeoJson\Geometry\Point([$featureElements[$i]['lat'], $featureElements[$i]['long']]);
            $features[] = new \GeoJson\Feature\Feature($point, $featureElements[$i]['properties'], null);
        }

        return $features;
    }


    function encode_geojson_FeatureCollection($features){
        // Encode to GeoJSON featurecollcection and Save it to the file in download folder
        $featurecollection = new \GeoJson\Feature\FeatureCollection($features);
        $geojson = json_encode($featurecollection);

        return $geojson;

    }


    function write_file_inDownloadDir($samtour_url, $category, $geojson){
        $host = parse_url($url)['host'];
        $subdomain = explode('.', $host)[0];

        // Unification of category name
        if($category == "Ovqatlanish" || $category == "Food%26Drink"){
            $category = "foodndrink";
        }
        elseif($category == "Mehmoxonalar" || $category == "Hotel"){
            $category = "hotel";
        }
        elseif($category == "Ziyoratgohlar" || $category == "Attraction"){
            $category = "attraction";
        }
        elseif($category == "Xaridlar" || $category == "Shopping"){
            $category = "shopping";
        }

        $dtz = new DateTimeZone("Asia/Samarkand");
        $current = new DateTime("now", $dtz);
        $date = $current->format("Ymd");

        $filename = "download/" . $subdomain . '_' . $category . '_' . $date . ".geojson";
        $file_handle = fopen($filename, "w+");
        if (fwrite($file_handle, $geojson) == FALSE) {
            echo "Cannot write to file ($filename)";
        }
        fclose($file_handle);

        echo "Generated $filename";
    }
?>