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
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addmorecriteria'] = 'Add {no} more criteria';
$string['addmorecriteriastep'] = 'Add more criteria increments';
$string['addmorecriteriastep_help'] = 'The number of assessment criteria to append to the form when an educator clicks the button to add more criteria.';
$string['completiongradedpeers'] = 'Require peers graded';
$string['completiongradedpeers_desc'] = 'Students must grade all their peers';
$string['completiongradedpeers_help'] = 'When enabled, a student must grade all their peers for this requirement to be met.';
$string['defaultsettings'] = 'Default settings';
$string['defaultsettings_desc'] = 'The values to use as defaults when adding a new instance of this module to a course.';
$string['editsubmission'] = 'Edit submission';
$string['justification'] = 'Justification';
$string['justificationdisabled'] = 'Disabled';
$string['justificationhiddenfromstudents'] = 'Hidden from students';
$string['justificationintro'] = 'Add comments below justifying the scores you provided for each of your peers.';
$string['justificationnoteshidden'] = 'Note: your comments will be hidden from your peers and only visible to teaching staff.';
$string['justificationnotesvisibleanon'] = 'Note: your comments will be visible to your peers but anonymised, your username will not be shown next to comments you leave.';
$string['justificationnotesvisibleuser'] = 'Note: your comments and your username will be visible to your peers.';
$string['justificationvisibleanon'] = 'Visible anonymous';
$string['justificationvisibleuser'] = 'Visible with usernames';
$string['modulename'] = 'Peer Assessment';
$string['modulenameplural'] = 'Peer Assessments';
$string['modulename_help'] = 'The Peer Assessment activity is a group assignment submission combined with peer grading.<br />
For this activity, peer grading refers to the ability for students to assess the performance/contribution of their peer group, and if enabled, themselves, in relation to a group task. The group task is the file(s) submission component of the activity. The peer grading consists of a grade out of five and written comments on each student\'s performance.<br />
Final overall grades for each individual student are then calculated from the differential of their individual and group peer grade averages, multiplied by five, and then added to or subtracted from the overall group submission grade (out of 100).';
$string['noncompletionpenalty'] = 'Penalty for non-submission of marks';
$string['noncompletionpenalty_help'] = 'If a student has not submitted any marks for the assessment (has not assessed their peers), how much should they be penalised?';
$string['paweighting'] = 'Peer assessment weighting';
$string['paweighting_help'] = 'What percentage of the group\'s total mark should be peer assessed?';
$string['peernameisyou'] = '{$a} (you)';
$string['peerwork:addinstance'] = 'Add a peerwork activity';
$string['peerworkfieldset'] = 'Peer assessment settings';
$string['peerworkname'] = 'Peer assessment';
$string['peerworkname_help'] = '<strong>Description</strong><br />In the description field you can add your peer assessment instructions. We advise that this should include all details of the assignment (word count, number of files and accepted file types) and guidance around your peer grading criteria (explain range and what to look for). You can also add links to module handbooks with reference to assessment guidelines. We also recommend including information on the support available to students should they have any problems submitting their group task.';
$string['peerwork'] = 'Peer Assessment';
$string['pluginadministration'] = 'Peer Assessment administration';
$string['pluginname'] = 'Peer Assessment';
$string['provideminimumonecriterion'] = 'Please provide at least one criterion.';
$string['provideajustification'] = 'Please provide a justification.';
$string['ratingnforuser'] = 'Rating \'{$a->rating}\' for {$a->user}';
$string['requirejustification'] = 'Require justification';
$string['requirejustification_help'] = '
- Disabled: Students will not be required to add any comments justifying the scores given for each of their peers
- Hidden from students: Any comments left by students will be visible only to teachers and hidden from their peers
- Visible anonymous: Any comments left by students will be visible to their peers but the identities of those leaving comments will be hidden
- Visible with usernames: Any comments left by students will be visible to their peers along with the identities of those leaving the feedback
';
$string['search:activity'] = 'Peer work - activity information';
$string['grade'] = 'Grade';
$string['feedback'] = 'Feedback to group';
$string['peers'] = 'Grade your peers';
$string['assessment'] = 'assessment';
$string['assignment'] = 'Assignment';
$string['selfgrading'] = 'Allow students to self-grade along with peers';
$string['selfgrading_help'] = 'Enabling this setting will allow students to score themselves alongside their peers. This score will be included in the final grade calculations.';
$string['duedate'] = 'Due date';

$string['submission'] = 'Submission(s)';
$string['submission_help'] = 'File(s) submitted by the group. <strong>Note:</strong> The maximum number of files can be adjusted in the peer assessment settings.';
$string['nothingsubmitted'] = 'Nothing submitted yet.';

$string['feedbackfiles'] = 'Feedback Files';
$string['selfgrading_help'] = 'If enabled, students will be able to give themselves a peer grade and feedback, along with the other members of their group. This will then be counted towards their and the overall groups peer grade averages.';
$string['duedate_help'] = 'This is when the peer assessment is due. Submissions will still be allowed after this date (if enabled).<br />
<strong>Note:</strong> All student file submissions and peer grading will become uneditable to the students after grading.';

$string['setup.maxfiles'] = 'Maximum number of uploaded files';
$string['setup.maxfiles_help'] = 'The maximum number of files the group will be able to upload for their submission.<br/>' .
'Setting to zero will remove the file upload ability completely.';

//Simple is the default, original calculation. It totals the marks awarded by peers and uses those to decide the proportion of the tutors final grade to award. Simple does not calculate standard deviation.<br />
//Outlier includes a standard deviation moderation.';

$string['standard_deviation'] = 'Maximum standard deviation to not be classed an outlier';
$string['standard_deviation_help'] = 'Average grades more than this standard deviation will be classed outliers and will be moderated.';
$string['defaultstandard_deviation'] = 'Default Standard Deviation';

$string['moderation'] = 'Mark moderation';
$string['moderation_help'] = 'Average grades which differ more than this number from the group average will be moderated .';
$string['defaultmoderation'] = 'Default Mark Moderation';


$string['contibutionscore'] = "Contribution";
$string['contibutionscore_help'] = "This is the webPA score which is the relative contribution made by group members";


$string['multiplyby'] = 'This is the multiplier used to calculate the final mark';
$string['multiplyby_help'] = 'This is the number used to multiply the average by to get the final mark moderation.';
$string['defaultmultiplyby'] = 'Default Multiplier';

$string['fromdate'] = 'Allow submissions from';
$string['fromdate_help'] = 'If enabled, students will not be able to submit before this date. If disabled, students will be able to start submitting right away.';
$string['notifylatesubmissions'] = 'Notify graders about late submissions';
$string['notifylatesubmissions_help'] = 'If enabled, graders (usually teachers) receive a message whenever a student submits their peer grades or peer grades and file submission late. Message methods are configurable.';
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
$string['peerwork:submit'] = 'Submit peer grades';
$string['peerwork:view'] = 'View peer assessment content';
// $string['teamsubmission'] = 'Students submit in groups';
// $string['teamsubmission_help'] = 'If enabled students will be divided into groups based on the default set of groups or a custom grouping. A group submission will be shared among group members and all members of the group will see each others changes to the submission.';


// Criteria strings
$string['assessmentcriteria:header'] = 'Assessment criteria settings'; 
$string['assessmentcriteria:description'] = 'Criteria {no} description';
$string['assessmentcriteria:scoretype'] = 'Criteria {no} scoring type';
$string['assessmentcriteria:weight'] = 'Weight';
$string['assessmentcriteria:modgradetypescale'] = "Likert";

$string['assessmentcriteria:description_help'] = 'Use this to concisely describe the purpose of this criteria';
$string['assessmentcriteria:scoretype_help'] = 'Choose the scale by which this criteria is to be graded';
$string['assessmentcriteria:weight_help'] = 'TODO not yet used';
$string['assessmentcriteria:nocriteria'] = 'No Criteria have been set for this assignment.';


$string['treat0asgrade'] = 'Treat 0 as grade';
$string['treat0asgrade_help'] = 'If enabled, students will be able to submit 0 as a valid grade. Otherwise, 0 means "not graded" and is not used for calculation';
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

/*** EVENTS ***/
$string['eventsubmission_viewed'] = 'peerwork view submit assignment form';
$string['eventsubmission_created'] = 'peerwork submission created';
$string['eventsubmission_updated'] = 'peerwork submission updated';
$string['eventsubmission_files_uploaded'] = 'peerwork file upload';
$string['eventsubmission_files_deleted'] = 'peerwork file delete';
$string['eventpeer_grade_created'] = 'peerwork peer grade';
$string['eventpeer_feedback_created'] = 'peerwork peer feedback';
$string['eventassessable_submitted'] = 'peerwork submit';
$string['eventsubmission_grade_form_viewed'] = 'peerwork view grading form';
$string['eventsubmission_graded'] = 'peerwork grade';
$string['eventsubmission_exported'] = 'peerwork export';
$string['eventsubmissions_exported'] = 'peerwork export all';

$string['multiplegroups'] = 'The following people belong to more than one group: {$a}. Their grades have not been updated.';
$string['messageprovider:late_submission'] = 'Late submission';

$string['privacy:metadata:peerwork_submission'] = 'Information about the group submissions made in a Peer Assessment';
$string['privacy:metadata:peerwork_submission:id'] = 'The ID of the user who has made a submission in Peer Assessment';
$string['privacy:metadata:peerwork_submission:assignment'] = 'The ID of the Peer Assessment';
$string['privacy:metadata:peerwork_submission:userid'] = 'The ID of the user who has created a Peer Assessment';
$string['privacy:metadata:peerwork_submission:timecreated'] = 'The time that the submission was submitted in Peer Assessment';
$string['privacy:metadata:peerwork_submission:timemodified'] = 'If the submission has been modified. The time that the submission was modified in Peer Assessment';
$string['privacy:metadata:peerwork_submission:status'] = 'Not used';
$string['privacy:metadata:peerwork_submission:groupid'] = 'The ID of the group who has made a submission in Peer Assessment';
$string['privacy:metadata:peerwork_submission:attemptnumber'] = 'The ID of the group who has made a submission in Peer Assessment';
$string['privacy:metadata:peerwork_submission:grade'] = 'The grade that the group submission was given by the lecturer in Peer Assessment';
$string['privacy:metadata:peerwork_submission:feedbacktext'] = 'The feedback text given to the group given by the lecturer who graded the group submission in Peer Assessment';
$string['privacy:metadata:peerwork_submission:feedbackformat'] = 'TRUE or NULL';
$string['privacy:metadata:peerwork_submission:timegraded'] = 'The time that the group submission was graded in Peer Assessment';
$string['privacy:metadata:peerwork_submission:gradedby'] = 'The ID of the lecturer who graded the group submission in Peer Assessment';
$string['privacy:metadata:peerwork_submission:finalgrade'] = 'Not used';
$string['privacy:metadata:peerwork_submission:groupaverage'] = 'Not used';
$string['privacy:metadata:peerwork_submission:individualaverage'] = 'Not used';

$string['privacy:metadata:peerwork_peers'] = 'Information about the peer grades and feedback given in a Peer Assessment';
$string['privacy:metadata:peerwork_peers:id'] = 'The ID of the feedback in Peer Assessment';
$string['privacy:metadata:peerwork_peers:peerwork'] = 'The ID of the Peer Assessment';
$string['privacy:metadata:peerwork_peers:groupid'] = 'The ID of the group who has made a submission in Peer Assessment';
$string['privacy:metadata:peerwork_peers:grade'] = 'The grade given to a group member by a group peer in Peer Assessment';
$string['privacy:metadata:peerwork_peers:groupid'] = 'The ID of the group who has submitted in Peer Assessment';
$string['privacy:metadata:peerwork_peers:gradedby'] = 'The ID of the user who has graded a peer in Peer Assessment';
$string['privacy:metadata:peerwork_peers:gradefor'] = 'The ID of the user who has been graded by a peer in Peer Assessment';
$string['privacy:metadata:peerwork_peers:feedback'] = 'The feedback given to a group member by a group peer in Peer Assessment';
$string['privacy:metadata:peerwork_peers:timecreated'] = 'The time that the submission was submitted in Peer Assessment';