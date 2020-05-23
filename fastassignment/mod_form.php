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
 * This file contains the forms to create and edit an instance of this module
 *
 * @package   mod_fastassignment
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

/**
 * Assignment settings form.
 *
 * @package   mod_fastassignment
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_fastassignment_mod_form extends moodleform_mod {

    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {
        global $CFG, $COURSE, $DB, $PAGE, $cm;
        $checkdata = optional_param('update', '', PARAM_INT);
        
        // Default category, test link and test name
        if(isset($_GET["update"])) {
        $get_tests = $DB->get_record_sql("SELECT test_links	, test_name, category_name FROM {fastassignment} WHERE id = $cm->instance");
        
        
            $default_testlinks = $get_tests->test_links;
            $default_test_name = $get_tests->test_name;
            $default_category_name = $get_tests->category_name;
        } else{
            $default_testlinks = $default_test_name = $default_category_name = "";
        }
        
        // Handle different Virtual tutor Node API's.
        $PAGE->requires->jquery();
        $PAGE->requires->js( new moodle_url($CFG->wwwroot . "/mod/fastassignment/api.js") );
        $PAGE->requires->js( new moodle_url($CFG->wwwroot . "/mod/fastassignment/api_autoeval.js") );

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('assignmentname', 'fastassignment'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('hidden', 'api_key', get_string('getapikey', 'fastassignment'), array('size' => '64'));
        /*$mform->setType('api_key', PARAM_TEXT);
        $mform->addHelpButton('api_key', 'getapikey', 'fastassignment');*/
        /*$mform->addElement('text', 'api_post_url', get_string('apiposturl', 'fastassignment'), array('size' => '64'));
        $mform->setType('api_post_url', PARAM_TEXT);
        $mform->addHelpButton('api_post_url', 'apiposturl', 'fastassignment');*/
        $mform->addElement('hidden','api_post_url', "https://virtualwritingtutor.com/api/checkgrammar.php");
        
        $mform->addElement('button', 'validate_api_limits', get_string('validateapibutton', 'fastassignment'));
        $mform->addElement('hidden','testlinks_hidden', $default_testlinks);
        $mform->addElement('hidden','test_name', $default_test_name);
        $mform->addElement('hidden','category_name', $default_category_name);

        // Select Category
        $select = $mform->addElement('select', 'category', get_string('category', 'fastassignment'), $OPTIONS_CATEGORIES);
        $mform->addHelpButton('category', 'category', 'fastassignment');
        
        // Select Tests
        $select = $mform->addElement('select', 'test', get_string('test', 'fastassignment'), $OPTIONS_TEST);
        $mform->addHelpButton('test', 'test', 'fastassignment');
        
        // Add to desc
        $mform->addElement('button', 'add_to_editor', get_string('add_to_editor', 'fastassignment'));
        $mform->addHelpButton('add_to_editor', 'add_to_editor', 'fastassignment');

        $this->standard_intro_elements(get_string('description', 'fastassignment'));


        $mform->addElement('filemanager', 'introattachments',
                            get_string('introattachments', 'fastassignment'),
                            null, array('subdirs' => 0, 'maxbytes' => $COURSE->maxbytes) );
        $mform->addHelpButton('introattachments', 'introattachments', 'fastassignment');

        $ctx = null;
        if ($this->current && $this->current->coursemodule) {
            $cm = get_coursemodule_from_instance('fastassignment', $this->current->id, 0, false, MUST_EXIST);
            $ctx = context_module::instance($cm->id);
        }
        $assignment = new fastassignment($ctx, null, null);
        if ($this->current && $this->current->course) {
            if (!$ctx) {
                $ctx = context_course::instance($this->current->course);
            }
            $course = $DB->get_record('course', array('id'=>$this->current->course), '*', MUST_EXIST);
            $assignment->set_course($course);
        }

        $config = get_config('fastassignment');

        $mform->addElement('header', 'availability', get_string('availability', 'fastassignment'));
        $mform->setExpanded('availability', true);

        $name = get_string('allowsubmissionsfromdate', 'fastassignment');
        $options = array('optional'=>true);
        $mform->addElement('date_time_selector', 'allowsubmissionsfromdate', $name, $options);
        $mform->addHelpButton('allowsubmissionsfromdate', 'allowsubmissionsfromdate', 'fastassignment');

        $name = get_string('duedate', 'fastassignment');
        $mform->addElement('date_time_selector', 'duedate', $name, array('optional'=>true));
        $mform->addHelpButton('duedate', 'duedate', 'fastassignment');

        $name = get_string('cutoffdate', 'fastassignment');
        $mform->addElement('date_time_selector', 'cutoffdate', $name, array('optional'=>true));
        $mform->addHelpButton('cutoffdate', 'cutoffdate', 'fastassignment');

        $name = get_string('gradingduedate', 'fastassignment');
        $mform->addElement('date_time_selector', 'gradingduedate', $name, array('optional' => true));
        $mform->addHelpButton('gradingduedate', 'gradingduedate', 'fastassignment');

        $name = get_string('alwaysshowdescription', 'fastassignment');
        $mform->addElement('checkbox', 'alwaysshowdescription', $name);
        $mform->addHelpButton('alwaysshowdescription', 'alwaysshowdescription', 'fastassignment');
        $mform->disabledIf('alwaysshowdescription', 'allowsubmissionsfromdate[enabled]', 'notchecked');

        $assignment->add_all_plugin_settings($mform);

        $mform->addElement('header', 'submissionsettings', get_string('submissionsettings', 'fastassignment'));

        $name = get_string('submissiondrafts', 'fastassignment');
        $mform->addElement('selectyesno', 'submissiondrafts', $name);
        $mform->addHelpButton('submissiondrafts', 'submissiondrafts', 'fastassignment');

        $name = get_string('requiresubmissionstatement', 'fastassignment');
        $mform->addElement('selectyesno', 'requiresubmissionstatement', $name);
        $mform->addHelpButton('requiresubmissionstatement',
                              'requiresubmissionstatement',
                              'fastassignment');
        $mform->setType('requiresubmissionstatement', PARAM_BOOL);

        $options = array(
            FASTASSIGN_ATTEMPT_REOPEN_METHOD_NONE => get_string('attemptreopenmethod_none', 'mod_fastassignment'),
            FASTASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL => get_string('attemptreopenmethod_manual', 'mod_fastassignment'),
            FASTASSIGN_ATTEMPT_REOPEN_METHOD_UNTILPASS => get_string('attemptreopenmethod_untilpass', 'mod_fastassignment')
        );
        $mform->addElement('select', 'attemptreopenmethod', get_string('attemptreopenmethod', 'mod_fastassignment'), $options);
        $mform->addHelpButton('attemptreopenmethod', 'attemptreopenmethod', 'mod_fastassignment');

        $options = array(FASTASSIGN_UNLIMITED_ATTEMPTS => get_string('unlimitedattempts', 'mod_fastassignment'));
        $options += array_combine(range(1, 30), range(1, 30));
        $mform->addElement('select', 'maxattempts', get_string('maxattempts', 'mod_fastassignment'), $options);
        $mform->addHelpButton('maxattempts', 'maxattempts', 'fastassignment');
        $mform->hideIf('maxattempts', 'attemptreopenmethod', 'eq', FASTASSIGN_ATTEMPT_REOPEN_METHOD_NONE);

        $mform->addElement('header', 'groupsubmissionsettings', get_string('groupsubmissionsettings', 'fastassignment'));

        $name = get_string('teamsubmission', 'fastassignment');
        $mform->addElement('selectyesno', 'teamsubmission', $name);
        $mform->addHelpButton('teamsubmission', 'teamsubmission', 'fastassignment');
        if ($assignment->has_submissions_or_grades()) {
            $mform->freeze('teamsubmission');
        }

        $name = get_string('preventsubmissionnotingroup', 'fastassignment');
        $mform->addElement('selectyesno', 'preventsubmissionnotingroup', $name);
        $mform->addHelpButton('preventsubmissionnotingroup',
            'preventsubmissionnotingroup',
            'fastassignment');
        $mform->setType('preventsubmissionnotingroup', PARAM_BOOL);
        $mform->hideIf('preventsubmissionnotingroup', 'teamsubmission', 'eq', 0);

        $name = get_string('requireallteammemberssubmit', 'fastassignment');
        $mform->addElement('selectyesno', 'requireallteammemberssubmit', $name);
        $mform->addHelpButton('requireallteammemberssubmit', 'requireallteammemberssubmit', 'fastassignment');
        $mform->hideIf('requireallteammemberssubmit', 'teamsubmission', 'eq', 0);
        $mform->disabledIf('requireallteammemberssubmit', 'submissiondrafts', 'eq', 0);

        $groupings = groups_get_all_groupings($assignment->get_course()->id);
        $options = array();
        $options[0] = get_string('none');
        foreach ($groupings as $grouping) {
            $options[$grouping->id] = $grouping->name;
        }

        $name = get_string('teamsubmissiongroupingid', 'fastassignment');
        $mform->addElement('select', 'teamsubmissiongroupingid', $name, $options);
        $mform->addHelpButton('teamsubmissiongroupingid', 'teamsubmissiongroupingid', 'fastassignment');
        $mform->hideIf('teamsubmissiongroupingid', 'teamsubmission', 'eq', 0);
        if ($assignment->has_submissions_or_grades()) {
            $mform->freeze('teamsubmissiongroupingid');
        }

        $mform->addElement('header', 'notifications', get_string('notifications', 'fastassignment'));

        $name = get_string('sendnotifications', 'fastassignment');
        $mform->addElement('selectyesno', 'sendnotifications', $name);
        $mform->addHelpButton('sendnotifications', 'sendnotifications', 'fastassignment');

        $name = get_string('sendlatenotifications', 'fastassignment');
        $mform->addElement('selectyesno', 'sendlatenotifications', $name);
        $mform->addHelpButton('sendlatenotifications', 'sendlatenotifications', 'fastassignment');
        $mform->disabledIf('sendlatenotifications', 'sendnotifications', 'eq', 1);

        $name = get_string('sendstudentnotificationsdefault', 'fastassignment');
        $mform->addElement('selectyesno', 'sendstudentnotifications', $name);
        $mform->addHelpButton('sendstudentnotifications', 'sendstudentnotificationsdefault', 'fastassignment');

        // Plagiarism enabling form.
        if (!empty($CFG->enableplagiarism)) {
            require_once($CFG->libdir . '/plagiarismlib.php');
            plagiarism_get_form_elements_module($mform, $ctx->get_course_context(), 'mod_fastassignment');
        }

        $this->standard_grading_coursemodule_elements();
        $name = get_string('blindmarking', 'fastassignment');
        $mform->addElement('selectyesno', 'blindmarking', $name);
        $mform->addHelpButton('blindmarking', 'blindmarking', 'fastassignment');
        if ($assignment->has_submissions_or_grades() ) {
            $mform->freeze('blindmarking');
        }

        $name = get_string('hidegrader', 'fastassignment');
        $mform->addElement('selectyesno', 'hidegrader', $name);
        $mform->addHelpButton('hidegrader', 'hidegrader', 'fastassignment');

        $name = get_string('markingworkflow', 'fastassignment');
        $mform->addElement('selectyesno', 'markingworkflow', $name);
        $mform->addHelpButton('markingworkflow', 'markingworkflow', 'fastassignment');

        $name = get_string('markingallocation', 'fastassignment');
        $mform->addElement('selectyesno', 'markingallocation', $name);
        $mform->addHelpButton('markingallocation', 'markingallocation', 'fastassignment');
        $mform->hideIf('markingallocation', 'markingworkflow', 'eq', 0);


        

        $this->standard_coursemodule_elements();
        $this->apply_admin_defaults();

        $this->add_action_buttons();
    }

    /**
     * Perform minimal validation on the settings form
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['allowsubmissionsfromdate']) && !empty($data['duedate'])) {
            if ($data['duedate'] < $data['allowsubmissionsfromdate']) {
                $errors['duedate'] = get_string('duedatevalidation', 'fastassignment');
            }
        }
        if (!empty($data['cutoffdate']) && !empty($data['duedate'])) {
            if ($data['cutoffdate'] < $data['duedate'] ) {
                $errors['cutoffdate'] = get_string('cutoffdatevalidation', 'fastassignment');
            }
        }
        if (!empty($data['allowsubmissionsfromdate']) && !empty($data['cutoffdate'])) {
            if ($data['cutoffdate'] < $data['allowsubmissionsfromdate']) {
                $errors['cutoffdate'] = get_string('cutoffdatefromdatevalidation', 'fastassignment');
            }
        }
        if ($data['gradingduedate']) {
            if ($data['allowsubmissionsfromdate'] && $data['allowsubmissionsfromdate'] > $data['gradingduedate']) {
                $errors['gradingduedate'] = get_string('gradingduefromdatevalidation', 'fastassignment');
            }
            if ($data['duedate'] && $data['duedate'] > $data['gradingduedate']) {
                $errors['gradingduedate'] = get_string('gradingdueduedatevalidation', 'fastassignment');
            }
        }
        if ($data['blindmarking'] && $data['attemptreopenmethod'] == FASTASSIGN_ATTEMPT_REOPEN_METHOD_UNTILPASS) {
            $errors['attemptreopenmethod'] = get_string('reopenuntilpassincompatiblewithblindmarking', 'fastassignment');
        }

        return $errors;
    }

    /**
     * Any data processing needed before the form is displayed
     * (needed to set up draft areas for editor and filemanager elements)
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        global $DB;

        $ctx = null;
        if ($this->current && $this->current->coursemodule) {
            $cm = get_coursemodule_from_instance('fastassignment', $this->current->id, 0, false, MUST_EXIST);
            $ctx = context_module::instance($cm->id);
        }
        $assignment = new fastassignment($ctx, null, null);
        if ($this->current && $this->current->course) {
            if (!$ctx) {
                $ctx = context_course::instance($this->current->course);
            }
            $course = $DB->get_record('course', array('id'=>$this->current->course), '*', MUST_EXIST);
            $assignment->set_course($course);
        }

        $draftitemid = file_get_submitted_draft_itemid('introattachments');
        file_prepare_draft_area($draftitemid, $ctx->id, 'mod_fastassignment', FASTASSIGN_INTROATTACHMENT_FILEAREA,
                                0, array('subdirs' => 0));
        $defaultvalues['introattachments'] = $draftitemid;

        $assignment->plugin_data_preprocessing($defaultvalues);
    }

    /**
     * Add any custom completion rules to the form.
     *
     * @return array Contains the names of the added form elements
     */
    public function add_completion_rules() {
        $mform =& $this->_form;

        $mform->addElement('advcheckbox', 'completionsubmit', '', get_string('completionsubmit', 'fastassignment'));
        // Enable this completion rule by default.
        $mform->setDefault('completionsubmit', 1);
        return array('completionsubmit');
    }

    /**
     * Determines if completion is enabled for this module.
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completionsubmit']);
    }

}
$api_handler_path = $CFG->wwwroot.'/mod/fastassignment/api_handler.php';
$acn = $_GET["action"];
