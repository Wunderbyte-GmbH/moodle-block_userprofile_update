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
 * Class containing data for timeline block.
 *
 * @package    block_userprofile_update
 * @copyright  2024 David Bogner Wunderbyte GmbH <davidbogner@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_userprofile_update;
defined('MOODLE_INTERNAL') || die();

use stdClass;

/**
 * Class containing static function for userprofile_update
 *
 * @copyright  2024 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userprofileupdate {

    /**
     * Update user profile fields to match the logged in users profile.
     *
     * @param stdClass $newuser
     * @return stdClass
     */
    public static function update_userprofile_fields (\stdClass $usernew, ?stdClass $user = null): stdClass {
        global $USER, $DB;

        if ($user === null) {
            $user = $USER;
        }
        profile_load_custom_fields($user);
        $userprofileconfig = block_userprofile_update_get_config();
        $createpassword = !empty ($usernew->createpassword);
        // Update partnerid.
        $field = $userprofileconfig['profilepartnerid'];
        $fieldid = $DB->get_field('user_info_field', 'id',  ['shortname' => $field]);
        $data = new stdClass();
        $data->userid = $usernew->id;
        $data->fieldid = $fieldid;
        $data->data = $user->profile[$userprofileconfig['profilepartnerid']];
        if ($dataid = $DB->get_field('user_info_data', 'id', array('userid' => $usernew->id, 'fieldid' => $fieldid))) {
            $data->id = $dataid;
            $DB->update_record('user_info_data', $data);
        } else {
            $DB->insert_record('user_info_data', $data);
        }
        // Update tenant.
        $field = $userprofileconfig['profiletenant'];
        $fieldid = $DB->get_field('user_info_field', 'id',  ['shortname' => $field]);
        $data = new stdClass();
        $data->fieldid = $fieldid;
        $data->userid = $usernew->id;
        $data->data = $user->profile[$userprofileconfig['profiletenant']];
        if ($dataid = $DB->get_field('user_info_data', 'id', array('userid' => $usernew->id, 'fieldid' => $fieldid))) {
            $data->id = $dataid;
            $DB->update_record('user_info_data', $data);
        } else {
            $DB->insert_record('user_info_data', $data);
        }
        // Update partner program / status.
        $field = $userprofileconfig['profilestatusfield'];
        $fieldid = $DB->get_field('user_info_field', 'id',  ['shortname' => $field]);
        $data = new stdClass();
        $data->fieldid = $fieldid;
        $data->userid = $usernew->id;
        $data->data = $user->profile[$userprofileconfig['profilestatusfield']];
        if ($dataid = $DB->get_field('user_info_data', 'id', array('userid' => $usernew->id, 'fieldid' => $fieldid))) {
            $data->id = $dataid;
            $DB->update_record('user_info_data', $data);
        } else {
            $DB->insert_record('user_info_data', $data);
        }
        // Update password.
        unset ($usernew->createpassword);
        if (empty ($usernew->auth)) {
            // User editing self.
            $authplugin = get_auth_plugin($user->auth);
            unset ($usernew->auth); // Can not change/remove.
        } else {
            $authplugin = get_auth_plugin($usernew->auth);
        }
        if ($authplugin->is_internal()) {
            if ($createpassword || empty ($usernew->newpassword)) {
                $usernew->password = '';
            } else {
                $usernew->password = hash_internal_user_password($usernew->newpassword);
            }
        } else {
            $usernew->password = AUTH_PASSWORD_NOT_CACHED;
        }
        // Update usermanager: The admin can assign an employee to manage users. It is saved in user profile department.
        if (isset($usernew->usermanager) && $usernew->usermanager === "1") {
            $usernew->department = "usermanager";
        } else {
            $usernew->department = "";
        }
        return $usernew;
    }
}