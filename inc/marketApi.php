<?php

/*
 * Copyright (C) 2011 Ryan Holmes
 * <http://www.gnu.org/licenses/agpl.html>
 */
 
$cfg = parse_ini_file('../inc/config.ini'); 
require 'lootTypes.php';

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
$data = get_data($url, $fields);
if (!$data) { exit; }
$market = new SimpleXMLElement($data);

$DB->query('TRUNCATE TABLE `marketData`');

foreach ($market->marketstat->type AS $type){
	$DB->ea("INSERT INTO `marketData` (`typeID`, `medianBuy`) VALUES (?, ?)", array((int)$type['id'], (float)$type->buy->median));
}

echo "Done.";
?> 