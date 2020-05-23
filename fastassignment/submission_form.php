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
 * This file contains the submission form used by the fastassignment module.
 *
 * @package   mod_fastassignment
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');
global $PAGE, $cm, $DB, $SESSION, $vwtgrmrfeedback;
/**
 * fastassignment submission form
 *
 * @package   mod_fastassignment
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_fastassignment_submission_form extends moodleform {
    /**
     * Define this form - called by the parent constructor
     */
    public function definition() {
        global $USER, $cm, $DB, $vwtgrmrfeedback;
        $mform = $this->_form;
        list($fastassignment, $data) = $this->_customdata;
        $instance = $fastassignment->get_instance();
        // Get API permissions
        $test_links_sql = $DB->get_record_sql("SELECT maxnoofchecks, maxnoofchecksstudent, automatedgrammarcheck	, studentaccessibleautoeval FROM {fastassignment} WHERE id = $cm->instance");
        $grammar_permission = $test_links_sql->automatedgrammarcheck;
        $autoeval_permission = $test_links_sql->studentaccessibleautoeval;
		$role = 5;
		
		$check_user_hits = $DB->get_record_sql("SELECT grammar_hits, autoeval_hits  FROM {fastassignment_api_validtn} WHERE activity = $cm->instance AND user = $data->userid AND role = $role");
		
		$grammar_hits = empty($check_user_hits->grammar_hits) ? 0 : $check_user_hits->grammar_hits;
		$autoeval_hits = empty($check_user_hits->autoeval_hits) ? 0 : $check_user_hits->autoeval_hits;
		
		$remaining_grammar_hits = "No. of grammar checks available: " .($test_links_sql->maxnoofchecks - $grammar_hits);
		$remaining_autoeval_hits = "No. of automated evaluation available: " .($test_links_sql->maxnoofchecksstudent - $autoeval_hits);
		
        $mform->addElement('hidden', 'grammar_hits', $grammar_hits);
        $mform->addElement('hidden', 'autoeval_hits', $autoeval_hits);
        if ($instance->teamsubmission) {
            $submission = $fastassignment->get_group_submission($data->userid, 0, true);
        } else {
            $submission = $fastassignment->get_user_submission($data->userid, true);
        }
        if ($submission) {
            $mform->addElement('hidden', 'lastmodified', $submission->timemodified);
            $mform->setType('lastmodified', PARAM_INT);
        }
        $fastassignment->add_submission_form_elements($mform, $data);
        $mform->addElement('html', '<div class="form-group row fitem api_messages"><div class="col-md-12">'.$_SESSION['api_messages'].'</div></div>');		
        $mform->addElement('html', '<div class="form-group row fitem"><div class="col-md-12">'.$remaining_grammar_hits.'<br>'.$remaining_autoeval_hits.'</div></div>');		
		$buttonarray = array();        
		if($grammar_permission) {			
            $buttonarray[] = $mform->createElement('submit', 'grammarcheck', get_string("grammarcheck", 'fastassignment'));
        }
        if($autoeval_permission){
            $buttonarray[] = $mform->createElement('submit', 'automatedevaluation', get_string("automatedevaluation", 'fastassignment'));
        }
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('savechanges', 'fastassignment'));
        $buttonarray[] = $mform->createElement('cancel');		$mform->addGroup($buttonarray, 'buttonar', '', ' ', false);		
        //$this->add_action_buttons(true, get_string('savechanges', 'fastassignment'));
        if ($data) {
            $this->set_data($data);
        }
    }
}
$activity =  $cm->instance;
$SESSION->activity = $activity;
?>
<style>
	.api_error { color: red; }
    .form-group.row.fitem.api_messages .api-heading { font-size: 20px;color: red;margin-bottom: 0.5em; }
    .form-group.row.fitem.api_messages .list-inline { list-style-type: none; }
	.form-group.row.fitem.api_messages h1,
	.form-group.row.fitem.api_messages h2,
	.form-group.row.fitem.api_messages h3 { color: #055f8c; }
	.form-group.row.fitem.api_messages .ml30 { margin-left:30px; }
	.form-group.row.fitem.api_messages .mr20 { margin-right:20px; }
	.form-group.row.fitem.api_messages .m30 { margin:30px; }
</style>