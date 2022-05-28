# mod_peerwork

About
=====

This is a peer work group assignment activity for Moodle.
This plugin addresses the issue of all students recieving the same grade for group assignments regardless of individual contribution.

Using this module:

Tutors create an activity defining peer grading criteria, calculation method and weightings.
Tutors define number of files to be submitted allowing "0" for an offline activity.
Tutors control visibility / privacy for students in terms of peer scores, comments.
Students submit work (optional).
Students rate their peers for each criteria (required).
Students can leave feedback comments (optional). 
Teachers grade the group assignment.

The activity then calculates the grade each individual student receives.
The final individual grade is based on the teacher grade, peer ratings and weighting. The calculator is a subplugin of the peer work activity.

If PA weighting is included in the selected calculator, then teachers can adjust weightings on a per group basis. Teachers can also override any individual grades/peer grades they need to.
Teachers then release grades ensuring all students recieve their grades at the same time.
Teachers can export grades for an individual group or the entire cohort as a csv file.
Teachers can lock the activity, giving students only one attempt to submit. They can also unlock a group submission or an individual student.

Calculator plugins
Calculator plugins can be assigned available scales. The default is 'all'. If scales are selected then only those will be available in the criteria settings. The scale setting is locked once a student submits peer grades. Changing the calculator type will not affect the peer grades even if the new calculator does not have the scale used as an available scale. Available scale is only checked when the criteria scale is enabled.


Authors
=======

2013
 - This module was built to a specification from City, University of London 
 - by Learning Technology Services Ltd, Ireland.

2017
 - This module was revised by Naomi Wilce, City, University of London
 
2018
 - Work by Kevin Moore, Coventry University to add grading criteria

2019
 - Extended and improved by https://branchup.tech/ with funding from Coventry University

 2020
 - Extended and improved by Amanda Doughty, City, University of London

Supported Versions
==================

 - The module has been tested with Moodle versions 4.0


Installation instructions
=========================

1. Copy the peerwork directory to the /mod directory of the Moodle instance
2. Log into your Moodle as administrator, or if logged in visit the Notifications 
   page
3. You should be prompted to upgrade your Moodle to install the module
4. Once installed there are no global settings for this activity.

Usage instructions
==================

1. The Peer Assessment module will now appear in the Add an activity or resource dialogue box

For more information, visit the following Moodle Docs Webpage
http://docs.moodle.org/en/Installing_contributed_modules_or_plugins
