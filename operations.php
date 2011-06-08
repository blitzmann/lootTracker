<?php

require '_.php';

if (isset($_SESSION['opID'])) {
	
	if(filter_has_var(INPUT_POST, 'phaseMembers')) {
		if ($User->charID != $DB->q1("SELECT charID FROM `operations` WHERE opID = ?", array($_SESSION['opID']))){
			$Page->errorfooter('You are not the owner of this operation; you cannot add or remove members from it'); }

		$members = filter_var_array($_POST['members'], FILTER_VALIDATE_INT);
		// if members  = 0, do something...
		$DB->e("INSERT INTO `groups` (`groupID`, `opID`) VALUES (?, ?)", null, $_SESSION['opID']);
		$groupID = $DB->lastInsertID();
		foreach ($members AS $id) {
			$DB->e("INSERT INTO `participants` (`charID`, `groupID`) VALUES (?, ?)", $id, $groupID);
		}
	}

	// Pull all the groups for the selected OP
	$groups = $DB->qa("
		SELECT *, GROUP_CONCAT(name ORDER BY name SEPARATOR ', ') AS members 
		FROM `groups` NATURAL JOIN `participants` NATURAL JOIN `memberList`
		WHERE opID = ?
		GROUP BY groupID", array($_SESSION['opID']));

	if (count($groups) === 0) {
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
			echo "<tr>";
				foreach ($row AS $values) {
					echo "
					<td>
						<label for='".$values['charID']."'>
						<input type='checkbox' name='members[]' id='".$values['charID']."' value='".$values['charID']."' ".
						(array_key_exists($values['name'], $selected) ? "checked='checked' " : null)
						."/> ".$values['name']."</label>
					</td>\n";
				}				
			echo "</tr>";
		}
		
	
		echo "
			</table>
			<button type='submit' name='phaseMembers'>Submit</button>
			</form>";
			
		$endCheck = $DB->q("SELECT * FROM `operations` NATURAL JOIN groups NATURAL JOIN lootData WHERE opID = ?", $_SESSION['opID']); 
		
		if ($endCheck === false) {
			echo "OP WILL BE DELETED UPON END DUE TO NO REAL DATA"; }

		echo "
			<h2>End Operation</h2>
			<p>Click this button if your operation is completed. This will shut off the ability to add [and or edit (future)] more loot to the operation and it's groups, and make it eligable for loot sales. If you have not added any groups, or submited any loot, the operation will be deleted from the system.</p>
			<form action='".$_SERVER['PHP_SELF']."' method='post'>
			<button type='submit' name='endOp' value='".$_SESSION['opID']."'>End</button>
			</form>
			<h2>Transfer Ownership</h2>
			<p>You can transfer ownership to another corpmate if you are planning on leaving the operation for whatever reason. This will allow the new owner to continue adding and remiving members from the operation and generally give them control of the operation.</p>
			DROP DOWN LIST OF MEMBERS HERE OR SOMETHING I DUNNO
		";
		
	}
		echo "
		<h2>Salvager</h2>
		<p>Are you this operation's salvager? Do you control the loot? Are you ready to stow it away in the corp hanger? If so, please head over to the <a href='lootRecord.php'>Loot Record</a> page whenever you're ready to drop it off.</p>";

}
else{

?>

<div style='width: 25%;float: right;'>
<h2>Create Operation</h2>
<form action='<?php echo $_SERVER['PHP_SELF']; ?>' method='post'>
Title: <input type='text' name='title' /><br />
Description:
<textarea name='description'></textarea>
<button type='submit' name='submitOperation' />Go!</button></form>
</div>
<h2>Current Operations:</h2>
<table>
<form action='<?php echo $_SERVER['PHP_SELF']; ?>' method='post'>
<?php

	$operations = $DB->qa("SELECT op.*, member.name FROM `operations` AS op INNER JOIN memberList member ON (member.charID = op.charID) WHERE op.timeEnd IS NULL ", array());

	foreach ($operations AS $operation){
		echo "
		<tr>
			<td>$operation[title]</td>
			<td>$operation[description]</td>
			<td>$operation[name]</td>
			<td>".date("H:i:s", $operation['timeStart'])."</td>
			<td><button type='submit' name='selectOpID' value='".$operation['opID']."'>Select Op</button></td>
		</tr>\n";
	}
	?>
	</table></form>
<div>
<h2>Last 10 Operations:<h2>
<?php

	$last = $DB->qa("SELECT * FROM `operations` NATURAL JOIN memberList NATURAL LEFT JOIN op2sale NATURAL LEFT JOIN saleHistory ORDER BY timeStart DESC LIMIT 0, 10", array());
	echo "<table border='1'>
	<tr><th>ID</th><th>Title</th><th>Owner</th><th>Ended?</th><th>Sold?</th><th>Payed?</th></tr>";
	
	foreach($last AS $op) {
		echo "
		<tr>
			<td>$op[opID]</td>
			<td>$op[title]</td>
			<td>$op[name]</td>
			<td>".($op['timeEnd'] !== null ? 'Yes' : 'No')."</td>
			<td>".($op['saleTime'] !== null ? 'Yes' : 'No')."</td>
			<td>".($op['payedTime'] !== null ? 'Yes' : 'No')."</td>";
	}
	?>
</table>

<?php
} ?>