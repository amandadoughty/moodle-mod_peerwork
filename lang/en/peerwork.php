<?php
// This file is part of a 3rd party created module for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings.
 *
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activitydate:closed'] = 'Closed: ';
$string['activitydate:closes'] = 'Due: ';
$string['activitydate:opened'] = 'Opened: ';
$string['activitydate:opens'] = 'Opens: ';
$string['addmorecriteria'] = 'Add {no} more criteria';
$string['addmorecriteriastep'] = 'Add more criteria increments';
$string['addmorecriteriastep_help'] = 'The number of assessment criteria to append to the form when an educator clicks the button to add more criteria.';
$string['addsubmission'] = 'Add submission';
$string['assessmentalreadygraded'] = 'Assessment already graded.';
$string['assessmentclosedfor'] = 'Assessment closed for: {$a}';
$string['assessmentopen'] = 'Assessment open.';
$string['assessmentnotopenyet'] = 'Assessment not open yet.';
$string['availablescales'] = 'Available scales.';
$string['availablescales_help'] = 'Scales this calculator can use.';
$string['base'] = 'Base calculator';
$string['calcmissing'] = 'The calculator used to apply PA weighting is not available. {$a}';
$string['calcmissinggraded'] = 'Please be aware that changing the calculator settings will result in changes to students final grades.';
$string['calculatedgrade'] = 'Calculated grade';
$string['calculatedgrade_help'] = 'The grade prior to applying weighting and penalties.';
$string['calculatedgrades'] = 'Calculated grades';
$string['calculatortypes'] = 'Calculator settings';
$string['calculator'] = 'Calculator';
$string['calculator_help'] = 'The calculator method to use.';
$string['calculatorplugins'] = 'Calculator plugins';
$string['calculatorupdate'] = 'Update calculator';
$string['charactersremaining'] = '{$a} character(s) remaining';
$string['clearallsubmissionsforallgroups'] = 'Clear all submissions';
$string['clearsubmission'] = 'Clear submission';
$string['criteria'] = 'Criteria';
$string['criterianum'] = 'Criteria {$a}';
$string['critscale'] = 'Criteria scoring type';
$string['critscale_help'] = 'The scale by which the criteria are to be graded.';
$string['comment'] = 'Comment: ';
$string['comments'] = 'Comments';
$string['comments_help'] = 'Required comment giving reason for override. This will not be visible to students. It will be recorded in the logs.';
$string['completiongradedpeers'] = 'Grade peers in group';
$string['completiongradedpeers_desc'] = 'Students must grade all their peers';
$string['completiongradedpeers_help'] = 'When enabled, a student must grade all their peers for this requirement to be met.';
$string['confimrclearsubmission'] = 'Are you sure that you would like to clear the submission of this group? This will remove the information provided by all students.';
$string['confimrclearsubmissions'] = 'Are you sure that you would like to clear the submission for all groups? This will remove the information provided by all students.';
$string['confirmeditgrade'] = 'Grading before due date';
$string['confirmeditgradetxt'] = 'The due date has not passed. If you grade now then students will no longer be able to edit submissions. Do you wish to continue?';
$string['confirmlockeditingaware'] = 'You will no longer be allowed to make changes to your submission and peer grades once they have been saved. Are you sure that you would like to continue?';
$string['confirmunlockeditinggrader'] = 'The grades given by {$a} are currently locked. Would you like to unlock them and allow this student to change any of their grades or justifications? This takes effect immediately.';
$string['confirmunlockeditingsubmission'] = 'Editing the submission is currently locked. Would you like to unlock it and allow students to update the submission? This takes effect immediately.';
$string['defaultsettings'] = 'Default settings';
$string['defaultsettings_desc'] = 'The values to use as defaults when adding a new instance of this module to a course.';
$string['displaypeergradestotals'] = 'Display peer grades totals';
$string['displaypeergradestotals_help'] = 'When enabled, students will be shown the total of their peer grades as a percentage for each criterion. Note that for the total to be displayed, the peer grades must be visible.';
$string['defaultcrit'] = 'Default criteria Settings (Optional)';
$string['defaultcrit_desc'] = 'Default values for up to 5 criteria and their corresponding scale';
$string['defaultcrit0'] = 'Default text - Criteria 1';
$string['defaultcrit0_help'] = 'The default text to use for the first criteria';
$string['defaultcrit1'] = 'Default text - Criteria 2';
$string['defaultcrit1_help'] = 'The default text to use for the second criteria';
$string['defaultcrit2'] = 'Default text - Criteria 3';
$string['defaultcrit2_help'] = 'The default text to use for the third criteria';
$string['defaultcrit3'] = 'Default text - Criteria 4';
$string['defaultcrit3_help'] = 'The default text to use for the fourth criteria';
$string['defaultcrit4'] = 'Default text - Criteria 5';
$string['defaultcrit4_help'] = 'The default text to use for the fifth criteria';
$string['defaultscale0'] = 'Default scale - Criteria 1';
$string['defaultscale0_help'] = 'The default scale to use for the first criteria.';
$string['defaultscale1'] = 'Default scale - Criteria 2';
$string['defaultscale1_help'] = 'The default scale to use for the second criteria.';
$string['defaultscale2'] = 'Default scale - Criteria 3';
$string['defaultscale2_help'] = 'The default scale to use for the third criteria.';
$string['defaultscale3'] = 'Default scale - Criteria 4';
$string['defaultscale3_help'] = 'The default scale to use for the fourth criteria.';
$string['defaultscale4'] = 'Default scale - Criteria 5';
$string['defaultscale4_help'] = 'The default scale to use for the fifth criteria.';
$string['defaultscale'] = 'Default scale';
$string['defaultscale_help'] = 'The default scale to use for all other criteria.';
$string['downloadallsubmissions'] = 'Download all submissions';
$string['draftnotsubmitted'] = 'Draft (not submitted).';
$string['duedateat'] = 'Due date: {$a}';
$string['duedatenotpassed'] = 'The due date has not passed. If you grade now then students will no longer be able to edit submissions.';
$string['duedatepassedago'] = 'Due date has passed {$a} ago.';
$string['editablebecause'] = 'Editable because: {$a}';
$string['editgrade'] = 'Edit grade for group: {$a}';
$string['editinglocked'] = 'Editing is locked';
$string['editsubmission'] = 'Edit submission';
$string['eventgradebookupdatefailed'] = 'peerwork gradebook update';
$string['eventgradesreleased'] = 'Grades released';
$string['eventsubmissioncleared'] = 'Submission cleared';
$string['eventsubmissionsdownloaded'] = 'Submissions downloaded';
$string['export'] = 'Export';
$string['finalweightedgrade'] = 'Final weighted grade';
$string['firstsubmittedbyon'] = 'First submitted by {$a->name} on {$a->date}.';
$string['grade'] = 'Grade';
$string['gradebefore'] = 'Grade before overrides: {$a}';
$string['gradecannotberemoved'] = 'The grade cannot be removed.';
$string['gradedby'] = 'Graded by';
$string['gradedbyon'] = 'Graded by {$a->name} on {$a->date}.';
$string['gradedon'] = 'Graded on';
$string['gradegivento'] = '<strong>Grade for</strong>';
$string['gradesgivenby'] = '<h2>Grades given by {$a}</h2>';
$string['gradesexistmsg'] = 'Some grades have already been released, so the calculator type cannot be changed. If you wish to change the calculator, you must first choose whether or not to recalculate existing grades.';
$string['gradeoverridden'] = 'Overridden peer grade: {$a}';
$string['gradeoverride'] = 'Final grade';
$string['groupgrade'] = 'Group grade';
$string['groupgradeoutof100'] = 'Group grade out of 100';
$string['gradesandfeedbacksaved'] = 'The grades and feedback have been saved.';
$string['groupsubmissionsettings'] = 'Group submission settings';
$string['groupsubmittedon'] = 'Group submitted on';
$string['hideshow'] = 'Hide/Show';
$string['invalidgrade'] = 'Invalid grade';
$string['invalidpaweighting'] = 'Invalid weighting';
$string['invalidscale'] = 'Invalid scale. Please select from options above.';
$string['justification'] = 'Justification';
$string['justification_help'] = 'Enable/disable justification comments and select visibility.';
$string['justificationbyfor'] = 'By {$a} for';
$string['justificationdisabled'] = 'Disabled';
$string['justificationhiddenfromstudents'] = 'Hidden from students';
$string['justificationintro'] = 'Add comments below justifying the scores you provided for each of your peers.';
$string['justificationmaxlength'] = 'Justification character limit';
$string['justificationmaxlength_help'] = 'The maximum number of characters allowed in justification fields. You may set this value to 0 to remove the limit.';
$string['justificationnoteshidden'] = 'Note: your comments will be hidden from your peers and only visible to teaching staff.';
$string['justificationnotesvisibleanon'] = 'Note: your comments will be visible to your peers but anonymised, your username will not be shown next to comments you leave.';
$string['justificationnotesvisibleuser'] = 'Note: your comments and your username will be visible to your peers.';
$string['justifications'] = 'Justifications';
$string['justificationtype'] = 'Justification type';
$string['justificationtype_help'] = 'Peer justifcation requires a comment for each peer. Criteria justification requires a comment for each criteria grade.';
$string['justificationtype0'] = 'Peer';
$string['justificationtype1'] = 'Criteria';
$string['justificationvisibleanon'] = 'Visible anonymous';
$string['justificationvisibleuser'] = 'Visible with usernames';
$string['lasteditedon'] = 'Last edited on {$a->date}.';
$string['latesubmissionsallowedafterduedate'] = 'After due date but late submissions allowed.';
$string['latesubmissionsnotallowedafterduedate'] = 'After due date and late submissions not allowed.';
$string['lockediting'] = 'Lock editing';
$string['lockediting_help'] = 'When enabled, submission and peer grades cannot be changed once they have been submitted by a student. Teachers can unlock editing for individual students while submissions are otherwise allowed.';
$string['managepeerworkcalculatorplugins'] = 'Manage peerwork calculator plugins';
$string['messageprovider:grade_released'] = 'Grade and feedback published';
$string['myfinalgrade'] = 'My final grade';
$string['modulename'] = 'Peer Assessment';
$string['modulenameplural'] = 'Peer Assessments';
$string['modulename_help'] = 'The Peer Assessment activity is a group assignment submission combined with peer grading.<br />
For this activity, peer grading refers to the ability for students to assess the performance/contribution of their peer group, and if enabled, themselves, in relation to a group task. The group task is the file(s) submission component of the activity. The peer grading consists of a choice of grade scales and written comments on each student\'s performance.<br />
Final overall grades for each individual student are then calculated by the selected calculator method';
$string['nocalculator'] = 'There are no calculators installed. Students will all recieve the group mark subject to non-completion penalty';
$string['nomembers'] = '# members';
$string['noncompletionpenalty'] = 'Penalty for non-submission of marks';
$string['noncompletionpenalty_help'] = 'If a student has not submitted any marks for the assessment (has not assessed their peers), how much should they be penalised?';
$string['none'] = 'None. ';
$string['nonegiven'] = 'None given';
$string['nonereceived'] = 'None received';
$string['nopeergrades'] = '# peer grades';
$string['noteditablebecause'] = 'Not editable because: {$a}';
$string['noteoverdueby'] = '(over due by {$a})';
$string['notifygradesreleasedsmall'] = 'Your grade for \'{$a}\' has been published.';
$string['notifygradesreleasedtext'] = 'The grade and feedback for your submission in \'{$a->name}\' have been published. You can access them here: {$a->url}';
$string['notifygradesreleasedhtml'] = 'The grade and feedback for your submission in \'<em>{$a->name}</em>\' have been published. You can access them <a href="{$a->url}">here</a>.';
$string['nothingsubmittedyet'] = 'Nothing submitted yet.';
$string['nothingsubmittedyetduedatepassednago'] = 'Nothing submitted yet but due date passed {$a} ago.';
$string['notyetgraded'] = 'Not yet graded';
$string['numcrit'] = 'Default number of criteria';
$string['numcrit_help'] = 'The default number of criteria to include. There are 5 default lang strings';
$string['overridden'] = 'Overridden';
$string['override'] = 'Override';
$string['overridepeergrades'] = 'Override peer grades.';
$string['overridepeergrades_help'] = 'When enabled, teachers will be able to override the grades given by students to their peers.';
$string['overridepeergradesby'] = 'Override peer grades given by: ';
$string['paweighting'] = 'Peer assessment weighting';
$string['paweighting_help'] = 'What percentage of the group\'s total mark should be peer assessed?';
$string['penalty'] = 'Penalty';
$string['peergradesvisibility'] = 'Peer grades visibility';
$string['peergradesvisibility_help'] = 'This setting determines whether students can see the peer grades they received.

- Hidden from students: Students will not see their peer scores at all
- Visible anonymous: Students will see their peer scores, but not the usernames of those that scored them
- Visible with usernames: Students will see their peer scores, and the names of those who scored them
';
$string['peergradeshiddenfromstudents'] = 'Hidden from students';
$string['peergradesvisibleanon'] = 'Visible anonymous';
$string['peergradesvisibleuser'] = 'Visible with usernames';
$string['peergrades'] = 'Peer grades';
$string['peergradetotal'] = 'Total: {$a}';
$string['peernameisyou'] = '{$a} (you)';
$string['peerratedyou'] = '{$a->name}: {$a->grade}';
$string['peersaid'] = '{$a}:';
$string['peersubmissionandgrades'] = 'Peer submission and grades';
$string['peerwork:addinstance'] = 'Add a peerwork activity';
$string['recalculategrades'] = 'Recalculate grades';
$string['recalculategrades_help'] = 'Grades have been released. You may only change the calculator if you accept that all grades will be recalculated.';
$string['subplugintype_peerworkcalculator'] = 'Grading calculator method';
$string['subplugintype_peerworkcalculator_plural'] = 'Grading calculator methods';
$string['peerworkcalculatorpluginname'] = 'Calculator plugin';
$string['peerworkfieldset'] = 'Peer assessment settings';
$string['peerworkname'] = 'Peer assessment';
$string['peerworkname_help'] = '<strong>Description</strong><br />In the description field you can add your peer assessment instructions. We advise that this should include all details of the assignment (word count, number of files and accepted file types) and guidance around your peer grading criteria (explain range and what to look for). You can also add links to module handbooks with reference to assessment guidelines. We also recommend including information on the support available to students should they have any problems submitting their group task.';
$string['peerwork'] = 'Peer Assessment';
$string['pleaseexplainoverride'] = 'Please give your reason for overriding this peer grade.';
$string['pleaseproviderating'] = 'Please provide a rating for each one of your peers.';
$string['pluginadministration'] = 'Peer Assessment administration';
$string['pluginname'] = 'Peer Work';
$string['privacy:metadata:core_files'] = 'The plugins stores submission and feedback files.';
$string['privacy:metadata:grades'] = 'Information about the grades computed and given by educators';
$string['privacy:metadata:grades:grade'] = 'The grade given to the student';
$string['privacy:metadata:grades:prelimgrade'] = 'The WebPA calculated grade prior to applying weighting and penalties';
$string['privacy:metadata:grades:revisedgrade'] = 'The revised grade which takes precedence over the grade if any';
$string['privacy:metadata:grades:userid'] = 'The ID of the user who provided the justification';
$string['privacy:metadata:justification'] = 'The justification provided by students for the grade given to a peer';
$string['privacy:metadata:justification:gradedby'] = 'The ID of the user who provided the justification';
$string['privacy:metadata:justification:gradefor'] = 'The ID of the user who received the grade';
$string['privacy:metadata:justification:justification'] = 'The justification left';
$string['privacy:metadata:peers'] = 'Information about the peer grades and feedback given';
$string['privacy:metadata:peers:comments'] = 'The comments made about the grade by the user who overrode it';
$string['privacy:metadata:peers:feedback'] = 'The feedback given to a group member by a group peer';
$string['privacy:metadata:peers:grade'] = 'The final grade given to a group member by a group peer';
$string['privacy:metadata:peers:gradedby'] = 'The ID of the user who has graded a peer';
$string['privacy:metadata:peers:gradefor'] = 'The ID of the user who has been graded by a peer';
$string['privacy:metadata:peers:overriddenby'] = 'The user who overrode the original peer grade given';
$string['privacy:metadata:peers:peergrade'] = 'The original grade given to a group member by a group peer';
$string['privacy:metadata:peers:timecreated'] = 'The time at which the grade was submitted';
$string['privacy:metadata:peers:timemodified'] = 'The time at which the grade was updated';
$string['privacy:metadata:peers:timeoverridden'] = 'The time at which the peer grade was overridden';
$string['privacy:metadata:submission'] = 'Information about the group submissions made';
$string['privacy:metadata:submission:feedbacktext'] = 'The feedback given to the group given by the grader';
$string['privacy:metadata:submission:grade'] = 'The grade that the group submission was given by the grader';
$string['privacy:metadata:submission:gradedby'] = 'The ID of the user who graded the submission';
$string['privacy:metadata:submission:groupid'] = 'The ID of the group this submission is from';
$string['privacy:metadata:submission:paweighting'] = 'The WebPA weight used by the grader for this submission';
$string['privacy:metadata:submission:released'] = 'The time at which the grades were released';
$string['privacy:metadata:submission:releasedby'] = 'The ID of the user who released the grades';
$string['privacy:metadata:submission:timecreated'] = 'The time at which the submission was submitted';
$string['privacy:metadata:submission:timegraded'] = 'The time at which the submission was graded';
$string['privacy:metadata:submission:timemodified'] = 'If the submission has been modified, the time at which the submission was modified';
$string['privacy:metadata:submission:userid'] = 'The ID of the user who has created the submission';
$string['privacy:path:grade'] = 'Grade';
$string['privacy:path:submission'] = 'Submission';
$string['privacy:path:peergrades'] = 'Peer grades';
$string['provideminimumonecriterion'] = 'Please provide at least one criterion.';
$string['provideajustification'] = 'Please provide a justification.';
$string['ratingnforuser'] = 'Rating \'{$a->rating}\' for {$a->user}';
$string['releaseallgradesforallgroups'] = 'Release all grades for all groups';
$string['releasedby'] = 'Released by';
$string['releasedbyon'] = 'Grades released by {$a->name} on {$a->date}';
$string['releasedon'] = 'Released on';
$string['releasegrades'] = 'Release grades';
$string['requirejustification'] = 'Require justification';
$string['requirejustification_help'] = '
- Disabled: Students will not be required to add any comments justifying the scores given for each of their peers
- Hidden from students: Any comments left by students will be visible only to teachers and hidden from their peers
- Visible anonymous: Any comments left by students will be visible to their peers but the identities of those leaving comments will be hidden
- Visible with usernames: Any comments left by students will be visible to their peers along with the identities of those leaving the feedback
';
$string['revisedgrade'] = 'Revised grade';
$string['revisedgrade_help'] = 'Use this field to override the final weighted grade, if needed. However if the grade has been overidden or locked in the gradebook then it cannot be edited.';
$string['search:activity'] = 'Peer work - activity information';
$string['studentcalculatedgrade'] = 'Student calculated grade';
$string['studentcontribution'] = 'Student contribution';
$string['studentondate'] = '{$a->fullname} on {$a->date}';
$string['studentrevisedgrade'] = 'Student revised grade';
$string['studentfinalgrade'] = 'Student final grade';
$string['studentfinalweightedgrade'] = 'Student final weighted grade';
$string['submissionstatus'] = 'Submission status';
$string['tasknodifystudents'] = 'Notify students';
$string['thesestudentspastduedate'] = 'These students submitted past the due date: {$a}.';
$string['timeremaining'] = 'Time remaining';
$string['timeremainingcolon'] = 'Time remaining: {$a}';
$string['tutorgrading'] = 'Tutor grading';
$string['youbelongtomorethanonegroup'] = 'You belong to more than one group, this is currently not supported.';
$string['youdonotbelongtoanygroup'] = 'You do not belong to any group.';
$string['youwereawardedthesepeergrades'] = 'For this criterion you were awarded the following scores from your peers.';
$string['feedback'] = 'Feedback to group';
$string['peers'] = 'Grade your peers';
$string['assessment'] = 'assessment';
$string['assignment'] = 'Assignment';
$string['selfgrading'] = 'Allow students to self-grade along with peers';
$string['selfgrading_help'] = 'Enabling this setting will allow students to score themselves alongside their peers. This score will be included in the final grade calculators.';
$string['duedate'] = 'Due date';

$string['submission'] = 'Submission(s)';
$string['submission_help'] = 'File(s) submitted by the group. <strong>Note:</strong> The maximum number of files can be adjusted in the peer assessment settings.';
$string['nothingsubmitted'] = 'Nothing submitted yet.';

$string['feedbackfiles'] = 'Feedback files';
$string['selfgrading_help'] = 'If enabled, students will be able to give themselves a peer grade and feedback, along with the other members of their group. This will then be counted towards their and the overall groups peer grade averages.';
$string['duedate_help'] = 'This is when the peer assessment is due. Submissions will still be allowed after this date (if enabled).<br />
<strong>Note:</strong> All student file submissions and peer grading will become uneditable to the students after grading.';

$string['setup.maxfiles'] = 'Maximum number of uploaded files';
$string['setup.maxfiles_help'] = 'The maximum number of files the group will be able to upload for their submission.<br/>Setting to zero will remove the file upload ability completely.';

$string['contibutionscore'] = "Contribution";
$string['contibutionscore_help'] = "This is the PA score which is the relative contribution made by group members";

$string['fromdate'] = 'Allow submissions from';
$string['fromdate_help'] = 'If enabled, students will not be able to submit before this date. If disabled, students will be able to start submitting right away.';
$string['allowlatesubmissions'] = 'Allow late submissions';
$string['allowlatesubmissions_help'] = 'If enabled, submissions will still be allowed after the due date.<br />
<strong>Note:</strong> Once the group grade has been saved and the final grades calculated, the student\'s submissions will become uneditable or locked. This is the stop tampering of the final grade by students amending their peer grades.';
$string['submissiongrading'] = 'File submission';
$string['submissiongrading_help'] = 'File(s) submitted by the group. <strong>Note:</strong> The maximum number of files can be adjusted in the peer assessment settings.';
$string['groupaverage'] = 'Group Average grade';
$string['groupaverage_help'] = 'This is the overall average of peer grades for the group.';
$string['finalgrades'] = 'Final grades';
$string['finalgrades_help'] = 'The final grade is calculated from adding or subtracting the individual/group average differential that is multiplied by five. The outcome is dependent on whether the individual\'s average is greater or lesser than the group\'s average.';
$string['teacherfeedback'] = 'Grader feedback';
$string['teacherfeedback_help'] = 'This is the feedback given by the grader.';
$string['latesubmissionsubject'] = 'Late submission';
$string['latesubmissiontext'] = 'Late submission have been submitted in {$a->name} by {$a->user}.';
$string['peerwork:grade'] = 'Grade assignments and peer grades';
$string['peerwork:view'] = 'View peer assessment content';

$string['assessmentcriteria:header'] = 'Assessment criteria settings';
$string['assessmentcriteria:description'] = 'Criteria {no} description';
$string['assessmentcriteria:scoretype'] = 'Criteria {no} scoring type';
$string['assessmentcriteria:weight'] = 'Weight';
$string['assessmentcriteria:modgradetypescale'] = "Likert";

$string['assessmentcriteria:description_help'] = 'Use this to concisely describe the purpose of this criteria';
$string['assessmentcriteria:scoretype_help'] = 'Choose the scale by which this criteria is to be graded';
$string['assessmentcriteria:weight_help'] = 'TODO not yet used';
$string['assessmentcriteria:nocriteria'] = 'No Criteria have been set for this assignment.';

$string['userswhodidnotsubmitbefore'] = 'Users who still need to submit: {$a}';
$string['userswhodidnotsubmitafter'] = 'Users who did not submit: {$a}';
$string['allmemberssubmitted'] = 'All group members submitted.';
$string['confirmationmailsubject'] = 'Peer assessment submission for {$a}';
$string['confirmationmailbody'] = 'You have submitted peer assessment {$a->url} at {$a->time}.
File(s) attached:
{$a->files}

Grades you have submitted:
{$a->grades}';
$string['exportxls'] = 'Export all group grades';

$string['eventsubmission_viewed'] = 'peerwork view submit assignment form';
$string['eventsubmission_created'] = 'peerwork submission created';
$string['eventsubmission_updated'] = 'peerwork submission updated';
$string['eventsubmission_files_uploaded'] = 'peerwork file upload';
$string['eventsubmission_files_deleted'] = 'peerwork file delete';
$string['eventpeer_grade_created'] = 'peerwork peer grade';
$string['eventpeer_grade_overridden'] = 'peerwork peer grade overridden';
$string['eventpeer_feedback_created'] = 'peerwork peer feedback';
$string['eventassessable_submitted'] = 'peerwork submit';
$string['eventsubmission_grade_form_viewed'] = 'peerwork view grading form';
$string['eventsubmission_graded'] = 'peerwork grade';
$string['eventsubmission_exported'] = 'peerwork export';
$string['eventsubmissions_exported'] = 'peerwork export all';

$string['multiplegroups'] = 'The following people belong to more than one group: {$a}. Their grades have not been updated.';


