<?php
    /**
     * Main module for parsing
     */
    require __DIR__ . '/vote.php';

    $propertiesArray = []; //Global variable

    function generate_ByCategroy($samtour_url, $category){
        global $propertiesArray;

        print_r("URL: {$samtour_url}, Category: {$category}, ");

        // Curl JSON data from Category. For query continuation, don't miss rawcontinue=
        $PageInfoQuery = $samtour_url . "/api.php?action=query&prop=revisions&rvprop=content&format=json&rawcontinue=&generator=categorymembers&gcmtitle=Category:" . $category;
        $result= curl_http_get($PageInfoQuery);

        if ($result == "[]") {
            print_r("$category has No data!\n");
            return;
        }

        parse_queryData($samtour_url, $category, $result);
        $featureElements = encode_geojson_features($propertiesArray);
        $geojson = encode_geojson_FeatureCollection($featureElements);
        write_file_inDownloadDir($samtour_url, $category, $geojson);

        $propertiesArray = []; //Initialize for next geojson creation
    }


    function curl_http_get($url){
        global $proxy;

        $ch = curl_init($url);
        if (isset($proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }
        // Set any other cURL options that are required
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        // if $result is false
        if(!$result) {
            die("curl_http_get():curl at {$url} failed!");
        }

        curl_close($ch);
        return $result;
    }


    function parse_queryData($samtour_url, $category, $result){
        static $total_pages;

        // Parse the JSON format of  "DataInputForm Template"
        $data = (array)json_decode($result);
        //print_r($data);

        $pages = $data['query']->pages;
        $array_pages = (array)$pages;
        $total_pages = $total_pages + count($array_pages);

        parse_detail($samtour_url, $array_pages);

        // If there is more data, Re-query continually until no more data
        // This is query-continuation, for more information Visit https://www.mediawiki.org/wiki/API:Query#Continuing_queries
        if (array_key_exists('query-continue', $data)){
            $query_continue = $data['query-continue'];
            $gcmcontinue = rawurlencode($query_continue->categorymembers->gcmcontinue); // Should be encoded for http get query string

            $ContinuationQuery = $samtour_url . "/api.php?action=query&prop=revisions&rvprop=content&format=json&rawcontinue=&generator=categorymembers&gcmtitle=Category:{$category}&gcmcontinue={$gcmcontinue}";
            $result= curl_http_get($ContinuationQuery);

            //recursive function call
            parse_queryData($samtour_url, $category, $result);
        }
        else {
            print_r("Total Pages count: {$total_pages}\n");
            $total_pages = 0;
        }
    }

    function parse_detail($samtour_url, $array_pages){
        global $propertiesArray;
        $photoPath = $rating = $name = $desc = $type = $price = $wifi = $open = $address = $tel = $url = $gps = "";

        foreach ($array_pages as $item){
            $pageId = $item->pageid;
            $pageTitle = $item->title;
            $dataInputForm = $item->revisions[0]->{'*'};

            $temp = explode('|', $dataInputForm);
            //print_r($temp);

            $photoPath = find_imagePath($samtour_url, $pageTitle);
            $rating = find_rating($samtour_url, $pageId);
            $name = $pageTitle;

            foreach ($temp as $val) {
                $splits = explode('=', $val);
                if (sizeof($splits) > 1) { // In case the property has value
                    $trimed = trim($splits[1]);
                } else {
                    $trimed = "";
                }

                if ($trimed != "") {
                    switch (trim($splits[0])) {
                        case "category":
                        case "image":
                        case "image2":
                        case "image3":
                        case "uz_name":
                        case "en_name":
                        case "ru_name":
                            break;
                        case "description":
                            $desc = $trimed;
                            break;
                        case "type":
                            $type = $trimed;
                            break;
                        case "price":
                            $price = $trimed;
                            break;
                        case "wifi":
                            $isWifi = strtolower($trimed);
                            if ($isWifi == "yes" || $isWifi == "bor" || $isWifi == " eсть") {
                                $wifi = "yes";
                            } else {
                                $wifi = $trimed;
                            }
                            break;
                        case "open":
                            $open = $trimed;
                            break;
                        case "address":
                            $addr = $trimed;
                            break;
                        case "tel":
                            $tel = $trimed;
                            break;
                        case "url":
                            $url = $trimed;
                            break;
                        case "gps":
                            $gps = $trimed;
                            $gps = trim(explode("}}", $gps)[0]); // Remove "}}" from $gps
                            break;
                        default:
                            print_r("$pageTitle has $trimed, That's wrong property!\n");
                            break;
                    }
                }

            }

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
            if($gps != "") {
                $fields = explode(",", $gps);
                if ($fields[0] != null) {
                    $long = (float)trim($fields[0]);
                }
                if ($fields[1] != null) {
                    $lat = (float)trim($fields[1]);
                }
            }

            if ($lat == 0 || $long == 0) { // If $gps has no value, don't send it to mobile!
                print_r("Warning! $pageTitle has wrong gps property!\n
                 In case of wrong gps, the page will not be included to geojson file\n");
                continue;
            }

            $propertiesArray[] = array('properties'=>$properties, 'long'=>$long, 'lat'=>$lat);
        }
    }


    function find_imagePath($samtour_url, $title){
        $source = null;
        $e_title = rawurlencode($title); //RFC 3986 URL encoding

        // Create a image of size of 528px
        $thumbsize = "528px";
        $ImageCreateQuery = $samtour_url . "/api.php?action=query&prop=pageimages&format=json&piprop=thumbnail&pithumbsize={$thumbsize}&titles={$e_title}";
        $result = curl_http_get($ImageCreateQuery);

        //Parse JSON and Extract ImagePath
        $data = (array)json_decode($result);
        //print_r($data);

        $pages = $data['query']->pages;
        $array_pages = (array)$pages;
        foreach ($array_pages as $item){
            if(property_exists($item, 'thumbnail')) {
                $source = $item->thumbnail->source;
            }
            else{ // If there is no thumbnail property
                $pageId = $item->pageid;
                print_r("find_imagePath(): thumbnail property of {$title} doesn't exist at pageId:{$pageId}\n
                    1. Check if the image really exist
                    2. If first reason is not, Try update forcibly cached link table by below command:\n
                    api.php?action=purge&titles={$e_title}&forcelinkupdate\n
                    For more information, Visit https://www.mediawiki.org/wiki/API:Purge\n");
                return;
            }
        }
        $imagePath = parse_url($source)['path'];
        $imagePath = "w" . $imagePath; // Because all images are stored in uz domin, add physically directory path from this files
        $imagePath = rawurldecode($imagePath); // Decode URL-encoded string

        return $imagePath;
    }


    function encode_ImageToBase64($imagePath){
        $encoded_image = null;

        if ($imagePath != null) {
            $image_data = file_get_contents($imagePath);
            $encoded_image = base64_encode($image_data);
        }

        return $encoded_image;
    }


    function encode_geojson_features($featureElements){
        $features = array();

        $count = count($featureElements);
        for ($i = 0; $i < $count; $i++) {
            // Generate GeoJSON format
            $point = new \GeoJson\Geometry\Point([$featureElements[$i]['long'], $featureElements[$i]['lat']]);
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
        $host = parse_url($samtour_url)['host'];
        $subdomain = explode('.', $host)[0];

        // Unification of category name
        if($category == "Mehmonxonalar" || $category == "Hotel" || $category == "Отели" ){
            $category = "hotels";
        }
        elseif($category == "Ovqatlanish" || $category == "Food%26Drink" || $category == "Еда и напиток"){
            $category = "foodndrinks";
        }
        elseif($category == "Ziyoratgohlar" || $category == "Attraction" || $category == "Привлечение"){
            $category = "attractions";
        }
        elseif($category == "Xaridlar" || $category == "Shopping" || $category == "Покупка"){
            $category = "shopping";
        }

        $dtz = new DateTimeZone("Asia/Samarkand");
        $current = new DateTime("now", $dtz);
        $date = $current->format("Ymd");

        $filename = "download/" . $subdomain . '_' . $category . ".geojson";
        $file_handle = fopen($filename, "w+");
        if (fwrite($file_handle, $geojson) == FALSE) {
            print_r("Cannot write to file {$filename}");
        }
        fclose($file_handle);

        print_r("Generated {$filename}\n");
    }
?>