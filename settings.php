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
 * Settings page.
 *
 * @package    block
 * @subpackage userprofile_update
 * @author     David Bogner
 * @copyright  2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // Group members
	$name = 'block_userprofile_update/showonlygroupmembers';
	$setting = new admin_setting_configcheckbox($name, get_string('showonlygroupmembers', 'block_userprofile_update'), get_string('showonlygroupmembersdesc', 'block_userprofile_update'), 0);
	$settings->add($setting);
    // Matching users
	$name = 'block_userprofile_update/showonlymatchingusers';
	$setting = new admin_setting_configcheckbox($name, get_string('showonlymatchingusers', 'block_userprofile_update'), get_string('showonlymatchingusersdesc', 'block_userprofile_update'), 0);
	$settings->add($setting);
    // Define the name of the setting
    $profile_field_choices = array();
    $profile_field_choices[''] = get_string('chooseprofilefield', 'block_userprofile_update'); // Default option

    // Query the user_info_field table to get the custom profile fields
    $profilefields = $DB->get_records('user_info_field');
    foreach ($profilefields as $field) {
        $profile_field_choices[$field->shortname] = $field->name;
    }

    $settings->add(new admin_setting_configselect(
            'block_userprofile_update/selectuserprofilefield',
            get_string('selectuserprofilefield', 'block_userprofile_update'),
            get_string('selectuserprofilefield_desc', 'block_userprofile_update'),
            '',
            $profile_field_choices // Populate choices dynamically
    ));
}

