<?php

/*
 *	Here is where you can list what you want to show on the loot record page.
 *	Key is the title for the group, Value is the SQL query used to fetch 
 *	the items. lootTracker expects all of them to come from the invTypes
 *	static DB dump
 */

$lootTypes = array(
	'Datacores'                     => 'SELECT * FROM invTypes WHERE groupID = 333 AND marketGroupID IS NOT NULL',
	'Decryptors'                    => 'SELECT * FROM invTypes WHERE groupID = 979',
	'Intact/Malfunctioning/Wrecked' => 'SELECT * FROM invTypes WHERE groupID = 971 OR groupID =	990 OR groupID = 991 OR groupID = 992 OR groupID = 993 OR groupID = 997',
	'Gas'                           => 'SELECT * FROM invTypes WHERE groupID = 711 AND marketGroupID = 1145',
	'Salvage'                       => 'SELECT * FROM invTypes WHERE groupID = 966',
	'Loot'                          => 'SELECT * FROM invTypes WHERE groupID = 880');
	
?>