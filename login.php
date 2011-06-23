<?php
if (isset($_POST['continue'])){

	$_SESSION['userID'] = filter_input(INPUT_POST, 'userID');
	$_SESSION['key']    = filter_input(INPUT_POST, 'key');
	
	$url = "http://api.eve-online.com/account/Characters.xml.aspx?userID=".$_SESSION['userID']."&apiKey=".$_SESSION['key'];
	$fullCheck = "http://api.eve-online.com/account/AccountStatus.xml.aspx?userID=".$_SESSION['userID']."&apiKey=".$_SESSION['key'];
	
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
	
	$check = new SimpleXMLElement(get_data($fullCheck));
	if ((int)$check->error['code'] == 200) {
		die ('Not a full API'); }

	$data = get_data($url);
	
	if (!$data) { die('API Failure, nothing returned from API servers. They may be down or something. Try again later.'); }
	
	$data = new SimpleXMLElement($data);

	$char = array();
	foreach ($data->result->rowset->row AS $character) {
		if ($character['corporationID'] == CORPID) {
			$char[] = $character; }
	}
	
	if (empty($char)) {
		die('None of your characters belong to '.CORPTIC); }

	echo
	"<h1 style='text-align: center;'>".SITE_NAME."</h1><h2>Choose Character</h2>\n".
	"<form style='width: 200px; margin:0 auto;' action='".$_SERVER['PHP_SELF']."' method='post'><div >";

	foreach ($char AS $character) {
		echo "	<label><input type='radio' name='charID' value='".$character['characterID']."' /> ".$character['name']."</label><br />"; }
	
	echo
	"</div><div style='text-align: center;'>	<dl><dt>Password</dt>\n".
	"		<dd><input style='text-align:center;' type='password' name='pass' /></dd>\n".
	"	<dt>Confirm Password</dt>\n".
	"		<dd><input style='text-align:center;' type='password' name='pass2' /></dd>\n"."</dl><br/>\n".
	"<button value='yes' name='register' type='submit'>Register</button></form>\n";
}



else {
	echo
	"<div style='text-align: center;'><h1>".SITE_NAME."</h1><h2>Login</h2>\n".
	"<form action='".$_SERVER['PHP_SELF']."' method='post'>".
	"<dl>\n".
	"	<dt>Character Name</dt>\n".
	"		<dd><input style='text-align:center;' type='text' name='charName'".
		($ingame ? " value='".$_SERVER['HTTP_EVE_CHARNAME']."'" : null)." /></dd>\n".
	"	<dt>Password</dt>\n".
	"		<dd><input style='text-align:center;' type='password' name='pass' /></dd>\n".
	"</dl><br/>\n".
	"<button value ='yes' name='login' type='submit'>Login</button></form>\n".
	"<h2>Register</h2>\n".
	"<form action='".$_SERVER['PHP_SELF']."' method='post'>".
	"<dl>\n".
	"	<dt>API UserID</dt>\n".
	"		<dd><input style='text-align:center;' type='text' name='userID' /></dd>\n".
	"	<dt>API <strong>Full</strong> Key</dt>\n".
	"		<dd><input style='text-align:center;' type='text' name='key' /></dd>\n".
	"</dl><br />\n".
	($ingame ? " <button name='continue' value='yes' type='submit'>Continue</button>" : "<br /><small>You must use the In-Game Browser to register</small>" ).
	"</form></div>";
	
}

?>