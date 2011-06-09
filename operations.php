<?php

$title = 'Operation Info';

require '_.php';

if (isset($_SESSION['opID'])) {
	
	// Member submit
	if(filter_has_var(INPUT_POST, 'phaseMembers')) {
		if ($User->charID != $DB->q1("SELECT charID FROM `operations` WHERE opID = ?", array($_SESSION['opID']))){
			$Page->errorfooter('You are not the owner of this operation; you cannot add or remove members from it'); }

		$members = filter_var_array($_POST['members'], FILTER_VALIDATE_INT);
		try{
			if (!count($members)) {
				throw new InvalidInput('No data submitted.'); }
		
			$DB->e("INSERT INTO `groups` (`groupID`, `opID`) VALUES (?, ?)", null, $_SESSION['opID']);
			$groupID = $DB->lastInsertID();
			foreach ($members AS $id) {
				$DB->e("INSERT INTO `participants` (`charID`, `groupID`) VALUES (?, ?)", $id, $groupID); }
		}
		catch (InvalidInput $e) {
			echo "
				<p class='error'>".$e->getMessage()."</p>\n";
		}
	}

	// Pull all the groups for the selected OP
	$groups = $DB->qa("
		SELECT *, GROUP_CONCAT(name ORDER BY name SEPARATOR ', ') AS members 
		FROM `groups` NATURAL JOIN `participants` NATURAL JOIN `memberList`
		WHERE opID = ?
		GROUP BY groupID", array($_SESSION['opID']));

	if (!count($groups)) {
		echo "You must add the participants of this opp:"; }
	else {
		echo "<h2>Groups</h2><dl>";
		
		for ($i=0, $l = count($groups); $i<$l; $i++) {
			echo "<dt>Group ".($i+1).":</dt><dd>".$groups[$i]['members']."</dd>";
		}
		echo "</dl>";
	}
	
	if ($User->charID == $DB->q1("SELECT charID FROM `operations` WHERE opID = ?", array($_SESSION['opID']))){

		echo "<div id='members'><h2>Add/Remove Members</h2>";
	
		$lastGroup	= array_pop($groups);
		$selected   = array_flip(explode(', ', $lastGroup['members']));

		echo "
			<form action='".$_SERVER['PHP_SELF']."' method='post'>
			<table width='100%'>";
	
		$members = $DB->qa("SELECT * FROM `memberList` ORDER BY name ASC", array());
		foreach (array_chunk($members, 4) AS $row){
			echo "<tr>\n";
				foreach ($row AS $values) {
					echo "
					<td>
						<label for='".$values['charID']."'>
						<input type='checkbox' name='members[]' id='".$values['charID']."' value='".$values['charID']."' ".
						(array_key_exists($values['name'], $selected) ? "checked='checked' " : null)
						."/> ".$values['name']."</label>
					</td>\n";
				}				
			echo "</tr>\n";
		}
		
	
		echo "
			</table>
			<button type='submit' name='phaseMembers'>Submit</button>
			</form></div>";
			
		echo "
			<div id='endOp'>
				<h2>End Operation</h2>
				<p>Click this button if your operation is completed. This will shut off the ability to add [and or edit (future)] more loot to the operation and it's groups, and make it eligable for loot sales. If you have not added any groups, or submited any loot, the operation will be deleted from the system.</p>
				<form action='".$_SERVER['PHP_SELF']."' method='post'>
					<label for='confirmEnd_'><input id='confirmEnd_' type='checkbox' name='confirmEnd' value='1' /> Confirm End</label>
					<button type='submit' name='endOp' value='".$_SESSION['opID']."'>End Op</button> 
				</form>
			</div>
			<div id='transferOwnership'>
				<h2>Transfer Ownership</h2>
				<p>You can transfer ownership to another corpmate if you are planning on leaving the operation for whatever reason. This will allow the new owner to continue adding and remiving members from the operation and generally give them control of the operation.</p>
				<form action='".$_SERVER['PHP_SELF']."' method='post'>
					<select name='transfer' size='1'>\n";
				
			foreach ($members AS $member) {
				echo "
				<option value='".
						$member['charID']."'".
						($member['charID'] == $User->charID ? " selected='selected'" : null).
						">".$member['name']."</option>"; }
			echo "
					</select>
					<button type='submit' name='transferOp'>Transfer Op</button> 
				</form>
			</div>
		";
		
	}
		echo "
		<div id='salvageInfo'>
			<h2>Salvager Info</h2>
			<p>Are you this operation's salvager? Do you control the loot? Are you ready to stow it away in the corp hanger? If so, please head over to the <a href='lootRecord.php'>Loot Record</a> page whenever you're ready to drop it off.</p>
		</div>\n";

	$Page->footer();
}
else{
	$form = new Form ('createOp', "Create Operation", $_SERVER['PHP_SELF'], 'post');
	$form->add_text('title', 'Title', false, 25, 30, 5, "30 chars max");
	$form->add_textarea('description', 'Description', false, 40,  3, 4000, null, null);
	$form->add_submit('submitOp', 'Submit');

	echo "
	<div id='opCreate'>
		<span class='top'><!-- --></span>
		<h2>Create Operation</h2>\n";
		
	if ($form->check_fields_exist()) {
		$form->update_values_from_post();

		try {
			if (!$form->validate()) {
				throw new InvalidInput(); }

			$DB->e("
				INSERT INTO `operations` (`opID`, `charID`, `title`, `description`, `timeStart`) 
				VALUES (?, ?, ?, ?, ?)", 
				null, 
				$User->charID, 
				filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS), 
				filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS), 
				time()
			);
		
			$_SESSION['opID'] = $DB->lastInsertID();
	
			// I don't like putting a redirect header here.
			// look at rearraging the page's logic
			header("Location: ".$_SERVER['PHP_SELF']);
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
	echo "
			<span class='bottom'><!-- --></span>
		</div>
		<div id='crntOps'>
		<h2>Current Operations</h2>
			<table>
			<form action='".$_SERVER['PHP_SELF']."' method='post'>
				<tr><th>Title</th><th>Owner</th><th>Start</th><th>Select</th></tr>\n";

	$operations = $DB->qa("SELECT op.*, member.name FROM `operations` AS op INNER JOIN memberList member ON (member.charID = op.charID) WHERE op.timeEnd IS NULL ", array());
	foreach ($operations AS $operation){
		echo "
		<tr>
			<td>".(strlen($operation['title']) > 20 ? substr($operation['title'],0,(20 -3)).'...' : $operation['title'])."</td>
			<td>$operation[name]</td>
			<td>".date("H:i:s", $operation['timeStart'])."</td>
			<td><button type='submit' name='selectOpID' value='".$operation['opID']."'>Select</button></td>
		</tr>\n";
	}

	echo "
		</form>
		</table>
	</div>
	<div id='lastOps'>
		<h2>Last 10 Operations</h2>\n";

	$last = $DB->qa("SELECT * FROM `operations` NATURAL JOIN memberList NATURAL LEFT JOIN op2sale NATURAL LEFT JOIN saleHistory ORDER BY timeStart DESC LIMIT 0, 10", array());
	echo "
		<table>
			<tr>
				<th>ID</th><th>Title</th><th>Owner</th><th>Ended?</th><th>Sold?</th><th>Payed?</th>
			</tr>\n";
	
	foreach($last AS $op) {
		echo "
			<tr>
				<td>$op[opID]</td>
				<td>$op[title]</td>
				<td>$op[name]</td>
				<td>".($op['timeEnd'] !== null ? 'Yes' : 'No')."</td>
				<td>".($op['saleTime'] !== null ? 'Yes' : 'No')."</td>
				<td>".($op['payedTime'] !== null ? 'Yes' : 'No')."</td>
			</tr>\n";
	}
	echo "
		</table>
	</div>\n";

	$Page->footer();
}
?>