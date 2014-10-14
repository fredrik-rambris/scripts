#!/bin/sh

# Uniform logging functions
# by Fredrik Rambris
#
# Use this function to make all log messages look the same way.
#
# Begin with sourcing this file like so (dot included):
# . /etc/scripts/log_funcs.sh
#
# Log your start and finish with
# log_start
# log_finished
#
# Log messages with
# log "Message"
# or add a severity of your message
# log "No rows returned" warning
#
# Log and exit with
# log_abort "Could not open file"
#
# Set LOG_SCREEN to mininum severity to output to stdout (default warning)
# Set LOG_FILE to minimum severity to output to syslog (default info)
# Eg. when developing, only log to screen.
# LOG_SCREEN=debug
# LOG_FILE=panic
#
# Note you do this all in your script


# PRG should be initialized to the scripts name. If it's not we
# try to guess it here.
[ -z "$PRG" ] && PRG="$( basename $0 )"

# Minimum levels to log to screen and syslog (file)
[ -z "$LOG_SCREEN" ] && LOG_SCREEN="warning"
[ -z "$LOG_FILE" ] && LOG_FILE="info"

# Returns true if $2 is more severe or equals $1
function check_severity()
{
	[ $# -lt 2 ] && return 1

	declare LEVELS[7]
	LEVELS[1]="debug"
	LEVELS[2]="info"
	LEVELS[3]="notice"
	LEVELS[4]="warning" # Warnings, not errors
	LEVELS[5]="err" # Recoverable errors
	LEVELS[6]="crit" # Always exit after a crit or more
	LEVELS[7]="alert" # Big trouble
	LEVELS[8]="panic" # System in a state of chaos

	for level in $( seq 1 ${#LEVELS[*]} ) ; do
		[ "$1" == ${LEVELS[level]} ] && reference=$level
		[ "$2" == ${LEVELS[level]} ] && check=$level
	done

	[ $check -ge $reference ] && return 0
	return 1
}

# Usage log_message "message" [severity]
function log_message()
{
	SEVERITY="$2"

	case "$2" in
		panic|emerg)
			SEVERITY="panic"
			SEVER="Emergency"
		;;
		alert)
			SEVERITY="alert"
			SEVER="Alert"
		;;
		crit)
			SEVERITY="crit"
			SEVER="Critical"
		;;
		err|error)
			SEVERITY="err"
			SEVER="Error"
		;;
		warning|warn)
			SEVERITY="warning"
			SEVER="Warning"
		;;
		notice)
			SEVERITY="notice"
			SEVER="Notice"
		;;
		info)
			SEVERITY="info"
			SEVER="Info"
		;;
		debug)
			SEVERITY="debug"
			SEVER="Debug"
		;;
		*)
		SEVERITY="info"
		SEVER="Info";
	esac
	MESSAGE="$SEVER: $1"
	check_severity $LOG_FILE $SEVERITY && logger -t"$PRG[$$]" -p user.$SEVERITY -- "$MESSAGE"
	check_severity $LOG_SCREEN $SEVERITY && echo "$(LANG=C date +"%b %d %T") $MESSAGE"
}

# Log script started
function log_start()
{
	SCRIPT_START=$( date +%s )
	log_message "Started" notice
}

# Log script finished
function log_finished()
{
	diff=$(( $( date +%s ) - $SCRIPT_START ))
	time=$( date -d "1970-01-01 + $diff seconds" +"%kh %Mm %Ss" )
	log_message "Finished $time" notice
}

# Die with a message
function log_abort()
{
	[ -z "$1" ] && { log_message "Aborted" notice; exit 1; }
	log_message "$1" crit
	[ ! -z "$2" ] && exit $2
	exit 1
}
