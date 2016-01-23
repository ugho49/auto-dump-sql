<?php
require 'vendor/autoload.php';

use Ifsnop\Mysqldump as IMysqldump;

$exclude = ['information_schema', 'performance_schema', 'mysql'];

$db_host      = "localhost";
$db_root_user = "ROOT_USER";
$db_root_pass = "ROOT_PASSWORD";

$db = new PDO('mysql:host='.$db_host, $db_root_user, $db_root_pass);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
$q = $db->query('SHOW DATABASES');

$databases = $q->fetchAll();
$date = date("Ymd_His");

foreach ($databases as $database) {
    $database_name = $database->Database;

    if(!in_array($database_name, $exclude)) {
        try {
            $sqlname = $database_name . '_' . $date . '.sql';
            $dump = new IMysqldump\Mysqldump('mysql:host='.$db_host.';dbname='.$database_name, $db_root_user, $db_root_pass);
            $dump->start('dumps/'.$sqlname);
        } catch (\Exception $e) {
            echo 'mysqldump-php error: ' . $e->getMessage();
        }
    }
}
?>
