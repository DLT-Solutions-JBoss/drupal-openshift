#!groovy
podTemplate(
  label: "doldemo-pod",
  cloud: "openshift",
  inheritFrom: "maven",
  containers: [
    containerTemplate(
      name: "jnlp",
      image: "docker-registry.default.svc:5000/${DEMONAME}-jenkins/jenkins-agent-appdev",
      resourceRequestMemory: "1Gi",
      resourceLimitMemory: "2Gi",
      resourceRequestCpu: "1",
      resourceLimitCpu: "2"
    )
  ]
) {
  node('doldemo-pod') {
    // Define Maven Command to point to the correct
    // settings for our Nexus installation
    stage('Checkout Source') {
      checkout scm
    }

    // Build the DolDemo Service
    dir('/') {
      // The following variables need to be defined at the top level
      // and not inside the scope of a stage - otherwise they would not
      // be accessible from other stages.
      // Extract version from the pom.xml
      def version = "v1"

      // Set the tag for the development image: version + build number
      def devTag  = "${version}-${currentBuild.number}"
      // Set the tag for the production image: version
      def prodTag = "${version}"
      
      // Build the OpenShift Image in OpenShift and tag it.
      stage('Build and Tag OpenShift Image') {
        // Build Image, tag Image
        echo "Building OpenShift container image ${DEMONAME}:${devTag}"

        // Start PHP Build in OpenShift
        script {
          openshift.withCluster() {
            openshift.withProject("${devProject}") {
                
              openshift.selector("bc", "${DEMONAME}").startBuild("--wait=true")
              openshift.tag("${DEMONAME}:latest", "${DEMONAME}:${devTag}")
            }
          }
        }
      }

      // Deploy the built image to the Development Environment.
      stage('Deploy to Dev') {
        echo "Deploying container image to Development Project"

        // Deploy to development Project
        // Set Image, Set VERSION
        // Make sure the application is running and ready before proceeding
        script {
            // Update the Image on the Development Deployment Config
            openshift.withCluster() {
              openshift.withProject("${devProject}") {
                openshift.set("image", "dc/${DEMONAME}", "doldemo=docker-registry.default.svc:5000/${devProject}/${DEMONAME}:${devTag}")
                openshift.set("env", "dc/${DEMONAME}", "VERSION='${devTag} (${DEMONAME}-dev)'")

                // Deploy the development application.
                openshift.selector("dc", "${DEMONAME}").rollout().latest();

                // Wait for application to be deployed
                def dc = openshift.selector("dc", "${DEMONAME}").object()
                def dc_version = dc.status.latestVersion
                def rc = openshift.selector("rc", "${DEMONAME}-${dc_version}").object()

                echo "Waiting for ReplicationController doldemo-${dc_version} to be ready"
                while (rc.spec.replicas != rc.status.readyReplicas) {
                  sleep 5
                  rc = openshift.selector("rc", "${DEMONAME}-${dc_version}").object()
              }
            }
          }
        }
      }

      // Blue/Green Deployment into Production
      // -------------------------------------
      def destApp   = "doldemo-green"
      def activeApp = ""

      stage('Blue/Green Production Deployment') {
        // TBD: Determine which application is active
        //      Set Image, Set VERSION
        //      Deploy into the other application
        //      Make sure the application is running and ready before proceeding
        script {
          openshift.withCluster() {

            openshift.withProject("${prodProject}") {
              activeApp = openshift.selector("route", "${DEMONAME}").object().spec.to.name
              if (activeApp == "${DEMONAME}-green") {
                destApp = "${DEMONAME}-blue"
              }
              
              echo "Active Application:      " + activeApp
              echo "Destination Application: " + destApp

              // Update the Image on the Production Deployment Config
              def dc = openshift.selector("dc/${destApp}").object()
              
              dc.spec.template.spec.containers[0].image="${nexusRegPub}/${DEMONAME}:${prodTag}"
              
              //Set VERSION environment variable
              dc.spec.template.spec.containers[0].env[0].value="${prodTag} (${destApp})"
              
              openshift.apply(dc)

              // Deploy the inactive application.
              openshift.selector("dc", "${destApp}").rollout().latest();

              // Wait for application to be deployed
              def dc_prod = openshift.selector("dc", "${destApp}").object()

              def dc_version = dc_prod.status.latestVersion

              def rc_prod = openshift.selector("rc", "${destApp}-${dc_version}").object()

              echo "Waiting for ${destApp} to be ready"
              while (rc_prod.spec.replicas != rc_prod.status.readyReplicas) {
                sleep 5
                rc_prod = openshift.selector("rc", "${destApp}-${dc_version}").object()
              }
            }
          }
        }
      }

      stage('Switch over to new Version') {
        echo "Switching Production application to ${destApp}."
        script {
          openshift.withCluster() {
            openshift.withProject("${prodProject}") {
              def route = openshift.selector("route/${DEMONAME}").object()
              route.spec.to.name="${destApp}"
              openshift.apply(route)
            }
          }
        }
      }
    }
  }
}

// Convenience Functions to read version from the pom.xml
// Do not change anything below this line.
// --------------------------------------------------------
def getVersionFromPom(pom) {
  def matcher = readFile(pom) =~ '<version>(.+)</version>'
  matcher ? matcher[0][1] : null
}

