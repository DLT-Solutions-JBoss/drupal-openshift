#!groovy
podTemplate(
  label: "skopeo-pod",
  cloud: "openshift",
  inheritFrom: "maven",
  containers: [
    containerTemplate(
      name: "jnlp",
      image: "docker-registry.default.svc:5000/${GUID}-jenkins/jenkins-agent-appdev",
      resourceRequestMemory: "1Gi",
      resourceLimitMemory: "2Gi",
      resourceRequestCpu: "1",
      resourceLimitCpu: "2"
    )
  ]
) {
  node('skopeo-pod') {
    // Define Maven Command to point to the correct
    // settings for our Nexus installation
    def mvnCmd       = "mvn -s ../nexus_settings.xml"

    // Checkout Source Code.
    stage('Checkout Source') {
      checkout scm
    }

    // Build the Tasks Service
    dir('openshift-tasks') {
      // The following variables need to be defined at the top level
      // and not inside the scope of a stage - otherwise they would not
      // be accessible from other stages.
      // Extract version from the pom.xml
      def version = getVersionFromPom("pom.xml")

      // Set the tag for the development image: version + build number
      def devTag  = "${version}-${currentBuild.number}"
      // Set the tag for the production image: version
      def prodTag = "${version}"
      
      def nexusRepo    = "http://nexus3-gpte-hw-cicd.apps.na311.openshift.opentlc.com"
      def nexusRegSvc  = "nexus-registry.gpte-hw-cicd.svc.cluster.local:5000"
      def nexusRegPub  = "nexus-registry-gpte-hw-cicd.apps.na311.openshift.opentlc.com"
      def sonarqube    = "http://sonarqube-gpte-hw-cicd.apps.na311.openshift.opentlc.com"
      def sonarqubeSvc = "sonarqube.gpte-hw-cicd.svc.cluster.local:9000"
      def appBaseUrl   = "apps.na311.openshift.opentlc.com"
      def devProject   = "${GUID}" +"-tasks-dev"
      def prodProject  = "${GUID}" +"-tasks-prod"
      def dockerEmail  = "rick.stewart@dlt.com"

      // Using Maven build the war file
      // Do not run tests in this step
      stage('Build war') {
        echo "Building version ${devTag}"

        // Execute Maven Build
        sh "${mvnCmd} clean package -DskipTests=true"
      }

      // The next two stages should run in parallel

      parallel unittest : {

        // Using Maven run the unit tests
        stage('Unit Tests') {
              echo "Running Unit Tests"

              // Execute Unit Tests
              sh "${mvnCmd} test"
          }
        }, codeanalysis: {

        // Using Maven to call SonarQube for Code Analysis
        stage('Code Analysis') {
          echo "Running Code Analysis"

          // Execute Sonarqube Tests
          sh "${mvnCmd} sonar:sonar -Dsonar.host.url=${sonarqube}/ -Dsonar.projectName=${JOB_BASE_NAME} -Dsonar.projectVersion=${devTag}"
        }

      }, 
      failFast: true //End Parallel

      // Publish the built war file to Nexus
      stage('Publish to Nexus') {
        echo "Publish to Nexus"

        // Publish to Nexus
        sh "${mvnCmd} deploy -DskipTests=true -DaltDeploymentRepository=nexus::default::${nexusRepo}/repository/releases"

      }

      // Build the OpenShift Image in OpenShift and tag it.
      stage('Build and Tag OpenShift Image') {
        // Build Image, tag Image
        echo "Building OpenShift container image tasks:${devTag}"

        // Start Binary Build in OpenShift using the file we just published
        script {
          openshift.withCluster() {
            openshift.withProject("${devProject}") {
                
              openshift.selector("bc", "tasks").startBuild("--from-file=${nexusRepo}/repository/releases/org/jboss/quickstarts/eap/tasks/${version}/tasks-${version}.war", "--wait=true")
              openshift.tag("tasks:latest", "tasks:${devTag}")
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
                openshift.set("image", "dc/tasks", "tasks=docker-registry.default.svc:5000/${devProject}/tasks:${devTag}")
                openshift.set("env", "dc/tasks", "VERSION='${devTag} (tasks-dev)'")

                openshift.selector('configmap', 'tasks-config').delete()
                def configmap = openshift.create('configmap', 'tasks-config', '--from-file=./configuration/application-users.properties', '--from-file=./configuration/application-roles.properties' )

                // Deploy the development application.
                openshift.selector("dc", "tasks").rollout().latest();

                // Wait for application to be deployed
                def dc = openshift.selector("dc", "tasks").object()
                def dc_version = dc.status.latestVersion
                def rc = openshift.selector("rc", "tasks-${dc_version}").object()

                echo "Waiting for ReplicationController tasks-${dc_version} to be ready"
                while (rc.spec.replicas != rc.status.readyReplicas) {
                  sleep 5
                  rc = openshift.selector("rc", "tasks-${dc_version}").object()
              }
            }
          }
        }
      }

//      // Run Integration Tests in the Development Environment.
//      stage('Integration Tests') {
//        echo "Running Integration Tests"
//          script {
//             def status = "000"
//             def devApp = ""
//             openshift.withCluster() {
//                openshift.withProject("${devProject}") {
//                  devApp = openshift.selector("route", "tasks").object().spec.host
//                  
//                  echo "Executing tests on Dev Project App at: ${devApp}"
//
//                  // Create a new task called "integration_test_${GUID}"
//                  echo "Creating task"
//                  status = sh(returnStdout: true, script: "curl -sw '%{response_code}' -o /dev/null -u 'tasks:redhat1' -H 'Content-Length: 0' -X POST http://${devApp}/ws/tasks/integration_test_${GUID}").trim()
//                  echo "Create Status: " + status
//                  if (status != "201") {
//                    error 'Integration Create Test Failed!'
//                  }
//
//                  echo "Retrieving tasks"
//                  status = sh(returnStdout: true, script: "curl -sw '%{response_code}' -o /dev/null -u 'tasks:redhat1' -H 'Accept: application/json' -X GET http://${devApp}/ws/tasks/1").trim()
//                  echo "Retrieve Status: " + status
//                  if (status != "200") {
//                     error 'Integration Get Test Failed!'
//                  }
//
//                  echo "Deleting tasks"
//                  status = sh(returnStdout: true, script: "curl -sw '%{response_code}' -o /dev/null -u 'tasks:redhat1' -X DELETE http://${devApp}/ws/tasks/1").trim()
//                  echo "Delete Status: " + status
//                  if (status != "204") {
//                     error 'Integration Create Test Failed!'
//                  }
//               }
//            }
//         }
//      }      
      
      // Copy Image to Nexus container registry
      stage('Copy Image to Nexus container registry') {

        // Copy image to Nexus container registry
        echo "Copy image to Nexus Docker Registry"
        script {
          sh "skopeo copy --src-tls-verify=false --dest-tls-verify=false \
              --src-creds openshift:\$(oc whoami -t) \
              --dest-creds admin:redhat  \
              docker://docker-registry.default.svc:5000/${devProject}/tasks:${devTag} \
              docker://${nexusRegSvc}/tasks:${prodTag}"

          // Tag the built image with the production tag.
          openshift.withCluster() {
            openshift.withProject("${prodProject}") {
              openshift.tag("${devProject}/tasks:${devTag}", "${prodProject}/tasks:${prodTag}")
            }
          }
        }
      }

      // Blue/Green Deployment into Production
      // -------------------------------------
      def destApp   = "tasks-green"
      def activeApp = ""

      stage('Blue/Green Production Deployment') {
        // TBD: Determine which application is active
        //      Set Image, Set VERSION
        //      Deploy into the other application
        //      Make sure the application is running and ready before proceeding
        script {
          openshift.withCluster() {

            openshift.withProject("${prodProject}") {
              activeApp = openshift.selector("route", "tasks").object().spec.to.name
              if (activeApp == "tasks-green") {
                destApp = "tasks-blue"
              }
              
              echo "Active Application:      " + activeApp
              echo "Destination Application: " + destApp

              // Update the Image on the Production Deployment Config
              def dc = openshift.selector("dc/${destApp}").object()
              
              dc.spec.template.spec.containers[0].image="${nexusRegPub}/tasks:${prodTag}"
              
              //Set VERSION environment variable
              dc.spec.template.spec.containers[0].env[0].value="${prodTag} (${destApp})"
    //          openshift.set("env", "dc/${destApp}", "VERSION=\"${prodTag} (${destApp})\"")
              
              openshift.apply(dc)

              // Update Config Map in change config files changed in the source
              openshift.selector("configmap", "${destApp}-config").delete()
              def configmap = openshift.create("configmap", "${destApp}-config", "--from-file=./configuration/application-users.properties", "--from-file=./configuration/application-roles.properties" )

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
              def route = openshift.selector("route/tasks").object()
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

