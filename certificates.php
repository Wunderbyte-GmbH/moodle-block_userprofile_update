<?php
// This file is part of the customcert module for Moodle - http://moodle.org/
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
 * Handles viewing the certificates for a certain user.
 *
 * @package    mod_customcert
 * @copyright  based on 2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$userid = optional_param('userid', $USER->id, PARAM_INT);
$download = optional_param('download', null, PARAM_ALPHA);
$coursecontext = optional_param('coursecontext', null, PARAM_INT);

$courseid = optional_param('courseid', null, PARAM_INT);
$downloadcert = optional_param('downloadcert', '', PARAM_BOOL);
if ($downloadcert) {
    $certificateid = required_param('certificateid', PARAM_INT);
    $customcert = $DB->get_record('customcert', ['id' => $certificateid], '*', MUST_EXIST);

    // Check there exists an issued certificate for this user.
    if (!$issue = $DB->get_record('customcert_issues', ['userid' => $userid, 'customcertid' => $customcert->id])) {
        throw new moodle_exception('You have not been issued a certificate');
    }
}

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', \mod_customcert\certificate::CUSTOMCERT_PER_PAGE, PARAM_INT);
$pageurl = $url = new moodle_url('/blocks/userprofile_update/certificate.php', ['userid' => $userid,
    'page' => $page, 'perpage' => $perpage]);

// Requires a login.
if ($courseid) {
    require_login($courseid);
} else {
    require_login();
}

// Check that we have a valid user.
$user = \core_user::get_user($userid, '*', MUST_EXIST);
//$coursecontext = CONTEXT_COURSE::instance($courseid);

// If we are viewing certificates that are not for the currently logged in user then do a capability check.
// if (($userid != $USER->id) && !has_capability('block/userprofile_update:updateuserprofile', $coursecontext)) {
//     throw new moodle_exception('You are not allowed to view these certificates');
// }

$profilefieldpartnerid = get_config('block_userprofile_update', 'partnerid');
profile_load_custom_fields($USER);
$partnerid = $USER->profile[$profilefieldpartnerid] ?: 0;
profile_load_custom_fields($user);
$extpartnerid = $user->profile[$profilefieldpartnerid] ?: 0;

if (($partnerid == 0) || ($partnerid != $extpartnerid)) {
    throw new moodle_exception('You are not allowed to view these certificates');
}

$PAGE->set_url($pageurl);
$PAGE->set_context(context_user::instance($userid));
$PAGE->set_title(get_string('certificate', 'customcert'));
$PAGE->set_pagelayout('standard');

// Check if we requested to download a certificate.
if ($downloadcert) {
    $template = $DB->get_record('customcert_templates', ['id' => $customcert->templateid], '*', MUST_EXIST);
    $template = new \mod_customcert\template($template);
    $template->generate_pdf(false, $userid);
    exit();
}

$table = new \block_userprofile_update\my_certificates_table($userid, $download);
$table->define_baseurl($pageurl);

if ($table->is_downloading()) {
    $table->download();
    exit();
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('privacy:metadata:customcert_issues', 'customcert') . ': ' . $user->firstname . ' ' . $user->lastname);
$table->out($perpage, false);
echo $OUTPUT->footer();
