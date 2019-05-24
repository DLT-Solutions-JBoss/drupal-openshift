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
# oc create secret docker-registry nexus-registry-secret -n ${DEMONAME}-prod --docker-email="rick.stewart@dlt.com" --docker-server="nexus-registry-gpte-hw-cicd.apps.na311.openshift.opentlc.com" --docker-username=admin --docker-password=redhat

# Create Blue Application
oc project ${DEMONAME}-prod
oc process drupal8-app-demo -n dev \
    -p APPLICATION_NAME=${DEMONAME}-blue \
    -p DATABASE_SERVICE_NAME=mysql-${DEMONAME}-blue \
    -p MYSQL_USER=${DEMONAME} \
    -p MYSQL_PASSWORD=${DEMONAME} \
    -p MYSQL_DATABASE=${DEMONAME} \
    -p MYSQL_ROOT_PASSWORD=${DEMONAME} \
    | oc create -f -
oc set triggers dc/${DEMONAME}-blue --remove-all -n ${DEMONAME}-prod
oc expose dc ${DEMONAME}-blue --port 8080 -n ${DEMONAME}-prod
#oc create configmap ${DEMONAME}-blue-config --from-literal="application-users.properties=Placeholder" --from-literal="application-roles.properties=Placeholder" -n ${DEMONAME}-prod
#oc set volume dc/${DEMONAME}-blue --add --name=jboss-config --mount-path=/opt/eap/standalone/configuration/application-users.properties --sub-path=application-users.properties --configmap-name='${DEMONAME}'-blue-config -n ${DEMONAME}-prod
#oc set volume dc/${DEMONAME}-blue --add --name=jboss-config1 --mount-path=/opt/eap/standalone/configuration/application-roles.properties --sub-path=application-roles.properties --configmap-name='${DEMONAME}'-blue-config -n ${DEMONAME}-prod
#oc set probe dc/${DEMONAME}-blue --readiness --get-url=http://:8080/ --initial-delay-seconds=30 --timeout-seconds=1 -n ${DEMONAME}-prod
#oc set probe dc/${DEMONAME}-blue --liveness --get-url=http://:8080/ --initial-delay-seconds=30 --timeout-seconds=1 -n ${DEMONAME}-prod
# Setting 'wrong' VERSION. This will need to be updated in the pipeline
oc set env dc/${DEMONAME}-blue VERSION='0.0 (${DEMONAME}-blue)' -n ${DEMONAME}-prod
oc patch dc ${DEMONAME}-blue -p '{"spec":{"template":{"spec":{"imagePullSecrets":[{"name":"nexus-registry-secret"}]}}}}'


# Create Green Application
oc project ${DEMONAME}-prod
oc process drupal8-app-demo -n dev \
    -p APPLICATION_NAME=${DEMONAME}-green \
    -p DATABASE_SERVICE_NAME=mysql-${DEMONAME}-green \
    -p MYSQL_USER=${DEMONAME} \
    -p MYSQL_PASSWORD=${DEMONAME} \
    -p MYSQL_DATABASE=${DEMONAME} \
    -p MYSQL_ROOT_PASSWORD=${DEMONAME} \
    | oc create -f -
oc set triggers dc/${DEMONAME}-green --remove-all -n ${DEMONAME}-prod
oc expose dc ${DEMONAME}-green --port 8080 -n ${DEMONAME}-prod
#oc create configmap ${DEMONAME}-green-config --from-literal="application-users.properties=Placeholder" --from-literal="application-roles.properties=Placeholder" -n ${DEMONAME}-prod
#oc set volume dc/${DEMONAME}-green --add --name=jboss-config --mount-path=/opt/eap/standalone/configuration/application-users.properties --sub-path=application-users.properties --configmap-name='${DEMONAME}'-green-config -n ${DEMONAME}-prod
#oc set volume dc/${DEMONAME}-green --add --name=jboss-config1 --mount-path=/opt/eap/standalone/configuration/application-roles.properties --sub-path=application-roles.properties --configmap-name='${DEMONAME}'-green-config -n ${DEMONAME}-prod
#oc set probe dc/${DEMONAME}-green --readiness --get-url=http://:8080/ --initial-delay-seconds=30 --timeout-seconds=1 -n ${DEMONAME}-prod
#oc set probe dc/${DEMONAME}-green --liveness --get-url=http://:8080/ --initial-delay-seconds=30 --timeout-seconds=1 -n ${DEMONAME}-prod
# Setting 'wrong' VERSION. This will need to be updated in the pipeline
oc set env dc/${DEMONAME}-green VERSION='0.0 (${DEMONAME}-green)' -n ${DEMONAME}-prod
oc patch dc ${DEMONAME}-green -p '{"spec":{"template":{"spec":{"imagePullSecrets":[{"name":"nexus-registry-secret"}]}}}}'

# Expose Blue service as route to make green application active
oc expose svc/${DEMONAME}-green --name ${DEMONAME} -n ${DEMONAME}-prod
