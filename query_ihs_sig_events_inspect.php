<?php
//Start execution time tracker
$mtime = microtime(); 
$mtime = explode(" ",$mtime); 
$mtime = $mtime[1] + $mtime[0]; 
$starttime = $mtime; 


//-----------------------------------------------------------------------------
//Database execution
//Keep database connection information secure
require("phpsql_dbinfo.php");


/* ************************************************** */

/* Building DSN */
$dsn =  'DRIVER={'.$odbc_driver.'};'.
		'Server='.$odbc_host.';'.
		'Database='.$odbc_database.';'.
		'uid='.$odbc_user.'; pwd='.$odbc_password;

/* Connecting */
$connection = @odbc_connect($dsn, '', '') or die('Connection error: '.htmlspecialchars(odbc_errormsg()));

/* Check connection */
if (!$connection) {
    exit("Connection Failed: " . $conn);
}

//Query statement - Look back by 1 year for changes based on IMO
//Special Cases : shipdetained and inspectiondate have values for all inputs and are not null
$imo = (string)$_GET["imo"];
$query = "SELECT shipDetained,TIMESTAMPDIFF(month, InspectionDate,NOW()) as 'time_diff_months'  FROM wros.tblinspections where LRIMOShipNo= ".$imo;
//$query = $query . " order by InspectionDate asc;

//Execute the query
$result = @odbc_exec($connection, $query) or die('Query error: '.htmlspecialchars(odbc_errormsg()));
//-----------------------------------------------------------------------------


//End execution time
$mtime = microtime(); 
$mtime = explode(" ",$mtime); 
$mtime = $mtime[1] + $mtime[0]; 
$endtime = $mtime; 
$totaltime = ($endtime - $starttime); 


// Prevent caching.
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');

// The JSON standard MIME header.
header('Content-type: application/json');

//echo json_encode(array(query => $query));
// Iterate through the rows, printing XML nodes for each
$count_results = 0;
$ihsarray = array();
while (odbc_fetch_row($result)){
   $count_results = $count_results + 1;

   //Output JSON object per row
   $ihs = array(shipdetained=>htmlspecialchars(odbc_result($result,"shipdetained")),
                   time_diff_months=>htmlspecialchars(odbc_result($result,"time_diff_months"))
   );
   array_push($ihsarray, $ihs);
}

$data = array(query => $query, resultcount => $count_results, exectime => $totaltime, ihsdata => $ihsarray);
echo json_encode($data, JSON_PRETTY_PRINT);
?>
