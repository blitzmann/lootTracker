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
			
			echo 
			"<div class='success'><strong>Success:</strong> Sale submited.</div>\n".
			"<p>Profit from this sale has successfully been submitted into the database.</p>\n";
			if(filter_has_var(INPUT_POST, 'debt')){
				echo
				"<p><strong>Remember:</strong> You have a debt to the corp for the amount of ".number_format($_POST['debt']).". This will be logged and tracked; if you do not pay this to the corp, it will be considered corp theft and be delt with accordingly.</p>"; 
			}
			
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
				$('span.total').html( $('[name^="sale"]').sumValues() ); // Total the values
			});

			// Add total div
			$('#sellLoot button[type="submit"]').before("<div><strong>Total:</strong> <span class='total'>0</span> ISK</div>");

			// This happens when the Import button is clicked
            $('[name="journalImport"]').click(function(){
                $.get('ajax.php',function(data){
                    data = $.parseJSON(data);
					if (data.cacheNotice) {
						$('#loading').after("<p class='note'><strong>Notice:</strong> "+data.cacheNotice+"</p>"); }

					if (data.debt) {
						$('#sellLoot button[type="submit"]').before("<p class='error'><strong>Warning:</strong> You have debt. This happens when you don't use the \"Use Corp Wallet\" option when selling items and the profit goes to your personal wallet rather than the corps'. Please remember to send the proper amount to the proper corp wallet; failing to do so is considered corp theft and will be delt with accordingly.<br/>Amount: <strong>"+$.mask.string(data.debt, 'integer')+"</strong></p>");
						$('#sellLoot').prepend("<input type='hidden' name='debt' value='"+data.debt+"' />");
					}

					$.each(data.data,function(i,v){
					    $('[name="sale['+i+']"]').attr('value',$.mask.string(v, 'integer'));
                    });

                });
            });

			$('[name^="sale"]').setMask('integer');

			$('[name^="sale"]').change(function() {
				$('span.total').html( $('[name^="sale"]').sumValues() );
			});

			$('#sellLoot').submit(function() {
				$('[name^="sale"]').setMask('999999999999'); });
        });
    </script>
	<button name='journalImport'>Import Journal</button> <span style='display: none;' id='loading'>LOADING... <img src='./inc/ajax.gif' /></span>

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
?>
	<script type="text/Javascript">
        $(document).ready(function(){
			$('#sellLoot button[type="submit"]').before("<div><strong>Total:</strong> <span class='total'>0</span>m<sup>3</sup></div>");

			$('[name^="opSelect"]').change(function() {
				$('span.total').html( $('input:checked ~ strong > .volume').sumValues() );
			});
		});
    </script>
<?php
	echo "
		<p>These operations have ended, but the loot has not been sold yet. Please select the operation(s) you wish to sell to continue.</p>
		<p><strong>Disclaimer:</strong> You must sell <em>the entire operation stock</em> in one go. You can't sell half now, half later. Make sure you have enough cargo to haul all the operation loot that you wish to sell, otherwise make plans to have more than one hauler/trip. Again, if you select an operation, <strong>you must sell all the loot</strong></p><hr />\n";
	
	echo "<form id='sellLoot' action='".$_SERVER['PHP_SELF']."' method='post'>\n<ul>\n";
	foreach ($ops AS $op) {
		echo "
			<li><label for='".$op['opID']."_'><input id='".$op['opID']."_' type='checkbox' name='opSelect[]' value='".$op['opID']."' /><img style='height: 100%; vertical-align: middle;' src='http://evefiles.capsuleer.de/icons/32_32/icon53_16.png' /> <em>".$op['title']."</em> -- ".$op['owner']." (total volume of op: <strong><span class='volume'>".ceil($op['volume'])."</span>m<sup>3</sup></strong>)</label></li>";
	}
	echo "<button name='submitOpSale' type='submit'>Go!</button></form>";
	
}
$Page->footer();
?>