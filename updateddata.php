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
 * Update endpoint for Moodiy integration.
 *
 * @package    tool_moodiyregistration
 * @copyright  2025 VidyaMantra <pinky@vidyamantra.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Disable moodle specific debug messages and any errors in output.
define('NO_DEBUG_DISPLAY', true);

// We need to use the right AJAX has_capability() check.
define('AJAX_SCRIPT', true);

// No need for Moodle cookies here (avoid session locking).
define('NO_MOODLE_COOKIES', true);

// Allow direct access to this endpoint without login requirement.
define('NO_REDIRECT_ON_UPGRADE', true);

require_once('../../../config.php');
require_once($CFG->libdir . '/filelib.php');
use tool_moodiyregistration\api;

// Set the appropriate content type for JSON responses.
header('Content-Type: application/json; charset=utf-8');

// Allow CORS requests from the Laravel application.
header('Access-Control-Allow-Origin:'. api::get_apiurl());
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request (preflight).
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed.
    echo json_encode([
        'status' => 'error',
        'message' => 'Only POST method is allowed',
    ]);
    exit;
}

/**
 * Get header key - reliable cross-server method
 */
function get_all_headers() {
    $headers = [];

    // If getallheaders() is available (Apache), use it.
    if (function_exists('getallheaders')) {
        return getallheaders();
    }

    // Otherwise manually extract headers from $_SERVER.
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) === 'HTTP_') {
            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$name] = $value;
        } else if ($name === 'CONTENT_TYPE') {
            $headers['Content-Type'] = $value;
        } else if ($name === 'CONTENT_LENGTH') {
            $headers['Content-Length'] = $value;
        } else if ($name === 'AUTHORIZATION') {
            $headers['Authorization'] = $value;
        }
    }

    return $headers;
}

// Get header key.
$headers = get_all_headers();
foreach ($headers as $key => $value) {
    if (strtolower($key) === 'key') {
        $headerkey = $value;
        break;
    }
}

// Get the hmac hashed payload from the POST data.
$input = file_get_contents('php://input');
$postdata = json_decode($input, true);

// If raw JSON parsing fails, try regular POST data.
if (json_last_error() !== JSON_ERROR_NONE) {
    $postdata = $_POST;
}
// Check for valid payload.
ksort($postdata);
$payload = json_encode($postdata);
$hmackey = hash_hmac('sha256', $payload, $postdata['site_uuid']);

if (!hash_equals($hmackey, $headerkey)) {
    // Invalid HMAC key.
    http_response_code(403); // Forbidden.
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid HMAC key',
    ]);
    exit;
}

// Validate the verification data.
if (isset($postdata['site_uuid']) && isset($postdata['id'])) {

    if ($DB->record_exists('tool_moodiyregistration', ['site_uuid' => $postdata['site_uuid']])) {
        $response = [
        'status' => 'success',
        'message' => 'ok',
        ];
        echo json_encode($response);

        // Trigger a site registration update request event.
        $event = \tool_moodiyregistration\event\update_request::create([
            'context' => context_system::instance(),
            'objectid' => $postdata['id'],
            'other' => [
                'site_uuid' => $postdata['site_uuid'],
            ],
        ]);
        $event->trigger();
        exit;
    } else {
        // Invalid verification.
        http_response_code(403); // Forbidden.
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid data',
        ]);
        exit;
    }
}
