<?php

require '_.php';

if(!isset($_SESSION['opID'])) {
	die('no opid');
}

$phases = $DB->qa("SELECT * FROM `phases` WHERE opID = ? ORDER BY phaseID ASC", array($_SESSION['opID']));

if(count($phases) === 0) {
	die("No groups added to op just yet.");
}
else {
	$parts = array();
	foreach ($phases AS $phase) {
		$parts[] = $DB->qa("SELECT part.*, mem.* FROM `participants` AS part INNER JOIN `memberList` AS mem ON (part.charID = mem.charID) WHERE phaseID = ?", array($phase['phaseID'])); 
	}
}


echo "<form method='post'>";

if (isset($_POST['lootSubmit'])){
	$phase = $_POST['phaseID'];
	// Still accepts negative numbers. Make it so it doesn't
	$items = array_filter(filter_var_array($_POST['item'], FILTER_SANITIZE_NUMBER_INT));
	foreach ($items AS $id => $amount){
		$DB->ea("INSERT INTO `phaseData` (`phaseID`, `lootID`, `amount`) VALUES (?, ?, ?)", array($phase, $id, $amount));

	}
	
	echo "<h2>Loot submited</h2>";
}

$lootDisplay = array();

foreach ($lootTypes AS $name => $sql) {
	$results = $DB->qa($sql." ORDER BY typeName ASC", array());

	foreach ($results AS $value){
		$lootDisplay[$name][$value['typeID']] = $value; }
}


echo "Group:<select name='phaseID'>";

for ($i=0, $l = count($parts); $i<$l; $i++) {
			$names = array();
			for ($b=0, $m = count($parts[$i]); $b < $m; ++$b) {
				$names[] = $parts[$i][$b]['name']; }
		echo "<option value='".$parts[$i][$b-1]['phaseID']."'>Group ".($i+1).": ".implode($names, ', ')."</option>\n";
	}

echo"</select>";

foreach ($lootDisplay AS $group => $items){
	echo "<h3 class='toggle'>".$group."</h3>";
	foreach ($items AS $id => $attr) {
		echo "<label for'".$id."'><input type='text' id='".$id."' name='item[".$id."]' size='5' /> ".$attr['typeName']."</label><br />"; 
	}
}
echo "<button type='submit' name='lootSubmit'>Go!</button>";
