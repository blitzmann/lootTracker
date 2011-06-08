<?php
session_start();

/****
 * IGB stuff
 ***/
 
//print_r($_SERVER);
//print_r($_COOKIE);
//print_r($_SESSION);

define('START', array_sum(explode(' ', microtime())));
define('SALT_LENGTH', 10);
define('SITE_NAME'  , "M.DYN lootTracker");
define('TAX', 0.10);
define('DEVELOPER', true);

$ingame = substr($_SERVER['HTTP_USER_AGENT'],-7) === 'EVE-IGB';
if ($ingame) {
    if ($_SERVER['HTTP_EVE_TRUSTED'] !== 'Yes') {
		die(
		"<div style='text-align: center;'>This website requires trust. Please click on trust, then click reload.<br />".
		"1) <a href='' onclick=\"CCPEVE.requestTrust('http://".$_SERVER['HTTP_HOST']."/')\">Request trust</a><br />".
		"2) <a href=''>Reload</a></div>"); }
}
else {
	die("This only works in the IGB for now. Maybe I'll get around to making it work for regular browsers..."); } 

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

try {
	$DB = new DB(parse_ini_file('../../../private/db.ini'));
}
catch ( PDOException $e ) {
    echo 'Database connection failed. PDOException:';
    echo $e->getMessage();
    die('=/');
}

$User = new User();
$Page = new Page();

// -- User thing --
if ( filter_has_var(INPUT_POST, 'register') ) {
    if ( empty($_SERVER['HTTP_EVE_CHARID']) && empty($_POST['pass']) ) {
        $User = new User; }
    else {
        $User = User::create(filter_input(INPUT_SERVER, 'HTTP_EVE_CHARID'), filter_input(INPUT_POST, 'pass')); }
}
elseif ( filter_has_var(INPUT_POST, 'login') ) {
    $User = User::login(filter_input(INPUT_SERVER, 'HTTP_EVE_CHARID'), filter_input(INPUT_POST, 'pass'));
}
elseif ( filter_has_var(INPUT_COOKIE, 'sessionID') ) {
    $User = User::auth(filter_input(INPUT_COOKIE, 'sessionID'));
}
else {
    $User = new User;
}

$lootTypes = array(
//	'Datacores'                     => 'SELECT * FROM invTypes WHERE groupID = 333 AND marketGroupID IS NOT NULL',
//	'Decryptors'                    => 'SELECT * FROM invTypes WHERE groupID = 979',
//	'Intact/Malfunctioning/Wrecked' => 'SELECT * FROM invTypes WHERE groupID = 971 OR groupID =	990 OR groupID = 991 OR groupID = 992 OR groupID = 993 OR groupID = 997',
//	'Gas'                           => 'SELECT * FROM invTypes WHERE groupID = 711 AND marketGroupID = 1145',
	'Salvage'                       => 'SELECT * FROM invTypes WHERE groupID = 966',
	'Loot'                          => 'SELECT * FROM invTypes WHERE groupID = 880');

	
// This is here instead of on the operations page because of var unsetting and stuff.
if (filter_has_var(INPUT_POST, 'endOp')){
	if ($User->charID != $DB->q1("SELECT charID FROM `operations` WHERE opID = ?", array($_SESSION['opID']))){
		$Page->errorfooter("You're not the owner of this operation; you cannot end it."); }

	$endCheck = $DB->q("SELECT * FROM `operations` NATURAL JOIN groups NATURAL JOIN lootData WHERE opID = ?", $_SESSION['opID']); 
	
	if ($endCheck === false) {
		$DB->e("DELETE FROM `operations` WHERE opID = ?", $_SESSION['opID']); }
	else{
		$DB->e("UPDATE `operations` SET timeEnd = ? WHERE opID = ?", time(), $_SESSION['opID']); }
	unset($_SESSION['opID']);
}

if (isset($_POST['selectOpID'])) {
	// filter!
	$_SESSION['opID'] = $_POST['selectOpID']; }
	
if(isset($_POST['removeOp'])) {
	unset($_SESSION['opID']); }

$Page->nav['Home'] = './';
$Page->nav['Operations']  = 'operations.php';

if (isset($_SESSION['opID'])) {
	$Page->nav['Loot Record'] = 'lootRecord.php';
}

$Page->nav['Sell Loot'] = 'sellLoot.php';
$Page->nav['Pay Out'] = 'payOut.php';
	
$Page->title = $title;
$Page->header();
?>