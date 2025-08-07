<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Code to be executed after the plugin's database scheme has been installed is defined here.
 *
 * @package     tool_moodiyregistration
 * @category    upgrade
 * @copyright   2025 VidyaMantra <pinky@vidyamantra.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Perform the post-install procedures.
 */
function xmldb_tool_moodiyregistration_install() {
    global $DB, $CFG;
    // Check config if the site register on Moodiy .
    if (array_key_exists("auth_maintenance", $CFG->forced_plugin_settings)) {
        // If the site is set to register on Moodiy, proceed with registration.
        if (!empty($CFG->moodiysiteregistrationuuid)) {
            \tool_moodiyregistration\registration::register_internal_site($CFG->moodiysiteregistrationuuid);
        } else {
            // Create adhoc task. Internal site but registration info not available.
            $postinstalltask = new \tool_moodiyregistration\task\internal_site_registration();
            $postinstalltask->set_next_run_time(time() + 1800); // 30 minutes delay.
            core\task\manager::queue_adhoc_task($postinstalltask);
        }
    }
    return true;
}
