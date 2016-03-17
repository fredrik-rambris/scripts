#!/bin/sh

# Script I run on virtual machines before making them into a template

echo "Deleting logs"
find /var/log -type f -delete
echo "Deleting sshd keys"
rm -f /etc/ssh/*key*
echo "Deleting bash_history"
rm -f /root/.bash_history
history -c
echo "Clearing hostname"
> /etc/hostname
echo "Clearing DHCP leases"
rm -f /var/lib/dhclient/dhclient.leases
echo "Clearing NeworkManager cache"
rm -f /var/lib/NetworkManager/*
echo "Clearing YUM cache"
yum clean all
