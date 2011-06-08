<?php

$title = 'Record Loot';

require '_.php';

if(!isset($_SESSION['opID'])) {
	$Page->errorfooter('No operation has been selected. Please go to the operations page and select an operation before recording your loot.', false); }


$groups = $DB->qa("
	SELECT *, GROUP_CONCAT(name ORDER BY name SEPARATOR ', ') AS members 
	FROM `groups` NATURAL JOIN `participants` NATURAL JOIN `memberList`
	WHERE opID = ?
	GROUP BY groupID", array($_SESSION['opID']));

if(count($groups) === 0) {
	$Page->errorfooter('No groups have been added to this operation just yet. Please speak to the owner of the operation about this.', false); }

$lootDisplay = array();
$options = array();

foreach ($lootTypes AS $name => $sql) {
	$results = $DB->qa($sql." ORDER BY typeName ASC", array());

	foreach ($results AS $value){
		$lootDisplay[$name][$value['typeID']] = $value; }
}

$form = new Form('lootsubmit', 'Submit Loot', $_SERVER['PHP_SELF'], 'post');

// Go through the groups, building the options array
for ($i=0, $l = count($groups); $i<$l; $i++) {
	$options[$groups[$i]['groupID']] = "Group ".($i+1).": ".$groups[$i]['members']; }
	
$form->add_fieldset('groupsSet', 'Group Select');
$form->add_select('groupID', 'Group:', $options, null, null, null, false, 'groupsSet');

foreach ($lootDisplay AS $group => $items){
	$form->add_fieldset($group, $group);
	foreach ($items AS $id => $attr) {
		$form->add_numeric('item',  "<img style='height: 100%; vertical-align: middle;' src='http://evefiles.capsuleer.de/types/".$id."_32.png' /> ".$attr['typeName'], null, 5, 6, 0, 0, null, false, $id, $group); 
	}
}
$form->add_submit('lootSubmit', 'Submit');

if ($form->check_fields_exist()) {
	$form->update_values_from_post();

	try {
		if (!$form->validate()) {
			throw new InvalidInput(); }

		$items = array_filter(filter_var_array($_POST['item'], FILTER_SANITIZE_NUMBER_INT));
		
		if (count($items) === 0) {
			$form->errors[false][] = "No data submitted.";
			throw new InvalidInput(); 
		}

		// Everything's fine, input	
		foreach ($items AS $id => $amount){
			$DB->e("
				INSERT INTO `lootData` (`groupID`, `typeID`, `amount`) 
				VALUES (?, ?, ?)", 
				filter_input(INPUT_POST, 'groupID', FILTER_SANITIZE_NUMBER_INT), 
				$id, 
				$amount);
		}

		echo 
		"<h2>Loot submited</h2>".
		"<p>Please make sure you put the loot in the proper corp hanger.</p>";
		
		$Page->footer();
	}
	catch (InvalidInput $e) {
		
		echo '<p class="error">There are errors in your form!<br />';
		foreach ($form->errors as $id => $errors) {
			echo implode('<br />', $errors). '<br />';
		}
		echo '</p>'."\n\n";

	}
}

$form->display_form();
$Page->footer();