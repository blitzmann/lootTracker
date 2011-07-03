<?php

require '_.php';

function get_data($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}

$stuffs = $DB->qa("
		SELECT lootData.typeID, SUM(lootData.amount) AS total FROM `lootData`
		NATURAL JOIN groups
		NATURAL JOIN operations
		INNER JOIN invTypes ON (lootData.typeID = invTypes.typeID)
		WHERE opID = ".implode($_SESSION['sellLootOps'], ' OR opID = ')."
		GROUP BY typeID
		", array());

$url = 'http://api.eve-online.com/char/WalletTransactions.xml.aspx?'.
		'useriD='.$User->userID.'&'.
		'apiKey='.User::decrypt($User->key).'&'.
		'characterID='.$User->charID.'&'.
		'rowCount='.(count($stuff) + 150);

if (JOURNAL_API_FILE) {
	$xml = new SimpleXMLElement(file_get_contents(JOURNAL_API_FILE)); }
else {
	$data = get_data($url);
	$xml = new SimpleXMLElement($data);

	if (JOURNAL_API_DUBUG) { 
		file_put_contents('./xmlDebug/charWalletTransaction-'.date("m-d-y_H:i:s").'.xml', $data); }
}

$transactions = array();
$newArray = array();
$json = array();
$check = array();

foreach ($xml->result->rowset->row AS $row) {
	$attr = (array)$row->attributes();
	$transactions[$attr['@attributes']['transactionID']] = $attr['@attributes'];
	// use xpath to to filter out only sale data via attributes 
}

krsort($transactions);

// data is cached for 1620 sec (27min). subtract 5 from the current time as a nice buffer.
if ((strtotime((string)$xml->cachedUntil) - (strtotime((string)$xml->currentTime)-5)) < 1620) {
	$json['cacheNotice'] =  'Transaction API seems to have been recently requested and thus may not be up-to-date with loot sales. '.
	'If the data below seems inaccurate, please try again after '.
	((string)$xml->cachedUntil).' ('.ceil((strtotime((string)$xml->cachedUntil)-time())/60).' min)'; }
	
foreach ($stuffs AS $stuff) {
	$newArray[$stuff['typeID']] = $stuff['total']; }

foreach ($transactions AS $transaction) {
	$id = $transaction['typeID'];

	if (empty($newArray)) {
		break; }
	if (!isset($newArray[$id]) || $transaction['transactionType'] === 'buy') { 
		continue; }

	if (!isset($json['data'][$id])) { $json['data'][$id] = 0; }
	if (!isset($check[$id])) { $check[$id] = 0; }

	$total = ($transaction['price']*$transaction['quantity']);
	// .01 for 1% sales tax
	$tax = round(($total * .01), 2);
	$profit = floor($total - $tax);
	$json['data'][$id] = ($json['data'][$id] + $profit);
	
	$newArray[$id] = ($newArray[$id] - (int)$transaction['quantity']);
	
	if ($transaction['transactionFor'] === 'personal') {
		$json['debt'] = (isset($json['debt']) ? $json['debt'] : 0) + $profit; }
		
	if ($newArray[$id] == 0) {
		unset($newArray[$id]); }
}

if (!empty($newArray)) {
	$json['leftOver'] = $newArray; }
	
echo json_encode($json);

?>