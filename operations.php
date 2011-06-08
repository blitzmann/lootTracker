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
		echo "<h2>Groups</h2>";
		
		for ($i=0, $l = count($groups); $i<$l; $i++) {
			echo "Group ".($i+1).": ".$groups[$i]['members']."<br/ >";
		}
	}
	
	if ($User->charID == $DB->q1("SELECT charID FROM `operations` WHERE opID = ?", array($_SESSION['opID']))){

		echo "<h2>Add/Remove Members</h2>";
	
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
			</form>";
			
		echo "
			<h2>End Operation</h2>
			TODO: add confirm!!!!!!!
			<p>Click this button if your operation is completed. This will shut off the ability to add [and or edit (future)] more loot to the operation and it's groups, and make it eligable for loot sales. If you have not added any groups, or submited any loot, the operation will be deleted from the system.</p>
			<form action='".$_SERVER['PHP_SELF']."' method='post'>
			<button type='submit' name='endOp' value='".$_SESSION['opID']."' onclick='return confirm(\"Ending this operation will prevent more loot from being recorded \\n Are you sure you want to do this? \\n Make sure all the loot has already been recorded before ending the op.\");'>End</button>
			</form>
			<h2>Transfer Ownership</h2>
			<p>You can transfer ownership to another corpmate if you are planning on leaving the operation for whatever reason. This will allow the new owner to continue adding and remiving members from the operation and generally give them control of the operation.</p>
			DROP DOWN LIST OF MEMBERS HERE OR SOMETHING I DUNNO
		";
		
	}
		echo "
		<h2>Salvager</h2>
		<p>Are you this operation's salvager? Do you control the loot? Are you ready to stow it away in the corp hanger? If so, please head over to the <a href='lootRecord.php'>Loot Record</a> page whenever you're ready to drop it off.</p>";

	$Page->footer();
}
else{
	$form = new Form ('createOp', "Create Operation", $_SERVER['PHP_SELF'], 'post');
	$form->add_text('title', 'Title', false, 15, 80, 5, 'Title for your operation');
	$form->add_textarea('description', 'Description', false, 50,  5, null, null, null, 'Description for your operation');
	$form->add_submit('submitOp', 'Submit');

	echo "
	<div style='width: 50%; float: right;'>\n";
		
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
		</div>
		<h2>Current Operations:</h2>
		<table>
		<form action='".$_SERVER['PHP_SELF']."' method='post'>\n";

	$operations = $DB->qa("SELECT op.*, member.name FROM `operations` AS op INNER JOIN memberList member ON (member.charID = op.charID) WHERE op.timeEnd IS NULL ", array());
	foreach ($operations AS $operation){
		echo "
		<tr>
			<td>$operation[title]</td>
			<td>$operation[name]</td>
			<td>".date("H:i:s", $operation['timeStart'])."</td>
			<td><button type='submit' name='selectOpID' value='".$operation['opID']."'>Select</button></td>
		</tr>\n";
	}

	echo "
	</table>
	</form>
	<div style='float: clear;'>
	<h2>Last 10 Operations:</h2>\n";

	$last = $DB->qa("SELECT * FROM `operations` NATURAL JOIN memberList NATURAL LEFT JOIN op2sale NATURAL LEFT JOIN saleHistory ORDER BY timeStart DESC LIMIT 0, 10", array());
	echo "
	<table border='1'>
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
	</table></div>\n";

	$Page->footer();
}
?>