#!/bin/bash
# Create Demo Projects with DEMONAME prefix.
# When FROM_JENKINS=true then project ownership is set to USER
# Set FROM_JENKINS=false for testing outside of the Grading Jenkins
if [ "$#" -ne 3 ]; then
    echo "Usage:"
    echo "  $0 DEMONAME USER FROM_JENKINS"
    exit 1
fi

DEMONAME=$1
USER=$2
FROM_JENKINS=$3

echo "Creating Demo Projects for DEMONAME=${DEMONAME} and USER=${USER}"
oc new-project ${DEMONAME}-jenkins    --display-name="${DEMONAME} Adv Demo Jenkins"
oc new-project ${DEMONAME}-dev  --display-name="${DEMONAME} Adv Demo Development"
oc new-project ${DEMONAME}-test  --display-name="${DEMONAME} Adv Demo Test"
oc new-project ${DEMONAME}-prod --display-name="${DEMONAME} Adv Demo Production"

if [ "$FROM_JENKINS" = "true" ]; then
  oc policy add-role-to-user admin ${USER} -n ${DEMONAME}-jenkins
  oc policy add-role-to-user admin ${USER} -n ${DEMONAME}-dev
  oc policy add-role-to-user admin ${USER} -n ${DEMONAME}-test
  oc policy add-role-to-user admin ${USER} -n ${DEMONAME}-prod

  oc annotate namespace ${DEMONAME}-jenkins    openshift.io/requester=${USER} --overwrite
  oc annotate namespace ${DEMONAME}-dev  openshift.io/requester=${USER} --overwrite
  oc annotate namespace ${DEMONAME}-test  openshift.io/requester=${USER} --overwrite
  oc annotate namespace ${DEMONAME}-prod openshift.io/requester=${USER} --overwrite
fi
