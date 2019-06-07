# Drupal 8 Demo on OpenShift 3.x

This repository contains a curated set of php files, configuration, and templates for OpenShift in order to produce a Drupal demo application and instructions to promote through its lifecycle. See the official OpenShift documentation for more information about **[templates](https://docs.okd.io/latest/dev_guide/templates.html)**.


- [Overview](#overview)
    - [Official](#official)
    - [Community](#community)
- [Building the OpenShift Drupal Lifecycle Projects](#building-the-openshift-drupal-lifecycle-projects)
- [Lifecycle Processing](#lifecycle-processing)
- [Additional Information](#additional-information)


## Overview

### Official

Provided and supported by DLT, official Templates are listed in the top level of this repository, making it easy for developers to get started creating drupal applications with the newest technologies.  This assumes you have a working OpenShift environment with proper authority and resources to build, deploy, and manage applications.

You can check to see which of the official Templates are available in your OpenShift cluster by doing one of the following:

- Log into the web console and click **Add to Project**
- List them for the openshift project using the **Command Line Interface**

    $ oc get templates -n openshift  
    $ oc get projects

### Community

Community templates and image streams are **not** provided or supported by Red Hat. This curated list of community maintained resources exemplify OpenShift best practices and provide clear documentation to serve as a reference for other developers.

## Building the OpenShift Drupal Lifecycle Projects

### Clone this repo to a Linux env

    $ git clone https://github.com/DLT-Solutions-JBoss/drupal-openshift.git
    $ cd drupal-openshift 

### Login to your OCP environment as administrator

    $ oc login https://master.example.ocp.yourcompany.com --username=*admin* (--password=*admin_pass*)

### Variables used in setup scripts

- DEMONAME - The name of the demo srupal app you wish to build
- USER - Your OpenShift user name.
- REPO - Your cloned git repository full url.
- CLUSER - The domain name of your OpenShift cluster.

### Setup Lifecycle Projects

    $ ./configuration/setup_projects.sh DEMONAME USER

    $ ./configuration/setup_dev.sh DEMONAME

    $ ./configuration/setup_test.sh DEMONAME

    $ ./configuration/setup_prod.sh DEMONAME

### Setup Jenkins Project

    $ ./configuration/setup_jenkins.sh DEMONAME REPO CLUSTER

Example:
    $ ./configuration/setup_jenkins.sh DEMONAME https://github.com/DLT-Solutions-JBoss/drupal-openshift.git master.example.ocp.yourcompany.com

### Installing the Drupal App Template

    $ oc create -f drupal8-mysql-persistent.yml -n openshift
    
## Verifying Your Additions

    $ ./configuration/verify_prerequisistes.sh DEMONAME USER
    
## Lifescycle Processing

### Adding the Development Template

- Clone this [repository](https://github.com/DLT-Solutions-JBoss/drupal-openshift) from github
- Create a new project for your development
- Install theme or module using git submodules in order to produce a federated look and feel
  - [Git Submodules](https://git-scm.com/book/en/v2/Git-Tools-Submodules)
- Create a new application instance from the Catalog
- Install Drupal app
- Install Config Suite, Backup and Migrate, and (optionally) Server IP modules
- Configure Config Suite to import and export automatically
- Restore the database with the latest backup from your repo.

#### Organization

TBD

### Cleaning up your projects

    $ ./configuration/cleanup.sh DEMONAME

## Additional information

### OpenShift - Creating OpenShift templates, using Quickstarts, image-streams, and image management

You can find more information about creating templates and image-streams in the official [OpenShift Documentation](https://docs.okd.io/latest).  Below are some quick links to important sections:

- [Writing Image Streams](https://docs.okd.io/latest/dev_guide/managing_images.html#writing-image-streams-for-s2i-builders)
- [Writing Templates](https://docs.okd.io/latest/dev_guide/templates.html#writing-templates)
- [Quickstart Templates](https://docs.okd.io/latest/dev_guide/dev_tutorials/quickstarts.html)
- [Image Streams](https://docs.okd.io/latest/architecture/core_concepts/builds_and_image_streams.html#image-streams)
- [Managing Images](https://docs.okd.io/latest/dev_guide/managing_images.html#dev-guide-managing-images)

### Drupal - Creating Drupal Apps, Federated Themes & Modules, Composer, etc. 

You can find more information about Drupal in the official [Drupal 8 Documentation](https://www.drupal.org/8).  Below are some quick links to important sections:

- [Local Development Guide](https://www.drupal.org/docs/official_docs/en/_local_development_guide.html)
- [Installing Drupal 8 Modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules)
- [Installing Drupal 8 Themes](https://www.drupal.org/docs/8/extending-drupal-8/installing-themes)
- [Installing Config Suite](https://www.drupal.org/project/config_suite)
- [Installing Backup and Migrate](https://www.drupal.org/docs/7/modules/backup-and-migrate/backup-and-migrate)
- [Installing Server IP](https://www.drupal.org/project/server_ip)

