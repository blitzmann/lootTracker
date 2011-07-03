<?php

/*
 * Copyright (C) 2011 Ryan Holmes
 * <http://www.gnu.org/licenses/agpl.html>
 */

require '_.php';

$Page->header('Admin Page');

if (!$User->hasRole('director')){
	$Page->errorfooter('Sorry, but only Directors can access this page.'); }
	
if(filter_has_var(INPUT_POST, 'submitPrec')) {
	$precision = (filter_var($_POST['precision'], FILTER_VALIDATE_INT) + 2);
	$sql = <<<SQL
 CREATE OR REPLACE ALGORITHM=UNDEFINED VIEW `saleView` AS select `t1`.`saleID` AS `saleID`,`groups`.`groupID` AS `groupID`,`t2`.`typeID` AS `typeID`,truncate((truncate((sum(`t2`.`amount`) / (select sum(`lootData`.`amount`) from ((`lootData` join `groups` on((`lootData`.`groupID` = `groups`.`groupID`))) join `op2sale` on((`groups`.`opID` = `op2sale`.`opID`))) where ((`op2sale`.`saleID` = `t1`.`saleID`) and (`lootData`.`typeID` = `t2`.`typeID`)) group by `lootData`.`typeID`)),?) * `saleData`.`profit`),0) AS `payout` from ((((`groups` join `op2sale` `t1` on((`groups`.`opID` = `t1`.`opID`))) join `saleHistory` on((`t1`.`saleID` = `saleHistory`.`saleID`))) join `lootData` `t2` on((`groups`.`groupID` = `t2`.`groupID`))) join `saleData` on(((`t1`.`saleID` = `saleData`.`saleID`) and (`t2`.`typeID` = `saleData`.`typeID`)))) group by `t2`.`groupID`,`t2`.`typeID`;
SQL;

	$DB->e($sql, $precision);
	echo "<div class='success'><strong>Success:</strong> Database precision updated to ".($precision-2)." decimal places.</div>";
}

if(filter_has_var(INPUT_POST, 'resetChar')) {
	$member = filter_input(INPUT_POST, 'member', FILTER_VALIDATE_INT);
	$DB->e("DELETE FROM `users` WHERE `charID` = ?", $member);
	echo "<div class='success'><strong>Success:</strong> Member reset.</div>";
}

$members = $DB->qa("SELECT * FROM `users` NATURAL JOIN `memberList` ORDER BY name ASC", array());
	
echo "
<div id='editPrecision'>
	<h2>Floating Point Precision</h2>
	<p>Use this to determine how many decimal places to keep whent he database does calculations for percentages and whatnot. 0 = integers only, 1 = one decimal place, etc.</p>
		<form action='".$_SERVER['PHP_SELF']."' method='post'>
			<label>Truncate to <input type='text' size='1' maxlength='1' value='0' name='precision' /> decimal places</label>
			<button type='submit' name='submitPrec'>Set Precision</button><br /><small>Note: Previous set value will <em>not</em> be displayed</small>
		</form>
	</div>
	<div id='resetChar'>
		<h2>Reset Member</h2>
		<p>If a member has forgotten their password, or you want them to re-register for whatever reason, select them from the drop down and hit the Reset button.</p>
		<form action='".$_SERVER['PHP_SELF']."' method='post'>
			<select name='member' size='1'>\n<option value=''>Select Character</option>";
		
	foreach ($members AS $member) {
		echo "
		<option value='".$member['charID']."'>".$member['name']."</option>"; }
	echo "
			</select>
			<button type='submit' name='resetChar'>Reset</button> 
		</form>
	</div>
	<p class='submit'></p>
";
$Page->footer();
?>
