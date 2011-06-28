<?php
session_start();


///// CONFIG STUFF
///// fix with require _.php, but don't print page headers...

$cfg = parse_ini_file('inc/config.ini');
$secret = parse_ini_file($cfg['secret_file']);

function __autoload($name) {
    include './classes/'.$name.'.class.php'; }

	try {
	$DB = new DB($secret);
}
catch ( PDOException $e ) {
    echo 'Database connection failed. PDOException:';
    echo $e->getMessage();
	unset($secret);
    die('=/');
}

$stuffs = $DB->qa("
		SELECT lootData.typeID, SUM(lootData.amount) AS total FROM `lootData`
		NATURAL JOIN groups
		NATURAL JOIN operations
		INNER JOIN invTypes ON (lootData.typeID = invTypes.typeID)
		WHERE opID = ".implode($_SESSION['sellLootOps'], ' OR opID = ')."
		GROUP BY typeID
		", array());

$xml = new SimpleXMLElement(file_get_contents('charWalletTransactions.xml'));

// if cache time hasn't been reached, error out.


$newArray = array();
$json = array();
foreach ($stuffs AS $stuff) {
	$newArray[$stuff['typeID']] = $stuff['total']; }

		$debt = 0;
foreach ($xml->result->rowset->row AS $transaction) {
	if (!isset($newArray[(int)$transaction['typeID']]) || (string)$transaction['transactionType'] === 'buy') { 
		continue; }
		
	if (!isset($json[(int)$transaction['typeID']])) { $json[(int)$transaction['typeID']] = 0; }

	$total = ((float)$transaction['price']*(int)$transaction['quantity']);
	// .01 for 1% sales tax
	$tax = round(($total * .01), 2);
	$total = ($total - $tax);
	$json[(int)$transaction['typeID']] = ($json[(int)$transaction['typeID']] + $total);
	
	if ((string)$transaction['transactionFor'] === 'personal') {
		$debt = $debt + $total; }
}
	
//header('Cache-Control: no-cache, must-revalidate');
//header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
//header('Content-type: application/json');
	echo json_encode($json);





?>