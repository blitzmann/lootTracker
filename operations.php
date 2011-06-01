<?php

require '_.php';

if (filter_has_var(INPUT_POST, 'endOp')){
	// check if user is owner
	$DB->e("UPDATE `operations` SET timeEnd = ? WHERE opID = ?", time(), filter_var_array($_POST['endOp'], FILTER_VALIDATE_INT)); 
}

if (isset($_SESSION['opID'])) {
	// check to see if ID even exists
	
	if(isset($_POST['phaseMembers'])) {
		$members = filter_var_array($_POST['members'], FILTER_VALIDATE_INT);

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
	
	// where to put this...
	$lastGroup	= array_pop($groups);
	$selected   = array_flip(explode(', ', $lastGroup['members']));

	echo "
		<form method='post'>
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

}
else{

?>

<div style='width: 25%;float: right;'>
<h2>Create Operation</h2>
<form method='post'>
Title: <input type='text' name='title' /><br />
Description:
<textarea name='description'></textarea>
<button type='submit' name='submitOperation' />Go!</button></form>
</div>
<h2>Current Operations:</h2>
<table>
<form method='post'>
<?php

	$blah = $DB->qa("SELECT op.*, member.name FROM `operations` AS op INNER JOIN memberList member ON (member.charID = op.ownerID) WHERE op.timeEnd IS NULL ", array());

	foreach ($blah AS $operation){
		echo "<tr><td>$operation[title]</td><td>$operation[description]</td><td>$operation[name]</td><td>".date("H:i:s", $operation['timeStart'])."</td><td><button type='submit' name='selectOpID' value='".$operation['opID']."'>Select Op</button></td><td><button type='submit' name='endOp' value='".$operation['opID']."'>End</button></td></tr>\n";
	}
	?>
	</table></form>
<div>

<?php
} ?>