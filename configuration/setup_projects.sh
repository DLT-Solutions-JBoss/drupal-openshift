#!/bin/bash
# Create Demo Projects with DEMONAME prefix.
if [ "$#" -ne 2 ]; then
    echo "Usage:"
    echo "  $0 DEMONAME USER"
    exit 1
fi

DEMONAME=$1
USER=$2

echo "Creating Demo Projects for DEMONAME=${DEMONAME} and USER=${USER}"
oc new-project ${DEMONAME}-jenkins    --display-name="${DEMONAME} Demo Jenkins"
oc new-project ${DEMONAME}-dev  --display-name="${DEMONAME} Demo Development"
oc new-project ${DEMONAME}-test  --display-name="${DEMONAME} Demo Test"
oc new-project ${DEMONAME}-prod --display-name="${DEMONAME} Demo Production"

oc policy add-role-to-user admin ${USER} -n ${DEMONAME}-jenkins
oc policy add-role-to-user admin ${USER} -n ${DEMONAME}-dev
oc policy add-role-to-user admin ${USER} -n ${DEMONAME}-test
oc policy add-role-to-user admin ${USER} -n ${DEMONAME}-prod

oc policy add-role-to-user system:image-puller system:serviceaccount:${DEMONAME}-test:default -n ${DEMONAME}-dev
oc policy add-role-to-user system:image-puller system:serviceaccount:${DEMONAME}-prod:default -n ${DEMONAME}-test

oc annotate namespace ${DEMONAME}-jenkins    openshift.io/requester=${USER} --overwrite
oc annotate namespace ${DEMONAME}-dev  openshift.io/requester=${USER} --overwrite
oc annotate namespace ${DEMONAME}-test  openshift.io/requester=${USER} --overwrite
oc annotate namespace ${DEMONAME}-prod openshift.io/requester=${USER} --overwrite
