pipeline {
  agent {
    // Using the Jenkins Agent Pod that we defined earlier
    label "maven"
  }

  environment {
      // Set your project Prefix
      def DEMONAME     = "dol"

      // Set variable globally to be available in all stages
      // Set Development and Production Project Names
      def devProject   = "${DEMONAME}-dev"
      def testProject  = "${DEMONAME}-test"
      def prodProject  = "${DEMONAME}-prod"
      def version = "1"
      def devTag  = "${version}-${currentBuild.number}"
      //def prodTag = "${version}"
      def prodTag = "2"
      def destApp   = "${DEMONAME}-green"
      def activeApp = ""
  }

  stages {
    stage('Checkout Source') {
      steps {
        echo "Checking out Code"
        checkout scm
      }
    }

    // Build the OpenShift Image in OpenShift and tag it.
    stage('ReBuild Dev and Tag OpenShift Image') {

      steps {

        echo "Building Dev OpenShift container image"

        script {
          // Build Image, tag Image
          echo "Building OpenShift container image ${DEMONAME}:${devTag}"

          // Start PHP Build in OpenShift
          openshift.withCluster() {
            openshift.withProject("${devProject}") {
                
              openshift.selector("bc", "${DEMONAME}").startBuild("--wait=true")
              openshift.tag("${DEMONAME}:latest", "${DEMONAME}:${devTag}")
              }
            }
          } 
        }
      }

      // Build the OpenShift Image in OpenShift and tag it.
      stage('ReBuild Test and Tag OpenShift Image') {

        steps {
          // Build Image, tag Image
          echo "Deploying OpenShift container image ${DEMONAME}:${devTag} in Test"

          // Deploy Drupal Image in OpenShift (Test)
          script {
            openshift.withCluster() {
              openshift.withProject("${testProject}") {

                // Update the Image on the Test Deployment Config
                def dc = openshift.selector("dc/${DEMONAME}").object()

                dc.spec.template.spec.containers[0].image="docker-registry.default.svc:5000/${devProject}/${DEMONAME}:${devTag}"

                openshift.apply(dc)

                // Deploy the test application.
                openshift.selector("dc", "${DEMONAME}").rollout().latest();
              }
            }
          }
        }
      }

      // Blue/Green Deployment into Production
      // -------------------------------------

      stage('Blue/Green Production Deployment') {

        steps {
          // Make sure the application is running and ready before proceeding
          script {
            openshift.withCluster() {

              openshift.withProject("${prodProject}") {
                activeApp = openshift.selector("route", "${DEMONAME}").object().spec.to.name
                if (activeApp == "${DEMONAME}-green") {
                  destApp = "${DEMONAME}-blue"
                }
              
                echo "Active Application:      " + activeApp
                echo "Destination Application: " + destApp

                // Update the Image on the Test Deployment Config
                def dc = openshift.selector("dc/${destApp}").object()

                dc.spec.template.spec.containers[0].image="docker-registry.default.svc:5000/${devProject}/${DEMONAME}:${devTag}"

                openshift.apply(dc)

                // Deploy the test application.
                openshift.selector("dc", "${destApp}").rollout().latest();

                // Wait for application to be deployed
                def dc_prod = openshift.selector("dc", "${destApp}").object()

                def dc_version = dc_prod.status.latestVersion

                def rc_prod = openshift.selector("rc", "${destApp}-${dc_version}").object()
              
                openshift.tag("${destApp}:latest", "${destApp}:${prodTag}")

                echo "Waiting for ${destApp} to be ready"
                while (rc_prod.spec.replicas != rc_prod.status.readyReplicas) {
                  sleep 5
                  rc_prod = openshift.selector("rc", "${destApp}-${dc_version}").object()
                }
              }
            }
          }
        }
      }

      stage('Switch over to new Version') {

        steps {
          input "Switch Production?"

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

