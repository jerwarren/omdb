<?php
// api key for the other omdb where I'm fetching the covers & info
$APIkey = "7f8bb3b0";

// $filmsSheetId comes from the google sheet url, found inside brackets here: https://docs.google.com/spreadsheets/d/[1U5SOsl2Du4Vk8mhCjcyixfr4jlOXoPIA-Qkuag51fKk]/edit#gid=0
$filmsSheetId = "10a2Ejb7yuYfWAAvQ6SfbtB5afrtag6pgZYHjs7h6llc";
$filmsSheetName = "Sheet1";

$i=0;

function getFilmsFromSheet($filmsSheetId, $filmsSheetName){
	// this requires the sheet to be viewable by anyone with the link before it will work.

	$url = "https://docs.google.com/spreadsheets/d/$filmsSheetId/gviz/tq?&sheet=$filmsSheetName&tq=Select%20*";

	$data = file_get_contents($url);
	$cleanData = substr($data, 47, -2);

	$dataJSON = json_decode($cleanData, true);

	$columns = [];
	$output = [];

	// loop through column headers, build an array
	foreach ($dataJSON["table"]["cols"] as $col){
		if ($col["label"] != null){
			array_push($columns, $col["label"]);
		}
	}

	foreach ($dataJSON["table"]["rows"] as $row){
		$item = [];
		
		// loop through our column headers and assign them to our data
		foreach ($columns as $key=>$column){
			$item[$column] = $row["c"][$key]["v"];
		}

		// add all the values from the sheet row to our data
		array_push($output, $item);
	}

	return $output;
}

$films = getFilmsFromSheet($filmsSheetId, $filmsSheetName);

// some overrides for titles that were messed up
foreach ($films as $film) {
	$title = $film['Title'];

	if ($title == "Friday the 13th Part II"){
		$title = "Friday the 13th Part 2";
	}
	if ($title == "Friday the 13th Part V: A New Beginning"){
		$title = "Friday the 13th: A New Beginning";
	}
	if ($title == "Wes Craven's New Nightmare"){
                $title = "New Nightmare";
	}
	if ($title == "The Exorcist 3"){
                $title = "The Exorcist III";
        }
	$year = $film['Release Year'];
	$titleEncoded = urlencode($title);

	#echo "<!-- http://www.omdbapi.com/?apikey=$APIkey&t=$titleEncoded&y=$year -->";
	$fileName = $title."-".$year;
	         //   Date(2023,8,15)
	preg_match('/Date\(([^,]*),([^,]*),([^)]*)\)/', $film["Watch Date"], $matches);
	$w_year = $matches[1];
	$month = intval($matches[2])+1;
	$day = $matches[3];
	$watched = "$month/$day/$w_year";
	$watched_key = "$w_year$month$day";
	/*
	$watched = str_replace("Date(","",$film["Watch Date"]);
	$watched = str_replace(")","",$watched);
	$watched = str_replace(",","-", $watched);
	 */

if (file_exists("films/".$fileName.".json")) {
		$details = file_get_contents("films/$fileName.json");
} else {
	$details = file_get_contents("http://www.omdbapi.com/?apikey=$APIkey&t=$titleEncoded&y=$year");
	file_put_contents("films/$fileName.json", $details);
	}
$details = json_decode($details,true);

$films[$i]["Poster"] = $details["Poster"];
$films[$i]["Plot"] = $details["Plot"];
$films[$i]["Watched"] = $watched;
$films[$i]["watched_key"] = $watched_key;
$films[$i]["IMDB"] = $details["imdbRating"];
$films[$i]["Metacritic"] = $details["Metascore"];

foreach ($details["Ratings"] as $item){

	if ($item["Source"] == "Rotten Tomatoes"){
		$films[$i]["Rotten Tomatoes"] = $item["Value"];
	}
}

$i++;
}
?> 
<!DOCTYPE html>
<html>
	<head>
		<style>html{background-color: black;}</style>
		<title>The Owen Movie Database</title>
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta property="og:title" content="The Owen Movie Database" />
		<meta property="og:description" content="Owen\'s horror movie satisfaction log" />
		<meta property="og:image" content="omdb.png" />
		<link rel="icon" href="images/ombd-favicon.png">
		<link rel="stylesheet" href="style.css">
	</head>
<body>
	<div class="header">
		<img class="logo" src="images/omdb.png">
		<img class="menu" src="images/fake menu.png">
	</div>
	<div class="banner">
		<h3 class="heading">The Owen Movie Database <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" class="ipc-icon ipc-icon--chevron-right-inline ipc-icon--inline ipc-title-link ipc-title-link-chevron" viewBox="0 0 24 24" fill="currentColor" role="presentation"><path d="M5.622.631A2.153 2.153 0 0 0 5 2.147c0 .568.224 1.113.622 1.515l8.249 8.34-8.25 8.34a2.16 2.16 0 0 0-.548 2.07c.196.74.768 1.317 1.499 1.515a2.104 2.104 0 0 0 2.048-.555l9.758-9.866a2.153 2.153 0 0 0 0-3.03L8.62.61C7.812-.207 6.45-.207 5.622.63z"></path></svg></h3>
		<div class="btn-group sort-options">
          <label class="btn active">
            <input type="radio" name="sort-value" value="watched" checked="checked"> Recent
          </label>
          <label class="btn">
            <input type="radio" name="sort-value" value="title"> Title
          </label>
          <label class="btn">
            <input type="radio" name="sort-value" value="score-high">High Score 
          </label>
		  <label class="btn">
            <input type="radio" name="sort-value" value="score-low">Low Score 
          </label>
        </div>
	</div>
	<div id="shuffle-container">

<?php

foreach (array_reverse($films) as $film){
	if ($film["Score"] != "")
		echo "<div class='card' data-title='".preg_replace("/[^A-Za-z0-9 ]/", '', $film["Title"])."' data-watched='".$film["watched_key"]."' data-score='".$film['Score']."'><div class='date'>Watched on ".$film['Watched']."</div><img src='".$film['Poster']."'><h3>".$film['Title']." (".$film["Release Year"].")</h3><div class='summary'>".$film["Plot"]."</div>
		
	<div class='notes'>".$film['Remarks']."</div>
	<div class='ratings'><div class='external'>
	<div class='rating'>".$film['Rotten Tomatoes']."</div><img class='icon' src='images/www_rottentomatoes_com.ico'>
	<div class='rating'>".$film['Metacritic']."%</div><img class='icon' src='images/www_metacritic_com.ico'>	
	<div class='rating'>".$film['IMDB']."</div><img class='icon' src='images/www_imdb_com.ico'> 
	<div class='rating' style=''>&nbsp;</div><div class='icon' style='width: 25px;'>&nbsp;</div>
	</div><div class='rating'>".$film['Score']."/10</div><img class='icon' src='https://media.fedia.social/media/thumbnail-9587a19b-121c-41e8-9187-82b88a455a57.webp'>
	</div></div>";
}

?>
</div>
</div>
<script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
<script>
  window.OneSignalDeferred = window.OneSignalDeferred || [];
  OneSignalDeferred.push(function(OneSignal) {
    OneSignal.init({
      appId: "0c6f9cee-4576-4040-87a6-7b09bb50015e",
    });
  });
</script>
<script src="//cdnjs.cloudflare.com/ajax/libs/Shuffle/6.1.0/shuffle.min.js"></script>
<script src="script.js"></script>
</body>
</html>
