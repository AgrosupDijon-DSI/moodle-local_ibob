# Inscription by open badges #

Ibob and Ibobenrol add a new enrolment method in courses, subject to the possession of a particular open badges.

It is a duo of plugins enabling users to enrol in courses via their Open Badges.


The first plugin lets you add a registration method to a course :
Teachers can add the "Enrolment by Open Badge" enrolment method to their courses.
He chooses the badge(s) required for the course from among the Open Badges held by platform users.
Access to the course is thus open to users who hold one or more of these Open Badges.

The second plugin manages notifications and emails sent to users, as well as the retrieval of public Open Badges stored in users' Open Badge Passport accounts and the updating of profiles in the Moodle platform via scheduled tasks.

## Changelog history ##
04/17/24  F. Grebot      version 1.0 released.

04/23/24  F. Grebot      version 2.0 released.

Implementation of a filter for the badges list, when you had the enrolment method.

03/03/25 F. Grebot      version 3.0 released.

New version for Moodle 4.5

## Functionning steps ##
### First step ###
The administrator must enable the new enrolment method (Open Badges Enrolment) in administration>Plugins>Manage enrol plugins
### Second step ###
The enrol method get all the open badges from Open Badge Passport, recorded in the platform.
To make it work, at least 1 user must have configured their Open Badge Passport account in Moodle. 
To configure their Open Badge Passport accounts : Preferences>Connect to your Open Badge Passport account (IBOB)>Manage your configuration
Only the public open badges of the user Open Badge Passport will be retrieved.
The more users who have configured their accounts, the more open badges will be available for use in the enrollment method.
### Third step ###
The teacher can now add the enrolment method to a course and select the open badges who will grant access to this course.
When the teacher save the configuration of this course, emails and notification will be send to the users who can now enrol in the this course.

## License ##

2025 Frédéric Grebot <frederic.grebot@agrosupdijon.fr>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.
