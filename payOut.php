<?php

/*
 * Copyright (C) 2011 Ryan Holmes
 * <http://www.gnu.org/licenses/agpl.html>
 */
 
$title = 'Pay Out';
require '_.php';

if (!$User->hasRole('director')){
	$Page->errorfooter('Sorry, but only Directors or other trusted members can access the loot container, and thus sell it.'); }

if (filter_has_var(INPUT_POST, 'submitPayed')){
		// todo: if refreshed, payment info will be updated again... Don't do that
		//       Perhaps set, thun unset, some session vars 
		$DB->e("UPDATE saleHistory SET payer = ?, payedTime = ? WHERE saleID = ?", $User->charID, time(), filter_input(INPUT_POST, 'submitPayed', FILTER_SANITIZE_NUMBER_INT));
		echo "
			<h2>Payments Complete</h2>
			<p>With payment completed, this sale and the operations associated with it will now go into archives. Thank you!</p>";
		$Page->footer();
}
else if (filter_has_var(INPUT_POST, 'payout')){
/*
	make it so that the checkboxes are REQUIRED
*/
	echo "<h2>Pay Out</h2><p>Click name to open Show Info. In the top left corner there's a white box thing. Click it -&gt; Give Money and type in the amount you see here. Checkboxes are provided to help you keep track of who has been payed; they serve no other function. <strong>Remember:</strong> Click the \"Done!\" button when finished to complete payout.</p>
	<p><strong>".(TAX*100)."%</strong> corp tax is automatically applied to calculations, which is then added to whatever has been left over from from total profit due to rounding down.</p><hr /><dl>";
	
	$data = $DB->qa("
		SELECT *, payout - (payout*?) AS truePayout, (payout*?) as tax
		FROM memberPayout
		WHERE saleID = ?", array(TAX, TAX, filter_input(INPUT_POST, 'payout', FILTER_SANITIZE_NUMBER_INT)));
		
	$corpTax = $DB->q1("SELECT difference FROM `profit-payout` WHERE saleID = ?", array(filter_input(INPUT_POST, 'payout', FILTER_SANITIZE_NUMBER_INT)));
	$class = 'even';
	foreach ($data AS $character) {
		$class = ($class == 'even' ? 'odd' : 'even');
		$corpTax = $corpTax+$character['tax'];
		echo "
		<dt class='".$class."'><input type='checkbox' /> <img style='vertical-align: middle;' src='http://evefiles.capsuleer.de/icons/16_16/icon09_09.png' />
			<a onclick='CCPEVE.showInfo(1377, ".$character['charID'].")'> ".$character['name']."</a></dt><dd class='".$class."'>".number_format($character['truePayout'])."</dd>"; }
		
	echo "
	<dt class='".($class == 'even' ? 'odd' : 'even')."'><input type='checkbox' /> <img style='vertical-align: middle;' src='http://evefiles.capsuleer.de/icons/16_16/icon09_09.png' />
			<a onclick='CCPEVE.showInfo(2, ".CORPID.")'> ".CORPTIC." ".CORP."</a></dt><dd class='".($class == 'even' ? 'odd' : 'even')."'>".number_format($corpTax)."</dd>
	</dl>
	<form action='".$_SERVER['PHP_SELF']."' method='post'><p class='submit'>
	<button name='submitPayed' value='".filter_input(INPUT_POST, 'payout', FILTER_SANITIZE_NUMBER_INT)."'>Done!</button></p></form>";
}
else {
	echo "
		<h2>Pay Out</h2>
		<p>These loot runs have yet to be payed out. Please choose one to pay out.</p>";

	$payouts = $DB->qa("
		SELECT saleHistory.*, COUNT(op2sale.opID) AS opCount, memberList.name
		FROM saleHistory 
		NATURAL JOIN op2sale 
		INNER JOIN memberList ON (saleHistory.seller = memberList.charID)
		WHERE payedTime IS NULL
		GROUP BY saleID", array());

	echo "<form action='".$_SERVER['PHP_SELF']."' method='post'><ul>\n";
	foreach ($payouts AS $payout) {
		echo "
			<li><button type='submit' name='payout' value='".$payout['saleID']."'>Pay</button> 
			".$payout['name']." sold stuff on ".date('m/d \a\t H:i', $payout['saleTime'])." (# of operations: ".$payout['opCount'].")</li>";
	}
	echo "</ul></form>";
}

$Page->footer();
?>

