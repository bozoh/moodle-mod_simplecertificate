* v.2.2.7 for moodle 3.4 
[![Build Status](https://travis-ci.org/bozoh/moodle.svg?branch=simple_certificate_MOODLE_34_STABLE)](https://travis-ci.org/bozoh/moodle/builds/)
* v.2.2.7 for moodle 3.3
[![Build Status](https://travis-ci.org/bozoh/moodle.svg?branch=simple_certificate_MOODLE_33_STABLE)](https://travis-ci.org/bozoh/moodle/builds/)
* v.2.2.7 for moodle 3.2
[![Build Status](https://travis-ci.org/bozoh/moodle.svg?branch=simple_certificate_MOODLE_32_STABLE)](https://travis-ci.org/bozoh/moodle/builds/)

## ATTENTION
---


**It's NOT RECOMMENDED to install version below 2.2.6 (MOODLE_31), due a security issues (#178 and #179)**, but if you choose to install it, apply the patch. I think this patch can be safely applied  in any version above 2.2.0 (MOODLE_25), and to do this first download the patch file:  https://gist.github.com/bozoh/a282badf56ffa7da0c4f1ec3d85a0af7/archive/ff385cda11e155167abf0ccb9e9127cc0427b48e.zip  
Unzip it and copy bug-178-179.patch file to <YOUR MOODLE FOLDER>/mod/simplecertificate

Run this command in  simplecertificate folder
``` 
patch < bug-178-179.patch
```

patch command it's part of most linux version and flavors



# QUICK INSTALL
---

There are two installation methods that are available. Follow one of these, then log into your Moodle site as an administrator and visit the notifications page
to complete the install.

## MOST RECOMMENDED METHOD - Git 
---
If you do not have git installed, please see the below link. Please note, it is
not necessary to set up the SSH Keys. This is only needed if you are going to
create a repository of your own on github.com.

Information on installing git - http://help.github.com/set-up-git-redirect/

Once you have git installed, simply visit the Moodle mod directory and clone
git://github.com/bozoh/moodle-mod_simplecertificate.git, remember to
rename the folder to certificate if you do not specify this in the clone command

Eg. Linux command line would be as follow -

git clone git://github.com/bozoh/moodle-mod_simplecertificate.git simplecertificate

Once cloned, checkout the branch that is specific to your Moodle version.
eg, MOODLE_22 is for Moodle 2.2, MOODLE_23 is for 2.3

Use git pull to update this branch periodically to ensure you have the latest version.

## Download the simplecertificate module. 

Visit https://github.com/bozoh/moodle-mod_simplecertificate, choose the branch
that matches your Moodle version (eg. MOODLE_22 is for Moodle 2.2, MOODLE_23 is for 2.3)
and download the zip, uncompress this zip and extract the folder. The folder will have a name similar to bozoh-moodle-mod_simplecertificate-c9fbadb, you MUST rename this to simplecertificate. 
Place this folder in your mod folder in your Moodle directory.

> The reason this is not the recommended method is due to the fact you have to over-write the contents of this folder to apply any future updates to the simplecertificate module. In the above method there is a simple command to update the files.
