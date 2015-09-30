<?php 
/*created by PhpStorm.
 * User: Islom
 * Date: 2015/09/23
 * Time: 12:37
 */

function find_rating($page_id)
{
$servername = "samarkandtour.crfhyqzk2yjn.ap-southeast-1.rds.amazonaws.com:3306";
$username = "root";
$password = "tatu2015";

// Create connection
$conn = mysql_connect($servername, $username, $password);
if(! $conn )
{
    die('Could not connect: ' . mysql_error());
}
mysql_select_db( 'mediawiki' );
$vote = mysql_query("SELECT vote_value FROM wp_uz_Vote WHERE vote_page_id =" .  $page_id);
while ($row = mysql_fetch_array($vote, MYSQL_ASSOC))
{
$sum += $row['vote_value']; 
$counts += count($row['vote_value']);
}
$rating =  $sum/$counts;

mysql_close($conn);
return $rating;
}
?>
