<?php

require '_.php';

$lastGroup=array(); //put this somewhere more senseable

if(isset($_POST['endOp'])){
	// check if user is owner
	print_R($_POST);
	$DB->ea("UPDATE `operations` SET timeEnd = ? WHERE opID = ?", array(time(), $_POST['endOp'])); }

if (isset($_SESSION['opID'])) {
	// check to see if ID even exists
	if(isset($_POST['phaseMembers'])) {
		$members = filter_var_array($_POST['members'], FILTER_VALIDATE_INT);
		// insert PHASE
		$DB->ea("INSERT INTO `phases` (`phaseID`, `opID`) VALUES (?, ?)", array(null, $_SESSION['opID']));
		$phaseID = $DB->lastInsertID();
		foreach ($members AS $id){
			$DB->ea("INSERT INTO `participants` (`charID`, `phaseID`) VALUES (?, ?)", array($id, $phaseID));
		}
	}
	
	$phases = $DB->qa("SELECT * FROM `phases` WHERE opID = ? ORDER BY phaseID ASC", array($_SESSION['opID']));
	
	if(count($phases) === 0) {
		echo "You must add the participants of this opp:";
	}
	else {
		foreach ($phases AS $phase) {
				$parts[] = $DB->qa("SELECT part.*, mem.* FROM `participants` AS part INNER JOIN `memberList` AS mem ON (part.charID = mem.charID) WHERE phaseID = ?", array($phase['phaseID'])); 
		}


	echo "<h2>Groups</h2>";

	for ($i=0, $l = count($parts); $i<$l; $i++) {
		$names = array();
		for ($b=0, $m = count($parts[$i]); $b < $m; ++$b) {
			$names[] = $parts[$i][$b]['name']; }
		echo "Group ".($i+1).": ".implode($names, ', ')."<br/ >";
	}

foreach (array_pop($parts) AS $member)	{
	$lastGroup[] = $member['charID']; }

	}
echo "
<form method='post'>
<table width='100%'>";

	$members = $DB->qa("SELECT * FROM `memberList` ORDER BY name ASC", array());

	foreach (array_chunk($members, 4) AS $row){
		echo "<tr>";
			foreach ($row AS $values) {
				echo "<td><label for='".$values['charID']."'><input type='checkbox' name='members[]' id='".$values['charID']."' value='".$values['charID']."' ".(in_array($values['charID'], $lastGroup) ? "checked='checked' " : null)."/> ".$values['name']."</label></td>\n";
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