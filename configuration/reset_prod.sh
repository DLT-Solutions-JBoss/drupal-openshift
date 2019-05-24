#!/bin/bash
# Reset Production Project (initial active services: Blue)
# This sets all services to the Blue service so that any pipeline run will deploy Green
if [ "$#" -ne 1 ]; then
    echo "Usage:"
    echo "  $0 DEMONAME"
    exit 1
fi

DEMONAME=$1
echo "Resetting ${DEMONAME} Production Environment in project ${DEMONAME}-prod to Green Services"

# Parksmap
# Set route to Green Service
oc patch route ${DEMONAME} -n ${DEMONAME}-prod -p '{"spec":{"to":{"name":"${DEMONAME}-green"}}}'

# Add echo statement so that the script succeeds even if the patch didn't do anything
echo "Updated"
