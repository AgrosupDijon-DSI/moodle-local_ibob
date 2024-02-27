<?php
// This file is part of Moodle - http://moodle.org/
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
 * Plugin local_ibob.
 *
 * @package     local_ibob
 * @category    string
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['noBadgesFound'] = 'No badges found...';
$string['modalBadgeDetail'] = 'Badge details';
$string['pluginname'] = 'Inscription by open badges';
$string['profilebadgelist'] = 'Open Badges list';
$string['testbackpackapiurlexception'] = 'Error : cannot connect to the backpack';
$string['ibobprefs'] = 'Connect to your Open Badge Passport account (IBOB)';
$string['ibobprefslink'] = 'Manage your configuration';
$string['emaildescriptiontitle'] = 'Configure your Open Badge Passport email.';
$string['emaildescription'] = 'Type here the mail used in your Open Badge Passport account. Leave blank to delete your open badges from moodle.';
$string['invalidemail'] = 'Invalid email.';
$string['messageprovider:defaults'] = 'Email modification demand for Open Badge Passport (ibob)';
$string['messageprovider:enrolcreatedupdated'] = 'A new course accesible by your Open Badges is open';
$string['messageprovider:ibobemailchange'] = 'Open Badge Passport email change made';
$string['emailvalidated'] = 'Email validated';
$string['emailvalidatedno'] = 'No';
$string['emailvalidatedyes'] = 'Yes';
$string['addvalidationcodelink'] = 'Validation code received ? Type it here';
$string['emailconfirmationdescription'] = 'Put your confirmation code here';
$string['emailconfirmationcode'] = 'Code';
$string['emailconfirmationinvalidcode'] = 'Invalid code !';
$string['emailconfirmationexpirationdatereached'] = 'The expiration date has been reached...';
$string['emailconfirmationlinknewcode'] = 'Clic here to generate a new one';
$string['emailconfirmationreturn'] = 'Back';
$string['emailsequenceexplanation'] = 'You will receive an email with a validation code. Your account will not be updated until you confirm your email address.';
$string['invalidcode'] = 'Your code must be a 4 digit number';
$string['scheduledupdateusersbadgesname'] = 'Scheduled task : updating data user - badge';
$string['scheduleddeletenotifications'] = 'Scheduled task : delete old notifications';
$string['forceprocessing'] = 'Force processing adhoc tasks, to ensure a 5 minutes maximum gap between execution';
$string['mustachelibcreationdate'] = 'Created';
$string['mustachelibexpirationdate'] = 'Expires on';
$string['mustachelibobtained'] = 'Obtained on';
$string['mustachelibemitter'] = 'Emitter';
$string['multiplenotificationhtml'] = '<p>Your Open badges allow you to subscribe to new courses.</p><p>The following courses are now open to subscription :<br>{$a}</p>';
$string['multiplenotificationtxt'] = 'Your Open badges allow you to subscribe to new courses.\nThe following courses are now open to subscription :\n{$a}\n';
$string['singlenotificationhtml'] = '<p>Your Open badges allow you to subscribe to a new course.<br>The course "{$a}" is now open to subscription.';
$string['notifopmailsubject'] = 'New course, accessible by your Open Badges.';
$string['notifopmailfullmessagehtml'] = '<p>Hello,<br>Your Open Badges allow you to subscribe to a new course !</p><p>{$a} for course detail ans subscription.</p>';
$string['notifopmailfullmessage'] = 'Hello, your Open Badges allow you to subscribe to a new course !\n\n{$a} for course detail ans subscription.\n';
$string['subjectconfirmchangeemail'] = 'Email change confirmation for your Open Badge Passport account in Moodle';
$string['messconfirmchangeemaillinktext'] = 'email confirmation';
$string['messconfirmchangeemail'] = 'Hello\n\nYou initiated a email change in the Moodle plateform {$a->wwwroot}
         for your Open Badge Passport account.\n\nIf it was you, you have to click the link {$a->link} and type the following confirmation code :
         {$a->code}\n\nThis code will be valid until {$a->date} at {$a->time}\n\nThanks for using {$a->wwwroot} and happy learning !';
$string['messconfirmchangeemailhtml'] = '<h1>Hello</h1><p>You initiated a email change in the Moodle plateform {$a->wwwroot}
         for your Open Badge Passport account.</p>If it was you, you have to click the link {$a->link} and type the following confirmation code :
         {$a->code}</strong></p>
         <p>This code will be valid until {$a->date} at {$a->time}</p><p>Thanks for using {$a->wwwroot} and happy learning !</p>';
$string['userconfig:errorgeneral'] = 'Error while updating provider : ';
$string['userconfig:error1'] = 'case 1<br>';
$string['userconfig:error2'] = 'case 2<br>';
$string['userconfig:error2'] = 'case 3<br>';
$string['emails_notifications:subject'] = 'Course "{$a}" has been modified';
$string['emails_notifications:fullmess1'] = 'Hello\n\n';
$string['emails_notifications:fullmess2'] = 'Course "{$a}" has been modified and you don\'t have anymore the required Open Badges to be enrolled.\n\n';
$string['emails_notifications:fullmess3'] = 'No action required, you are automatically disenrolled from this course.\n\n';
$string['emails_notifications:fullmess4'] = 'Thank you for using {$a} and good learning !';
$string['emails_notifications:fullmesshtml1'] = '<p>Hello</p>';
$string['emails_notifications:fullmesshtml2'] = '<p>Course "{$a}" has been modified and you don\'t have anymore the required Open Badges to be enrolled.</p>';
$string['emails_notifications:fullmesshtml3'] = '<p>No action required, you are automatically disenrolled from this course.</p>';
$string['emails_notifications:fullmesshtml4'] = '<p>Thank you for using {$a} and good learning !</p>';
$string['emails_notifications:click'] = 'Click here';

