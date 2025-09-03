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
 * Plugin administration pages are defined here.
 *
 * @package     tool_moodiyregistration
 * @category    admin
 * @copyright   2025 VidyaMantra <pinky@vidyamantra.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('root', new admin_externalpage('moodiyregistration',
        get_string('pluginname', 'tool_moodiyregistration'),
        new moodle_url('/admin/tool/moodiyregistration/index.php')));

    $settings = new admin_settingpage('tool_moodiyregistration_settings', new lang_string('pluginname', 'tool_moodiyregistration'));

    $settings->add(new admin_setting_configtext(
        'tool_moodiyregistration/apiurl',
        new lang_string('apiurl', 'tool_moodiyregistration'),
        new lang_string('apiurl_desc', 'tool_moodiyregistration'),
        'https://api.moodiycloud.com',
        PARAM_URL
    ));
    $redirect_url = new moodle_url('/admin/tool/moodiyregistration/index.php');
    $redirectjs = new admin_setting_description(
        'tool_moodiyregistration/redirectjs',
        '',
        '<div id="tool_moodiyregistration_redirect_container">
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const form = document.querySelector("#adminsettings");
                    if (form) {
                        form.addEventListener("submit", function() {
                            // Store a flag in session storage
                            sessionStorage.setItem("moodiy_settings_saved", "1");
                        });
                    }

                    // Check if we\'re coming back after a save
                    if (sessionStorage.getItem("moodiy_settings_saved") === "1") {
                        sessionStorage.removeItem("moodiy_settings_saved");
                        window.location.href = "' . $redirect_url->out(false) . '";
                    }
                });
            </script>
        </div>'
    );
    $settings->add($redirectjs);

    $ADMIN->add('tools', $settings);
}
