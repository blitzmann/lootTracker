<?php

/*
 * Copyright (C) 2011 Ryan Holmes
 * <http://www.gnu.org/licenses/agpl.html>
 */
 
require '_.php';
echo "<pre>";
$types = array();

$url = 'http://api.eve-central.com/api/marketstat';
$system = 30000142;

foreach ($lootTypes AS $name => $sql) {
	$results = $DB->qa($sql." ORDER BY typeName ASC", array());

	foreach ($results AS $value){
		$types[] = $value['typeID']; }
}

$fields = implode($types, '&typeid=');
$fields = 'usesystem='.$system.'&typeid='.$fields;

function get_data($url, $post) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}



$market = new SimpleXMLElement(get_data($url, $fields));

foreach ($market->marketstat->type AS $type){
	$DB->ea("INSERT INTO `marketData` (`typeID`, `medianBuy`) VALUES (?, ?)", array((int)$type['id'], (float)$type->buy->median));
}

echo "Done.";
/*


$apiDetails = parse_ini_file('../../../private/apiDetails.ini'); // path to protected file (outside of web root) containing director API key
$memberURL = "http://api.eve-online.com/corp/MemberTracking.xml.aspx?useriD=$apiDetails[userID]&apiKey=$apiDetails[apiKey]&characterID=$apiDetails[charID]";
unset($apiDeatils);



$members = new SimpleXMLElement(get_data($memberURL));

$membersNew = array();

$DB->query('TRUNCATE TABLE `memberList`');

foreach($members->result->rowset->row AS $member) {
	$name = (string)$member['name'];
	$id = (string)$member['characterID'];
	$roles = (string)$member['roles'];
}

echo 'Member list refreshed.';
*/
?> 