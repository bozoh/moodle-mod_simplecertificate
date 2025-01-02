# COMPATIBILITY
---

Current version tested on Moodle 4.3, 4.4 and 4.5.

## ABOUT
* **Developed by:**  Carlos Alexandre S. da Fonseca - bozoh. Currently maintained by David Herney - davidherney at gmail dot com
* **GIT:** https://github.com/bozoh/moodle-mod_simplecertificate
* **Powered by:** Moodle >= 4 by [BambuCo](https://bambuco.co/)

# QUICK INSTALL
---

There are two installation methods that are available.
Follow one of these, then log into your Moodle site as an administrator and visit the notifications page
to complete the install.

## MOST RECOMMENDED METHOD - Git
---
If you do not have git installed, please see the below link. Please note, it is
not necessary to set up the SSH Keys. This is only needed if you are going to
create a repository of your own on github.com.

Once you have git installed, simply visit the Moodle mod directory and clone
git://github.com/bozoh/moodle-mod_simplecertificate.git, remember to
rename the folder to certificate if you do not specify this in the clone command.

Eg. Linux command line would be as follow -

git clone git://github.com/bozoh/moodle-mod_simplecertificate.git simplecertificate

Once cloned, checkout the branch that is specific to your Moodle version.
eg, MOODLE_22 is for Moodle 2.2, MOODLE_23 is for 2.3

Use git pull to update this branch periodically to ensure you have the latest version.

## Download the simplecertificate module.

Visit https://github.com/bozoh/moodle-mod_simplecertificate, choose the branch
that matches your Moodle version (eg. MOODLE_22 is for Moodle 2.2, MOODLE_23 is for 2.3)
and download the zip, uncompress this zip and extract the folder. The folder will have a name similar to moodle-mod_simplecertificate-c9fbadb, you MUST rename this to simplecertificate.
Place this folder in your mod folder in your Moodle directory.

> The reason this is not the recommended method is due to the fact you have to over-write the contents of this folder to apply any future updates to the simplecertificate module. In the above method there is a simple command to update the files.

## In version

### 2024051104:
- Compatibility with Moodle 4.5+

### 2024051104:
- New setting to define the name of PDF certificates
- New site-level setting to enable use of the username field in certificates

### 2024051103:
- Compatibility with Moodle 4.4+
- Fixed UnitTest by @MichaelReyesCatcan
