#!/bin/bash
# Create Demo Projects with DEMONAME prefix.
# When FROM_JENKINS=true then project ownership is set to USER
# Set FROM_JENKINS=false for testing outside of the Grading Jenkins
if [ "$#" -ne 1 ]; then
    echo "Usage:"
    echo "  $0 DEMONAME"
    exit 1
fi

DEMONAME=$1

echo "getting pods from each project"

DEV_POD=$(oc get pod -n dol-dev |grep '^dol' |grep 'Running'| cut -f1 -d" ")
echo "Dev pod is ${DEV_POD}"
TEST_POD=$(oc get pod -n dol-test |grep '^dol' |grep 'Running'| cut -f1 -d" ")
echo "Test pod is ${TEST_POD}"
PROD_BLUE_POD=$(oc get pod -n dol-prod |grep '^dol' |grep 'Running'| grep blue | cut -f1 -d" ")
echo "Prod Blue pod is ${PROD_BLUE_POD}"
PROD_GREEN_POD=$(oc get pod -n dol-prod |grep '^dol' |grep 'Running'| grep green | cut -f1 -d" ")
echo "Prod Green pod is ${PROD_GREEN_POD}"

echo "issuing copy via bash script on each pod"

#oc exec dol-blue-3-5jkhx -c dol-blue -n dol-prod bash init_settings.sh

oc exec ${DEV_POD} -c ${DEMONAME} -n ${DEMONAME}-dev bash copy_config.sh
oc exec ${TEST_POD} -c ${DEMONAME} -n ${DEMONAME}-test bash copy_config.sh
oc exec ${PROD_BLUE_POD} -c ${DEMONAME}-blue -n ${DEMONAME}-prod bash copy_config.sh
oc exec ${PROD_GREEN_POD} -c ${DEMONAME}-green -n ${DEMONAME}-prod bash copy_config.sh
