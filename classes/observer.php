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
 * Event observer.
 *
 * @package    block_userprofile_update
 * @copyright  2024 David Bogner <davidbogner@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_userprofile_update\userprofileupdate;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/userprofile_update/lib.php');
require_once($CFG->dirroot . '/blocks/userprofile_update/classes/userprofile_update.php');

/**
 * Event observer.
 * Stores all actions about modules create/update/delete in plugin own's table.
 * This allows the block to avoid expensive queries to the log table.
 *
 * @package    block_userprofile_update
 * @copyright  2024 David Bogner <davidbogner@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_userprofile_update_observer {

    /**
     * Observer for the user_updated event
     *
     * @param \core\event\user_updated $event
     */
    public static function user_updated(\core\event\user_updated $event) {
        global $DB;

        $userid = $event->relateduserid;
        $userprofileconfig = block_userprofile_update_get_config();

        // Get user data.
        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        profile_load_custom_fields($user);
        $partneridfield = $userprofileconfig['profilepartnerid'] ?: 0;
        // Check if user is partner.
        $ispartnerfield = $userprofileconfig['ispartner'];
        if ($user->profile[$ispartnerfield] !== "1") {
            return;
        }
        // Fetch all users of the partner.
        $partnerusers = block_userprofile_update_get_matchingusers($user->profile[$partneridfield],
                $userprofileconfig['profilepartnerid'],
                $user->id);
        // Update user profile fields according to the profile fields of the partner user.
        if (!empty($partnerusers)) {
            foreach ($partnerusers as $partneruser) {
                profile_load_custom_fields($partneruser);
                $partneruser = userprofileupdate::update_userprofile_fields($partneruser, $user);
                // Does not do anything. Somehow we have to use the returned value.
                profile_save_data($partneruser);
            }
        }
    }
}
