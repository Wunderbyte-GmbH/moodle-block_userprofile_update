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
 * Configuration page.
 *
 * @package    block
 * @subpackage userprofile_update
 * @author     David Bogner
 * @copyright  2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_userprofile_update\userprofileupdate;

require_once('../../config.php');
require_once('userprofile_update_form.php');
require_once('lib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/authlib.php');
require_once($CFG->dirroot . '/user/filters/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/blocks/userprofile_update/classes/userprofile_update.php');

$courseid = required_param('courseid', PARAM_INT);
$parentcontextid = required_param('parentcontextid', PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM); // Md5 confirmation hash.
$confirmuser = optional_param('confirmuser', 0, PARAM_INT);
$sort = optional_param('sort', 'name', PARAM_ALPHANUM);
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 10, PARAM_INT); // How many per page.
$ru = optional_param('ru', '2', PARAM_INT); // Show remote users.
$lu = optional_param('lu', '2', PARAM_INT); // Show local users.
$acl = optional_param('acl', '0', PARAM_INT); // Id of user to tweak mnet ACL (requires $access).
$suspend = optional_param('suspend', 0, PARAM_INT);
$unsuspend = optional_param('unsuspend', 0, PARAM_INT);
$unlock = optional_param('unlock', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array(
    'id' => $courseid
))) {
    throw new moodle_exception('invalidaccess');
}

require_login($course);

$url = new moodle_url ('/blocks/userprofile_update/userprofile_update.php', array(
    'courseid' => $courseid,
    'parentcontextid' => $parentcontextid,
));

$PAGE->set_url($url);
$context = CONTEXT_SYSTEM::instance();
$coursecontext = CONTEXT_COURSE::instance($courseid);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'block_userprofile_update'));
$PAGE->set_heading(get_string('title', 'block_userprofile_update'));
$PAGE->set_context($coursecontext);
$PAGE->navbar->add(get_string('userprofile_update:updateuserprofile', 'block_userprofile_update'), $url);
require_capability('block/userprofile_update:updateuserprofile', $coursecontext);

// If the block is not added to this course, do not display user update possibility.
if ($coursecontext->id != $parentcontextid) {
    throw new moodle_exception('invalidaccess');
}

$header = get_string('userprofile_update:updateuserprofile', 'block_userprofile_update');
$groupmembersonly = get_config('block_userprofile_update', 'showonlygroupmembers');
$tenantmatchonly = get_config('block_userprofile_update', 'showonlymatchingusers');
$userprofileconfig = block_userprofile_update_get_config();

$stredit = get_string('edit');
$strdelete = get_string('delete');
$strdeletecheck = get_string('deletecheck');
$strshowallusers = get_string('showallusers');
$strsuspend = get_string('suspenduser', 'admin');
$strunsuspend = get_string('unsuspenduser', 'admin');
$strunlock = get_string('unlockaccount', 'admin');
$strconfirm = get_string('confirm');
if ($userid > 0) {
    $user = core_user::get_user($userid);
    $usermanager = 0;
    if ($user->department === 'usermanager') {
        $usermanager = 1;
    }
    /*
    $user = $DB->get_record('user', array(
        'id' => $userid
    ), '*', MUST_EXIST);
    */
    $userform = new block_userprofile_update_form (null, array(
        'userid' => $userid,
        'parentcontextid' => $parentcontextid,
        'courseid' => $courseid,
        'username' => $user->username,
        'firstname' => $user->firstname,
        'usermanager' => $usermanager,
    ));
    $userform->set_data($user);
} else if ($userid == -1) {
    $user = new stdClass ();
    $user->id = -1;
    $user->auth = 'manual';
    $user->confirmed = 1;
    $user->deleted = 0;
    $userform = new block_userprofile_update_form (null, array(
            'userid' => $userid,
            'parentcontextid' => $parentcontextid,
            'courseid' => $courseid,
            'username' => block_userprofile_update_create_username($USER),
    ));
    $userform->set_data($user);
} else {
    $userform = new block_userprofile_update_form (null, array(
        'userid' => $userid,
        'parentcontextid' => $parentcontextid,
        'courseid' => $courseid,
        'username' => block_userprofile_update_create_username($USER),
    ));
}

if (empty ($CFG->loginhttps)) {
    $securewwwroot = $CFG->wwwroot;
} else {
    $securewwwroot = str_replace('http:', 'https:', $CFG->wwwroot);
}

$returnurl = new moodle_url ($url->out(), array(
    'sort' => $sort,
    'dir' => $dir,
    'perpage' => $perpage,
    'page' => $page
));
if ($userform->is_cancelled()) {
    redirect($returnurl);
}

if ($confirmuser && confirm_sesskey()) {
    require_capability('block/userprofile_update:updateuserprofile', $coursecontext);
    if (!$user = $DB->get_record('user', array(
        'id' => $confirmuser,
        'mnethostid' => $CFG->mnet_localhost_id
    ))) {
        throw new moodle_exception('nousers');
    }

    $auth = get_auth_plugin($user->auth);

    $result = $auth->user_confirm($user->username, $user->secret);

    if ($result == AUTH_CONFIRM_OK || $result == AUTH_CONFIRM_ALREADY) {
        redirect($returnurl);
    } else {
        echo $OUTPUT->header();
        redirect($returnurl, get_string('usernotconfirmed', '', fullname($user, true)));
    }
} else if ($delete && confirm_sesskey()) { // Delete a selected user, after confirmation.
    require_capability('moodle/user:delete', $context);

    $user = $DB->get_record('user', array(
        'id' => $delete,
        'mnethostid' => $CFG->mnet_localhost_id
    ), '*', MUST_EXIST);
    profile_load_custom_fields($user);
    // ToDo!
    if (is_siteadmin($user->id) || $user) {
        throw new moodle_exception('useradminodelete', 'error');
    }

    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();
        $fullname = fullname($user, true);
        echo $OUTPUT->heading(get_string('deleteuser', 'admin'));
        $optionsyes = array(
            'delete' => $delete,
            'confirm' => md5($delete),
            'sesskey' => sesskey()
        );
        echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$fullname'"), new moodle_url ($returnurl, $optionsyes),
            $returnurl);
        echo $OUTPUT->footer();
        die ();
    } else if (data_submitted() && !$user->deleted) {
        if (delete_user($user)) {
            session_gc(); // Remove stale sessions.
            redirect($returnurl);
        } else {
            session_gc(); // Remove stale sessions.
            echo $OUTPUT->header();
            echo $OUTPUT->notification($returnurl, get_string('deletednot', '', fullname($user, true)));
        }
    }
} else if ($acl && confirm_sesskey()) {
    if (!has_capability('moodle/user:update', $context)) {
        throw new moodle_exception('nopermissions', 'error', '', 'modify the NMET access control list');
    }
    if (!$user = $DB->get_record('user', array(
        'id' => $acl
    ))) {
        throw new moodle_exception('nousers', 'error');
    }
    if (!is_mnet_remote_user($user)) {
        throw new moodle_exception('usermustbemnet', 'error');
    }
    $accessctrl = strtolower(required_param('accessctrl', PARAM_ALPHA));
    if ($accessctrl != 'allow' && $accessctrl != 'deny') {
        throw new moodle_exception('invalidaccessparameter', 'error');
    }
    $aclrecord = $DB->get_record('mnet_sso_access_control', array(
        'username' => $user->username,
        'mnet_host_id' => $user->mnethostid
    ));
    if (empty ($aclrecord)) {
        $aclrecord = new stdClass ();
        $aclrecord->mnet_host_id = $user->mnethostid;
        $aclrecord->username = $user->username;
        $aclrecord->accessctrl = $accessctrl;
        $DB->insert_record('mnet_sso_access_control', $aclrecord);
    } else {
        $aclrecord->accessctrl = $accessctrl;
        $DB->update_record('mnet_sso_access_control', $aclrecord);
    }
    $mnethosts = $DB->get_records('mnet_host', null, 'id', 'id,wwwroot,name');
    redirect($returnurl);
} else if ($suspend && confirm_sesskey()) {
    require_capability('block/userprofile_update:suspenduser', $coursecontext);

    if ($user = $DB->get_record('user', array(
        'id' => $suspend,
        'mnethostid' => $CFG->mnet_localhost_id,
        'deleted' => 0
    ))) {
        if (!is_siteadmin($user) && $USER->id != $user->id && $user->suspended != 1) {
            $user->suspended = 1;
            $user->timemodified = time();
            $DB->set_field('user', 'suspended', $user->suspended, array(
                'id' => $user->id
            ));
            $DB->set_field('user', 'timemodified', $user->timemodified, array(
                'id' => $user->id
            ));
            // Force logout.
            \core\session\manager::kill_user_sessions($user->id);
            \core\event\user_updated::create_from_userid($user->id)->trigger();
        }
    }
    redirect($returnurl);
} else if ($unsuspend && confirm_sesskey()) {
    require_capability('block/userprofile_update:suspenduser', $coursecontext);

    if ($user = $DB->get_record('user', array(
        'id' => $unsuspend,
        'mnethostid' => $CFG->mnet_localhost_id,
        'deleted' => 0
    ))) {
        if ($user->suspended != 0) {
            $user->suspended = 0;
            $user->timemodified = time();
            $DB->set_field('user', 'suspended', $user->suspended, array(
                'id' => $user->id
            ));
            $DB->set_field('user', 'timemodified', $user->timemodified, array(
                'id' => $user->id
            ));
            \core\event\user_updated::create_from_userid($user->id)->trigger();
        }
    }
    redirect($returnurl);
} else if ($unlock && confirm_sesskey()) {
    require_capability('moodle/user:update', $context);

    if ($user = $DB->get_record('user', array(
        'id' => $unlock,
        'mnethostid' => $CFG->mnet_localhost_id,
        'deleted' => 0
    ))) {
        login_unlock_account($user);
    }
    redirect($returnurl);
} else if ($usernew = $userform->get_data()) {

    $usernew->id = $usernew->userid;
    // Check if user has right to edit the user.
    if (!has_capability('moodle/site:config', $context)) {
        if (!($groupmembersonly || $tenantmatchonly)) {
            echo $OUTPUT->header();
            echo $OUTPUT->error_text('Invalid access');
            echo $OUTPUT->continue_button($url);
            echo $OUTPUT->footer();
            die ();
        }
        if ($tenantmatchonly) {
            $allowedusers = block_userprofile_update_get_matchingusers($userprofileconfig['partnerid'],
                    $userprofileconfig['profilepartnerid'],
                    $USER->id);
            $alloweduserids = array_keys($allowedusers);
            if (!(in_array($usernew->id, $alloweduserids)) && $usernew->id != -1) {
                echo $OUTPUT->header();
                echo $OUTPUT->error_text('Invalid access');
                echo $OUTPUT->continue_button($url);
                echo $OUTPUT->footer();
                die ();
            }
        }
        if ($groupmembersonly) {
            $editingallowed = false;
            $groupsofuser = groups_get_all_groups($courseid, $USER->id);
            if (!empty ($groupsofuser)) {
                foreach ($groupsofuser as $group) {
                    if (groups_is_member($group->id, $usernew->id) || $usernew->id == -1) {
                        $editingallowed = true;
                    }
                }
            }
            if (!$editingallowed) {
                echo $OUTPUT->header();
                echo $OUTPUT->error_text('Invalid access');
                echo $OUTPUT->continue_button($url);
                echo $OUTPUT->footer();
                die ();
            }
        }
    }
    $usercreated = false;

    if (empty ($usernew->auth)) {
        // User editing self.
        $authplugin = get_auth_plugin($user->auth);
        unset ($usernew->auth); // Can not change/remove.
    } else {
        $authplugin = get_auth_plugin($usernew->auth);
    }
    $usernew->timemodified = time();

    if ($usernew->id == -1) {
        if ($DB->record_exists('user', array(
            'username' => $usernew->username
        ))) {
            echo $OUTPUT->header();
            echo $OUTPUT->error_text('Username exists already');
            echo $OUTPUT->continue_button($url);
            echo $OUTPUT->footer();
            die ();
        }
        unset ($usernew->id);
        $createpassword = !empty($usernew->createpassword);
        unset ($usernew->createpassword);
        $usernew->mnethostid = $CFG->mnet_localhost_id; // Always local user.
        $usernew->confirmed = 1;
        $usernew->timecreated = time();
        $usernew->city = $USER->city;
        $usernew->country = $USER->country;
        $usernew->lang = 'de';
        if ($usernew->usermanager === "1") {
            $usernew->department = 'usermanager';
        }
        unset($usernew->usermanager);

        if ($authplugin->is_internal()) {
            if ($createpassword || empty ($usernew->newpassword)) {
                $usernew->password = '';
            } else {
                $usernew->password = hash_internal_user_password($usernew->newpassword);
            }
        } else {
            $usernew->password = AUTH_PASSWORD_NOT_CACHED;
        }
        $usernew->confirmed = 1;
        $usernew->id = user_create_user($usernew, false, false);
        $fields = profile_get_user_fields_with_data($usernew->id);
        foreach ($fields as $formfield) {
            $formfield->edit_save_data($usernew);
        }
        $usernew = userprofileupdate::update_userprofile_fields($usernew);
        $usercreated = true;
    } else {
        $usernew = userprofileupdate::update_userprofile_fields($usernew);
        $DB->update_record('user', $usernew);
    }

    // Reload from db.
    $usernew = $DB->get_record('user', array(
        'id' => $usernew->id
    ));
    set_user_preference('auth_forcepasswordchange', 1, $usernew);

    if ($usercreated) {
        \core\event\user_created::create_from_userid($usernew->id)->trigger();
    }
    redirect($url, get_string('changessaved'));
}

// Create the user filter form.
$ufiltering = new user_filtering (null, $url);

echo $OUTPUT->header();

if ($userid > 0) {
    $user = $DB->get_record('user', array(
        'id' => $userid
    ), '*', MUST_EXIST);
    $userform->set_data($user);
    $userform->display();
    echo $OUTPUT->footer();
    exit ();
} else if ($userid === -1) {
    $userform->set_data($user);
    $userform->display();
    echo $OUTPUT->footer();
    exit ();
}
// Carry on with the user listing.
$context = context_system::instance();
$fields = \core_user\fields::for_identity($context)->including("profile_field_" . $userprofileconfig['profilepartnerid']);
$extracolumns = $fields->get_required_fields();

// TODO: extra fields to display.

$columns = array_merge(array(
    'firstname',
    'lastname'
), $extracolumns, array(
    'city',
    'country',
));

foreach ($columns as $column) {
    $namedcolumns[$column] = \core_user\fields::get_display_name($column);
    if ($sort != $column) {
        $columnicon = "";
        if ($column == "lastaccess") {
            $columndir = "DESC";
        } else {
            $columndir = "ASC";
        }
    } else {
        $columndir = $dir == "ASC" ? "DESC" : "ASC";
        if ($column == "lastaccess") {
            $columnicon = ($dir == "ASC") ? "sort_desc" : "sort_asc";
        } else {
            $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
        }
        $columnicon = "<img class='iconsort' src=\"" . $OUTPUT->image_url('t/' . $columnicon) . "\" alt=\"\" />";
    }
    // $column = "<a href=\"" . $url->out() . "&sort=$column&amp;dir=$columndir\">" . $string [$column] . "</a>$columnicon";
}

// Add action columns.
$namedcolumns['edit'] = get_string('edit');
$namedcolumns['suspend']  = get_string('suspenduser', 'admin');
$namedcolumns['cert']  = get_string('certificate', 'mod_customcert');

$override = new stdClass ();
$override->firstname = 'firstname';
$override->lastname = 'lastname';
$fullnamelanguage = get_string('fullnamedisplay', '', $override);
if (($CFG->fullnamedisplay == 'firstname lastname') || ($CFG->fullnamedisplay == 'firstname') ||
    ($CFG->fullnamedisplay == 'language' && $fullnamelanguage == 'firstname lastname')) {
    $fullnamedisplay = "firstname / lastname";
    if ($sort == "name") { // If sort has already been set to something else then ignore.
        $sort = "firstname";
    }
} else {
    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    /* ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'lastname firstname'). */
    $fullnamedisplay = "$lastname / $firstname";
    if ($sort == "name") { // This should give the desired sorting based on fullnamedisplay.
        $sort = "lastname";
    }
}
// Default: do not show any users.
$displayuserssql = ' id = 0 ';
// Check group members of user who has capability to edit user profiles in this course and collect them in array.
if (has_capability('block/userprofile_update:updateuserprofile', $coursecontext)) {
    $allmembers = array();
    if ($groupmembersonly) {
        $groupsofuser = groups_get_all_groups($courseid, $USER->id);
        $memberidasstring = '';
        $allmembers = array();
        if (!empty ($groupsofuser)) {
            foreach ($groupsofuser as $group) {
                $groupmemberids [$group->id] = groups_get_members($group->id, 'u.id');
                if (!empty ($groupmemberids [$group->id])) {
                    foreach ($groupmemberids [$group->id] as $id => $value) {
                        $allmembers [$id] = $id;
                    }
                }
            }
        }
    }
    if ($tenantmatchonly) {
        $matchingusers = block_userprofile_update_get_matchingusers($userprofileconfig['partnerid'],
                $userprofileconfig['profilepartnerid'],
                $USER->id);
        $tenantpartnerids = block_userprofile_update_get_tenant_partners();
        foreach ($tenantpartnerids as $userid) {
            if (isset($matchingusers[$userid])) {
                unset($matchingusers[$userid]);
            }
        }
        // Remove partner admin from list, if user is an employee allowed to create users.
        profile_load_custom_fields($USER);
        $partnerfield = get_config('block_userprofile_update', 'ispartner');
        if (!($USER->department === "usermanager") || ($USER->profile[$partnerfield] === "0")) {
            // Unset user who is partner.
            unset($matchingusers[$USER->id]);
        }
        if (!empty ($matchingusers)) {
            foreach ($matchingusers as $id => $value) {
                $allmembers[$id] = $id;
            }
        }
    }
}

if (!empty ($allmembers)) {
    $useridsasstring = implode(',', $allmembers);
    $displayuserssql = ' id IN (' . $useridsasstring . ')';
}

if (has_capability('moodle/site:config', $context)) {
    $displayuserssql = '';
}

list ($extrasql, $params) = $ufiltering->get_sql_filter($displayuserssql);
$users = get_users_listing($sort, $dir, $page * $perpage, $perpage, '', '', '', $extrasql, $params, $context);
$usercount = get_users(false);
$usersearchcount = get_users(false, '', false, null, "", '', '', '', '', '*', $extrasql, $params);

if ($extrasql !== '') {
    echo $OUTPUT->heading("$usersearchcount " . get_string('users'));
    $usercount = $usersearchcount;
} else {
    echo $OUTPUT->heading("$usercount " . get_string('users'));
}

$strall = get_string('all');

$baseurl = new moodle_url ($url->out(), array(
    'sort' => $sort,
    'dir' => $dir,
    'perpage' => $perpage
));
echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);

flush();

if (!$users) {
    $match = array();
    echo $OUTPUT->heading(get_string('nousersfound'));

    $table = null;
} else {

    $countries = get_string_manager()->get_list_of_countries(false);
    if (empty ($mnethosts)) {
        $mnethosts = $DB->get_records('mnet_host', null, 'id', 'id,wwwroot,name');
    }

    foreach ($users as $key => $user) {
        if (isset ($countries [$user->country])) {
            $users[$key]->country = $countries[$user->country];
        }
    }
    if ($sort == "country") { // Need to resort by full country name, not code.
        foreach ($users as $user) {
            $susers [$user->id] = $user->country;
        }
        asort($susers);
        foreach ($susers as $key => $value) {
            $nusers [] = $users [$key];
        }
        $users = $nusers;
    }

    $table = new html_table ();
    $table->head = $namedcolumns;
    $table->id = "users";
    foreach ($users as $user) {
        $lastcolumn = '';
        $buttons = array();
        $buttons['suspend'] = '';
        $buttons['edit'] = '';
        $buttons['cert'] = html_writer::link(new moodle_url ('./certificates.php', array(
                    'userid' => $user->id,
                    'courseid' => $courseid,
                )), html_writer::empty_tag('img', [
                    'src' => $OUTPUT->image_url('monologo', 'mod_customcert'),
                    'alt' => get_string('certificate', 'mod_customcert'),
                    'class' => 'iconsmall',
                ]), array(
                    'title' => get_string('certificate', 'mod_customcert'),
                    'target' => '_blank'
                ));
        $buttons ['delete'] = '';
        // Delete button.
        if (!(is_mnet_remote_user($user) || $user->id == $USER->id || is_siteadmin($user))) {
            // No deleting of self, mnet accounts or admins allowed.
            $buttons ['delete'] = html_writer::link(new moodle_url ($returnurl, array(
                'delete' => $user->id,
                'sesskey' => sesskey()
            )), html_writer::empty_tag('img', array(
                'src' => $OUTPUT->image_url('t/delete'),
                'alt' => $strdelete,
                'class' => 'iconsmall'
            )), array(
                'title' => $strdelete
            ));
        }

        // Suspend button.
        if (has_capability('block/userprofile_update:suspenduser', $coursecontext)) {
            if (is_mnet_remote_user($user)) {
                // Mnet users have special access control, they can not be deleted the standard way or suspended.
                $accessctrl = 'allow';
                if ($acl = $DB->get_record('mnet_sso_access_control', array(
                    'username' => $user->username,
                    'mnet_host_id' => $user->mnethostid
                ))) {
                    $accessctrl = $acl->accessctrl;
                }
                $changeaccessto = ($accessctrl == 'deny' ? 'allow' : 'deny');
                $buttons ['suspend'] =
                    " (<a href=\"?acl={$user->id}&amp;accessctrl=$changeaccessto&amp;sesskey=" . sesskey() . "\">" .
                    get_string($changeaccessto, 'mnet') . " access</a>)";
            } else {
                if ($user->suspended) {
                    $buttons ['suspend'] = html_writer::link(new moodle_url ($returnurl, array(
                        'unsuspend' => $user->id,
                        'sesskey' => sesskey()
                    )), html_writer::empty_tag('img', array(
                        'src' => $OUTPUT->image_url('t/show'),
                        'alt' => $strunsuspend,
                        'class' => 'iconsmall'
                    )), array(
                        'title' => $strunsuspend
                    ));
                } else {
                    if (!($user->id == $USER->id || is_siteadmin($user))) {
                        // No suspending of admins or self!
                        $buttons ['suspend'] = html_writer::link(new moodle_url ($returnurl, array(
                            'suspend' => $user->id,
                            'sesskey' => sesskey()
                        )), html_writer::empty_tag('img', array(
                            'src' => $OUTPUT->image_url('t/hide'),
                            'alt' => $strsuspend,
                            'class' => 'iconsmall'
                        )), array(
                            'title' => $strsuspend
                        ));
                    }
                }

                if (login_is_lockedout($user)) {
                    $buttons ['suspend'] = html_writer::link(new moodle_url ($returnurl, array(
                        'unlock' => $user->id,
                        'sesskey' => sesskey()
                    )), html_writer::empty_tag('img', array(
                        'src' => $OUTPUT->image_url('t/unlock'),
                        'alt' => $strunlock,
                        'class' => 'iconsmall'
                    )), array(
                        'title' => $strunlock
                    ));
                }
            }
        }

        // Edit button.
        if (has_capability('block/userprofile_update:updateuserprofile', $coursecontext)) {
            // Prevent editing of admins by non-admins.
            if (is_siteadmin($USER) || !is_siteadmin($user)) {
                $buttons ['edit'] = html_writer::link(new moodle_url ($url->out(), array(
                    'userid' => $user->id
                )), html_writer::empty_tag('img', array(
                    'src' => $OUTPUT->image_url('t/edit'),
                    'alt' => $stredit,
                    'class' => 'iconsmall'
                )), array(
                    'title' => $stredit
                ));
            }
        }

        // The last column - confirm or mnet info.
        if (is_mnet_remote_user($user)) {
            // All mnet users are confirmed, let's print just the name of the host there.
            if (isset ($mnethosts [$user->mnethostid])) {
                $lastcolumn = get_string($accessctrl, 'mnet') . ': ' . $mnethosts [$user->mnethostid]->name;
            } else {
                $lastcolumn = get_string($accessctrl, 'mnet');
            }
        } else if ($user->confirmed == 0) {
            if (has_capability('moodle/user:update', $context)) {
                $userediturl = '/admin/user.php';
                $lastcolumn = html_writer::link(new moodle_url ($userediturl, array(
                    'confirmuser' => $user->id,
                    'sesskey' => sesskey()
                )), $strconfirm);
            } else {
                $lastcolumn = "<span class=\"dimmed_text\">" . get_string('confirm') . "</span>";
            }
        }

        if ($user->lastaccess) {
            $strlastaccess = format_time(time() - $user->lastaccess);
        } else {
            $strlastaccess = get_string('never');
        }
        $fullname = fullname($user, true);
        profile_load_data($user);

        $row = array();
        $row [] = $user->firstname;
        $row [] = $user->lastname;
        /* "<a href=\"../../user/view.php?id=$user->id&amp;course=$courseid\">$user->firstname $user->lastname</a>"; */
        foreach ($extracolumns as $field) {
            $row [] = $user->$field;
        }
        $row [] = $user->city;
        $row [] = $user->country;
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /*$row [] = $strlastaccess; */
        if ($user->suspended) {
            foreach ($row as $k => $v) {
                $row [$k] = html_writer::tag('span', $v, array(
                    'class' => 'usersuspended'
                ));
            }
        }
        $row[] = $buttons['edit'];
        $row[] = $buttons['suspend'];
        $row[] = $buttons['cert'];
        $row[] = $lastcolumn;
        $table->data[] = $row;
    }
}

// Add filters.
$ufiltering->display_add();
$ufiltering->display_active();

if (has_capability('block/userprofile_update:createuser', $coursecontext)) {
    echo $OUTPUT->heading('<a class="btn btn-primary" href="' . $url->out(true, array(
            'userid' => -1
        )) . '" >' . get_string('addnewuser') . '</a>');
}
if (!empty ($table)) {
    echo html_writer::start_tag('div', array(
        'class' => 'no-overflow'
    ));
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
    echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
}

echo $OUTPUT->footer();
