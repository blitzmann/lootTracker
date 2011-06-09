<?php

$title = 'Sell Loot';

require '_.php';

//if ($User->hasRole('director')){
//	$Page->errorfooter('Sorry, but only Directors or other trusted members can access the loot container, and thus sell it.'); }
	
if (filter_has_var(INPUT_POST, 'submitLootSale')) {
	$loots = filter_var_array($_POST['sale'], FILTER_VALIDATE_INT);
	$ops = filter_var_array($_POST['ops'], FILTER_VALIDATE_INT);
	
	$DB->e("INSERT INTO `saleHistory` (`saleID`, `seller`, `saleTime`) VALUES (?, ?, ?)", null, $User->charID, time());
	$saleID = $DB->lastInsertID();
	
	foreach ($ops AS $op) {
		$DB->e("INSERT INTO `op2sale` (`opID`, `saleID`) VALUES (?, ?)", $op, $saleID); }

	foreach ($loots AS $id => $profit) {
		$DB->e("INSERT INTO `saleData` (`saleID`, `typeID`, `profit`) VALUES (?, ?, ?)", $saleID, $id, $profit);
	}

	die('<h2>Sale Submited</h2>');
}
else if (filter_has_var(INPUT_POST, 'submitOpSale')) {
// IF NOT OPS ARE SUBMITTED, DO SOMETHING
	$ops = filter_var_array($_POST['opSelect'], FILTER_VALIDATE_INT);
	
	$stuffs = $DB->qa("
		SELECT lootData.typeID, invTypes.typeName, SUM(lootData.amount) AS total FROM `lootData`
		NATURAL JOIN groups
		NATURAL JOIN operations
		INNER JOIN invTypes ON (lootData.typeID = invTypes.typeID)
		WHERE opID = ".implode($ops, ' OR opID = ')."
		GROUP BY typeID
		", array());

	echo "<form action='".$_SERVER['PHP_SELF']."' method='post'>Please type in how much each loot sold for. ie: if 30 MNRs sold for 180mill total, type in 180000000 for MNR. <br />";
	// put OPS in a session?
	foreach ($ops As $op) { 
		echo "<input type='hidden' name='ops[]' value='".$op."' />\n";}
	foreach ($stuffs AS $stuff) {
		echo "<label for='".$stuff['typeID']."'><input type='text' id='".$stuff['typeID']."' name='sale[".$stuff['typeID']."]' size='15' /> (qty: ".$stuff['total'].") ".$stuff['typeName']."</label><br />\n"; }
	echo "<button name='submitLootSale' type='submit'>Go!</button></form>";

}
else {

	echo "<p>These ops have ended, but have not been sold yet:</p><p><strong>Disclaimer:</strong> You must sell <em>the entire operation stock</em> in one go. You can't sell half now, half later. Make sure you have enough cargo to haul all the operation loot that you wish to sell, otherwise make plans to have more than one hauler/trips. Again, if you select an operation, <strong>you must sell all the loot</strong></p>";

	$ops = $DB->qa("
		SELECT operations.opID, operations.title, memberList.name AS owner, SUM((invTypes.volume*lootData.amount)) AS volume FROM `operations` 
		NATURAL LEFT JOIN op2sale
		NATURAL JOIN groups
		NATURAL JOIN memberList
		NATURAL JOIN lootData
		INNER JOIN invTypes ON (lootData.typeID = invTypes.typeID)
		WHERE 
			timeEnd IS NOT NULL AND 
			saleID IS NULL
		GROUP BY operations.opID", array());
		
	echo "<form action='".$_SERVER['PHP_SELF']."' method='post'>";
	foreach ($ops AS $op) {
		echo "
			<input type='checkbox' name='opSelect[]' value='".$op['opID']."' /> (opID: ".$op['opID'].") ".$op['title']." (".$op['owner'].") (total volume of op: <strong>".$op['volume']."m<sup>3</sup></strong>)<br />\n";
	}
	echo "<button name='submitOpSale' type='submit'>Go!</button></form>";
	
}
$Page->footer();
?>