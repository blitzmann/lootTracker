<?php

/*
 * Copyright (C) 2011 Ryan Holmes
 * <http://www.gnu.org/licenses/agpl.html>
 */
 
require 'DB.php';

$DB = new DB(parse_ini_file('/home/ryan/www/private/db.ini'));

$apiDetails = parse_ini_file('../../../private/apiDetails.ini'); // path to protected file (outside of web root) containing director API key
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

$members = new SimpleXMLElement(get_data($memberURL));

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