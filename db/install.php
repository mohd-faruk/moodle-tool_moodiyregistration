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
    if (!empty($CFG->moodiysiteregistrationuuid) && !empty($CFG->moodiysiteregistrationid)) {
        $admin = get_admin();
        $site = get_site();
        $sitedata = new \stdClass();
        $sitedata->site_name = format_string($site->fullname, true, ['context' => \context_course::instance(SITEID)]);
        $sitedata->description = $site->summary;
        $sitedata->admin_email = $admin->email;
        $sitedata->country_code = $admin->country ?: $CFG->country;
        $sitedata->language = explode('_', current_language())[0];
        $sitedata->privacy = 'notdisplayed';
        $sitedata->policyagreed = 0;
        $sitedata->organisation_type = 'donotshare';
        \tool_moodiyregistration\registration::save_site_info($sitedata);

        // Create a new record in 'tool_moodiyregistration'.
        $record = new \stdClass();
        $record->registrationid = $CFG->moodiysiteregistrationid;
        $record->site_uuid = $CFG->moodiysiteregistrationuuid;
        $record->site_url = $CFG->wwwroot;
        $record->timecreated = time();
        $record->timemodified = time();

        $id = $DB->insert_record('tool_moodiyregistration', $record);
    }
    return true;
}
