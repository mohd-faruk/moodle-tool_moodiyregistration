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
 * Observer class containing methods monitoring various events.
 *
 * @package     tool_moodiyregistration
 * @copyright   2025 VidyaMantra <pinky@vidyamantra.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_moodiyregistration;

/**
 * Class eventobservers
 *
 * This class contains methods that respond to various events in Moodle.
 *
 * @package tool_moodiyregistration
 */
class eventobservers {
    /**
     * Observer method to response when request received form
     * moodiy to pull registration update.
     *
     * This method is triggered when request to pull updated data received.
     *
     * @param \tool_moodiyregistration\event\moodiyregistration_updated $event The event object.
     */
    public static function process_update_request(\tool_moodiyregistration\event\update_request $event) {

        // Inactivate registration with Moodidy.
        \tool_moodiyregistration\registration::update_registration();
    }
}
