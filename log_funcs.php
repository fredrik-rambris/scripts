<?php
/*
 * Uniform logging functions
 * by Fredrik Rambris
 *
 * Use these functions to make all log messages look the same way.
 *
 * Log your start and finish with
 * log_start();
 * log_finish();
 *
 * Log messages with
 * log_message("Message");
 * or add a severity of your message
 * log_message("No rows returned", LOG_WARNING);
 *
 * Log and exit with
 * log_abort("Could not open file");
 *
 * Define LOG_SCREEN to mininum severity to output to stdout (default LOG_WARNING)
 * Define LOG_FILE to minimum severity to output to syslog (default LOG_INFO)
 */
function log_start($show=true)
{
	// Compatibility with logger_funcs
	if( !defined("LOG_SCREEN") && defined("LOGGER_SCREEN") ) define("LOG_SCREEN", LOGGER_SCREEN);
	if( !defined("LOG_FILE") && defined("LOGGER_FILE") ) define("LOG_FILE", LOGGER_FILE);

	if( !isset($GLOBALS['PRG']) ) $GLOBALS['PRG']=basename($_SERVER["argv"][0]);

	$GLOBALS['SCRIPT_START']=time();

	openlog($GLOBALS['PRG'], LOG_PID, LOG_USER);
	if($show) log_message("Started", LOG_NOTICE);
}

function log_message($message, $severity=LOG_INFO)
{
	$log_screen=defined("LOG_SCREEN")?LOG_SCREEN:LOG_WARNING;
	$log_file=defined("LOG_FILE")?LOG_FILE:LOG_INFO;

	$classes=Array("Emergency","Alert","Critical","Error","Warning","Notice","Info","Debug");
	$message=$classes[$severity] . ": " . trim($message);

	if( $severity<=$log_file ) syslog($severity, $message);
	if( $severity<=$log_screen ) echo date("M d H:i:s") . " $message\n";
}

function log_finished($show=true)
{
	if($show)
	{
		$tm=gmdate("G\h i\m s\s", time() - $GLOBALS['SCRIPT_START'] );
		log_message("Finished $tm",LOG_NOTICE);
	}
	closelog();
}

function log_abort($message,$code=1)
{
	log_message($message, LOG_CRIT);
	exit($code);
}

/* Custom error handler that displays all PHP errors in the same way as our custom messages
 * Use like this  set_error_handler("log_errorhandler");
*/
function log_errorhandler($errno, $errstr, $errfile=false, $errline=false, $errcontext=false)
{
	switch($errno)
	{
		case E_WARNING:
		case E_USER_WARNING:
			$priority=LOG_WARNING;
			break;
		case E_NOTICE:
		case E_USER_NOTICE:
			$priority=LOG_NOTICE;
			break;
		case E_USER_ERROR:
		case E_ERROR:
			$priority=LOG_ERR;
			break;
		default:
			$priority=LOG_ERR;
	}
	log_message($errstr,$priority);
}
?>
