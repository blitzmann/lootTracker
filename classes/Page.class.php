<?php
/**
 * Page class, author Ant P <ant@specialops.ath.cx>.
 * Probably from one of his SO packages (file had no meta info)
 * Handles the headers, footers, error output, and misc page functions
 * Modified by Ryan H.
 */
class Page {
	public $title;
	public $nav = array();
	public $headers = false;	
	public $active = './';
	
	public $errors = array();

	function __construct() {
		set_error_handler(array($this, 'error_handler')); 
	}
	
	public function header() {
		global $DB, $User;
		
		//date_default_timezone_set("America/New_York"); // Sets timezone for site, so that I don't get confused with the time functions and whatnot
		header('Content-Type: text/html; charset=UTF-8'); // Charsets and types. Nothing fancy.
		echo 
		"<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"". 
		"	\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n".
		"<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en' dir='ltr'>\n".
		"<head>\n".
		"	<title>".( $this->title ? $this->title.' : '.SITE_NAME : SITE_NAME )."</title>\n".
		"	<link rel='stylesheet' type='text/css' href='style/style.css' />\n".
		"</head>\n\n".
		"<body id='lt-".basename($_SERVER['SCRIPT_NAME'], '.php')."'>\n\n".
		"	<div id='header'>\n";
		$this->userbox(); // Prints out the userbox
		echo
		"		<h1>".SITE_NAME."</h1>\n".
		//"		<h2>".SUB_HEAD."</h2>\n".
	//	"	<p class='hide'><a href='#content'>Skip to content</a></p>\n".
		"	<p class='note'><strong>Notice:</strong> Don't use the back button. I dunno why, but it'll cause a 400 error.</p>".
		"	</div>\n";
		"	<div id='menu-wrapper'>";
		
		// Start NAVIGATION
		echo
		"		<ul id='menu'>\n";
		
		foreach ( $this->nav as $title => $url ) {
			echo 
		"			<li><a href='$url'".(basename($_SERVER['REQUEST_URI']) == $url ? " class='current'" : null).">$title</a></li>\n"; }
		
		echo	
		"		</ul>\n".
		"	</div>\n\n\n".
		"<div id='wrap'>\n\n".
		"<div id='content'>\n";

		$this->headers = true;
	} 
	
	// finish this off...
	public function date_format($date, $time_zone = '-5', $casual_date = true) {
		$date_format = array('m/d/Y', 'h:i:s A');
		   	
		// edit the date so it matches the given time zone
		$date += ($time_zone * 3600);
    	
		// daylight savings time
		if (date('I', $date) == '1') $date += 3600;
    	
		$current_date = gmdate($date_format[0], $date);
				
		// date formats that aren't passed as arrays can be let go immediately
		// otherwise you can replace the first key in the array with "Today, " or "Yesterday, "
		if (gettype($date_format) != 'array') {
			return gmdate($date_format, $date);
		} elseif ($casual_date && $current_date == gmdate($date_format[0], time() + $time_zone)) {
			return 'Today, '. gmdate($date_format[1], $date);
		} elseif ($casual_date && $current_date == gmdate($date_format[0], time() + $time_zone - 84600)) {
			return 'Yesterday, '. gmdate($date_format[1], $date);
		} else {
			return gmdate(implode(', ', $date_format), $date);
		}
	}
	
	// This just tells the script that if the headers haven't been sent out yet, then send them already!
	private function finish_headers() {
		if ( $this->headers === false ) {
			$this->header();
		}
	}

	public function userbox() {
		global $User, $DB;
		
		if ($User->is_logged_in == true) {
		
			echo
			"<div id='userbox'>".
			"	<span>Wecome <strong>$User->name</strong>!</span>\n".
			"	<form id='form_logout' action='index.php' method='post'>\n".
			"	<fieldset><legend>Session:</legend>\n".
			"		<button type='submit' name='logout'>Log Out</button>\n";
			if (isset($_SESSION['opID'])){
				echo
			"		<button type='submit' name='removeOp' value='".$_SESSION['opID']."'>Unset Op</button>\n"; }
			echo
			"	</fieldset>\n".
			"	</form>\n".
			"</div>\n";
		}
		else {
			echo
				"<div style='text-align: center;'><h2>Welcome ".$_SERVER['HTTP_EVE_CHARNAME']."!</h2>".
				"<p>Please login:</p><p>$loginMessage</p><form action='".$_SERVER['PHP_SELF']."' method='post'>".
				"<input style='text-align:center;' type='text' name='pass' onfocus='if(this.value == \"Password\") { this.value = \"\"; }' value='Password' /><br /><br /><button value ='yes' name='login' type='submit'>Login</button><button name='register' value='yes' type='submit'>Register</button></form></div>";
			$this->headers = true;
			$this->footer();
		}
	}
	
	// Errors
	public function errorfooter($message, $header = 'access')
	{
		if ( !headers_sent() ) {
			switch ( $header ) {
				case 'access':
				case 'login': 
					header('HTTP/1.1 403 Forbidden');
					break;
				default:
					header('HTTP/1.1 400 Bad Request');
			}
		}
		
		$this->finish_headers();
		echo '<p class="error">'.$message.'</p>';
		$this->footer();
	}
	
	public function footer()
	{
		$this->finish_headers();
		
		if (defined('DEVELOPER') && count($this->errors)) {
			echo "<h2>Errors on page:</h2>\n<dl>\n";
			foreach ( $this->errors as $e ) {
				vprintf('<dt>File %s at line <var>%s</var></dt><dd>errcode <var>%s</var>: <tt>%s</tt></dd>', $e);
			}
			echo "</dl>\n";
		}
		
		echo
		"</div></div></div><div id='footer'>";
		$end = microtime(true);

		printf('<p>Footer goes here, lulz. | %01.002fms (%0.5fs)', ($end - START) * 1000, $end - START);
		echo "</div>\n\n</body>\n</html>";
		
		exit;
	}
	
	public function error_handler($number, $string, $file, $line)
	{
		// If errors are being returned as unencoded text then try to fix them before they fuck the XHTML -- Not working
		//if ( ! ini_get('html_errors') ) {
		//	$string = new parse($string);
		//	$string = $string->getOutput();
		//}
		$this->errors[] = array($file, $line, $number, $string);
	}
}
?>
