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

namespace mod_fastassignment\task;
defined('MOODLE_INTERNAL') || die();

/**
 * A schedule task for assignment cron.
 *
 * @package   mod_fastassignment
 * @copyright 2019 Simey Lameze <simey@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask', 'mod_fastassignment');
    }

    /**
     * Run assignment cron.
     */
    public function execute() {
        global $CFG;

        require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');
        \fastassignment::cron();

        $plugins = \core_component::get_plugin_list('fastassignsubmission');

        foreach ($plugins as $name => $plugin) {
            $disabled = get_config('fastassignsubmission_' . $name, 'disabled');
            if (!$disabled) {
                $class = 'fastassignment_submission_' . $name;
                require_once($CFG->dirroot . '/mod/fastassignment/submission/' . $name . '/locallib.php');
                $class::cron();
            }
        }
        $plugins = \core_component::get_plugin_list('fastassignfeedback');

        foreach ($plugins as $name => $plugin) {
            $disabled = get_config('fastassignfeedback_' . $name, 'disabled');
            if (!$disabled) {
                $class = 'fastassignment_feedback_' . $name;
                require_once($CFG->dirroot . '/mod/fastassignment/feedback/' . $name . '/locallib.php');
                $class::cron();
            }
        }

        return true;
    }
}
