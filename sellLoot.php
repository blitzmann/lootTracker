<?php

$title = 'Sell Loot';

require '_.php';
//unset($_SESSION['sellLootOps']);
//if ($User->hasRole('director')){
//	$Page->errorfooter('Sorry, but only Directors or other trusted members can access the loot container, and thus sell it.'); }

if (filter_has_var(INPUT_POST, 'submitOpSale') || filter_has_var(INPUT_POST, 'submitLootSale')) {

	if (filter_has_var(INPUT_POST, 'submitOpSale')){
		$_SESSION['sellLootOps'] = filter_var_array($_POST['opSelect'], FILTER_VALIDATE_INT); }

	if (!count($_SESSION['sellLootOps'])){
				$Page->errorfooter('No operations selected.', false); }

	$stuffs = $DB->qa("
		SELECT lootData.typeID, invTypes.typeName, SUM(lootData.amount) AS total FROM `lootData`
		NATURAL JOIN groups
		NATURAL JOIN operations
		INNER JOIN invTypes ON (lootData.typeID = invTypes.typeID)
		WHERE opID = ".implode($_SESSION['sellLootOps'], ' OR opID = ')."
		GROUP BY typeID
		", array());

	echo "
		<h2>Sell Stash</h3>
		make sure you put in only what is listed<p>dont put decimal place -- wont work and isn't needed
		<p>On this page, you'll be able to record how much the loot sold for. When you sell the loot, record how much each one sold for. For example, if 30 Melted Nanoribbons sold for 180mil ISK total, type in 180000000 for Melthed Nanoribbons. <strong>Remember:</strong> sometimes you might sell loot to multiple people's buy orders. If this happens, you'll have to sell that loot multiple times. Remember to add the totals up and put the total here.</p>
		<p>Also, please only sell the amount listed as 'qty'. If, forwhatever reason, you have more, don't sell it. If you don't have enough... well, I'll get to that later (go ahead and sell what you have for now)</p>
		<hr />";

	$form = new Form('sellLoot', 'Sell Stash', $_SERVER['PHP_SELF'], 'post');
	
	foreach ($stuffs AS $stuff) {
		$form->add_numeric(
			'sale', 
			"<img style='height: 100%; vertical-align: middle;' src='http://evefiles.capsuleer.de/types/".$stuff['typeID']."_32.png' /> ".$stuff['typeName']." <small>(qty: ".$stuff['total'].")</small>", 
			null, 15, 13, 5, 0, null, 'ISK profit', $stuff['typeID']); 
	}
	$form->add_submit('submitLootSale', 'Submit');

	// this filter is here as a hack. Look into check_fields_exist() to fix it at the core
	// Actually, filter_has_var might be good enough to get rid of check_fields_exist()
	if ($form->check_fields_exist() && filter_has_var(INPUT_POST, 'submitLootSale')) {

		$form->update_values_from_post();
	
		try {
			if (!$form->validate()) {
				throw new InvalidInput(); }
	
			$sale = array_filter(filter_var_array($_POST['sale'], FILTER_SANITIZE_NUMBER_INT));
			
			if (count($sale) === 0) {
				$form->errors[false][] = "No data submitted.";
				throw new InvalidInput(); 
			}
		
			$loots = filter_var_array($_POST['sale'], FILTER_VALIDATE_INT);
		
			$DB->e("INSERT INTO `saleHistory` (`saleID`, `seller`, `saleTime`) VALUES (?, ?, ?)", null, $User->charID, time());
			$saleID = $DB->lastInsertID();
		
			foreach ($_SESSION['sellLootOps'] AS $op) {
				$DB->e("INSERT INTO `op2sale` (`opID`, `saleID`) VALUES (?, ?)", $op, $saleID); }
			unset($_SESSION['sellLootOps']);
			foreach ($loots AS $id => $profit) {
				$DB->e("INSERT INTO `saleData` (`saleID`, `typeID`, `profit`) VALUES (?, ?, ?)", $saleID, $id, $profit); }
	
			echo 
			"<h2>Sale Submited</h2>".
			"<p>Profit from this sale has successfully been submitted into the database. To issue payments to all the corp members involved, please visit the <a href='payOut.php'>Pay Out</a> page.</p>";
			
			$Page->footer();
		}
		catch (InvalidInput $e) {
			
			echo '<p class="error">There are errors in your form!<br />';
			foreach ($form->errors as $id => $errors) {
				echo implode('<br />', $errors). '<br />';
			}
			echo '</p>'."\n\n";
	
		}
	}

	$form->display_form();
}
else {

	echo "<h2>Sell Stash</h2>";
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
		
	if (!count($ops)){
		$Page->errorfooter('No operations waiting to be sold', false); }
	
	echo "
		<p>These operations have ended, but the loot has not been sold yet. Please select the operation(s) you wish to sell to continue.</p>
		<p><strong>Disclaimer:</strong> You must sell <em>the entire operation stock</em> in one go. You can't sell half now, half later. Make sure you have enough cargo to haul all the operation loot that you wish to sell, otherwise make plans to have more than one hauler/trip. Again, if you select an operation, <strong>you must sell all the loot</strong></p><hr />\n";
	
	echo "<form action='".$_SERVER['PHP_SELF']."' method='post'>\n<ul>\n";
	foreach ($ops AS $op) {
		echo "
			<li><label for='".$op['opID']."_'><input id='".$op['opID']."_' type='checkbox' name='opSelect[]' value='".$op['opID']."' /><img style='height: 100%; vertical-align: middle;' src='http://evefiles.capsuleer.de/icons/32_32/icon53_16.png' /> <em>".$op['title']."</em> -- ".$op['owner']." (total volume of op: <strong>".$op['volume']."m<sup>3</sup></strong>)</label></li>";
	}
	echo "<button name='submitOpSale' type='submit'>Go!</button></form>";
	
}
$Page->footer();
?>