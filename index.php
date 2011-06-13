<?php

/*
 * Copyright (C) 2011 Ryan Holmes
 * <http://www.gnu.org/licenses/agpl.html>
 */
 
require '_.php';


/*
$form = new Form('newForm', 'Test Form', $_SERVER['PHP_SELF'], 'post');

$form->add_text('text1', 'TEST', $default = '', $size = 20, $max_length = 40, $min_length = 3, $description = false);

$form->add_fieldset(1532, 'Text Array');
$form->add_fieldset(5568, 'Checkbox Array');
$form->add_fieldset(4471, 'Numeric Array');
$form->add_fieldset(4474, 'nth-child test');


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

for ($i=0, $l = 32; $i<$l; $i++) {
	$form->add_checkbox("checks", 'Check '.($i+1), null, null, "c$i", 4474);
}


$form->add_submit('wwf', 'Submit');

if ($form->check_fields_exist()) {
	$form->update_values_from_post();

	if ($form->validate()) {
		echo "Yay! Proper form! Now we can do stuff... <br />"; } 
}

$form->display_form();


*/
?>
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean vitae magna et nunc blandit lacinia tempus in tortor. Donec mattis mollis eleifend. Etiam congue vestibulum nisl, eget luctus ante sollicitudin vel. Fusce non aliquam magna. Vestibulum suscipit orci massa. Suspendisse in lorem mauris. Nulla eu nibh tincidunt diam fringilla molestie. Mauris gravida, metus ac fringilla dapibus, massa elit vulputate tortor, in elementum eros risus at nulla. Mauris blandit ultrices nulla vel blandit. Sed metus ante, ullamcorper a egestas non, euismod ac massa. Sed sed iaculis tortor. Morbi ultricies, mauris sed scelerisque porta, quam nunc auctor sapien, id molestie mauris felis eget justo. Aenean ultrices elementum elit, malesuada porta quam rhoncus at. Pellentesque tristique cursus arcu. Aenean vehicula, urna nec molestie pellentesque, diam mauris congue nisl, sed malesuada ligula neque eu neque.</p>

<p>Nam mattis orci dui. Integer aliquet, odio vel cursus dictum, velit urna consectetur sem, eget porttitor nisi erat non nulla. Morbi elit nulla, faucibus sit amet varius ac, rutrum sed erat. Aenean id dolor ante, nec aliquam felis. Quisque fermentum congue aliquam. In diam nibh, lacinia in commodo id, varius vitae quam. Aenean scelerisque tortor sed dolor porta fringilla. In est dolor, aliquet bibendum mattis a, porttitor sit amet ligula. Nunc accumsan, ante et tempor fringilla, felis dolor ultrices urna, nec eleifend enim velit quis dolor. Aenean adipiscing scelerisque nisi, sit amet ultricies sapien mollis eu. Sed molestie ante non tortor dapibus viverra. Suspendisse tincidunt varius tristique.</p>

<p>Vivamus tellus metus, vestibulum vitae imperdiet a, pharetra quis lacus. Quisque pulvinar auctor diam, eget cursus turpis cursus sed. Sed et est arcu. Proin vel turpis non quam convallis fringilla quis quis leo. Suspendisse sit amet interdum dui. Quisque ipsum massa, vestibulum eget mattis sit amet, rutrum ac purus. Integer a ipsum non elit malesuada pretium. Donec dapibus, velit id cursus mattis, nibh augue porttitor ante, eget porta massa lectus a ante. Etiam malesuada tincidunt turpis, vestibulum faucibus erat posuere vel. Nullam blandit leo eget lacus iaculis quis faucibus diam vulputate. Sed ullamcorper mauris at nunc sodales fringilla.</p>

<?php        
$Page->footer();
//echo "<pre>";

//var_dump($form);

//var_dump($Page);


?>