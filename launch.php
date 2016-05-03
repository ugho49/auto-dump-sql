<?php
// Get autoloader from composer
require 'vendor/autoload.php';
// Use Ifsnop lib for create Mysql dumps
use Ifsnop\Mysqldump as IMysqldump;

// Here an array of exclude bases
$exclude = ['information_schema', 'performance_schema', 'mysql', 'phpmyadmin'];

// Constants for mysql connection
define("DB_HOST", "localhost", true);
define("DB_ROOT_USER", "root", true);
define("DB_ROOT_PASS", "__CHANGE__", true);

// Constant for the dump folder
define("DUMP_FOLDER", realpath(dirname(__FILE__)) . "/dumps", true);

// This constant is use to keep a specify number of dump
// exemple :
// 1 : keep just the current dump
// 5 : keep 5 older dumps
// 0 : keep all dumps
define("DUMP_DURATION", 5, true);

// Init PDO object
$db = new PDO('mysql:host=' . DB_HOST, DB_ROOT_USER, DB_ROOT_PASS);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

// Get all DATABASES from Mysql
$q = $db->query('SHOW DATABASES');
$databases = $q->fetchAll();

// Create folder with timestamp for the current dump
$date = date("Ymd_His");
$folder = DUMP_FOLDER . "/" . $date;

// If this folder don't exist, it has to be create
if (!file_exists($folder)) {
    mkdir($folder, 0755, true);
}

// For all databases
foreach ($databases as $database) {
    // Get the name
    $database_name = $database->Database;
    // if the database is not exclude
    if(!in_array($database_name, $exclude)) {
        try {
            // the filename is database_name.sql
            $sqlname = $database_name . '.sql';
            // init dump for this base
            $dump = new IMysqldump\Mysqldump('mysql:host=' . DB_HOST . ';dbname=' . $database_name, DB_ROOT_USER, DB_ROOT_PASS);
            // store the dump file in the folder
            $dump->start($folder . '/' . $sqlname);
        } catch (\Exception $e) {
            // Show error
            echo 'mysqldump-php error: ' . $e->getMessage();
        }
    }
}

// Delete old dump according to the constant
if (DUMP_DURATION > 0) {
    // init arrays of dumps directory
    $dirs = [];
    // get all dump folder
    foreach(glob(DUMP_FOLDER . '/*', GLOB_ONLYDIR) as $fold) {
        $date_folder = DateTime::createFromFormat('Ymd_His', basename($fold));
        $dirs[] = [
            "name" => $fold,
            "date" => $date_folder
        ];
    }
    // if number of folder is greater than the number of dump autorized
    if (count($dirs) > DUMP_DURATION) {
        // Sort files by date : older first
        usort($dirs, function($a, $b) {
            return $b["date"] < $a["date"];
        });

        // drop the element from the array we don't want delete
        for ($i=0; $i < DUMP_DURATION; $i++) {
            array_pop($dirs);
        }

        // for all elements who are in the array
        foreach ($dirs as $d) {
            // use rmrdir from perchten/rmrdir
            rmrdir($d['name']);
        }
    }
}
