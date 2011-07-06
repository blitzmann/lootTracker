<?php

/*
 * Copyright (C) 2011 Ryan Holmes
 * <http://www.gnu.org/licenses/agpl.html>
 */

require '_.php';

$Page->header('Sell Loot');

if (!$User->hasRole('director')){
	$Page->errorfooter('Sorry, but only Directors or other trusted members can access the loot container, and thus sell it.'); }

echo "<h2>Sell Stash</h2>";

if (filter_has_var(INPUT_POST, 'submitOpSale') || filter_has_var(INPUT_POST, 'submitLootSale')) {

	if (filter_has_var(INPUT_POST, 'opSelect')){
		$_SESSION['sellLootOps'] = filter_var_array($_POST['opSelect'], FILTER_VALIDATE_INT); }

	if (!count($_SESSION['sellLootOps'])){
				$Page->errorfooter('<strong>Error:</strong> No operations selected.', false); }

	$stuffs = $DB->qa("
		SELECT lootData.typeID, invTypes.typeName, SUM(lootData.amount) AS total FROM `lootData`
		NATURAL JOIN groups
		NATURAL JOIN operations
		INNER JOIN invTypes ON (lootData.typeID = invTypes.typeID)
		WHERE opID = ".implode($_SESSION['sellLootOps'], ' OR opID = ')."
		GROUP BY typeID
		", array());

	$form = new Form('sellLoot', 'Sell Stash', $_SERVER['PHP_SELF'], 'post');
	
	foreach ($stuffs AS $stuff) {
		$form->add_numeric(
			'sale', 
			"<img style='height: 100%; vertical-align: middle;' src='http://evefiles.capsuleer.de/types/".$stuff['typeID']."_32.png' /> ".$stuff['typeName']." <small>(qty: ".$stuff['total'].")</small>", 
			null, 15, 13, 1, 0, null, 'ISK profit', $stuff['typeID']); 
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
	
			$profit = $DB->q1("SELECT SUM(profit) FROM saleData WHERE saleID = ? GROUP BY saleID", $saleID);
			echo 
			"<div class='success'><strong>Success:</strong> Sale submited</div>".
			"<p>Profit from this sale has successfully been submitted into the database.</p>
			<p><strong>Important:</strong> If you sold the loot and the money went to your personal wallet (ie: did not \"use corp wallet\" when selling), transfer <strong>".number_format($profit)."</strong> to the corp's payout wallet. <strong>Failing to do so will be considered corp theft and be delt with accordingly.</strong></p>
			<p>To issue payments to all the corp members involved, please visit the <a href='payOut.php'>Pay Out</a> page. This can be done later if now is not a convienent time.</p>";
			
			$Page->footer();
		}
		catch (InvalidInput $e) {
		
			$error = '<p class="error"><span>There were errors processing your request</span>';
			foreach ($form->errors as $id => $errors) {
				$error .= implode('<br />', $errors). '<br />';
			}
			$error .= '</p>'."\n\n";

		}	
	}
	
	echo "
		<p>On this page, you'll be able to record how much the loot sold for. Simple process:</p>
		<p>1) Load up your cargohold. The loot should be found in director-only corp hangers. Only put the amount listed as 'qty' in your cargohold. Do not put more or less. If there isn't enough in the corp hanger to meet the qty requirement shown here, then something is wrong -- please consult the other directors as to what may have happened; chances are someone typed in the wrong amount when recording loot, or it might be in another hanger.</p>
		<p>2) Go to Wallet -> Corporation Wallet -> Wallet Divisions and right click ".PAYNAME." -> Set As Active Wallet.</p>
		<p>3) Haul the loot to a trade station and sell. When you sell the loot, remember to check \"use corp wallet\" so that the money recieved goes directly to the corp's payout wallet. <strong>Remember: </strong>Some loot is bought by NPC's at a fixed rate. Many trade stations, including Jita, do not have these NPC orders. Remember to sell these items to the NPCs.</p>
		<p>4) When done selling all the items, click the \"Import Journal\" button. This will fetch the transaction data from your API and automatically fill in the details for you. Please double check these calues if they seem off.</p>
		<p>5) When finished, click \"Submit\". <strong>Remember:</strong> Change the corp wallet division back to the Master Wallet (or any other wallet other than ".PAYNAME.")</strong></p>
		<hr />";?>
	<script type="text/Javascript">
        $(document).ready(function(){

			$('#loading').ajaxStart(function() {
				$(this).show();
			}).ajaxComplete(function() {
				$(this).hide();
			});

            $('[name="journalImport"]').click(function(){
                $.get('ajax.php',function(data){
                    data = $.parseJSON(data);
					if (data.cacheNotice) {
						$('#loading').after("<p class='note'><strong>Notice:</strong> "+data.cacheNotice+"</p>"); }
					if (data.debt) {
						alert ('You have debt. Please remember to send this money to the proper corp wallet. Amount: '+$.mask.string(data.debt, 'integer'));
					}
					$('[type="submit"]').before("<h3><strong>Total:</strong> "+$.mask.string(data.total, 'integer')+"</h3>");
					$.each(data.data,function(i,v){
					    $('[name="sale['+i+']"]').attr('value',$.mask.string(v, 'integer'));
                    });
                });
            });

			$('[name^="sale"]').setMask('integer');

			$('#sellLoot').submit(function() {
				$('[name^="sale"]').setMask('999999999999'); });

        });
    </script>
	<button name='journalImport'>Import Journal</button> <span style='display: none;' id='loading'>LOADING...</span>

<?php

	if (isset($error)) { echo $error; }
	$form->display_form();
}
else {

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
			<li><label for='".$op['opID']."_'><input id='".$op['opID']."_' type='checkbox' name='opSelect[]' value='".$op['opID']."' /><img style='height: 100%; vertical-align: middle;' src='http://evefiles.capsuleer.de/icons/32_32/icon53_16.png' /> <em>".$op['title']."</em> -- ".$op['owner']." (total volume of op: <strong>".ceil($op['volume'])."m<sup>3</sup></strong>)</label></li>";
	}
	echo "<button name='submitOpSale' type='submit'>Go!</button></form>";
	
}
$Page->footer();
?>