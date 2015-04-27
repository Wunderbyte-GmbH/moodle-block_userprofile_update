This plugin lets users of the same group in a course with the capability "update userprofiles of own group" 
update user profiles and disable user profiles.
These profile fields can be updated:
-> email
-> firstname
-> lastname
-> all custom user profile fields created in /user/profile/index.php 
   (Site administration -> Users -> Accounts -> User profile fields)
   These profile fields have to be visible to other users (setting in User profile fields)
This plugin allows the use of the same e-mail adresses for different users.

Installation:
To install the plugin just copy the folder "userprofile_update" into moodle/blocks/.

Afterwards you have to go to http://your-moodle/admin (Site administration -> Notifications) to trigger the installation process.

Usage:
Setup the permissions for the role, that should be able to update user profiles.
Setup groups: Only group members can update the user profiles, if you want to use to update for an entier
course, then create a single group for the course and add all course participants to that group
Add the block to a course. The block shows a link to the page, where the user profiles can be updated

copyright  2014 www.edulabs.org http://www.edulabs.org
license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
You can receive a copy of the GNU General Public License at <http:www.gnu.org/licenses/>.
