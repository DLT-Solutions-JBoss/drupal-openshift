#!/bin/bash
# Delete all Demo Projects
if [ "$#" -ne 1 ]; then
    echo "Usage:"
    echo "  $0 DEMONAME"
    exit 1
fi

DEMONAME=$1
echo "Removing all Demo Projects for DEMONAME=$DEMONAME"
oc delete project $DEMONAME-jenkins
oc delete project $DEMONAME-dev
oc delete project $DEMONAME-test
oc delete project $DEMONAME-prod
