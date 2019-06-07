#!/bin/bash
# Setup Production Project (initial active services: Green)
if [ "$#" -ne 1 ]; then
    echo "Usage:"
    echo "  $0 DEMONAME"
    exit 1
fi

DEMONAME=$1
echo "Setting up Tasks Production Environment in project ${DEMONAME}-prod"

# Set up Production Project
oc policy add-role-to-group system:image-puller system:serviceaccounts:${DEMONAME}-prod -n ${DEMONAME}-test
oc policy add-role-to-user edit system:serviceaccount:${DEMONAME}-jenkins:jenkins -n ${DEMONAME}-prod

# Create Blue Application
oc project ${DEMONAME}-prod
oc process drupal8-app-demo -n openshift \
    -p APPLICATION_NAME=${DEMONAME}-blue \
    -p DATABASE_SERVICE_NAME=mysql-${DEMONAME}-blue \
    -p MYSQL_USER=${DEMONAME} \
    -p MYSQL_PASSWORD=${DEMONAME} \
    -p MYSQL_DATABASE=${DEMONAME} \
    -p MYSQL_ROOT_PASSWORD=${DEMONAME} \
    | oc create -f -

# Setting 'wrong' VERSION. This will need to be updated in the pipeline
oc set env dc/${DEMONAME}-blue VERSION="0.0 (${DEMONAME}-blue)" -n ${DEMONAME}-prod
#oc patch dc ${DEMONAME}-blue -p '{"spec":{"template":{"spec":{"imagePullSecrets":[{"name":"nexus-registry-secret"}]}}}}'

# Create Green Application
oc project ${DEMONAME}-prod
oc process drupal8-app-demo -n openshift \
    -p APPLICATION_NAME=${DEMONAME}-green \
    -p DATABASE_SERVICE_NAME=mysql-${DEMONAME}-green \
    -p MYSQL_USER=${DEMONAME} \
    -p MYSQL_PASSWORD=${DEMONAME} \
    -p MYSQL_DATABASE=${DEMONAME} \
    -p MYSQL_ROOT_PASSWORD=${DEMONAME} \
    | oc create -f -

# Setting 'wrong' VERSION. This will need to be updated in the pipeline
oc set env dc/${DEMONAME}-green VERSION="0.0 (${DEMONAME}-green)" -n ${DEMONAME}-prod
#oc patch dc ${DEMONAME}-green -p '{"spec":{"template":{"spec":{"imagePullSecrets":[{"name":"nexus-registry-secret"}]}}}}'

# Expose Blue service as route to make green application active
oc expose svc/${DEMONAME}-green --name ${DEMONAME} -n ${DEMONAME}-prod

# Make sure that blue app is fully up and running before proceeding!
echo "Making sure that blue app is fully up and running before proceeding!"
while : ; do
  echo "Checking if ${DEMONAME}-blue is Ready..."
  AVAILABLE_REPLICAS=$(oc get dc ${DEMONAME}-blue -n ${DEMONAME}-prod -o=jsonpath='{.status.availableReplicas}')
  if [[ "$AVAILABLE_REPLICAS" == "1" ]]; then
    echo "...Yes. ${DEMONAME}-blue is ready."
    break
  fi
  echo "...no. Sleeping 10 seconds."
  sleep 10
done

PROD_BLUE_POD=$(oc get pod -n dol-prod |grep '^dol' |grep 'Running'| grep blue | cut -f1 -d" ")
echo "Prod Blue pod is ${PROD_BLUE_POD}"
echo "issuing copy via bash script on each pod"
oc exec ${PROD_BLUE_POD} -c ${DEMONAME}-blue -n ${DEMONAME}-prod bash init_settings.sh

# Make sure that green app is fully up and running before proceeding!
echo "Making sure that green app is fully up and running before proceeding!"
while : ; do
  echo "Checking if ${DEMONAME}-green is Ready..."
  AVAILABLE_REPLICAS=$(oc get dc ${DEMONAME}-green -n ${DEMONAME}-prod -o=jsonpath='{.status.availableReplicas}')
  if [[ "$AVAILABLE_REPLICAS" == "1" ]]; then
    echo "...Yes. ${DEMONAME}-green is ready."
    break
  fi
  echo "...no. Sleeping 10 seconds."
  sleep 10
done

PROD_GREEN_POD=$(oc get pod -n dol-prod |grep '^dol' |grep 'Running'| grep green | cut -f1 -d" ")
echo "Prod Green pod is ${PROD_GREEN_POD}"
echo "issuing copy via bash script on each pod"
oc exec ${PROD_GREEN_POD} -c ${DEMONAME}-green -n ${DEMONAME}-prod bash init_settings.sh

