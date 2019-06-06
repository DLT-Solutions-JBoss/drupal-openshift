#!/bin/bash
# Setup Jenkins Project
if [ "$#" -ne 3 ]; then
    echo "Usage:"
    echo "  $0 DEMONAME REPO CLUSTER"
    echo "  Example: $0 dol https://github.com/DLT-Solutions-JBoss/drupal-openshift.git master.labor.ocp.demo-dlt.com"
    exit 1
fi

DEMONAME=$1
REPO=$2
CLUSTER=$3
echo "Setting up Jenkins in project ${DEMONAME}-jenkins from Git Repo ${REPO} for Cluster ${CLUSTER}"

# Set up Jenkins with sufficient resources
oc new-app jenkins-persistent  \
  -p ENABLE_OAUTH=true \
  -p MEMORY_LIMIT=4Gi \
  -p VOLUME_CAPACITY=10Gi \
  -p DISABLE_ADMINISTRATIVE_MONITORS=true \
  --namespace $DEMONAME-jenkins

# 

# Create custom agent container image with skopeo
echo "
FROM docker.io/openshift/jenkins-agent-maven-35-centos7:v3.11
USER root
RUN yum -y install skopeo && yum clean all
USER 1001
" | oc new-build \
--name jenkins-agent-appdev \
--namespace dol-jenkins \
--dockerfile -

# Create pipeline build config pointing to the ${REPO} with contextDir `openshift`
echo "apiVersion: v1
items:
- kind: "BuildConfig"
  apiVersion: "v1"
  metadata:
    name: "${DEMONAME}-pipeline"
  spec:
    source:
      type: "Git"
      git:
        uri: "$REPO"
      contextDir: /
    strategy:
      type: "JenkinsPipeline"
      jenkinsPipelineStrategy:
        jenkinsfilePath: Jenkinsfile
        env:
        - name: DEMONAME
          value: ${DEMONAME}
        - name: REPO
          value: ${REPO}
        - name: CLUSTER
          value: ${CLUSTER}
kind: List
metadata: []" | oc create -n $DEMONAME-jenkins -f -

# Make sure that Jenkins is fully up and running before proceeding!
while : ; do
  echo "Checking if Jenkins is Ready..."
  AVAILABLE_REPLICAS=$(oc get dc jenkins -n ${DEMONAME}-jenkins -o=jsonpath='{.status.availableReplicas}')
  if [[ "$AVAILABLE_REPLICAS" == "1" ]]; then
    echo "...Yes. Jenkins is ready."
    break
  fi
  echo "...no. Sleeping 10 seconds."
  sleep 10
done
