<?php
    require __DIR__ . '/vendor/autoload.php';
    require __DIR__ . '/generate_ByCategory.php';

    // Uz
    generate_ByCategroy("http://uz.samarkandtour.org", "Ovqatlanish");
    generate_ByCategroy("http://uz.samarkandtour.org", "Ovqatlanish");
    generate_ByCategroy("http://uz.samarkandtour.org", "Ovqatlanish");
    generate_ByCategroy("http://uz.samarkandtour.org", "Ovqatlanish");

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