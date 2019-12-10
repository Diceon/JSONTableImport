<?php


$DBHostname = "localhost";
$DBUsername = "root";
$DBPassword = "";
$DBDatabase = "stackoverflow";

$baseDIR = "dump/";

// Connect to Database
$db = mysqli_connect($DBHostname, $DBUsername, $DBPassword, $DBDatabase);
if (mysqli_connect_errno()) {
    printf("DB Connect error: %s\n", mysqli_connect_error());
    die();
}

// NOS
ini_set('memory_limit', '-1');

function inRows($needle, $haystack) {
    foreach ($haystack as $stack) {
        if (in_array($needle, $stack)) {
            return true;
        }
    }
    return false;
}

function mapJSON($json) {
    $data = json_decode($json);
    $rows = [];

    foreach ($data as $val) {
        $dataRows = get_object_vars($val);

        foreach ($dataRows as $rowKey => $rowVal) {
            if (inRows($rowKey, $rows) === false) {

                switch (gettype($rowVal)) {
                    case "integer":
                        $rowDataType = "int";
                        break;
                    case "string":
                        if (strpos($rowVal, '/Date') !== false) {
                            $rowDataType = "datetime";
                        } else {
                            $rowDataType = "text";
                        }
                        break;
                    default:
                        $rowDataType = gettype($rowVal);
                        break;
                }

                $rows[] = ['row' => $rowKey, 'dataType' => $rowDataType];
            }
        }
    }
    unset($data);
    return $rows;
}

function createTablesFromMap($map, $table, $database) {

    $query = "CREATE TABLE IF NOT EXISTS  `$table` (";
    foreach ($map as $key => $row) {
        echo $row['row'] . '<br>';
        $rowName = $row['row'];
        $rowDataType = $row['dataType'];

        $query .= "`$rowName` $rowDataType" . (($key != array_key_last($map)) ? ", " : "");
    }
    $query .= ")";
    echo $query . '<br><br>';
    $database->query($query);
}


$files = array_slice(scandir($baseDIR), 2);
print_r($files);
echo "<br>";

foreach ($files as $file) {
    $rows = mapJSON(file_get_contents('dump/' . $file));
    createTablesFromMap($rows, preg_replace('/\\.[^.\\s]{3,4}$/', '', $file), $db);
    unset($rows);
}


$db->close();
