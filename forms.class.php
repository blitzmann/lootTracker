<?php

/*
	Form class. Author unknown.
	Was digging around through some old scripts and projects, found this.
	I hate writing forms, so this does it for me, complete with validation
	functions and whatnot. \o/
	
	If ANYONE knows where this came from, let me know <ryan.xgamer99@gmail.com>
	so that I can give credit.
*/

define('checkbox', 1);
define('radio', 2);
define('select', 3);
define('text', 4);
define('password', 5);
define('file', 6);
define('textarea', 7);
define('submit', 8);

$tabindex = 1;

class Form2 {

	var $fields = array();
	var $label = '';
	var $conditions = array();
	var $errors = array();

	function Form($id, $label, $action, $method) {
		$this->id     = $id;
		$this->label  = $label;
		$this->action = $action;
		$this->method = $method;
	}

	function add_checkbox($id, $label, $description, $default = true) {
		$this->fields[$id] = array('label' => $label, 'value' => $default, 'description' => $description, 'type' => checkbox);
	}

	function add_radio($id, $label, $options, $default = '', $description = false) { // no soap
		$this->fields[$id] = array('label' => $label, 'options' => $options, 'value' => $default, 'description' => $description, 'type' => radio);
	}

	function add_select($id, $label, $options, $default = '', $size = 1, $description = false) {
		$this->fields[$id] = array('label' => $label, 'options' => $options, 'value' => $default, 'size' => $size, 'description' => $description, 'type' => select);
	}

	function add_text($id, $label, $default = '', $size = 20, $max_length = 40, $min_length = 0, $description = false) {
		$this->fields[$id] = array('label' => $label, 'value' => $default, 'size' => $size, 'max_length' => $max_length, 'min_length' => $min_length, 'description' => $description, 'type' => text);
	}

	function add_password($id, $label, $size = 20, $max_length = 40, $min_length = 6, $description = false) {
		$this->fields[$id] = array('label' => $label, 'value' => '', 'size' => $size, 'max_length' => $max_length, 'min_length' => $min_length, 'description' => $description, 'type' => password);
	}

	function add_file($id, $label, $max_size, $description = false) {
		$this->fields[$id] = array('label' => $label, 'max_size' => $max_size, 'description' => $description, 'type' => file);
	}

	function add_textarea($id, $label, $default = '', $width = 50, $height = 5, $max_length = 1024, $min_length = 0, $max_lines = 0, $description = false) {
		$this->fields[$id] = array('label' => $label, 'value' => $default, 'width' => $width, 'height' => $height, 'max_length' => $max_length, 'min_length' => $min_length, 'description' => $description, 'type' => textarea);
	}
	
	function add_submit($id, $label) {
		$this->fields[$id] = array('label' => $label, 'type' => submit);
	}

	function get_field_value($id) {
		if (!isset($this->fields[$id]['value']))
			return null;
		else
			return $this->fields[$id]['value'];
	}

	function update_values_from_post() {
		foreach ($this->fields as $id => $field_info) {
			if ($field_info['type'] == checkbox)
				$this->fields[$id]['value'] = isset($_POST[$id]);
			elseif ($field_info['type'] != file)
				$this->fields[$id]['value'] = $_POST[$id];
		}
	}

	function check_fields_exist() {
		foreach ($this->fields as $id => $field_info) {
			if (!isset($_POST[$id]) && !in_array($field_info['type'], array(checkbox, file))) {
				return false;
			}
        }
		return true;
	}

	function display_form() {

		global $tabindex;

		// Displays errors
		if (!empty($this->errors)) {

			echo '<p class="error">There are errors in your form!<br />';
			foreach ($this->errors as $id => $errors) {
				echo implode('<br />', $errors). '<br />';
			}
			echo '</p>'. nn;

		}

		$class = 'even';

		echo 
			"<form action='$this->action' method='$this->method'>". nn.
			"<fieldset id='$this->id'>". nn.
			"<legend>$this->label</legend>". nn.
			'<dl>'. nn;

		foreach ($this->fields as $id => $field_info) {

			if ($field_info['type'] == text) {

				echo 
					"  <dt class='". ($class = $class == 'even' ? 'odd' : 'even'). (isset($this->errors[$id]) ? ' error' : ''). "'>";

				printf(
					'<label for=\'%1$s_\'>%2$s</label></dt>'. n.
					"  <dd class='$class'>". n .
					'    <input id=\'%1$s_\' name=\'%1$s\' size=\'%3$d\' maxlength=\'%4$d\' type=\'text\' value=\'%5$s\' tabindex=\''. $tabindex .'\' />%6$s'. n.
					'  </dd>'. nn,
					$id, $field_info['label'], $field_info['size'], $field_info['max_length'], htmlspecialchars($field_info['value']),
					$field_info['description'] ? (n .
					"    <small>$field_info[description]</small>") : ''
				);

			} elseif ($field_info['type'] == checkbox) {

				echo 
					"  <dt class='checkbox ". ($class = $class == 'even' ? 'odd' : 'even'). (isset($this->errors[$id]) ? ' error' : ''). "'>". n;

				printf(
					'    <label for=\'%1$s_\'><input name=\'%1$s\' id=\'%1$s_\' type=\'checkbox\' value=\'1\'%3$s tabindex=\''. $tabindex .'\' /> %2$s</label>'. n.
					'  </dt>'. n,
					$id, htmlspecialchars($field_info['label']),
					$field_info['value'] ? " checked='checked'" : ''
				);

				echo
					"  <dd class='checkbox $class'>". n;

				printf(
					'    <small>%1$s</small>'. n.
					'  </dd>'. nn,
					$field_info['description']
				);

	
	
			} elseif ($field_info['type'] == radio) { // no soap

				echo 
					"  <dt class='radio ". ($class = $class == 'even' ? 'odd' : 'even'). (isset($this->errors[$id]) ? ' error' : ''). "'>$field_info[label]</dt>". n.
					"  <dd class='radio $class'>". n;
	            
				foreach ($field_info['options'] as $value => $label) {
					echo 
						"    <label for='{$id}_{$value}_'><input ";
					if ($value == $field_info['value']) echo "checked='checked' ";
					echo 
						"id='{$id}_{$value}_' name='$id' type='radio' value='$value' /> $label</label>". n;
				}

				if ($field_info['description'])
					echo 
						"    <br><small>$field_info[description]</small>". n;

				echo '  </dd>'. nn;

			} elseif ($field_info['type'] == select) {

				printf(
					'  <dt class=\''. ($class = $class == 'even' ? 'odd' : 'even'). (isset($this->errors[$id]) ? ' error' : ''). '\'><label for=\'%1$s_\'>%2$s</label></dt>'. n.
					"  <dd class='$class'>". n.
					'    <select id=\'%1$s_\' name=\'%1$s\' size=\'%3$d\' tabindex=\''. $tabindex .'\'>'. n,
					$id, $field_info['label'], $field_info['size']
				);

				foreach ($field_info['options'] as $value => $label) {
					echo 
						'      <option ';
					if ($value == $field_info['value']) echo "selected='selected' ";
					echo "value='$value'>$label</option>". n;
				}

				echo 
					'    </select>'. n;

				if ($field_info['description'])
					echo "    <small>$field_info[description]</small>". n;

				echo '  </dd>'. nn;

			} elseif ($field_info['type'] == password) {

				printf(
					'  <dt class=\''. ($class = $class == 'even' ? 'odd' : 'even'). (isset($this->errors[$id]) ? ' error' : ''). '\'><label for=\'%1$s_\'>%2$s</label></dt>'. n.
					"  <dd class='$class'>". n.
					'    <input id=\'%1$s_\' name=\'%1$s\' size=\'%3$d\' maxlength=\'%4$d\' type=\'password\' tabindex=\''. $tabindex .'\' />%5$s'. n.
					'  </dd>'. nn,
					$id, $field_info['label'], $field_info['size'], $field_info['max_length'],
					$field_info['description'] ? (n .
					"    <small>$field_info[description]</small>") : ''
				);

			} elseif ($field_info['type'] == file) {

				printf(
					'  <dt class=\''. ($class = $class == 'even' ? 'odd' : 'even'). (isset($this->errors[$id]) ? ' error' : ''). '\'><label for=\'%1$s_\'>%2$s</label></dt>'. n.
					"  <dd class='$class'>". n.
					'    <input id=\'%1$s_\' name=\'%1$s\' type=\'file\' />'. n.
					'    <input name=\'MAX_FILE_SIZE\' type=\'hidden\' value=\'%3$d\' />%4$s'. n.
					'  </dd>'. nn,
					$id, $field_info['label'], $field_info['max_size'],
					$field_info['description'] ? ('<small>'. $field_info['description']. '</small>') : ''
				);

			} elseif ($field_info['type'] == textarea) {

				printf(
					'  <dt class=\''. ($class = $class == 'even' ? 'odd' : 'even'). (isset($this->errors[$id]) ? ' error' : ''). '\'><label for=\'%1$s_\'>%2$s</label></dt>'. n.
					"  <dd class='$class'>". n.
					'    <textarea cols=\'%3$d\' id=\'%1$s_\' name=\'%1$s\' rows=\'%4$s\'>%5$s</textarea>%6$s'. n.
					'  </dd>'. nn,
					$id, $field_info['label'], $field_info['width'], $field_info['height'], htmlspecialchars($field_info['value']),
					$field_info['description'] ? (n. 
					"    <br /><small>$field_info[description]</small>") : ''
				);

			} elseif ($field_info['type'] == submit) {

				printf(
					'<p class=\'submit\'><button id=\'%1$s_\' name=\'%1$s\' type=\'submit\'>%2$s</button></p>'. nn,
					$id, $field_info['label']
				);

			}

			$tabindex++;
		}

		echo 
			'</dl>'. nn.
			'</fieldset>'. nn.
			'</form>'. nn;

	}

	function add_condition($error_text, $condition, $field = false) {
		$this->conditions[$field][$error_text] = $condition;
	}

	function validate() {

		$fields = array_merge($this->fields, array(false => array('type' => -1)));

		foreach ($fields as $id => $field_info) {

			if (isset($this->conditions[$id]))
				foreach ($this->conditions[$id] as $title => $condition)
					if ($condition)
						$this->errors[$id][] = $title;

			if ($field_info['type'] == text || $field_info['type'] == password || $field_info['type'] == textarea) {

				$value_length = strlen($field_info['value']);
				if ($value_length > $field_info['max_length']) {
					$this->errors[$id][] = 'Please limit your '. strtolower($field_info['label']). ' to a maximum of '. $field_info['max_length']. ' characters.';
				} elseif ($value_length < $field_info['min_length']) {
					if ($field_info['min_length'] == 1)
						$this->errors[$id][] = 'Sorry, but your '. strtolower($field_info['label']). ' cannot be blank.';
					else
						$this->errors[$id][] = 'Please limit your '. strtolower($field_info['label']). ' to a minimum of '. $field_info['min_length']. ' characters.';
				}

				if ($field_info['type'] == textarea && $field_info['max_lines'])
					if ((substr_count($field_info['value'], n) + 1) > $field_info['max_lines'])
						$this->errors[$id][] = 'Please limit '. strtolower($field_info['label']). ' to a maximum of '. $field_info['max_lines']. ' lines.';

			} elseif ($field_info['type'] == radio || $field_info['type'] == select) {
				if (!in_array($field_info['value'], $field_info['options']))
					$this->errors[false][] = 'Invalid form data.';
			}

		}

		if (!empty($this->errors)) return false;
		return true;

	}

	function debug_display_variable($varname) {
		echo '<xmp>';
		var_dump($this->$varname);
		echo '</xmp>';
	}

}

?>