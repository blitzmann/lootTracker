<?php
session_start();

/****
 * IGB stuff
 ***/
 
//print_r($_SERVER);
//print_r($_COOKIE);
//print_r($_SESSION);

// yes, I shamelessly stole this from somewhere online
define('SALT_LENGTH', 10);
$loginMessage = '';

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

require 'DB.php';
require 'user.class.php';

$DB = new DB(parse_ini_file('/home/ryan/www/private/db.ini'));
$User = new User();

if (isset($_GET['logout'])) {
	$User->logout(); }

if ($_SESSION['logged'] == false && isset($_POST['login'])){
	$User->checkLogin((int)$_SERVER['HTTP_EVE_CHARID'], $_POST['pass']); 
	
	if ($User->loginFailed === true) {
		$loginMessage = "Opps! Someone can't type their password in! Login failed."; }
}

if ($_SESSION['logged'] == false && isset($_POST['register'])){
	$User->regUser((int)$_SERVER['HTTP_EVE_CHARID'], $_POST['pass']); 
	if ($User->notInCorp === true){
		$loginMessage = "You're not part of this corp. Go away."; }
	if ($User->loginFailed === true) { // if user is already reg'd, but still didn't put in proper password.
		$loginMessage = "Opps! Someone can't type their password in! Login failed."; }
}

// check for proper session data and set $User vars for all pages
if ($_SESSION['logged'] == true ) {
	$User->id   = $_SESSION['charID'];
	$User->name = $_SESSION['name'];
}

if ($_SESSION['logged'] == false) {
	die(
	"<div style='text-align: center;'><h2>Welcome ".$_SERVER['HTTP_EVE_CHARNAME']."!</h2>".
	"<p>Please login:</p><p>$loginMessage</p><form action='".$_SERVER['PHP_SELF']."' method='post'>".
	"<input style='text-align:center;' type='text' name='pass' onfocus='if(this.value == \"Password\") { this.value = \"\"; }' value='Password' /><br /><br /><button name='login' type='submit'>Login</button><button name='register' type='submit'>Register</button></form></div>");
}

if(isset($_POST['removeOp'])) {
	unset($_SESSION['opID']); }

$lootTypes = array(
	'Datacores'                     => 'SELECT * FROM invTypes WHERE groupID = 333 AND marketGroupID IS NOT NULL',
	'Decryptors'                    => 'SELECT * FROM invTypes WHERE groupID = 979',
	'Intact/Malfunctioning/Wrecked' => 'SELECT * FROM invTypes WHERE groupID = 971 OR groupID =	990 OR groupID = 991 OR groupID = 992 OR groupID = 993 OR groupID = 997',
	'Gas'                           => 'SELECT * FROM invTypes WHERE groupID = 711 AND marketGroupID = 1145',
	'Salvage'                       => 'SELECT * FROM invTypes WHERE groupID = 966',
	'Loot'                          => 'SELECT * FROM invTypes WHERE groupID = 880');

echo "<a href='?logout'>Logout</a><br />
<a href='operations.php'>Operations</a><br />
<a href='lootRecord.php'>Record Loot</a><br />";

if(isset($_POST['submitOperation'])) {
	$DB->ea("INSERT INTO `operations` (`opID`, `ownerID`, `title`, `description`, `timeStart`) VALUES (?, ?, ?, ?, ?)", array(null, $User->id, $_POST['title'], $_POST['description'], time()));
	$_SESSION['opID'] = $DB->lastInsertID();
}

if (isset($_POST['selectOpID'])) {
	$_SESSION['opID'] = $_POST['selectOpID']; }

if(isset($_SESSION['opID'])) {
	$operation = $DB->q("SELECT * FROM `operations` WHERE opID = ?", array($_SESSION['opID']));

	echo "<h2>Current Op:</h2>id: ".$operation['opID']."<br />Title: ".$operation['title']."<br />
	<form method='post'><button type='submit' name='removeOp'>Remove Op Session</button></form><br /><br /><br />";
}

?>