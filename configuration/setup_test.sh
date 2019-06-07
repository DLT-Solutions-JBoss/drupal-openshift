#!/bin/bash
# Setup Test Project
if [ "$#" -ne 1 ]; then
    echo "Usage:"
    echo "  $0 DEMONAME"
    exit 1
fi

DEMONAME=$1
echo "Setting up Tasks Test Environment in project ${DEMONAME}-test"

# Set up Test Project
oc policy add-role-to-user edit system:serviceaccount:${DEMONAME}-jenkins:jenkins -n ${DEMONAME}-test

# Set up Test Application
oc project ${DEMONAME}-test
oc process drupal8-app-demo -n openshift \
    -p APPLICATION_NAME=${DEMONAME} \
    -p DATABASE_SERVICE_NAME=mysql-${DEMONAME} \
    -p MYSQL_USER=${DEMONAME} \
    -p MYSQL_PASSWORD=${DEMONAME} \
    -p MYSQL_DATABASE=${DEMONAME} \
    -p MYSQL_ROOT_PASSWORD=${DEMONAME} \
    | oc create -f -

# Setting 'wrong' VERSION. This will need to be updated in the pipeline
oc set env dc/${DEMONAME} VERSION='0.0 (${DEMONAME}-test)' -n ${DEMONAME}-test

# Make sure that app is fully up and running before proceeding!
while : ; do
  echo "Checking if ${DEMONAME} is Ready..."
  AVAILABLE_REPLICAS=$(oc get dc ${DEMONAME} -n ${DEMONAME}-test -o=jsonpath='{.status.availableReplicas}')
  if [[ "$AVAILABLE_REPLICAS" == "1" ]]; then
    echo "...Yes. ${DEMONAME} is ready."
    break
  fi
  echo "...no. Sleeping 10 seconds."
  sleep 10
done

# initialize settings and latest content/configuration

TEST_POD=$(oc get pod -n dol-test |grep '^dol' |grep 'Running'| cut -f1 -d" ")
echo "Test pod is ${TEST_POD}"

echo "issuing init via bash script on new pod"

oc exec ${TEST_POD} -c ${DEMONAME} -n ${DEMONAME}-test bash init_settings.sh

