<?php
    require __DIR__ . '/vendor/autoload.php';

    generate_ByCategroy("http://uz.samarkandtour.org", "Ovqatlanish");
    //generate_ByCategroy("http://en.samarkandtour.org", "Food%26Drink");
    //generate_ByCategroy("http://localhost", "Food%26Drink");


    function generate_ByCategroy($domain, $category){
        // Curl JSON data from Food&Drink Category
        $PageInfoQuery = $domain . "/api.php?action=query&prop=revisions&rvprop=content&format=json&generator=categorymembers&gcmtitle=Category:" . $category;
        $result= curl_http_get($PageInfoQuery);

        parse_queryData($domain, $result);
    }


    function curl_http_get($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        // if $result is false
        assert('$result', "curl_http_get():curl failed!");

        curl_close($ch);
        return $result;
    }


    function parse_queryData($domain, $result){
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

            $photoPath = find_imagePath($domain, $pageTitle);
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

            // Encode to GeoJSON features
            $features = encode_geojson_features($photoPath, $rating, $name, $desc, $type, $price, $wifi, $open, $addr, $tel, $url, $gps);
        }

        // Encode to GeoJSON featurecollcection and Save it to the file in download folder
        $featurecollection = new \GeoJson\Feature\FeatureCollection($features);
        $geojson = json_encode($featurecollection);
        print_r($geojson);
    }


    function find_imagePath($domain, $title) {
        // Create a image of size of 528px
        $ImageCreateQuery = $domain . "/api.php?action=query&prop=pageimages&format=json&piprop=thumbnail&pithumbsize=528&generator=images&titles=" . $title;
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

    function find_rating($pageId){
        $rating = 4;
        return $rating;
    }

    function encode_geojson_features($photoPath, $rating, $name, $desc, $type, $price, $wifi, $open, $addr, $tel, $url, $gps){
        static $features;

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
        // Generate GeoJSON format
        $point = new \GeoJson\Geometry\Point([$lat, $long]);
        $features[] = new \GeoJson\Feature\Feature($point, $properties, null);

        return $features;
    }

    function encode_ImageToBase64($imagePath){
        $image_data=file_get_contents($imagePath);
        $encoded_image=base64_encode($image_data);

        return $encoded_image;
    }
?>