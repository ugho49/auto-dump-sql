<?php
// Get autoloader from composer
require 'vendor/autoload.php';
// Use Ifsnop lib for create Mysql dumps
use Ifsnop\Mysqldump as IMysqldump;

define("NL", "\n", true);

echo "-------------------------------------" . NL;
echo "-------------------------------------" . NL;
echo "BEGIN of Dump database script at " . DateTime::createFromFormat('U.u', microtime(true))->format("Y-m-d H:i:s.u") . NL;
echo NL;

// Here an array of exclude bases
$exclude = ['information_schema', 'performance_schema', 'mysql'];

// Constants for mysql connection
define("DB_HOST", "localhost", true);
define("DB_ROOT_USER", "____CHANGE_ME____", true);
define("DB_ROOT_PASS", "____CHANGE_ME____", true);

// analytics
$number_of_bases_dumped=0;
$number_of_bases_failed=0;

//options
$options = [
    'databases' => true,
    'add-locks' => false,
    'no-create-info' => false,
    'skip-comments' => true,
    'add-drop-table' => false,
    'single-transaction' => true,
    'lock-tables' => false,
    'add-locks' => false,
];

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
    echo "Create folder : " . $folder . NL;
    echo NL;
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
            $dump = new IMysqldump\Mysqldump('mysql:host=' . DB_HOST . ';dbname=' . $database_name, DB_ROOT_USER, DB_ROOT_PASS, $options);
            // store the dump file in the folder
            $dump->start($folder . '/' . $sqlname);
            // trace in console
            echo "- Dump database name : " . $database_name . NL;
            // increment
            $number_of_bases_dumped++;
        } catch (\Exception $e) {
            // Show error
            echo 'mysqldump-php error: ' . $e->getMessage();
            // increment
            $number_of_bases_failed++;
        }
    }
}

echo NL;
echo "Number of success : " . $number_of_bases_dumped . NL;
echo "Number of failure : " . $number_of_bases_failed . NL;

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
            echo NL;
            echo "Remove folder " . $d['name'] . NL;
            // use rmrdir from perchten/rmrdir
            rmrdir($d['name']);
        }
    }
}

echo NL;
echo "END of Dump database script at " . DateTime::createFromFormat('U.u', microtime(true))->format("Y-m-d H:i:s.u") . NL;
echo "-------------------------------------" . NL;
echo "-------------------------------------" . NL;
