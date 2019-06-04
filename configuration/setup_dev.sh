#!/bin/bash
# Setup Development Project
if [ "$#" -ne 1 ]; then
    echo "Usage:"
    echo "  $0 DEMONAME"
    exit 1
fi

DEMONAME=$1
echo "Setting up Tasks Development Environment in project ${DEMONAME}-dev"

# Set up Development Project
oc policy add-role-to-user edit system:serviceaccount:${DEMONAME}-jenkins:jenkins -n ${DEMONAME}-dev

# Set up Development Application
# oc new-build --binary=true --name="${DEMONAME}" jboss-eap71-openshift:1.3 -n ${DEMONAME}-dev
# oc new-build --binary=true --name="${DEMONAME}" --image-stream=openshift/jboss-eap71-openshift:1.1 -n ${DEMONAME}-dev
oc project ${DEMONAME}-dev
oc process drupal8-app-demo -n openshift \
    -p APPLICATION_NAME=${DEMONAME} \
    -p DATABASE_SERVICE_NAME=mysql-${DEMONAME} \
    -p MYSQL_USER=${DEMONAME} \
    -p MYSQL_PASSWORD=${DEMONAME} \
    -p MYSQL_DATABASE=${DEMONAME} \
    -p MYSQL_ROOT_PASSWORD=${DEMONAME} \
    | oc create -f -
# oc new-app ${DEMONAME}-dev/${DEMONAME}:0.0-0 --name=tasks --allow-missing-imagestream-tags=true -n ${DEMONAME}-dev
oc set triggers dc/${DEMONAME} --remove-all -n ${DEMONAME}-dev
# oc expose dc ${DEMONAME} --port 8080 -n ${DEMONAME}-dev
# oc expose svc ${DEMONAME} -n ${DEMONAME}-dev
#oc create configmap tasks-config --from-literal="application-users.properties=Placeholder" --from-literal="application-roles.properties=Placeholder" -n ${DEMONAME}-dev
#oc set volume dc/${DEMONAME} --add --name=jboss-config --mount-path=/opt/eap/standalone/configuration/application-users.properties --sub-path=application-users.properties --configmap-name=tasks-config -n ${DEMONAME}-dev
# oc set volume dc/${DEMONAME} --add --name=jboss-config1 --mount-path=/opt/eap/standalone/configuration/application-roles.properties --sub-path=application-roles.properties --configmap-name=tasks-config -n ${DEMONAME}-dev
# oc set probe dc/${DEMONAME} --readiness --get-url=http://:8080/ --initial-delay-seconds=30 --timeout-seconds=1 -n ${DEMONAME}-dev
# oc set probe dc/${DEMONAME} --liveness --get-url=http://:8080/ --initial-delay-seconds=30 --timeout-seconds=1 -n ${DEMONAME}-dev

# Setting 'wrong' VERSION. This will need to be updated in the pipeline
oc set env dc/${DEMONAME} VERSION='0.0 (${DEMONAME}-dev)' -n ${DEMONAME}-dev
