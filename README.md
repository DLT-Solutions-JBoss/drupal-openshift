# Drupal 8 Demo on OpenShift 3.x

This repository contains a curated set of php files, configuration, and templates for OpenShift in order to produce a Drupal demo application and instructions to promote through its lifecycle. See the official OpenShift documentation for more information about **[templates](https://docs.okd.io/latest/dev_guide/templates.html)**.


- [Overview](#overview)
    - [Official](#official)
    - [Community](#community)
- [Building the OpenShift Drupal Lifecycle Projects](#building-the-library)
    - [Clone this repo to a Linux env](#python-dependencies)
    - [Login to your OCP environment as administrator](#running-the-script)
    - [Variables used in setup scripts](#verifying-your-updates)
    - [Setup Lifecycle Projects](#verifying-your-updates)
    - [Setup Jenkins Project](#verifying-your-updates)
    - [Installing the Drupal App Template](#verifying-your-updates)
    - [Verifying Your Additions](#verifying-your-updates)
- [Contributing](#contributing)
    - [YAML File Structure](#yaml-file-structure)
        - [Variables](#variables)
        - [Organization](#organization)
        - [folder_name](#folder_name)
        - [location](#location)
        - [docs](#docs)
        - [regex](#regex)
        - [suffix](#suffix)
    - [Adding Your Template or ImageStream](#adding-your-template-or-imagestream)
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

Example: ./setup_jenkins.sh DEMONAME https://github.com/DLT-Solutions-JBoss/drupal-openshift.git master.example.ocp.yourcompany.com

### Installing the Drupal App Template

    $ oc create -f drupal8-dol-demo.yml -n openshift
    
## Verifying Your Additions

    $ ./configuration/verify_prerequisistes.sh DEMONAME
    
The `make verify` command runs the following checks:
 - verifies YAML syntax
 - verifies the Python script *(using pylint)* 
 - verifies that make import has been run

## Contributing

### Adding the Development Template

- Fork the [openshift/library](https://github.com/openshift/library) repository on github
- Add your template or image stream to the **community.yaml** or **official.yaml** file in the top level of this project
- Run the `make import` command and make sure that your template(s) and/or image-stream(s) are processed and written to the correct directory under the **community** or **official** folder and that no errors have occurred.
- Run the `make verify` command and ensure that no errors occur
- Commit and push your changes to your fork of the github repository
  - Make sure to commit any changes in the **community** and **official** folders
- Create a pull request against the [openshift/library](https://github.com/openshift/library) upstream repository

That's it!  Your pull request will be reviewed by a member of the OpenShift Team and merged if everything looks good.


### YAML file structure:

    variables: # (optional) top level block item
      <variable_name>: <value> # (optional)
    data: # (required) top level block item
      DEMONAME<folder_name>: # (required) folder that the below items will be stored in
        imagestreams: # (optional) list of image-streams to process into the above folder
          - location: # (required) github url to a json file or folder of json files
            regex: # (optional) matched against ['metadata']['name'] in the json file
            suffix: # (optional) suffix for the file that is created ex: ruby-<suffix>.json
            docs: # (optional) web address of the documentation for this image-stream
        templates: # (optional) list of templates to process into the above folder
          - location: # (required) github url to a template or folder of templates in json format
            regex: # (optional) matched against ['metadata']['name'] in the json file
            suffix: # (optional) suffix for the file that is created ex: ruby-<suffix>.json
            docs: # (optional) web address of the documentation for this template

#### Variables

Anything under the **data** block can contain a reference to a variable by using the following syntax:

    {variable_name}

You must also specify a value for that variable name under the **variables** block with the following syntax:

    <variable_name>: <value>

#### Organization

Listings in the **official.yaml** file will be created in a sub folder of the  **official** top level folder.  Listings in the **community.yaml** file will be created in a sub folder of the **community** top level folder.

#### folder_name

The **folder_name** is a sub folder which represents a logical grouping for a set of templates or image-streams in the top level **official** or **community** folders.

#### location

The **location** must be a publicly available url that points to either a template, image-stream, or image-stream list file in JSON or YAML format

#### docs

The **docs** is a field to list the web address of the documentation for the template, image-stream, or image-stream list

#### regex

The **regex** is a plain string that is matched against the `['metadata']['name']` element in the template or image-stream.  Make sure that the **regex** string that you provide is descriptive enough to only match the `['metadata']['name']` that you are trying to target.

#### suffix

The **suffix** is applied to the end of the filename that is created right before the .json file extension and can contain dashes (-) or underscores (_).

### Cleaning up your projects

    $ ./configuration/cleanup.sh DEMONAME

## Additional information

### Creating templates, image-streams, and image-stream lists

You can find more information about creating templates and image-streams in the official [OpenShift Documentation](https://docs.okd.io/latest).  Below are some quick links to important sections:

- [Writing Image Streams](https://docs.okd.io/latest/dev_guide/managing_images.html#writing-image-streams-for-s2i-builders)
- [Writing Templates](https://docs.okd.io/latest/dev_guide/templates.html#writing-templates)
- [Quickstart Templates](https://docs.okd.io/latest/dev_guide/dev_tutorials/quickstarts.html)
- [Image Streams](https://docs.okd.io/latest/architecture/core_concepts/builds_and_image_streams.html#image-streams)
- [Managing Images](https://docs.okd.io/latest/dev_guide/managing_images.html#dev-guide-managing-images)
