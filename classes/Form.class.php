<?php

/*
	Form class. Author unknown.
	Was digging around through some old scripts and projects, found this.
	I hate writing forms, so this does it for me, complete with validation
	functions and whatnot. \o/
	
	If ANYONE knows where this came from, let me know <ryan.xgamer99@gmail.com>
	so that I can give credit.
	
	Some of the code I hacked together to add support for form arrays
	(passing things like name[] for text inputs and whatnot)
	Look more into this at a later date. Only text is supported for now
	
	Only supports name and name[], not name[][] or more
	
	I also added fieldset support. =D
	numeric
	
	TODO: add confirm() to display submitted values
*/

define('checkbox', 1);
define('radio', 2);
define('select', 3);
define('text', 4);
define('password', 5);
define('file', 6);
define('textarea', 7);
define('submit', 8);
define('fieldset', 9);
define('numeric', 10);


$tabindex = 1;

class Form {

	var $fields = array();
	var $fieldsets = array();
	var $fieldsetQueue = array();
	var $label = '';
	var $conditions = array();
	var $formerrors = array();
	var $output = null;

	function Form($id, $label, $action, $method = 'post') {
		$this->id     = $id;
		$this->label  = $label;
		$this->action = $action;
		$this->method = $method;
	}

	
	// array means that the input is part of an array. Things like check boxs default to true
	
	function addField($type, $id, $label, $default = '', $array = false, $fieldset = false) {
		return array_shift(array_shift(array_shift(func_get_args())));
		//options array???
		
	}	
	
	function add_checkbox($id, $label, $description, $default = true, $array = false, $fieldset = false) {
		$value = array('label' => $label, 'value' => $default, 'description' => $description, 'fieldset' => $fieldset, 'array' => $array, 'type' => checkbox);
		$array !== false ? $this->fields[$id][$array] = $value : $this->fields[$id] = $value;
	}

	function add_radio($id, $label, $options, $default = '', $description = false, $array = false, $fieldset = false) { // no soap
		$value = array('label' => $label, 'options' => $options, 'value' => $default, 'description' => $description, 'fieldset' => $fieldset, 'array' => $array, 'type' => radio);
		$array !== false ? $this->fields[$id][$array] = $value : $this->fields[$id] = $value;
	}

	function add_numeric($id, $label, $default = '', $size = 20, $max_length = 40, $min_length = 0, $min_value = null, $max_value = null, $description = false, $array = false, $fieldset = false) {
		$value = array('label' => $label, 'value' => $default, 'size' => $size, 'max_length' => $max_length, 'min_length' => $min_length, 'min_value' => $min_value, 'max_value' => $max_value, 'description' => $description, 'fieldset' => $fieldset, 'array' => $array, 'type' => numeric);
		$array !== false ? $this->fields[$id][$array] = $value : $this->fields[$id] = $value;
	}
	
	function add_select($id, $label, $options, $default = '', $size = 1, $description = false, $array = false, $fieldset = false) {
		$value = array('label' => $label, 'options' => $options, 'value' => $default, 'size' => $size, 'description' => $description, 'fieldset' => $fieldset, 'array' => $array, 'type' => select);
		$array !== false ? $this->fields[$id][$array] = $value : $this->fields[$id] = $value;
	}

	function add_text($id, $label, $default = '', $size = 20, $max_length = 40, $min_length = 0, $description = false, $array = false, $fieldset = false) {
		$value = array('label' => $label, 'value' => $default, 'size' => $size, 'max_length' => $max_length, 'min_length' => $min_length, 'description' => $description, 'fieldset' => $fieldset, 'array' => $array, 'type' => text);
		$array !== false ? $this->fields[$id][$array] = $value : $this->fields[$id] = $value;
	}

	function add_password($id, $label, $size = 20, $max_length = 40, $min_length = 6, $description = false, $array = false, $fieldset = false) {
		$value = array('label' => $label, 'value' => '', 'size' => $size, 'max_length' => $max_length, 'min_length' => $min_length, 'description' => $description, 'fieldset' => $fieldset, 'array' => $array, 'type' => password);
		$array !== false ? $this->fields[$id][$array] = $value : $this->fields[$id] = $value;
	}

	function add_file($id, $label, $max_size, $description = false, $array = false, $fieldset = false) {
		$value = array('label' => $label, 'max_size' => $max_size, 'description' => $description, 'fieldset' => $fieldset, 'array' => $array, 'type' => file);
		$array !== false ? $this->fields[$id][$array] = $value : $this->fields[$id] = $value;
	}

	function add_textarea($id, $label, $default = '', $width = 50, $height = 5, $max_length = 1024, $min_length = 0, $max_lines = 0, $description = false, $array = false, $fieldset = false) {
		$value = array('label' => $label, 'value' => $default, 'width' => $width, 'height' => $height, 'max_length' => $max_length, 'min_length' => $min_length, 'description' => $description, 'fieldset' => $fieldset, 'array' => $array, 'type' => textarea);
		$array !== false ? $this->fields[$id][$array] = $value : $this->fields[$id] = $value;
	}
	
	function add_submit($id, $label, $array = false, $fieldset = false) {
		$value = array('label' => $label, 'fieldset' => $fieldset, 'array' => $array, 'type' => submit);
		$array !== false ? $this->fields[$id][$array] = $value : $this->fields[$id] = $value;
	}

	function add_fieldset($id, $label, $array = false) {
		$this->fields[$id] = array('label' => $label, 'type' => fieldset, 'fieldset' => false); // set fieldset to false because it IS a fieldset
	}


	function get_field_value($id) {
	
		if (!isset($this->fields[$id]['value']))
			return null;
		else
			return $this->fields[$id]['value'];
	}
	
	function update_values_from_post() {

		$params = func_get_args();
		assert('count($params) <= 2');
		if (count($params) === 0){
			$fields = $this->fields; }
		else {
			$fields = $params[0];
			$parent = $params[1]; }

		foreach ($fields as $id => $field_info) {
			if (!isset($field_info['type'])) {
				self::update_values_from_post($field_info, $id); }
			else {
				if ($field_info['type'] != file) {
					if (isset($parent)) {
						$this->fields[$parent][$id]['value'] = ($field_info['type'] == checkbox ? isset($_POST[$parent][$id]) : $_POST[$parent][$id]); }
					else {
						$this->fields[$id]['value'] = ($field_info['type'] == checkbox ? isset($_POST[$id]) : $_POST[$id]); }
				}
			}
		}

	}

	// this may or may not be completely broken. =/
	function check_fields_exist() {
		$params = func_get_args();
		assert('count($params) <= 1');

		if (count($params) === 0){
			$fields = $this->fields; }
		else {
			$fields = $params[0]; }

		foreach ($fields as $id => $field_info) {
			
			if (!isset($field_info['type'])) {
				self::check_fields_exist($field_info); break;}
			
			// check if the fields have been set, unless it's a fieldset or part of an array (may or may not be set)
			if (!isset($_POST[$id]) && (isset($field_info['array']) && !$field_info['array']) && $field_info['type'] !== fieldset) {
				return false;
			}
        }
		return true;
	}
	
	function display_form() {

		global $tabindex;

		$output = '';

		$class = 'even';

		echo "
		<form id='$this->id' action='$this->action' method='$this->method'>
			<dl>\n\n";

		self::walk($this->fields);
		
		foreach ($this->fieldsets AS $id => $label) {
			echo "
				<fieldset id='fs-$id'><legend>".$label."</legend>\n";
			foreach ($this->fieldsetQueue[$id] AS $field) {
				echo $field;
			}
			echo "
				</fieldset>\n";
		}

		echo $this->output.'
			</dl>
		</form>';

	}

	function walk($fields, $parent = null) {
		global $tabindex, $class;	

		foreach ($fields as $id => $field_info) {

			if (!isset($field_info['type'])) {
				self::walk($field_info, $id); }
			else {
				if ($field_info['fieldset'] !== false){
					$this->fieldsetQueue[$field_info['fieldset']][] = self::doOutput($id, $field_info, $parent); }
				else {
					$this->output .= self::doOutput($id, $field_info, $parent); 
				}
			}
			$tabindex++;
		}
	}
	
	function doOutput($id, $field_info, $parent = null){
		global $tabindex, $class;

		if ($field_info['type'] == fieldset) {// ad field set label to array
			$this->fieldsets[$id] = $field_info['label'];
			return;
		}
		else if ($field_info['type'] == text || $field_info['type'] == numeric) {
			$output =  
				"<dt class='". ($class = $class == 'even' ? 'odd' : 'even'). (isset($this->errors[$id]) ? ' error' : ''). "'>";
        
			$output .= sprintf(
				'<label for=\'%1$s_\'>%2$s</label></dt>'."\n".
				"  <dd class='$class'>".'<input id=\'%1$s_\' name=\'%1$s\' size=\'%3$d\' maxlength=\'%4$d\' type=\'text\' value=\'%5$s\' tabindex=\''. $tabindex .'\' />%6$s</dd>'."\n",
				($parent !== null ? $parent."[$id]" : $id), 
				$field_info['label'], $field_info['size'], $field_info['max_length'], htmlspecialchars($field_info['value']),
				$field_info['description'] ? ("\n".
				"	<small>$field_info[description]</small>") : ''
			);
			return $output;
			
		} elseif ($field_info['type'] == checkbox) {
        
			$output = 
				"  <dt class='checkbox ". ($class = $class == 'even' ? 'odd' : 'even'). (isset($this->errors[$id]) ? ' error' : ''). "'>\n";
        
			$output .= sprintf(
				'    <label for=\'%1$s_\'><input name=\'%1$s\' id=\'%1$s_\' type=\'checkbox\' value=\'1\'%3$s tabindex=\''. $tabindex .'\' /> %2$s</label>'."\n".
				'  </dt>'."\n",
				($parent !== null ? $parent."[$id]" : $id), 
				htmlspecialchars($field_info['label']),
				$field_info['value'] ? " checked='checked'" : ''
			);
        
			$output .=
				"  <dd class='checkbox $class'>\n";
        
			$output .= sprintf(
				'    <small>%1$s</small>'."\n".
				'  </dd>'."\n\n",
				$field_info['description']
			);
		
			return $output;
        
		} elseif ($field_info['type'] == radio) {
        
			$output = 
				"  <dt class='radio ". ($class = $class == 'even' ? 'odd' : 'even'). (isset($this->errors[$id]) ? ' error' : ''). "'>$field_info[label]</dt>\n".
				"  <dd class='radio $class'>\n";
	           
			foreach ($field_info['options'] as $value => $label) {
				$output .= 
					"    <label for='{$id}_{$value}_'><input ".
					($value == $field_info['value'] ? "checked='checked' " : null).
					"id='{$id}_{$value}_' name='$id' type='radio' value='$value' /> $label</label>\n";
			}
        
			if ($field_info['description'])
				$output .= 
					"    <br><small>$field_info[description]</small>\n";
        
			$output .= '  </dd>'."\n\n";
        
			return $output;
        
		} elseif ($field_info['type'] == select) {
			$output = sprintf(
				'  <dt class=\''. ($class = $class == 'even' ? 'odd' : 'even'). (isset($this->errors[$id]) ? ' error' : ''). '\'><label for=\'%1$s_\'>%2$s</label></dt>'."\n".
				"  <dd class='$class'>\n".
				'    <select id=\'%1$s_\' name=\'%1$s\' size=\'%3$d\' tabindex=\''. $tabindex .'\'>'."\n",
				$id, $field_info['label'], $field_info['size']
			);
        
			foreach ($field_info['options'] as $value => $label) {
				$output .= 
					'      <option '.
				($value == $field_info['value'] ? "selected='selected' " : null).
				"value='$value'>$label</option>\n";
			}
        
			$output .= 
				'    </select>'."\n";
        
			if ($field_info['description'])
				$output .=  "    <small>$field_info[description]</small>\n";
        
			$output .=  '  </dd>'."\n\n";
        
			return $output;
			
		} elseif ($field_info['type'] == password) {
        
			$output = sprintf(
				'  <dt class=\''. ($class = $class == 'even' ? 'odd' : 'even'). (isset($this->errors[$id]) ? ' error' : ''). '\'><label for=\'%1$s_\'>%2$s</label></dt>'."\n".
				"  <dd class='$class'>\n".
				'    <input id=\'%1$s_\' name=\'%1$s\' size=\'%3$d\' maxlength=\'%4$d\' type=\'password\' tabindex=\''. $tabindex .'\' />%5$s'."\n".
				'  </dd>'."\n\n",
				($parent !== null ? $parent."[$id]" : $id),
				$field_info['label'], $field_info['size'], $field_info['max_length'],
				$field_info['description'] ? ("\n".
				"    <small>$field_info[description]</small>") : ''
			);
        
			return $output;
			
		} elseif ($field_info['type'] == file) {
        
			$output = sprintf(
				'  <dt class=\''. ($class = $class == 'even' ? 'odd' : 'even'). (isset($this->errors[$id]) ? ' error' : ''). '\'><label for=\'%1$s_\'>%2$s</label></dt>'."\n".
				"  <dd class='$class'>\n".
				'    <input id=\'%1$s_\' name=\'%1$s\' type=\'file\' />'."\n".
				'    <input name=\'MAX_FILE_SIZE\' type=\'hidden\' value=\'%3$d\' />%4$s'."\n".
				'  </dd>'."\n\n",
				($parent !== null ? $parent."[$id]" : $id),
				$field_info['label'], $field_info['max_size'],
				$field_info['description'] ? ('<small>'. $field_info['description']. '</small>') : ''
			);
        
			return $output;
			
		} elseif ($field_info['type'] == textarea) {
        
			$output = sprintf(
				'  <dt class=\''. ($class = $class == 'even' ? 'odd' : 'even'). (isset($this->errors[$id]) ? ' error' : ''). '\'><label for=\'%1$s_\'>%2$s</label></dt>'."\n".
				"  <dd class='$class'>\n".
				'    <textarea cols=\'%3$d\' id=\'%1$s_\' name=\'%1$s\' rows=\'%4$s\'>%5$s</textarea>%6$s'."\n".
				'  </dd>'."\n\n",
				($parent !== null ? $parent."[$id]" : $id),
				$field_info['label'], $field_info['width'], $field_info['height'], htmlspecialchars($field_info['value']),
				$field_info['description'] ? ( 
				"\n    <br /><small>$field_info[description]</small>") : ''
			);
        
			return $output;
			
		} elseif ($field_info['type'] == submit) {
        
			$output = sprintf(
				'<p class=\'submit\'><button id=\'%1$s_\' name=\'%1$s\' type=\'submit\'>%2$s</button></p>'."\n\n",
				$id, $field_info['label']
			);
        
			return $output;
			
		}
	}

	function add_condition($error_text, $condition, $field = false) {
		$this->conditions[$field][$error_text] = $condition;
	}

	function validate() {

		$params = func_get_args();
		assert('count($params) <= 2');
		if (count($params) === 0){
			$fields = array_merge($this->fields, array(false => array('type' => -1))); }
		else {
			$fields = array_merge($params[0], array(false => array('type' => -1)));
			$parent = $params[1]; }

		foreach ($fields as $id => $field_info) {
		
			if (!isset($field_info['type'])) { // should we add a break; here also??
				self::validate($field_info, $id); }
				
			if (isset($this->conditions[$id]))
				foreach ($this->conditions[$id] as $title => $condition)
					if ($condition)
						$this->errors[$id][] = $title;

			if ($field_info['type'] == text || $field_info['type'] == password || $field_info['type'] == textarea || $field_info['type'] == numeric) {
				$value_length = strlen($field_info['value']);
				if ($value_length > $field_info['max_length']) {
					$this->errors[$id][] = 'Please limit <strong>'. $field_info['label']. '</strong> to a maximum of '. $field_info['max_length']. ' characters.';
				} elseif ($value_length < $field_info['min_length']) {
					if ($value_length == 0)
						$this->errors[$id][] = 'Sorry, but <strong>'. $field_info['label']. '</strong> cannot be blank.';
					else
						$this->errors[$id][] = 'Please limit <strong>'. $field_info['label']. '</strong> to a minimum of '. $field_info['min_length']. ' characters.';
				}

				if ($field_info['type'] == numeric) {
					if ($field_info['value'] !== '' && self::int($field_info['value']) === false) {
						$this->errors[$id][] = 'Sorry, but <strong>'.$field_info['label']. '</strong> has to be a numeric value.'; }
					elseif ($field_info['min_value'] !== null && $field_info['value'] < $field_info['min_value']) {
						$this->errors[$id][] = 'Sorry, but <strong>'.$field_info['label']. '</strong> cannot be less than '.$field_info['min_value'].'.'; }
					elseif ($field_info['max_value'] !== null && $field_info['value'] > $field_info['max_value']) {
						$this->errors[$id][] = 'Sorry, but <strong>'.$field_info['label']. '</strong> cannot be more than '.$field_info['max_value'].'.'; }
				}

				if ($field_info['type'] == textarea && $field_info['max_lines'])
					if ((substr_count($field_info['value'], "\n") + 1) > $field_info['max_lines'])
						$this->errors[$id][] = 'Please limit <strong>'. $field_info['label']. '</strong> to a maximum of '. $field_info['max_lines']. ' lines.';

			} elseif ($field_info['type'] == radio || $field_info['type'] == select) {
				if (!array_key_exists($field_info['value'], $field_info['options']))
					$this->errors[false][] = 'Invalid form data.';
			}

		}
		
		if (!empty($this->errors)) return false;
		return true;

	}

	static function int($int){
		if(is_numeric($int) === true && ((int)$int == $int || (float)$int == $int)){
			return true; }
		
		return false;
	}
	
	function debug_display_variable($varname) {
		echo '<xmp>';
		var_dump($this->$varname);
		echo '</xmp>';
	}

}

?>