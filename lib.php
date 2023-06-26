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
 * Automatically create a username according to a given pattern
 * Default: username of creator plus prefix
 *
 * @param  $creator string user who creates new user
 * @return string username
 */
function block_userprofile_update_create_username($creator) {
    (string)$username = '';
    (array)$existingusers = array();
    (array)$usernumber = array();

    // Check which usernames already exists with pattern.
    $existingusers = block_userprofile_update_get_matchingusers($creator->username);
    if (!empty($existingusers)) {
        foreach ($existingusers as $user) {
            $success = preg_match('/m(\d+?)_.+?/', $user->username, $matches);
            if ($success) {
                $usernumber[] = $matches[1];
            }
        }
    }
    if (!empty($usernumber)) {
        $newusernumber = max($usernumber) + 1;
        $username = 'm' . $newusernumber . "_" . $creator->username;
    } else {
        $username = 'm1_'. $creator->username;
    }
    // Define new username.
    return $username;
}

/**
 * Get all users who match a username pattern and return them as array.
 * @param string $username
 * @return array users indexed by user id
 */
function block_userprofile_update_get_matchingusers($username) {
    global $DB;
    $sql = 'username LIKE \'m%' . $username . "'";
    $existingusers = $DB->get_records_select('user', $sql);
    return $existingusers;
}
