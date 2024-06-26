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
 * Configuration form.
 *
 * @package    block
 * @subpackage userprofile_update
 * @author     David Bogner
 * @copyright  2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * Form for updating the user profile.
 *
 */
class block_userprofile_update_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;
        $strgeneral = get_string('general');
        $strrequired = get_string('required');
        $mform =& $this->_form;
        $usermanager = 0;
        if (is_array($this->_customdata)) {
            if (array_key_exists('userid', $this->_customdata)) {
                $userid = $this->_customdata['userid'];
            }
            if (array_key_exists('parentcontextid', $this->_customdata)) {
                $parentcontextid = $this->_customdata['parentcontextid'];
            }
            if (array_key_exists('courseid', $this->_customdata)) {
                $courseid = $this->_customdata['courseid'];
            }
            if (array_key_exists('usermanager', $this->_customdata)) {
                $usermanager = $this->_customdata['usermanager'];
            }
        }
        // Hidden elements.
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'parentcontextid', $parentcontextid);
        $mform->setType('parentcontextid', PARAM_INT);
        $mform->addElement('hidden', 'userid', $userid);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'username', $this->_customdata['username']);
        $mform->setType('username', PARAM_USERNAME);

        $mform->addElement('header', 'moodle', $strgeneral);

        $mform->addElement('html', '<div class="fitem fitem_ftext row"><div class="fitemtitle col-md-3"><label>' .
            get_string('username') .
            '</label></div><div class="felement ftext col-md-9">' . $this->_customdata['username'] . '</div></div>');

        $mform->addElement('text', 'firstname', get_string('firstname'));
        $mform->addRule('firstname', $strrequired, 'required', null, 'client');
        $mform->setType('firstname', PARAM_NOTAGS);

        $mform->addElement('text', 'lastname', get_string('lastname'), 'maxlength="100" size="30"');
        $mform->addRule('lastname', $strrequired, 'required', null, 'client');
        $mform->setType('lastname', PARAM_NOTAGS);

        $mform->addElement('text', 'email', get_string('email'), 'maxlength="100" size="30"');
        $mform->addRule('email', $strrequired, 'required', null, 'client');
        $mform->setType('email', PARAM_EMAIL);

        if (!empty($CFG->passwordpolicy)) {
            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        }

        $mform->addElement('passwordunmask', 'newpassword', get_string('newpassword'), 'size="20"');
        $mform->addHelpButton('newpassword', 'newpassword');
        $mform->setType('newpassword', PARAM_RAW);

        profile_load_custom_fields($USER);
        $partnerfield = get_config('block_userprofile_update', 'ispartner');
        if (!($USER->department === "usermanager") || ($USER->profile[$partnerfield] === "1")) {
            $mform->addElement('advcheckbox', 'usermanager', get_string('usermanager', 'block_userprofile_update'),
                    get_string('canmanageusers', 'block_userprofile_update'), array('group' => 1), array(0, 1));
            $mform->setDefault('usermanager', $usermanager);
        }


        if ($categories = $DB->get_records('user_info_category', null, 'sortorder ASC')) {
            foreach ($categories as $category) {
                if ($fields = $DB->get_records('user_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {

                    // Check first if *any* fields will be displayed.
                    $display = false;
                    foreach ($fields as $field) {
                        if ($field->visible != PROFILE_VISIBLE_NONE) {
                            $display = true;
                        }
                    }

                    // Display the header and the fields.
                    $userprofileconfig = block_userprofile_update_get_config();
                    profile_load_custom_fields($USER);
                    if ($display) {
                        $mform->addElement('header', 'category_' . $category->id, format_string($category->name));
                        foreach ($fields as $field) {
                            if ($field->shortname == $userprofileconfig['profilepartnerid'] || $field->shortname == $userprofileconfig['profiletenant']) {
                                require_once($CFG->dirroot . '/user/profile/field/' . $field->datatype . '/field.class.php');
                                $newfield = 'profile_field_' . $field->datatype;
                                $formfield = new $newfield($field->id, $userid);
                                $formfield->edit_field($mform);
                                $mform->setDefault($formfield->inputname, $USER->profile[$field->shortname]);
                            }
                        }
                    }
                }
            }
        }

        $this->add_action_buttons();

    }

    public function definition_after_data(): void {
        global $DB;
        $mform =& $this->_form;

        $userid = $mform->getElementValue('userid');
        $usernew = $DB->get_record('user', array('id' => $userid));
        if ($mform->isSubmitted()) {
            // Save config here.
            $mform->addElement('static', 'saved', '', get_string('changessaved'));
        }
    }

    /**
     * Validate data.
     *
     * @param $usernew
     * @param $files
     * @return array
     */
    public function validation($usernew, $files): array {
        global $DB;

        $usernew = (object) $usernew;
        $usernew->id = $usernew->userid;

        $user = $DB->get_record('user', array('id' => $usernew->userid));
        $err = array();

        if (!$user || $user->email !== $usernew->email) {
            if (!validate_email($usernew->email)) {
                $err['email'] = get_string('invalidemail');
            }
        }

        if (!empty($usernew->newpassword)) {
            $errmsg = ''; // Prevent eclipse warning.
            if (!check_password_policy($usernew->newpassword, $errmsg)) {
                $err['newpassword'] = $errmsg;
            }
        }

        $err += profile_validation($usernew, $files);

        return $err;

    }
}
