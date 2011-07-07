<?php

/*
 * Copyright (C) 2011 Ryan Holmes
 * <http://www.gnu.org/licenses/agpl.html>
 */

require '_.php';

$Page->header('Record Loot');

if(!isset($_SESSION['opID'])) {
	$Page->errorfooter('No operation has been selected. Please go to the operations page and select an operation before recording your loot.', false); }

echo "<h2>Submit Loot</h2>\n";
	
	
echo "
<script language='javascript' type='text/javascript'>
$(document).ready(function () {

	$('fieldset:not(#fs-groupsSet)').hide();
	
	$('#groupID_').change(function(){
		if (this.value != '') {
			$('fieldset:not(#fs-groupsSet)').show();	}
		else {
			$('fieldset:not(#fs-groupsSet)').hide();	}
	});

	$('dl:not(#fs-groupsSet dl)').hide();
	
	$('legend:not(#fs-groupsSet legend)').prepend('<span class=\'toggle\'>+</span> ').toggle(function() {
		$(this).children().text('-');
		$(this).next('dl').slideDown('fast');
	}, function() {
		$(this).children().text('+');
		$(this).next('dl').slideUp('fast');
	});
});
</script>	
";

$groups = $DB->qa("
	SELECT *, GROUP_CONCAT(name ORDER BY name SEPARATOR ', ') AS members 
	FROM `groups` NATURAL JOIN `participants` NATURAL JOIN `memberList`
	WHERE opID = ?
	GROUP BY groupID", array($_SESSION['opID']));
$owner = $DB->q1("
	SELECT name
	FROM `operations` NATURAL JOIN memberList
	WHERE opID = ?", array($_SESSION['opID']));
	
if(count($groups) === 0) {
	$Page->errorfooter('<strong>Error:</strong> No groups have been added to this operation just yet. Please speak to the owner of the operation about this: <strong>'.$owner.'</strong>', false); }

$lootDisplay = array();
$options = array();

foreach ($lootTypes AS $name => $sql) {
	$results = $DB->qa($sql." ORDER BY typeName ASC", array());

	foreach ($results AS $value){
		$lootDisplay[$name][$value['typeID']] = $value; }
}

$form = new Form('lootsubmit', 'Submit Loot', $_SERVER['PHP_SELF'], 'post');

$options[null] = "-- Select Group --";
// Go through the groups, building the options array
for ($i=0, $l = count($groups); $i<$l; $i++) {
	$options[$groups[$i]['groupID']] = "Group ".($i+1).": ".$groups[$i]['members']; }
	
$form->add_fieldset('groupsSet', 'Select Group');
$form->add_select('groupID', 'Group:', $options, null, null, null, false, 'groupsSet');

foreach ($lootDisplay AS $group => $items){
	$form->add_fieldset($group, $group);
	foreach ($items AS $id => $attr) {
		$form->add_numeric('item',  "<img style='height: 100%; vertical-align: middle;' src='http://evefiles.capsuleer.de/types/".$id."_32.png' /> ".$attr['typeName'], null, 5, 6, 0, 0, null, 'qty', $id, $group); 
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
		"<div class='success'><strong>Success:</strong> Loot submited</div>".
		"<p>You may now drop off the loot at any station or POS in which the corp owns a hanger. The specific hanger is called <strong>CEO Secret Stash</strong>. Note that you may not be able to view items in this hanger, but everyone is able to add items to it. Just drag n' drop.</p>
		<p>Alternatively, you can private contract all the loot directly to a corp director and they should be able to pick it up at their leasure. Make sure the contract is in high or lowsec (no nullsec).</p>";
		
		$Page->footer();
	}
	catch (InvalidInput $e) {
		
		$error = '<p class="error"><span>There were errors processing your request</span>';
		foreach ($form->errors as $id => $errors) {
			$error .= implode('<br />', $errors). '<br />';
		}
		$error .= '</p>'."\n\n";

	}
}

echo "
	<p>On this page, you'll be able to record the loot that your operation has collected thus far. If you are in an operation and your cargo is getting full, record the loot here before you drop it off at the corp hanger. You should record the loot here everytime someone joins/leaves the operation (if the correct group isn't listed in the drop down, poke the operation owner: <strong>".$owner."</strong>).</p>
	<p>To record loot, select the proper group that worked to obtain the loot you currently have. Then type in the quantity of each loot. If a loot hasn't dropped, simply leave the field blank. The loot is organized in the order you should see in your cargo or hanger if you were to sort by type. <strong>Double check that the values you provide are correct; there currently is no confirmation screen.</strong></p><hr />";

if (isset($error)) { echo $error; }
$form->display_form();
$Page->footer();