<pre><?php

require '_.php';

// Only allow directors and above to sell loot



echo "<br /><br />";

$stuffs = $DB->qa("
SELECT loot.*, data.lootID, SUM(data.amount) AS total, phase.*, op.timeEnd, op.timePayed FROM `phaseData` AS data
INNER JOIN phases AS phase ON (phase.phaseID = data.phaseID)
INNER JOIN operations AS op ON (phase.opID = op.opID)
INNER JOIN invTypes AS loot ON (data.lootID = loot.typeID)

WHERE timeEnd IS NOT NULL
GROUP BY `lootID` ORDER BY `lootID`", array());

foreach ($stuffs AS $stuff) {
	echo "<input type='text' size='15' /> (".$stuff['total'].") ".$stuff['typeName']."<br />";
}




?>