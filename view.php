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
 * This file is the entry point to the fastassignment module. All pages are rendered from here
 *
 * @package   mod_fastassignment
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

$id = required_param('id', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'fastassignment');

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/fastassignment:view', $context);

$fastassignment = new fastassignment($context, $cm, $course);
$urlparams = array('id' => $id,
                  'action' => optional_param('action', '', PARAM_ALPHA),
                  'rownum' => optional_param('rownum', 0, PARAM_INT),
                  'useridlistid' => optional_param('useridlistid', $fastassignment->get_useridlist_key_id(), PARAM_ALPHANUM));

$url = new moodle_url('/mod/fastassignment/view.php', $urlparams);
$PAGE->set_url($url);

// Update module completion status.
$fastassignment->set_module_viewed();

// Apply overrides.
$fastassignment->update_effective_access($USER->id);

// Get the fastassignment class to
// render the page.
echo $fastassignment->view(optional_param('action', '', PARAM_ALPHA));
?>