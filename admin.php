<?php

/*
 * Copyright (C) 2011 Ryan Holmes
 * <http://www.gnu.org/licenses/agpl.html>
 */
 
$title = 'Admin';

require '_.php';

if (!$User->hasRole('director')){
	$Page->errorfooter('Sorry, but only Directors can access this page.'); }
	
	
	$members = $DB->qa("SELECT * FROM `memberList` ORDER BY name ASC", array());
		
	echo "
	<div id='editPrecision'>
		<h2>Floating Point Precision</h2>
		<p>Use this to determine how many decimal places to keep whent he database does calculations for percentages and whatnot. 0 = integers only, 1 = one decimal place, etc.</p>
			<form action='".$_SERVER['PHP_SELF']."' method='post'>
				<label>Truncate to <input type='text' size='1' maxlength='1' value='0' name='precision' /> decimal places</label>
				<button type='submit' name='submitPrec'>Set Precision</button> 
			</form>
		</div>
		<div id='resetChar'>
			<h2>Reset Member</h2>
			<p>If a member has forgotten their password, or you want them to re-register for whatever reason, use this.</p>
			<form action='".$_SERVER['PHP_SELF']."' method='post'>
				<select name='transfer' size='1'>\n<option value=''>Select Character</option>";
			
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
