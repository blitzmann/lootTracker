<?php

$title = 'Pay Out';
require '_.php';


if (filter_has_var(INPUT_POST, 'submitPayed')){
		$DB->e("UPDATE saleHistory SET payer = ?, payedTime = ? WHERE saleID = ?", $User->charID, time(), filter_input(INPUT_POST, 'submitPayed', FILTER_SANITIZE_NUMBER_INT));
		die("<h2>Payments Complete</h2>");
}
else if (filter_has_var(INPUT_POST, 'payout')){

	echo "<p>Click name to open Show Info. top left corner there's a white box thing. Click it -> give money and type in the amount you see here. Checkboxes are provided to help you keep track of who has been payed; they serve no other function. <strong>REMEMBER:</strong> Click the \"Done!\" button when finished to complete payout.</p><p>TODO: (JS function) when name is clicked, copy amount owed to clipboard so that payer cane just paste it.</p>
	<p>".(TAX*100)."% corp tax is applied to calculations, including whatever has been left over from rounding down.</p><dl>";
	
	$data = $DB->qa("
		SELECT *, payout - (payout*?) AS truePayout, (payout*?) as tax
		FROM memberPayout
		WHERE saleID = ?", array(TAX, TAX, filter_input(INPUT_POST, 'payout', FILTER_SANITIZE_NUMBER_INT)));
		
	$corpTax = $DB->q1("SELECT difference FROM `profit-payout` WHERE saleID = ?", array(filter_input(INPUT_POST, 'payout', FILTER_SANITIZE_NUMBER_INT)));
	foreach ($data AS $character) {
		$corpTax = $corpTax+$character['tax'];
		echo "
		<dt><input type='checkbox' /> <img style='vertical-align: middle;' src='http://evefiles.capsuleer.de/icons/16_16/icon09_09.png' />
			<a onclick='CCPEVE.showInfo(1377, ".$character['charID'].")'>".$character['name']."</a></dt><dd>".number_format($character['truePayout'])."</dd>"; }
		
	echo "
	<dt><input type='checkbox' /> <img style='vertical-align: middle;' src='http://evefiles.capsuleer.de/icons/16_16/icon09_09.png' />
			<a onclick='CCPEVE.showInfo(1377, ".$character['charID'].")'>[M.DYN] Massively Dynamic</a></dt><dd>".number_format($corpTax)."</dd>
	</dl>
	<form action='".$_SERVER['PHP_SELF']."' method='post'>
	<button name='submitPayed' value='".filter_input(INPUT_POST, 'payout', FILTER_SANITIZE_NUMBER_INT)."'>Done!</button></form>";
}
else {
	echo "These loot runs have yet to be payed out. Please choose one to pay out.";

	$payouts = $DB->qa("
		SELECT saleHistory.*, COUNT(op2sale.opID) AS opCount, memberList.name
		FROM saleHistory 
		NATURAL JOIN op2sale 
		INNER JOIN memberList ON (saleHistory.seller = memberList.charID)
		WHERE payedTime IS NULL
		GROUP BY saleID", array());

	echo "<form action='".$_SERVER['PHP_SELF']."' method='post'>";
	foreach ($payouts AS $payout) {
		echo "
			<button type='submit' name='payout' value='".$payout['saleID']."'>Pay</button> 
			(id: ".$payout['saleID'].") (OPs: ".$payout['opCount'].") ".$payout['name']." sold stuff on [date here]<br />";
	}
	echo "</form>";
}

$Page->footer();
?>

