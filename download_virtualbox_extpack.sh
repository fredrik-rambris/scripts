#!/bin/bash
# I got tired of having to manually download the extpack every update so I
# made it into a script

URL="$( wget -q -O- 'https://www.virtualbox.org/wiki/Downloads' | grep Oracle_VM_VirtualBox | head -n1 | awk '{ url=substr($0,match($0,"href=")+6); print substr(url,0,match(url,"\"")-1); }' )"

if ! [ -z "$URL" ] ; then
	wget -q -O- "$URL" | sudo tar -xzv -C "/usr/lib/virtualbox/ExtensionPacks/Oracle_VM_VirtualBox_Extension_Pack/"
fi
