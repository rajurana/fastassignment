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
 * This file adds the settings pages to the navigation menu
 *
 * @package   mod_fastassignment
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/fastassignment/adminlib.php');

$ADMIN->add('modsettings', new admin_category('modfastassignmentfolder', new lang_string('pluginname', 'mod_fastassignment'), $module->is_enabled() === false));

$settings = new admin_settingpage($section, get_string('settings', 'mod_fastassignment'), 'moodle/site:config', $module->is_enabled() === false);

if ($ADMIN->fulltree) {
    $menu = array();
    foreach (core_component::get_plugin_list('fastassignfeedback') as $type => $notused) {
        $visible = !get_config('fastassignfeedback_' . $type, 'disabled');
        if ($visible) {
            $menu['fastassignfeedback_' . $type] = new lang_string('pluginname', 'fastassignfeedback_' . $type);
        }
    }

    
    $name = new lang_string('apikey', 'mod_fastassignment');
    $description = new lang_string('settings_apikey_help', 'mod_fastassignment');
    $dflt = "Please enter your API key.";
    $setting = new admin_setting_configtext('fastassignment/apikey',
        $name,
        $description,
        $dflt);
    $setting->set_force_ltr(true);
    $settings->add($setting);
    
    // The default here is feedback_comments (if it exists).
    $name = new lang_string('feedbackplugin', 'mod_fastassignment');
    $description = new lang_string('feedbackpluginforgradebook', 'mod_fastassignment');
    $settings->add(new admin_setting_configselect('fastassignment/feedback_plugin_for_gradebook',
                                                  $name,
                                                  $description,
                                                  'fastassignfeedback_comments',
                                                  $menu));

    $name = new lang_string('showrecentsubmissions', 'mod_fastassignment');
    $description = new lang_string('configshowrecentsubmissions', 'mod_fastassignment');
    $settings->add(new admin_setting_configcheckbox('fastassignment/showrecentsubmissions',
                                                    $name,
                                                    $description,
                                                    0));

    $name = new lang_string('sendsubmissionreceipts', 'mod_fastassignment');
    $description = new lang_string('sendsubmissionreceipts_help', 'mod_fastassignment');
    $settings->add(new admin_setting_configcheckbox('fastassignment/submissionreceipts',
                                                    $name,
                                                    $description,
                                                    1));

    $name = new lang_string('submissionstatement', 'mod_fastassignment');
    $description = new lang_string('submissionstatement_help', 'mod_fastassignment');
    $default = get_string('submissionstatementdefault', 'mod_fastassignment');
    $setting = new admin_setting_configtextarea('fastassignment/submissionstatement',
                                                    $name,
                                                    $description,
                                                    $default);
    $setting->set_force_ltr(false);
    $settings->add($setting);

    $name = new lang_string('submissionstatementteamsubmission', 'mod_fastassignment');
    $description = new lang_string('submissionstatement_help', 'mod_fastassignment');
    $default = get_string('submissionstatementteamsubmissiondefault', 'mod_fastassignment');
    $setting = new admin_setting_configtextarea('fastassignment/submissionstatementteamsubmission',
        $name,
        $description,
        $default);
    $setting->set_force_ltr(false);
    $settings->add($setting);

    $name = new lang_string('submissionstatementteamsubmissionallsubmit', 'mod_fastassignment');
    $description = new lang_string('submissionstatement_help', 'mod_fastassignment');
    $default = get_string('submissionstatementteamsubmissionallsubmitdefault', 'mod_fastassignment');
    $setting = new admin_setting_configtextarea('fastassignment/submissionstatementteamsubmissionallsubmit',
        $name,
        $description,
        $default);
    $setting->set_force_ltr(false);
    $settings->add($setting);
    
    
    $name = new lang_string('maxperpage', 'mod_fastassignment');
    $options = array(
        -1 => get_string('unlimitedpages', 'mod_fastassignment'),
        10 => 10,
        20 => 20,
        50 => 50,
        100 => 100,
    );
    $description = new lang_string('maxperpage_help', 'mod_fastassignment');
    $settings->add(new admin_setting_configselect('fastassignment/maxperpage',
                                                    $name,
                                                    $description,
                                                    -1,
                                                    $options));

    $name = new lang_string('defaultsettings', 'mod_fastassignment');
    $description = new lang_string('defaultsettings_help', 'mod_fastassignment');
    $settings->add(new admin_setting_heading('defaultsettings', $name, $description));

    $name = new lang_string('alwaysshowdescription', 'mod_fastassignment');
    $description = new lang_string('alwaysshowdescription_help', 'mod_fastassignment');
    $setting = new admin_setting_configcheckbox('fastassignment/alwaysshowdescription',
                                                    $name,
                                                    $description,
                                                    1);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('allowsubmissionsfromdate', 'mod_fastassignment');
    $description = new lang_string('allowsubmissionsfromdate_help', 'mod_fastassignment');
    $setting = new admin_setting_configduration('fastassignment/allowsubmissionsfromdate',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_enabled_flag_options(admin_setting_flag::ENABLED, true);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('duedate', 'mod_fastassignment');
    $description = new lang_string('duedate_help', 'mod_fastassignment');
    $setting = new admin_setting_configduration('fastassignment/duedate',
                                                    $name,
                                                    $description,
                                                    604800);
    $setting->set_enabled_flag_options(admin_setting_flag::ENABLED, true);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('cutoffdate', 'mod_fastassignment');
    $description = new lang_string('cutoffdate_help', 'mod_fastassignment');
    $setting = new admin_setting_configduration('fastassignment/cutoffdate',
                                                    $name,
                                                    $description,
                                                    1209600);
    $setting->set_enabled_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('gradingduedate', 'mod_fastassignment');
    $description = new lang_string('gradingduedate_help', 'mod_fastassignment');
    $setting = new admin_setting_configduration('fastassignment/gradingduedate',
                                                    $name,
                                                    $description,
                                                    1209600);
    $setting->set_enabled_flag_options(admin_setting_flag::ENABLED, true);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('submissiondrafts', 'mod_fastassignment');
    $description = new lang_string('submissiondrafts_help', 'mod_fastassignment');
    $setting = new admin_setting_configcheckbox('fastassignment/submissiondrafts',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('requiresubmissionstatement', 'mod_fastassignment');
    $description = new lang_string('requiresubmissionstatement_help', 'mod_fastassignment');
    $setting = new admin_setting_configcheckbox('fastassignment/requiresubmissionstatement',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    // Constants from "locallib.php".
    $options = array(
        'none' => get_string('attemptreopenmethod_none', 'mod_fastassignment'),
        'manual' => get_string('attemptreopenmethod_manual', 'mod_fastassignment'),
        'untilpass' => get_string('attemptreopenmethod_untilpass', 'mod_fastassignment')
    );
    $name = new lang_string('attemptreopenmethod', 'mod_fastassignment');
    $description = new lang_string('attemptreopenmethod_help', 'mod_fastassignment');
    $setting = new admin_setting_configselect('fastassignment/attemptreopenmethod',
                                                    $name,
                                                    $description,
                                                    'none',
                                                    $options);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    // Constants from "locallib.php".
    $options = array(-1 => get_string('unlimitedattempts', 'mod_fastassignment'));
    $options += array_combine(range(1, 30), range(1, 30));
    $name = new lang_string('maxattempts', 'mod_fastassignment');
    $description = new lang_string('maxattempts_help', 'mod_fastassignment');
    $setting = new admin_setting_configselect('fastassignment/maxattempts',
                                                    $name,
                                                    $description,
                                                    -1,
                                                    $options);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('teamsubmission', 'mod_fastassignment');
    $description = new lang_string('teamsubmission_help', 'mod_fastassignment');
    $setting = new admin_setting_configcheckbox('fastassignment/teamsubmission',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('preventsubmissionnotingroup', 'mod_fastassignment');
    $description = new lang_string('preventsubmissionnotingroup_help', 'mod_fastassignment');
    $setting = new admin_setting_configcheckbox('fastassignment/preventsubmissionnotingroup',
        $name,
        $description,
        0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('requireallteammemberssubmit', 'mod_fastassignment');
    $description = new lang_string('requireallteammemberssubmit_help', 'mod_fastassignment');
    $setting = new admin_setting_configcheckbox('fastassignment/requireallteammemberssubmit',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('teamsubmissiongroupingid', 'mod_fastassignment');
    $description = new lang_string('teamsubmissiongroupingid_help', 'mod_fastassignment');
    $setting = new admin_setting_configempty('fastassignment/teamsubmissiongroupingid',
                                                    $name,
                                                    $description);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('sendnotifications', 'mod_fastassignment');
    $description = new lang_string('sendnotifications_help', 'mod_fastassignment');
    $setting = new admin_setting_configcheckbox('fastassignment/sendnotifications',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('sendlatenotifications', 'mod_fastassignment');
    $description = new lang_string('sendlatenotifications_help', 'mod_fastassignment');
    $setting = new admin_setting_configcheckbox('fastassignment/sendlatenotifications',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('sendstudentnotificationsdefault', 'mod_fastassignment');
    $description = new lang_string('sendstudentnotificationsdefault_help', 'mod_fastassignment');
    $setting = new admin_setting_configcheckbox('fastassignment/sendstudentnotifications',
                                                    $name,
                                                    $description,
                                                    1);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('blindmarking', 'mod_fastassignment');
    $description = new lang_string('blindmarking_help', 'mod_fastassignment');
    $setting = new admin_setting_configcheckbox('fastassignment/blindmarking',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('hidegrader', 'mod_fastassignment');
    $description = new lang_string('hidegrader_help', 'mod_fastassignment');
    $setting = new admin_setting_configcheckbox('fastassignment/hidegrader',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('markingworkflow', 'mod_fastassignment');
    $description = new lang_string('markingworkflow_help', 'mod_fastassignment');
    $setting = new admin_setting_configcheckbox('fastassignment/markingworkflow',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('markingallocation', 'mod_fastassignment');
    $description = new lang_string('markingallocation_help', 'mod_fastassignment');
    $setting = new admin_setting_configcheckbox('fastassignment/markingallocation',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);
}

$ADMIN->add('modfastassignmentfolder', $settings);
// Tell core we already added the settings structure.
$settings = null;

$ADMIN->add('modfastassignmentfolder', new admin_category('fastassignsubmissionplugins',
    new lang_string('submissionplugins', 'fastassignment'), !$module->is_enabled()));
$ADMIN->add('fastassignsubmissionplugins', new fastassignment_admin_page_manage_fastassignment_plugins('fastassignsubmission'));
$ADMIN->add('modfastassignmentfolder', new admin_category('fastassignfeedbackplugins',
    new lang_string('feedbackplugins', 'fastassignment'), !$module->is_enabled()));
$ADMIN->add('fastassignfeedbackplugins', new fastassignment_admin_page_manage_fastassignment_plugins('fastassignfeedback'));

foreach (core_plugin_manager::instance()->get_plugins_of_type('fastassignsubmission') as $plugin) {
    /** @var \mod_fastassignment\plugininfo\fastassignsubmission $plugin */
    $plugin->load_settings($ADMIN, 'fastassignsubmissionplugins', $hassiteconfig);
}

foreach (core_plugin_manager::instance()->get_plugins_of_type('fastassignfeedback') as $plugin) {
    /** @var \mod_fastassignment\plugininfo\fastassignfeedback $plugin */
    $plugin->load_settings($ADMIN, 'fastassignfeedbackplugins', $hassiteconfig);
}
