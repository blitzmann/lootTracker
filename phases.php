This is a phases prototype
<pre>
<?php

require '_.php';

if (isset($_POST['lootSubmit'])){
	$op = $_POST['op'];
	$phase = $_POST['phase'];
	// Still accepts negative numbers. Make it so it doesn't
	$items = array_filter(filter_var_array($_POST['item'], FILTER_SANITIZE_NUMBER_INT));
	foreach ($items AS $id => $amount){
		$DB->ea("INSERT INTO `phaseData` (`phaseID`, `lootID`, `amount`) VALUES (?, ?, ?)", array($phase, $id, $amount));

	}
}

$lootDisplay = array();

foreach ($lootTypes AS $name => $sql) {
	$results = $DB->qa($sql." ORDER BY typeName ASC", array());

	foreach ($results AS $value){
		$lootDisplay[$name][$value['typeID']] = $value; }
}

//print_r($lootDisplay);
echo "<form method='post'>
Op: <input type='text' name='op' /><br />
Phase: <input type='text' name='phase' /><br />";
foreach ($lootDisplay AS $group => $items){
	echo "<h3 class='toggle'>".$group."</h3>";
	foreach ($items AS $id => $attr) {
		echo $attr['typeName'].": <input type='text' name='item[".$id."]' /><br />"; 
	}
}
echo "<button type='submit' name='lootSubmit'>Go!</button>";
