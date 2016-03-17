#!/bin/sh

# Rescan disks. I run this after adding or enlarging disks on VMs

for i in /sys/class/scsi_host/host*/scan ; do echo "- - -" >$i ; done
for i in /sys/class/scsi_disk/*/device/rescan ; do echo "1" >$i ; done
