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
	public $active = '/';
	
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
		"	<link rel='stylesheet' type='text/css' href='style/css.css' />\n".
		"</head>\n\n".
		"<body id='te-".basename($_SERVER['SCRIPT_NAME'], '.php')."'>\n\n".
		"<div id='container'>\n\n". // Needed for more fluidity... stuffs...
		"	<div id='header'>\n".
		"		<h1>".SITE_NAME."</h1>\n".
		//"		<h2>".SUB_HEAD."</h2>\n".
		"	</div>\n";
		$this->userbox(); // Prints out the userbox
		echo
	//	"	<p class='hide'><a href='#content'>Skip to content</a></p>\n".
		"<h3>FOR THE LOVE OF GOD</h3><p>Don't use the back button. I dunno why, but it'll cause a 400 error. Shouldn't have any negative effects tho, just refresh.</p><div id='menu'>";
		
		// Start NAVIGATION
		echo
		"	<ul>\n";
		
		foreach ( $this->nav as $title => $url ) {
			echo "\t\t<li><a href='$url'".( $this->active == $url ? " class='active'" : null).">$title</a></li>\n"; }
		
		echo	
		"	</ul></div>\n".
		"\n\n".
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
		
		echo
		"	<div id='userbox'>\n";
		
		if ($User->is_logged_in == false) {
			echo
				"<div style='text-align: center;'><h2>Welcome ".$_SERVER['HTTP_EVE_CHARNAME']."!</h2>".
				"<p>Please login:</p><p>$loginMessage</p><form action='".$_SERVER['PHP_SELF']."' method='post'>".
				"<input style='text-align:center;' type='text' name='pass' onfocus='if(this.value == \"Password\") { this.value = \"\"; }' value='Password' /><br /><br /><button value ='yes' name='login' type='submit'>Login</button><button name='register' value='yes' type='submit'>Register</button></form></div>";
			$this->headers = true;
		
			$this->footer();
		}
		else {
			echo
			"	Wecome <b>$User->name</b>!<br />\n".
			"	<form id='form_logout' action='./' method='post'>\n".
			"	<fieldset><legend>Session:</legend>\n".
			"		<button type='submit' name='logout' onclick='return confirm(\"Really logout?\");'>Log Out</button>\n".
			"	</fieldset>\n".
			"	</form>\n";
			
			if(isset($_SESSION['opID'])) {
				$opData = $DB->q("
					SELECT operations.opID, operations.title, operations.timeStart, memberList.name 
					FROM `operations` NATURAL JOIN memberList WHERE opID = ?"
					, array($_SESSION['opID']));

				echo 
					"<div style='float: right; margin-right: 3em;'>
					<h2>Current Op:</h2>
					id: ".$opData['opID']."<br />Title: ".$opData['title']."<br />Owner: ".$opData['name']."<br />
					<form action='".$_SERVER['PHP_SELF']."' method='post'><button type='submit' name='removeOp'>Leave Op</button></form>
					</div>";
			}
		}
		
		echo
		"	</div>\n";		
	}
	
	// Errors
	public function errorfooter($message, $header = 'access')
	{
		if ( !headers_sent() ) {
			switch ( $type ) {
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

		printf('<p>Footer goes here, lulz. | %.4f seconds.</p>', array_sum(explode(' ', microtime())) - START);
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
