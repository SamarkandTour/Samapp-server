<?php
    require __DIR__ . '/login.php';

    /**
     * To get a rating value of each page
     *
     * return: the value of rating
     */
    function find_rating($samtour_url, $page_id) {
        global $servername;
        global $username;
        global $password;
        global $db;
        global $port;

        $host = parse_url($samtour_url)['host'];
        $subdomain = explode('.', $host)[0];

        switch($subdomain){
            case 'uz':
                $table = "wp_uz_Vote";
                break;

            case 'en':
                $table = "mw_Vote";
                break;

            case 'ru':
                $table = "mw_ru_Vote";
                break;
        }

        // Create connection
        $conn = new mysqli($servername, $username, $password, $db, $port);
        if($conn->connect_error)
        {
            die("find_rating():db connection failed! Err Code:{$conn->connect_errno}");
        }

        $query = "SELECT vote_value FROM {$table} WHERE vote_page_id = {$page_id}";
        $result = $conn->query($query);

        if (!$result) {
            die("find_rating():query error! {$conn->error}");
        }

        $rows = $result->num_rows;
        if($rows == 0){ // Block a error of division by zero
            $rows = 1;
        }
        $sum = 0;

        for ($i = 0; $i < $rows; $i++ ) {
            $result->data_seek($i);
            $sum += $result->fetch_assoc()['vote_value'];
        }
        $rating =  $sum/$rows;

        $result->close();
        $conn->close();

        return $rating;
    }
?>