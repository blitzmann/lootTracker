<?php

/*
 * Copyright (C) 2011 Ryan Holmes
 * <http://www.gnu.org/licenses/agpl.html>
 */
 
$cfg = parse_ini_file('../inc/config.ini'); 

function __autoload($name) {
    include '../classes/'.$name.'.class.php'; }
	
try {
	$DB = new DB(parse_ini_file($cfg['db_file']));
}
catch ( PDOException $e ) {
    echo 'Database connection failed. PDOException:';
    echo $e->getMessage();
    die('=/');
}

$apiDetails = parse_ini_file($cfg['api_file']);
$memberURL = "http://api.eve-online.com/corp/MemberTracking.xml.aspx?useriD=$apiDetails[userID]&apiKey=$apiDetails[apiKey]&characterID=$apiDetails[charID]";
unset($apiDeatils);

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

$data = get_data($memberURL);

if (!$data) { exit; } // if failed (server is offline or whatever), just stop. Otherwise, Member List would be deleted and replaced with nothing.

$members = new SimpleXMLElement($data);



$membersNew = array();

$DB->query('TRUNCATE TABLE `memberList`');

foreach($members->result->rowset->row AS $member) {
	$name = (string)$member['name'];
	$id = (string)$member['characterID'];
	$roles = (string)$member['roles'];
	$DB->ea("INSERT INTO `memberList` (`charID`, `name`, `roles`) VALUES (?, ?, ?)", array($id, $name, $roles));
}

echo 'Member list refreshed.';

?> 