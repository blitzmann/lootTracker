<?php

/*
 * Copyright (C) 2011 Ryan Holmes
 * <http://www.gnu.org/licenses/agpl.html>
 */
 
ob_start("ob_gzhandler"); 
session_start();

define('START', microtime(true));
define('SALT_LENGTH', 10);

$cfg = parse_ini_file('inc/config.ini');

define('SITE_NAME', $cfg['site_name']);
define('CORP', $cfg['corp_name']);
define('CORPTIC', $cfg['corp_ticker']);
define('CORPID', (float)$cfg['corpid']);
define('PAYNAME', $cfg['payname']);
define('TAX', (float)$cfg['tax']); //Tax. 1 = 100%, .35 = 35%, etc
define('DEVELOPER', (bool)$cfg['developer']); // If true, prints out errors on page in the footer
define('DBVERSION', $cfg['db_version']); // static DB version, shows up in footer

/* the XML file to use. If this is set, the journal import will not fetch from the API server.
 * usefull for testing out the XML files that are generated via JOURNAL_API_DUBUG */
define('JOURNAL_API_FILE', './xmlDebug/charWalletTransaction-07-03-11_21:02:12.xml');
/* causes API fetches to be saved as an XML file on the server for later viewing and debugging. 
 * Not relevent if JOURNAL_API_FILE is set */
define('JOURNAL_API_DUBUG', false); 

$ingame = substr($_SERVER['HTTP_USER_AGENT'],-7) === 'EVE-IGB';
if ($ingame) {
    if ($_SERVER['HTTP_EVE_TRUSTED'] !== 'Yes') {
		die(
		"<div style='text-align: center;'>This website requires trust. Please click on trust, then click reload.<br />".
		"1) <a href='' onclick=\"CCPEVE.requestTrust('http://".$_SERVER['HTTP_HOST']."/')\">Request trust</a><br />".
		"2) <a href='./'>Reload</a></div>"); }
}

function __autoload($name) {
    include './classes/'.$name.'.class.php'; }

//* basic Error stuff

function e_handler($exception) {
	header('HTTP/1.1 500 Internal Server Error');
	header('Content-Type: text/html; charset=UTF-8');
	echo "<h1>Error</h1>\n",
		'<pre class="error">',$exception,'</pre>';
	exit;
}

//set_error_handler(create_function('$a, $b, $c, $d', 'throw new ErrorException($b, 0, $a, $c, $d);'), E_ALL);
set_exception_handler('e_handler');

class InvalidInput extends Exception {}

$secret = parse_ini_file($cfg['secret_file']);
define('CRYPT_KEY', $secret['salt']);
try {
	$DB = new DB($secret);
}
catch ( PDOException $e ) {
    echo 'Database connection failed. PDOException:';
    echo $e->getMessage();
	unset($secret);
    die('=/');
}
unset($secret);

// sanity check
try {
	if(TAX > 1 || TAX < 0){
		throw new Exception('You clutz! Set your Tax to a sane value.'); }
} catch ( Exception $e ) {
	die($e->getMessage()); }

$User = new User();
$Page = new Page();

// -- User thing --
if ( filter_has_var(INPUT_POST, 'register') ) {
    if ( empty($_POST['charID']) && empty($_POST['pass']) ) {
        $User = new User; }
    else {
		if ($_POST['pass'] == $_POST['pass2']) {
			$User = User::create(filter_input(INPUT_POST, 'charID'), filter_input(INPUT_POST, 'pass'), $_SESSION['userID'], $_SESSION['key']); }
	}
}
elseif ( filter_has_var(INPUT_POST, 'login') ) {
    $User = User::login(filter_input(INPUT_POST, 'charName'), filter_input(INPUT_POST, 'pass'));
}
elseif ( filter_has_var(INPUT_COOKIE, 'sessionID') ) {
    $User = User::auth(filter_input(INPUT_COOKIE, 'sessionID'));
}
else {
    $User = new User;
}

require 'inc/lootTypes.php'; // include loot array


$Page->nav['Home'] = 'index.php';
	
// This is here instead of on the operations page because of var unsetting and stuff.
if (filter_has_var(INPUT_POST, 'endOp') && filter_has_var(INPUT_POST, 'confirmEnd') && isset($_SESSION['opID'])){
	if ($User->charID != $DB->q1("SELECT charID FROM `operations` WHERE opID = ?", array($_SESSION['opID']))){
		$Page->errorfooter("You're not the owner of this operation; you cannot end it."); }

	$endCheck = $DB->q("SELECT * FROM `operations` NATURAL JOIN groups NATURAL JOIN lootData WHERE opID = ?", $_SESSION['opID']); 
	
	if ($endCheck === false) {
		$DB->e("DELETE FROM `operations` WHERE opID = ?", $_SESSION['opID']); }
	else{
		$DB->e("UPDATE `operations` SET timeEnd = ? WHERE opID = ?", time(), $_SESSION['opID']); }
	unset($_SESSION['opID']);
}

if (filter_has_var(INPUT_POST, 'transferOp') && filter_input(INPUT_POST, 'transfer', FILTER_VALIDATE_INT) != $User->charID && isset($_SESSION['opID'])){
	if ($User->charID != $DB->q1("SELECT charID FROM `operations` WHERE opID = ?", array($_SESSION['opID']))){
		$Page->errorfooter("You're not the owner of this operation; you cannot transfer the ownership."); }

	$DB->e("UPDATE `operations` SET charID = ? WHERE opID = ?", filter_input(INPUT_POST, 'transfer', FILTER_VALIDATE_INT), $_SESSION['opID']);
}

if (isset($_POST['selectOpID'])) {
	// filter!
	$_SESSION['opID'] = $_POST['selectOpID']; }
	
if(isset($_POST['removeOp'])) {
	unset($_SESSION['opID']); }
	
$Page->nav['Operations']  = 'operations.php';
if (isset($_SESSION['opID'])) {
	$Page->nav['Loot Record'] = 'lootRecord.php';
}
$Page->nav['Sell Stash'] = 'sellLoot.php';
$Page->nav['Pay Out'] = 'payOut.php';
if ($User->hasRole('director')){
	$Page->nav['Admin'] = 'admin.php'; }

?>