#!/bin/sh
FROM=1.7.0
TO=1.8.0

# After installing new Java and I want to switch to the new Java (without
# uninstalling the old one).
# HACK


cd /etc/alternatives
ls -lA | grep "$FROM" | awk '{ print $9 }' | while read f ; do
	OPTION="$( echo | alternatives --config $f | grep "$TO" | awk '{ print $2 }' )"
	if ! [ -z "$OPTION" ] ; then
		echo "$OPTION" | alternatives --config $f
	fi
done
