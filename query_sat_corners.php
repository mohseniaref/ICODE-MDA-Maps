<?php
//Start execution time tracker
$mtime = microtime(); 
$mtime = explode(" ",$mtime); 
$mtime = $mtime[1] + $mtime[0]; 
$starttime = $mtime; 

//For comparing strings
function startsWith($haystack, $needle)
{
    return $needle === "" || strpos($haystack, $needle) === 0;
}

//-----------------------------------------------------------------------------
//Database execution
//Keep database connection information secure
require("phpsql_dbinfo.php");

/* ************************************************** */

/* Building DSN */
$dsn =  'DRIVER={'.$odbc_driver.'};'.
		'Server='.$odbc_host.';'.
		'Database='.$ais_database.';'.
		'uid='.$odbc_user.'; pwd='.$odbc_password;

/* Connecting */
$connection = @odbc_connect($dsn, '', '') or die('Connection error: '.htmlspecialchars(odbc_errormsg()));

/* Check connection */
if (!$connection) {
    exit("Connection Failed: " . $conn);
}

//Flag to keep track of whether a filter/criteria string has been started
$criteriaListStarted = 0;


//------------------------ Build the initial base query --------------------------
if(count($_GET) > 0) {
   //============================= CONSTRAINTS ===========================================
   $geoboundsclause = '';
   if (!empty($_GET["minlat"]) && !empty($_GET["minlon"]) && !empty($_GET["maxlat"]) && !empty($_GET["maxlon"])) {
      //Check if flipped, then probably crossed the meridian (> +180, or < -180)
      if ($_GET["minlon"] > $_GET["maxlon"]) {
         $meridianflag = true;
      }
      else {
         $meridianflag = false;
      }

      if ($meridianflag == false) { //Handle normal case
         $geoboundsclause = "Latitude BETWEEN " . round($_GET["minlat"],3) . " AND " . round($_GET["maxlat"],3) . 
            " AND Longitude BETWEEN " .  round($_GET["minlon"],3) . " AND " . round($_GET["maxlon"],3);
      }
      else {
         $geoboundsclause = "Latitude BETWEEN " . round($_GET["minlat"],3) . " AND " . round($_GET["maxlat"],3) . 
            " AND (Longitude BETWEEN -180 AND " . round($_GET["maxlon"],3) .
            " OR Longitude BETWEEN " . round($_GET["minlon"],3) . " AND 180 )";
      }
   }

   //age
   $ageclause = '';
   if (!empty($_GET["age"])) {
      $age = $_GET["age"];

      //Handle Time Machine case
      if (!empty($_GET["endtime"])) {
         $endtime = $_GET["endtime"];
         //TODO
         $ageclause = 
      }
      //non-Time Machine
      else {
         //TODO
         $ageclause = 
      }
   }

   //Add limit contraint
   $limitclause = '';
   if (!empty($_GET["limit"])) {
      $limit = $_GET["limit"];
      $limitclause = "limit " . $limit;
   }

   //================================ Build the query =========================================
   $query = "SELECT * FROM $sat_database.pvol_pdm_memory WHERE possource = 'SAT-SAR-START' OR possource = 'SAT-SAR-END' $geoboundsclause $ageclause $limitclause";


   //custom query, erase everything else and use this query
   if (!empty($_GET["query"])) {
      //TODO: add security checks against SQL injections
      $query = $_GET["query"];
   }

   $basequery = $query;
}
//No arguments provided
else {
   //Default query, just limit to 10 row results
   $limit = 10;
   $query = $query . " limit " . $limit;
}


//Execute the query
$result = @odbc_exec($connection, $query) or die('Query error: '.htmlspecialchars(odbc_errormsg()).' // '.$query);
//-----------------------------------------------------------------------------


//End execution time
$mtime = microtime(); 
$mtime = explode(" ",$mtime); 
$mtime = $mtime[1] + $mtime[0]; 
$endtime = $mtime; 
$totaltime = ($endtime - $starttime); 


// Prevent caching
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');

// The JSON standard MIME header
header('Content-type: application/json');

//echo json_encode(array(query => $query));
// Iterate through the rows, printing XML nodes for each
$count_results = 0;
$vesselarray = array();
while (odbc_fetch_row($result)){
   $count_results = $count_results + 1;

   //Output JSON object per row
   if ($source === "LAISIC_AIS_TRACK") {  
        $vessel = array(trkguid=>htmlspecialchars(odbc_result($result,"trkguid")),
                   trknum=>odbc_result($result,"trknum"),
                   updateguid=>htmlspecialchars(odbc_result($result,"updateguid")),
                   srcguid=>htmlspecialchars(odbc_result($result,"srcguid")),
                   datetime=>odbc_result($result,"TimeOfFix"),
                   lat=>addslashes(odbc_result($result,"Latitude")),
                   lon=>addslashes(odbc_result($result,"Longitude")),
                   cog=>odbc_result($result,"cog"),
                   sog=>odbc_result($result,"sog"),
                   stage=>htmlspecialchars(odbc_result($result,"stage")),
                   semimajor=>odbc_result($result,"semimajor"),
                   semiminor=>odbc_result($result,"semiminor"),
                   orientation=>odbc_result($result,"orientation"),
                   holdtime=>odbc_result($result,"holdtime"),
                   hitscount=>odbc_result($result,"hitscount"),
                   quality=>odbc_result($result,"quality"),
                   source=>htmlspecialchars(odbc_result($result,"source")),
                   inttype=>htmlspecialchars(odbc_result($result,"inttype")),
                   callsign=>htmlspecialchars(odbc_result($result,"callsign")),
                   mmsi=>odbc_result($result,"mmsi"),
                   vesselname=>htmlspecialchars(odbc_result($result,"vesselname")),
                   imo=>odbc_result($result,"imo")
           );
    }
    else if ($source === "LAISIC_RADAR") {
        $vessel = array(mmsi=>odbc_result($result,"mmsi"),
                   sog=>odbc_result($result,"sog"),
                   lon=>addslashes(odbc_result($result,"Longitude")),
                   lat=>addslashes(odbc_result($result,"Latitude")),
                   cog=>odbc_result($result,"cog"),
                   datetime=>odbc_result($result,"TimeOfFix"),
                   streamid=>htmlspecialchars(odbc_result($result,"streamid")),
                   target_status=>htmlspecialchars(odbc_result($result,"target_status")),
                   target_acq=>htmlspecialchars(odbc_result($result,"target_acq")),
                   trknum=>odbc_result($result,"trknum"),
                   sourceid=>htmlspecialchars(odbc_result($result,"sourceid"))
           );
    }
    else if ($source === "LAISIC_AIS_OBS") {
         $vessel = array(obsguid=>htmlspecialchars(odbc_result($result,"obsguid")),
                   lat=>addslashes(odbc_result($result,"Latitude")),
                   lon=>addslashes(odbc_result($result,"Longitude")),
                   semiminor=>odbc_result($result,"semiminor"),
                   semimajor=>odbc_result($result,"semimajor"),          
                   orientation=>odbc_result($result,"orientation"),  
                   cog=>odbc_result($result,"cog"),
                   sog=>odbc_result($result,"sog"),
                   datetime=>odbc_result($result,"TimeOfFix"),
                   callsign=>htmlspecialchars(odbc_result($result,"callsign")),
                   mmsi=>odbc_result($result,"mmsi"),
                   vesselname=>htmlspecialchars(odbc_result($result,"vesselname")),
                   imo=>odbc_result($result,"imo"),
                   streamid=>htmlspecialchars(odbc_result($result,"streamid"))      
           );
    }
    else if ($source === "LIVE_LAISIC") {
        $vessel = array(mmsi=>odbc_result($result,"mmsi"),
                   commsid=>odbc_result($result,"CommsID"),
                   name=>odbc_result($result,"Name"),
                   datetime=>odbc_result($result,"TimeOfFix"),
                   lat=>odbc_result($result,"Latitude"),
                   lon=>odbc_result($result,"Longitude"),
                   sog=>odbc_result($result,"SOG"),
                   heading=>odbc_result($result,"Heading"),
                   streamid=>htmlspecialchars(odbc_result($result,"PosSource")),
                   cog=>odbc_result($result,"COG"),
                   opt1tag=>odbc_result($result,"Opt1Tag"),
                   opt1val=>odbc_result($result,"Opt1Val"),
                   opt2tag=>odbc_result($result,"Opt2Tag"),
                   opt2val=>odbc_result($result,"Opt2Val"),
                   opt3tag=>odbc_result($result,"Opt3Tag"),
                   opt3val=>odbc_result($result,"Opt3Val"),
                   opt4tag=>odbc_result($result,"Opt4Tag"),
                   opt4val=>odbc_result($result,"Opt4Val")
           );
    }
    else if ($source === "RADAR" || $source === "SAT-SAR" || $source === "SAT-EO") {
       $vessel = array(mmsi=>odbc_result($result,"mmsi"),
                   commsid=>odbc_result($result,"CommsID"),
                   name=>odbc_result($result,"Name"),
                   datetime=>odbc_result($result,"TimeOfFix"),
                   lat=>odbc_result($result,"Latitude"),
                   lon=>odbc_result($result,"Longitude"),
                   sog=>odbc_result($result,"SOG"),
                   heading=>odbc_result($result,"Heading"),
                   streamid=>htmlspecialchars(odbc_result($result,"PosSource")),
                   cog=>odbc_result($result,"COG"),
                   opt1tag=>odbc_result($result,"Opt1Tag"),
                   opt1val=>odbc_result($result,"Opt1Val"),
                   opt2tag=>odbc_result($result,"Opt2Tag"),
                   opt2val=>odbc_result($result,"Opt2Val"),
                   opt3tag=>odbc_result($result,"Opt3Tag"),
                   opt3val=>odbc_result($result,"Opt3Val"),
                   opt4tag=>odbc_result($result,"Opt4Tag"),
                   opt4val=>odbc_result($result,"Opt4Val")
           );
    }
    else {
       //Extract the vessel type number only
       $pos = strpos(odbc_result($result,"VesType"), '-');
       $vesseltype = substr(odbc_result($result,"VesType"), 0, $pos);

       //Fix the type 60-99 types, SeaVision format skipped the trailing '0'
       if ($vesseltype == '6' OR $vesseltype == '7' OR $vesseltype == '8' OR $vesseltype == '9')
          $vesseltype = $vesseltype . '0';

       //SeaVision field names
       $vessel = array(mmsi=>odbc_result($result,"MMSI"),
             navstatus=>odbc_result($result,"NavStatus"),
             rot=>odbc_result($result,"ROT"),
             sog=>odbc_result($result,"SOG"),
             lon=>odbc_result($result,"Longitude"),
             lat=>odbc_result($result,"Latitude"),
             cog=>odbc_result($result,"COG"),
             heading=>odbc_result($result,"Heading"),
             datetime=>odbc_result($result,"TimeOfFix"),
             imo=>odbc_result($result,"IMONumber"),
             vesselname=>htmlspecialchars(odbc_result($result,"Name")),
             vesseltypeint=>$vesseltype,
             length=>odbc_result($result,"Length"),
             shipwidth=>odbc_result($result,"Beam"),
             bow=>odbc_result($result,"AntOffsetBow"),
             port=>odbc_result($result,"AntOffsetPort"),
             draught=>odbc_result($result,"Draft"),
             destination=>htmlspecialchars(odbc_result($result,"Destination")),
             callsign=>htmlspecialchars(odbc_result($result,"CallSign")),
             posaccuracy=>odbc_result($result,"PosQuality"),
             eta=>odbc_result($result,"ETADest"),
             posfixtype=>odbc_result($result,"PosSource"),
             streamid=>htmlspecialchars(odbc_result($result,"RxStnID")),
             security_rating=>odbc_result($result,"security_rating"),
             safety_rating=>odbc_result($result,"safety_rating"),
             risk_score_security=>odbc_result($result,"security_score"),
             risk_score_safety=>odbc_result($result,"safety_score")
          );
    }

    array_push($vesselarray, $vessel);
}

$memused = memory_get_usage(false);

$data = array(basequery => $basequery, query => $query, resultcount => $count_results, exectime => $totaltime, memused => $memused, vessels => $vesselarray);
echo json_encode($data, JSON_PRETTY_PRINT);
?>

