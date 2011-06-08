<?php

require '_.php';



$form = new Form('newForm', 'Test Form', $_SERVER['PHP_SELF'], 'post');

$form->add_text('text1', 'TEST', $default = '', $size = 20, $max_length = 40, $min_length = 3, $description = false);

$form->add_fieldset(1532, 'Text Array');
$form->add_fieldset(5568, 'Checkbox Array');
$form->add_fieldset(4471, 'Numeric Array');

for ($i=0, $l = 2; $i<$l; $i++) {
	$form->add_text("name", 'Text '.($i+1), null, 20, 15, 3, 'Description here', "t$i", 1532);
}

	$form->add_numeric("num", 'num 1', null, 20, 15, 0, 0,    90,   'min 0, max 90',                     "n1", 4471);
	$form->add_numeric("num", 'num 2', null, 20, 15, 0, null, 150,  'no min, max 150',                   "n2", 4471);
	$form->add_numeric("num", 'num 3', null, 20, 15, 0, -50,  0,    'min -50, max 0',                    "n3", 4471);
	$form->add_numeric("num", 'num 4', null, 20, 15, 0, null, null, 'non numeric test, no min/max',      "n4", 4471);
	$form->add_numeric("num", 'num 5', null, 20, 15, 1, null, null, 'non numeric test, must contain at least 1 char', "n5", 4471);

for ($i=0, $l = 3; $i<$l; $i++) {
	$form->add_checkbox("checks", 'Check '.($i+1), null, null, "c$i", 5568);
}
$form->add_submit('wwf', 'Submit');

if ($form->check_fields_exist()) {
	$form->update_values_from_post();

	if ($form->validate()) {
		echo "Yay! Proper form! Now we can do stuff... <br />"; } 
}
echo "</pre>";
$form->display_form();
echo "<pre>";
//var_dump($form);

//var_dump($Page);


?>