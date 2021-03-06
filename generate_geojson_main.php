<?php
    require __DIR__ . '/vendor/autoload.php';
    require __DIR__ . '/generate_geojson_sub.php';

    // Test code for localhost
    //generate_ByCategroy("http://uz.localhost", "Food%26Drink");

    // Uz
    generate_ByCategroy("http://uz.samarkandtour.org", "Mehmonxonalar");
    generate_ByCategroy("http://uz.samarkandtour.org", "Ovqatlanish");
    generate_ByCategroy("http://uz.samarkandtour.org", "Ziyoratgohlar");
    generate_ByCategroy("http://uz.samarkandtour.org", "Xaridlar");

    // Eng
    generate_ByCategroy("http://en.samarkandtour.org", "Hotel");
    generate_ByCategroy("http://en.samarkandtour.org", "Food%26Drink"); //'&' should be encoded to %26
    generate_ByCategroy("http://en.samarkandtour.org", "Attraction");
    generate_ByCategroy("http://en.samarkandtour.org", "Shopping");

    // Rus
    generate_ByCategroy("http://ru.samarkandtour.org", "Отели");
    generate_ByCategroy("http://ru.samarkandtour.org", "Еда и напиток");
    generate_ByCategroy("http://ru.samarkandtour.org", "Привлечение");
    generate_ByCategroy("http://ru.samarkandtour.org", "Покупка");
?>