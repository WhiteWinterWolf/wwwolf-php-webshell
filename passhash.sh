#! /bin/sh
################################################################################
### passhash.sh BEGIN
################################################################################
# Copyright 2017 WhiteWinterWolf (https://www.whitewinterwolf.com)
# https://www.whitewinterwolf.com/tags/php-webshell/
#
# This file is part of wwwolf-php-webshell.
#
# wwwolf-php-webshell is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
################################################################################

usage="Generate a password hash for the WhiteWinterWolf's PHP webshell.

Usage: ./passhash.sh [-hnq] [-p prompt]

Options:
 
  -h    Show usage information.
  -n    Input the password only once, don't check for typos.
  -p prompt
        Set the password prompt used by the webshell.
        This prompt is used to salt the hash.
  -q    Quiet output: display only the hash and error messages.
"

################################################################################
# Global variables
################################################################################

# If 'yes', asks to input the password twice to avoid typos.
check='yes'

# The password prompt, also used to salt the hash.
prompt="WhiteWinterWolf's PHP webshell: "

# If 'yes', don't print informational messages.
quiet='no'


################################################################################
# Functions
################################################################################

h() {
	# Use printf to strip trailing EOL.
	printf '%s' "$( openssl sha256 -hmac "$prompt" -hex | cut -d ' ' -f 2 )"
}

xprintf() {
	if [ "$quiet" != 'yes' ]
	then
		printf "$@"
	fi
}


################################################################################
# Parse parameters
################################################################################

OPTIND=1
while getopts 'hnp:q' opt
do
	case "$opt" in
		'h')
			echo "$usage"
			exit 0
			;;
		'n') check='no' ;;
		'p') prompt=$OPTARG ;;
		'q') quiet='yes' ;;
		*)
			echo "Invalid parameter: '$opt'." >&2
			exit 2
			;;
	esac
done


################################################################################
# Main
################################################################################

xprintf "WhiteWinterWolf's PHP webshell password hash generator\n\n"

if ! type 'openssl' >/dev/null 2>&1
then
	echo "ERROR: The 'openssl' command has not been found." >&2
	exit 1
fi

IFS='
'
trap 'stty echo 2>/dev/null' EXIT INT QUIT TERM
stty -echo 2>/dev/null

xprintf 'Input the new password: '
read -r pass
xprintf '\n'

if [ "$check" = 'yes' ]
then
	xprintf 'Type the new password again: '
	read -r pass2
	xprintf '\n'

	if [ "$pass" != "$pass2" ]
	then
		echo "ERROR: The two paswords mismatch." >&2
		exit 1
	fi
fi

hash=$( printf '%s' "$pass" | h | h )

if [ "$quiet" = 'yes' ]
then
	printf '%s\n' "$hash"
else
	printf '\n%s\n' "Update 'webshell.php' with the following values:"
	printf '$passprompt = "%s";\n' \
		"$( printf '%s' "$prompt" | sed 's/[\"]/\\&/g' )"
	printf '$passhash = "%s";\n' "$hash"
fi

################################################################################
### passhash.sh END
################################################################################
