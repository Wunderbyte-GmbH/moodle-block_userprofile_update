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
 * En language file for the plugin.
 *
 * @package    block
 * @subpackage userprofile_update
 * @author     David Bogner
 * @copyright  2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'Userprofile update';
$string['title'] = 'Userprofile update';

$string['userprofile_update:addinstance'] = 'Add userprofile update block';
$string['userprofile_update:updateuserprofile'] = 'Update user profiles';
$string['userprofile_update:createuser'] = 'Create new user';
$string['userprofile_update:suspenduser'] = 'Nutzerkonto sperren';

$string['eventuserprofile_updated'] = 'User profile has been updated';
$string['showonlygroupmembers'] = 'Show only users that are members of the same group of the current user';
$string['showonlygroupmembersdesc'] = 'Allow editing and viewing of users belonging to the same group as the editing user only';
$string['showonlymatchingusers'] = 'Show only users that have the same tenant name in the tenant profile field of the current user';
$string['showonlymatchingusersdesc'] = 'Allow editing of users only for users who have the same tenant name in the profilefield
 that you have chosen to be the tenant profile field.';

$string['selecttenant'] = 'Select user profile field tu user for tenant';
$string['selecttenant_desc'] = 'Choose a custom user profile field where the avaible tenants are defined.
 You have to create it in /user/profile/index.php before you can select it here. It should be a dropdown list with the
 name of all available tenants.';

$string['partnerid'] = 'Partner ID';
$string['partnerid_desc'] = 'Select a custom user profile field where the partner IDs are defined.';

$string['ispartner'] = 'Is Partner';
$string['ispartner_desc'] = 'Select a custom user profile field to determine if the user is a partner.';
$string['partnerstatus'] = 'Status of the partner';
$string['partnerstatus_desc'] = 'Choose the field for programme where the status of the partner is saved';

$string['usermanager'] = 'Employee manager';
$string['canmanageusers'] = 'Can create and manage employees';
