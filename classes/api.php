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
 * Class api for moodiy communication
 *
 * @package    tool_moodiyregistration
 * @copyright  2025 VidyaMantra <pinky@vidyamantra.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_moodiyregistration;
defined('MOODLE_INTERNAL') || die();

use moodle_exception;
use curl;
use stdClass;
use coding_exception;
use moodle_url;

/**
 * Provides methods to communicate with the hub (sites directory) web services.
 *
 * @package    tool_moodiyregistration
 * @copyright  2025 VidyaMantra <pinky@vidyamantra.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {
    /** @var string The Moodiy API URL */
    const MOODIY_API_URL = 'https://api.moodiycloud.com';
    /** Error message when site registration does not exist. */
    public const ERROR_REGISTRATION_NONEXISTENT = 'site registration does not exist';

    /**
     * Get the API URL for Moodiy.
     *
     * @return string The API URL.
     */
    public static function get_apiurl() {
        global $CFG;

        $apiurl = $CFG->moodiy_api_url ?? self::MOODIY_API_URL;
        return rtrim($apiurl, '/') . '/api';
    }

    /**
     * Register the site with Moodiy.
     *
     * @param array $params Parameters for registration.
     * @return array Response from the API.
     * @throws moodle_exception If there is an error during the API call.
     */
    public static function moodiy_registration($params = []) {
        global $CFG;

        $endpoint = self::get_apiurl() . '/site/register';

        $curl = new curl();
        $header = ['Accept: application/json'];
        $curl->setHeader($header);
        $response = $curl->post($endpoint, $params);

        $response = json_decode($response, true);
        $info = $curl->get_info();
        if ($curl->get_errno()) {
            // Connection error.
            throw new moodle_exception('errorconnect', 'tool_moodiyregistration', '', $curl->error);
        } else if ($response === false) {
            throw new coding_exception('Error calling API: ' . $curl->getError());
        } else if ($info['http_code'] != 200 || empty($response['success'])) {
            throw new moodle_exception($response['message'] ?? 'Error during registration');
        } else {
            return $response;
        }
    }

    /**
     * Update the registration information of the site.
     *
     * @param object $reginfo Registration information.
     * @param array $params Parameters to update.
     * @return array Response from the API.
     * @throws moodle_exception If there is an error during the API call.
     */
    public static function update_registration(object $reginfo, array $params = []) {
        global $CFG;

        $endpoint = self::get_apiurl() . '/site/register/';
        try {
            ksort($params);
            $payload = json_encode($params);
        } catch (\Exception $e) {
            throw new moodle_exception( $e->getMessage());
        }

        $hmac = hash_hmac("sha256", $payload, $reginfo->site_uuid);

        $curl = new curl();
        $header = ['key: ' . $hmac];
        $curl->setHeader($header);
        // To fix laravel issue. Not parse the data if it's multipart/form-data(PUT/PATCH).
        $params['_method'] = 'PUT';

        $response = $curl->post($endpoint, $params);

        $response = json_decode($response, true);

        $info = $curl->get_info();
        if ($curl->get_errno()) {
            // Connection error.
            throw new moodle_exception('errorconnect', 'tool_moodiyregistration', '', $curl->error);
        } else if ($response === false) {
            throw new coding_exception('Error calling API: ' . $curl->getError());
        } else if ($info['http_code'] != 200 || empty($response['success'])) {
            if (isset($response['errors']) && is_array($response['errors'])) {
                foreach ($response['errors'] as $error) {
                    if (stripos($error, self::ERROR_REGISTRATION_NONEXISTENT) !== false) {
                        // Throw exception to remove registration from moodle.
                        throw new moodle_exception($error);
                    }
                }
            }
            throw new moodle_exception($response['message'] ?? 'Error during registration update');
        } else {
            return $response;
        }
    }

    /**
     * Unregister the site from Moodiy.
     *
     * @param object $reginfo Registration information.
     * @return array Response from the API.
     * @throws moodle_exception If there is an error during the API call.
     */
    public static function unregister_site(object $reginfo) {
        global $CFG;

        $endpoint = self::get_apiurl() . '/site/register/';
        $params = [];
        $params['site_uuid'] = $reginfo->site_uuid;
        $params['timestamp'] = time();
        ksort($params);
        $payload = json_encode($params);
        $hmac = hash_hmac("sha256", $payload, $reginfo->site_uuid);

        $curl = new curl();
        $header = ['key: ' . $hmac];
        $curl->setHeader($header);
        // To fix laravel issue. Not parse the data if it's multipart/form-data(PUT/PATCH).
        $params['_method'] = 'DELETE';

        $response = $curl->post($endpoint, $params);

        $response = json_decode($response, true);
        $info = $curl->get_info();

        if ($curl->get_errno()) {
            // Connection error.
            throw new moodle_exception('errorconnect', 'tool_moodiyregistration', '', $curl->error);
        } else if ($response === false) {
            throw new coding_exception('Error calling API: ' . $curl->getError());
        } else if ($info['http_code'] != 200 || empty($response['success'])) {
            if (isset($response['errors']) && is_array($response['errors'])) {
                foreach ($response['errors'] as $error) {
                    if (stripos($error, self::ERROR_REGISTRATION_NONEXISTENT) !== false) {
                        // Throw exception to remove registration from moodle.
                        throw new moodle_exception($error);
                    }
                }
            }
            throw new moodle_exception($response['message'] ?? 'Error during un-registration');
        }
        return $response;

    }
}
