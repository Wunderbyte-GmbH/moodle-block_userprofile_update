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
 * Automatically create a username according to a given tenant
 * Default: username of creator plus prefix
 *
 * @param  \stdClass $creator  user who creates new user
 * @return string username
 */
function block_userprofile_update_create_username(stdClass $creator): string {
    $blockconfig = block_userprofile_update_get_config();
    (array)$usernumber = array();

    // Get all users that have the same partnerid.
    $partnerusers = block_userprofile_update_get_matchingusers($blockconfig['partnerid'], $blockconfig['profilepartnerid'],
            $creator->id);
    if (!empty($partnerusers)) {
        foreach ($partnerusers as $user) {
            $success = preg_match('/m(\d+?)_.+?/', $user->username, $matches);
            if ($success) {
                $usernumber[] = $matches[1];
            }
        }
    }
    if (!empty($usernumber)) {
        $newusernumber = max($usernumber) + 1;
        $username = 'm' . $newusernumber . "_" . $creator->profile[$blockconfig['profilepartnerid']];
    } else {
        $username = 'm1_'. $creator->profile[$blockconfig['profilepartnerid']];
    }
    // Define new username.
    return $username;
}

/**
 *  Get all users who have the same partnerid.
 *
 * @param string $partnerid PBL number
 * @param string $profilefieldshortname name of the profile field used to store the partnerid
 * @param int $userid the id of the partner usually $USER
 * @return array
 */
function block_userprofile_update_get_matchingusers(string $partnerid, string $profilefieldshortname, int $userid) {
    global $DB;
    if (empty($partnerid)) {
        return [];
    }
    // Construct the SQL query to retrieve users with the same "partnerid".
    $sql = "SELECT u.* 
        FROM {user} u
        INNER JOIN {user_info_data} pid ON u.id = pid.userid
        WHERE pid.data = :partnerid
        AND pid.fieldid = (SELECT id FROM {user_info_field} WHERE shortname = :fieldshortname)
        AND u.id != :userid";

    $params = array('partnerid' => $partnerid, 'fieldshortname' => $profilefieldshortname, 'userid' => $userid);
    // Execute the query.
    return $DB->get_records_sql($sql, $params);
}

/**
 *  Get config and profile field values for $USER.
 *  We get the partnerid of the user, the profile field name where it is saved and
 *  the tenantname and the profilefield short name where the tenant name ist saved.
 *
 * @return array with partnerid, tenant, profilepartnerid, profiletenant
 */
function block_userprofile_update_get_config(): array {
    global $USER;
    // Profile field where the partner id is saved.
    $profilefieldpartnerid = get_config('block_userprofile_update', 'partnerid');
    $profiletenant = get_config('block_userprofile_update', 'selecttenant');
    $ispartner = get_config('block_userprofile_update', 'ispartner');
    // Get the partnerid.
    profile_load_custom_fields($USER);
    $partnerid = $USER->profile[$profilefieldpartnerid] ?: 0;
    $tenantid = $USER->profile[$profiletenant] ?: '';
    return ['partnerid' => $partnerid,
            'tenant' => $tenantid,
            'ispartner' => $ispartner,
            'profilepartnerid' => $profilefieldpartnerid,
            'profiletenant' => $profiletenant];
}
