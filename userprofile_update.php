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
 * @author     David Bogner <info@edulabs.org>
 * @copyright  2014 www.edulabs.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('userprofile_update_form.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');

$courseid     = required_param('courseid', PARAM_INT);
$parentcontextid = required_param('parentcontextid', PARAM_INT);
$delete       = optional_param('delete', 0, PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash
$confirmuser  = optional_param('confirmuser', 0, PARAM_INT);
$sort         = optional_param('sort', 'name', PARAM_ALPHANUM);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 30, PARAM_INT);        // how many per page
$ru           = optional_param('ru', '2', PARAM_INT);            // show remote users
$lu           = optional_param('lu', '2', PARAM_INT);            // show local users
$acl          = optional_param('acl', '0', PARAM_INT);           // id of user to tweak mnet ACL (requires $access)
$suspend      = optional_param('suspend', 0, PARAM_INT);
$unsuspend    = optional_param('unsuspend', 0, PARAM_INT);
$unlock       = optional_param('unlock', 0, PARAM_INT);
$userid       = optional_param('userid', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
 print_error('invalidaccess');
}

require_login($course);

$url = new moodle_url('/blocks/userprofile_update/userprofile_update.php', array('courseid'=>$courseid, 'parentcontextid' => $parentcontextid));

$PAGE->set_url($url);
$context = CONTEXT_SYSTEM::instance();
$coursecontext = CONTEXT_COURSE::instance($courseid);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title','block_userprofile_update'));
$PAGE->set_heading(get_string('title','block_userprofile_update'));
$PAGE->set_context($coursecontext);
$PAGE->navbar->add(get_string('userprofile_update:updateuserprofile', 'block_userprofile_update'),$url);
require_capability('block/userprofile_update:updateuserprofile', $coursecontext);

// if the block is not added to this course, do not display user update possibility
if($coursecontext->id != $parentcontextid){
  print_error('invalidaccess');
}

$header = get_string('userprofile_update:updateuserprofile', 'block_userprofile_update');

$stredit   = get_string('edit');
$strdelete = get_string('delete');
$strdeletecheck = get_string('deletecheck');
$strshowallusers = get_string('showallusers');
$strsuspend = get_string('suspenduser', 'admin');
$strunsuspend = get_string('unsuspenduser', 'admin');
$strunlock = get_string('unlockaccount', 'admin');
$strconfirm = get_string('confirm');
$userform = new block_userprofile_update_form(null, array('userid' => $userid,'parentcontextid' => $parentcontextid,'courseid'=>$courseid));
if ($userid != 0) {
    $user = $DB->get_record('user', array('id'=>$userid), '*', MUST_EXIST);
    $userform->set_data($user);
}

if (empty($CFG->loginhttps)) {
    $securewwwroot = $CFG->wwwroot;
} else {
    $securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
}

$returnurl = new moodle_url($url->out(), array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'page'=>$page));
if ($userform->is_cancelled()) {
    redirect($returnurl);
}

if ($confirmuser and confirm_sesskey()) {
    require_capability('block/userprofile_update:updateuserprofile', $context);
    if (!$user = $DB->get_record('user', array('id'=>$confirmuser, 'mnethostid'=>$CFG->mnet_localhost_id))) {
        print_error('nousers');
    }

    $auth = get_auth_plugin($user->auth);

    $result = $auth->user_confirm($user->username, $user->secret);

    if ($result == AUTH_CONFIRM_OK or $result == AUTH_CONFIRM_ALREADY) {
        redirect($returnurl);
    } else {
        echo $OUTPUT->header();
        redirect($returnurl, get_string('usernotconfirmed', '', fullname($user, true)));
    }

} else if ($delete and confirm_sesskey()) {              // Delete a selected user, after confirmation
    require_capability('moodle/user:delete', $context);

    $user = $DB->get_record('user', array('id'=>$delete, 'mnethostid'=>$CFG->mnet_localhost_id), '*', MUST_EXIST);

    if (is_siteadmin($user->id)) {
        print_error('useradminodelete', 'error');
    }

    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();
        $fullname = fullname($user, true);
        echo $OUTPUT->heading(get_string('deleteuser', 'admin'));
        $optionsyes = array('delete'=>$delete, 'confirm'=>md5($delete), 'sesskey'=>sesskey());
        echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$fullname'"), new moodle_url($returnurl, $optionsyes), $returnurl);
        echo $OUTPUT->footer();
        die;
    } else if (data_submitted() and !$user->deleted) {
        if (delete_user($user)) {
            session_gc(); // remove stale sessions
            redirect($returnurl);
        } else {
            session_gc(); // remove stale sessions
            echo $OUTPUT->header();
            echo $OUTPUT->notification($returnurl, get_string('deletednot', '', fullname($user, true)));
        }
    }
} else if ($acl and confirm_sesskey()) {
    if (!has_capability('moodle/user:update', $context)) {
        print_error('nopermissions', 'error', '', 'modify the NMET access control list');
    }
    if (!$user = $DB->get_record('user', array('id'=>$acl))) {
        print_error('nousers', 'error');
    }
    if (!is_mnet_remote_user($user)) {
        print_error('usermustbemnet', 'error');
    }
    $accessctrl = strtolower(required_param('accessctrl', PARAM_ALPHA));
    if ($accessctrl != 'allow' and $accessctrl != 'deny') {
        print_error('invalidaccessparameter', 'error');
    }
    $aclrecord = $DB->get_record('mnet_sso_access_control', array('username'=>$user->username, 'mnet_host_id'=>$user->mnethostid));
    if (empty($aclrecord)) {
        $aclrecord = new stdClass();
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

} else if ($suspend and confirm_sesskey()) {
    require_capability('moodle/user:update', $context);

    if ($user = $DB->get_record('user', array('id'=>$suspend, 'mnethostid'=>$CFG->mnet_localhost_id, 'deleted'=>0))) {
        if (!is_siteadmin($user) and $USER->id != $user->id and $user->suspended != 1) {
            $user->suspended = 1;
            $user->timemodified = time();
            $DB->set_field('user', 'suspended', $user->suspended, array('id'=>$user->id));
            $DB->set_field('user', 'timemodified', $user->timemodified, array('id'=>$user->id));
            // force logout
            session_kill_user($user->id);
            events_trigger('user_updated', $user);
        }
    }
    redirect($returnurl);

} else if ($unsuspend and confirm_sesskey()) {
    require_capability('moodle/user:update', $context);

    if ($user = $DB->get_record('user', array('id'=>$unsuspend, 'mnethostid'=>$CFG->mnet_localhost_id, 'deleted'=>0))) {
        if ($user->suspended != 0) {
            $user->suspended = 0;
            $user->timemodified = time();
            $DB->set_field('user', 'suspended', $user->suspended, array('id'=>$user->id));
            $DB->set_field('user', 'timemodified', $user->timemodified, array('id'=>$user->id));
            events_trigger('user_updated', $user);
        }
    }
    redirect($returnurl);

} else if ($unlock and confirm_sesskey()) {
    require_capability('moodle/user:update', $context);

    if ($user = $DB->get_record('user', array('id'=>$unlock, 'mnethostid'=>$CFG->mnet_localhost_id, 'deleted'=>0))) {
        login_unlock_account($user);
    }
    redirect($returnurl);
} 
else if ($usernew = $userform->get_data()) {
    
    add_to_log($courseid, 'user', 'update', "view.php?id=$userid&course=$courseid", '');
    $usernew->id = $usernew->userid;
    $usertoupdate = $DB->get_record('user', array('id'=>$usernew->userid));
    
    $usertoupdate->timemodified = time();
    $usertoupdate->firstname = $usernew->firstname;
    $usertoupdate->lastname = $usernew->lastname;
    $usertoupdate->email = $usernew->email;
    $usertoupdate->password = hash_internal_user_password($usernew->newpassword);

    $DB->update_record('user', $usertoupdate);

    // save custom profile fields data
    profile_save_data($usernew);

    // reload from db
    $usernew = $DB->get_record('user', array('id'=>$usernew->userid));
    events_trigger('user_updated', $usernew);
    redirect($url, get_string('changessaved'));
} 

// create the user filter form
$ufiltering = new user_filtering(null,$url);

echo $OUTPUT->header();

if ($userid != 0) {
    $user = $DB->get_record('user', array('id'=>$userid), '*', MUST_EXIST);
    $userform->set_data($user);
    $userform->display();
    echo $OUTPUT->footer();
    exit();
}
// Carry on with the user listing
$context = context_system::instance();
$extracolumns = get_extra_user_fields($context);

//TODO: extra fields to display

$columns = array_merge(array('firstname', 'lastname'), $extracolumns,
        array('city', 'country', 'lastaccess'));

foreach ($columns as $column) {
    $string[$column] = get_user_field_name($column);
    if ($sort != $column) {
        $columnicon = "";
        if ($column == "lastaccess") {
            $columndir = "DESC";
        } else {
            $columndir = "ASC";
        }
    } else {
        $columndir = $dir == "ASC" ? "DESC":"ASC";
        if ($column == "lastaccess") {
            $columnicon = ($dir == "ASC") ? "sort_desc" : "sort_asc";
        } else {
            $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
        }
        $columnicon = "<img class='iconsort' src=\"" . $OUTPUT->pix_url('t/' . $columnicon) . "\" alt=\"\" />";

    }
    $$column = "<a href=\"" .$url->out(). "&sort=$column&amp;dir=$columndir\">".$string[$column]."</a>$columnicon";
}

$override = new stdClass();
$override->firstname = 'firstname';
$override->lastname = 'lastname';
$fullnamelanguage = get_string('fullnamedisplay', '', $override);
if (($CFG->fullnamedisplay == 'firstname lastname') or
    ($CFG->fullnamedisplay == 'firstname') or
    ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
    $fullnamedisplay = "$firstname / $lastname";
    if ($sort == "name") { // If sort has already been set to something else then ignore.
        $sort = "firstname";
    }
} else { // ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'lastname firstname').
    $fullnamedisplay = "$lastname / $firstname";
    if ($sort == "name") { // This should give the desired sorting based on fullnamedisplay.
        $sort = "lastname";
    }
}

// check group members of user who has capability to edit user profiles in this course and collect them in array
$memberids = array();
if(has_capability('block/userprofile_update:updateuserprofile', $coursecontext)){
    $groupsofuser = groups_get_all_groups($courseid, $USER->id);
    if(!empty($groupsofuser)){
       foreach ($groupsofuser as $group){
          $groupmemberids[$group->id] = groups_get_members($group->id,'u.id');
          $memberids = array_keys($groupmemberids[$group->id]);
       }
    }
}

// do not display any users if the user is not member of any group
if(has_capability('moodle/site:config', $context)){
 $displayuserssql = '';
} else if(!empty($memberids)) {
 $memberidasstring = implode(',', $memberids);
 $displayuserssql = ' id IN ('.$memberidasstring.')';
} else {
 $displayuserssql = ' id = 0 ';
}


list($extrasql, $params) = $ufiltering->get_sql_filter($displayuserssql);
$users = get_users_listing($sort, $dir, $page*$perpage, $perpage, '', '', '',
        $extrasql, $params, $context);
$usercount = get_users(false);
$usersearchcount = get_users(false, '', false, null, "", '', '', '', '', '*', $extrasql, $params);

if ($extrasql !== '') {
    echo $OUTPUT->heading("$usersearchcount ".get_string('users'));
    $usercount = $usersearchcount;
} else {
    echo $OUTPUT->heading("$usercount ".get_string('users'));
}

$strall = get_string('all');

$baseurl = new moodle_url($url->out(), array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);

flush();


if (!$users) {
    $match = array();
    echo $OUTPUT->heading(get_string('nousersfound'));

    $table = NULL;

} else {

    $countries = get_string_manager()->get_list_of_countries(false);
    if (empty($mnethosts)) {
        $mnethosts = $DB->get_records('mnet_host', null, 'id', 'id,wwwroot,name');
    }

    foreach ($users as $key => $user) {
        if (isset($countries[$user->country])) {
            $users[$key]->country = $countries[$user->country];
        }
    }
    if ($sort == "country") {  // Need to resort by full country name, not code
        foreach ($users as $user) {
            $susers[$user->id] = $user->country;
        }
        asort($susers);
        foreach ($susers as $key => $value) {
            $nusers[] = $users[$key];
        }
        $users = $nusers;
    }

    $table = new html_table();
    $table->head = array ();
    $table->colclasses = array();
    $table->head[] = $fullnamedisplay;
    $table->attributes['class'] = 'admintable generaltable';
    $table->colclasses[] = 'leftalign';
    foreach ($extracolumns as $field) {
        $table->head[] = ${$field};
        $table->colclasses[] = 'leftalign';
    }
    $table->head[] = $city;
    $table->colclasses[] = 'leftalign';
    $table->head[] = $country;
    $table->colclasses[] = 'leftalign';
    $table->head[] = $lastaccess;
    $table->colclasses[] = 'leftalign';
    $table->head[] = get_string('edit');
    $table->colclasses[] = 'centeralign';
    $table->head[] = get_string('suspenduser', 'admin');
    $table->colclasses[] = 'centeralign';
    $table->head[] = "";
    $table->colclasses[] = 'centeralign';

    $table->id = "users";
    foreach ($users as $user) {
        $lastcolumn = '';
        
        $buttons = array();
        $buttons['delete'] = '';
        $buttons['suspend'] = '';
        $buttons['delete'] = '';
        // delete button
        if (has_capability('moodle/user:delete', $context)) {
            
            if (is_mnet_remote_user($user) or $user->id == $USER->id or is_siteadmin($user)) {
                // no deleting of self, mnet accounts or admins allowed
            } else {
                $buttons['delete'] = html_writer::link(new moodle_url($returnurl, array('delete'=>$user->id, 'sesskey'=>sesskey())), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'alt'=>$strdelete, 'class'=>'iconsmall')), array('title'=>$strdelete));
            }
        }

        // suspend button
        if (has_capability('block/userprofile_update:updateuserprofile', $coursecontext)) {
            if (is_mnet_remote_user($user)) {
                // mnet users have special access control, they can not be deleted the standard way or suspended
                $accessctrl = 'allow';
                if ($acl = $DB->get_record('mnet_sso_access_control', array('username'=>$user->username, 'mnet_host_id'=>$user->mnethostid))) {
                    $accessctrl = $acl->accessctrl;
                }
                $changeaccessto = ($accessctrl == 'deny' ? 'allow' : 'deny');
                $buttons['suspend'] = " (<a href=\"?acl={$user->id}&amp;accessctrl=$changeaccessto&amp;sesskey=".sesskey()."\">".get_string($changeaccessto, 'mnet') . " access</a>)";

            } else {
                if ($user->suspended) {
                    $buttons['suspend'] = html_writer::link(new moodle_url($returnurl, array('unsuspend'=>$user->id, 'sesskey'=>sesskey())), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/show'), 'alt'=>$strunsuspend, 'class'=>'iconsmall')), array('title'=>$strunsuspend));
                } else {
                    if ($user->id == $USER->id or is_siteadmin($user)) {
                        // no suspending of admins or self!
                    } else {
                        $buttons['suspend'] = html_writer::link(new moodle_url($returnurl, array('suspend'=>$user->id, 'sesskey'=>sesskey())), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/hide'), 'alt'=>$strsuspend, 'class'=>'iconsmall')), array('title'=>$strsuspend));
                    }
                }

                if (login_is_lockedout($user)) {
                    $buttons['suspend'] = html_writer::link(new moodle_url($returnurl, array('unlock'=>$user->id, 'sesskey'=>sesskey())), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/unlock'), 'alt'=>$strunlock, 'class'=>'iconsmall')), array('title'=>$strunlock));
                }
            }
        }

        // edit button
        if (has_capability('block/userprofile_update:updateuserprofile', $coursecontext)) {
            // prevent editing of admins by non-admins
            if (is_siteadmin($USER) or !is_siteadmin($user)) {
                $buttons['edit'] = html_writer::link(new moodle_url($url->out(), array('userid' => $user->id)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>$stredit, 'class'=>'iconsmall')), array('title'=>$stredit));
            }
        }

        // the last column - confirm or mnet info
        if (is_mnet_remote_user($user)) {
            // all mnet users are confirmed, let's print just the name of the host there
            if (isset($mnethosts[$user->mnethostid])) {
                $lastcolumn = get_string($accessctrl, 'mnet').': '.$mnethosts[$user->mnethostid]->name;
            } else {
                $lastcolumn = get_string($accessctrl, 'mnet');
            }

        } else if ($user->confirmed == 0) {
            if (has_capability('moodle/user:update', $context)) {
                $lastcolumn = html_writer::link(new moodle_url($returnurl, array('confirmuser'=>$user->id, 'sesskey'=>sesskey())), $strconfirm);
            } else {
                $lastcolumn = "<span class=\"dimmed_text\">".get_string('confirm')."</span>";
            }
        }

        if ($user->lastaccess) {
            $strlastaccess = format_time(time() - $user->lastaccess);
        } else {
            $strlastaccess = get_string('never');
        }
        $fullname = fullname($user, true);

        $row = array ();
        $row[] = "<a href=\"../../user/view.php?id=$user->id&amp;course=$courseid\">$fullname</a>";
        foreach ($extracolumns as $field) {
            $row[] = $user->{$field};
        }
        $row[] = $user->city;
        $row[] = $user->country;
        $row[] = $strlastaccess;
        if ($user->suspended) {
            foreach ($row as $k=>$v) {
                $row[$k] = html_writer::tag('span', $v, array('class'=>'usersuspended'));
            }
        }
        $row[] = $buttons['edit']." ".$buttons['delete'];
        $row[] = $buttons['suspend'];
        $row[] = $lastcolumn;
        $table->data[] = $row;
    }
}

// add filters
$ufiltering->display_add();
$ufiltering->display_active();

if (has_capability('moodle/user:create', $context)) {
    echo $OUTPUT->heading('<a href="'.$securewwwroot.'/user/editadvanced.php?id=-1">'.get_string('addnewuser').'</a>');
}
if (!empty($table)) {
    echo html_writer::start_tag('div', array('class'=>'no-overflow'));
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
    echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
    if (has_capability('moodle/user:create', $context)) {
        echo $OUTPUT->heading('<a href="'.$securewwwroot.'/user/editadvanced.php?id=-1">'.get_string('addnewuser').'</a>');
    }
}

echo $OUTPUT->footer();
