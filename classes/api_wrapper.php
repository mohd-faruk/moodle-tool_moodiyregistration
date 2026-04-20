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

namespace tool_moodiyregistration;
defined('MOODLE_INTERNAL') || die();

/**
 * Class api_wrapper
 *
 * @package    tool_moodiyregistration
 * @copyright   2025-2026 MoodiyCloud <support@moodiycloud.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Wrapper for API calls to make testing easier.
 */
class api_wrapper {
    /**
     * Wrapper for static moodiy_registration method
     */
    public function moodiy_registration($params) {
        return api::moodiy_registration($params);
    }

    /**
     * Wrapper for static update_registration method
     */
    public function update_registration($reginfo, $data) {
        return api::update_registration($reginfo, $data);
    }

    /**
     * Wrapper for static unregister_site method
     */
    public function unregister_site($reginfo) {
        return api::unregister_site($reginfo);
    }
}
