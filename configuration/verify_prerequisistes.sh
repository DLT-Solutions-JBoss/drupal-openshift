#!/bin/bash
# Verify Demo Projects with DEMONAME prefix.
if [ "$#" -ne 2 ]; then
    echo "Usage:"
    echo "  $0 DEMONAME USER"
    exit 1
fi

DEMONAME=$1
USER=$2

echo "Verifying Demo Projects for DEMONAME=${DEMONAME} and USER=${USER}"
oc project ${DEMONAME}-jenkins
oc project ${DEMONAME}-dev
oc project ${DEMONAME}-test
oc project ${DEMONAME}-prod

oc get templates -n openshift |grep Drupal8-Sample-App-mysql
