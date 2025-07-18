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
 * Class registration
 *
 * @package    tool_moodiyregistration
 * @copyright  2025 VidyaMantra <pinky@vidyamantra.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_moodiyregistration;

defined('MOODLE_INTERNAL') || die();

use moodle_exception;
use moodle_url;
use context_system;
use stdClass;
use html_writer;
use core_plugin_manager;
use tool_moodiyregistration\api;

/**
 * Methods to use when registering the site at the moodiy sites directory.
 *
 * @package    tool_moodiyregistration
 * @copyright  2025 VidyaMantra <pinky@vidyamantra.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class registration {

    /** @var array Fields used in a site registration form.
     * IMPORTANT: any new fields with non-empty defaults have to be added to CONFIRM_NEW_FIELDS */
    const FORM_FIELDS = ['policyagreed', 'language', 'country_code', 'privacy',
        'admin_email', 'site_name', 'description', 'organisation_type'];

    /** @var stdClass cached site registration information */
    protected static $registration = null;

    /**
     * Checks if site is registered
     *
     * @return bool
     */
    public static function is_registered() {
        return self::get_registration() ? true : false;
    }

    /**
     * Get the options for organisation type form element to use in registration form.
     *
     * Indexes reference Moodle internal ids and should not be changed.
     *
     * @return array
     */
    public static function get_site_organisation_type_options(): array {
        return [
            'wholeuniversity' => get_string('siteorganisationtype:wholeuniversity', 'hub'),
            'universitydepartment' => get_string('siteorganisationtype:universitydepartment', 'hub'),
            'college' => get_string('siteorganisationtype:college', 'hub'),
            'collegedepartment' => get_string('siteorganisationtype:collegedepartment', 'hub'),
            'highschool' => get_string('siteorganisationtype:highschool', 'hub'),
            'highschooldepartment' => get_string('siteorganisationtype:highschooldepartment', 'hub'),
            'primaryschool' => get_string('siteorganisationtype:primaryschool', 'hub'),
            'independentteacher' => get_string('siteorganisationtype:independentteacher', 'hub'),
            'companyinternal' => get_string('siteorganisationtype:companyinternal', 'hub'),
            'companydepartment' => get_string('siteorganisationtype:companydepartment', 'hub'),
            'commercialcourseprovider' => get_string('siteorganisationtype:commercialcourseprovider', 'hub'),
            'other' => get_string('siteorganisationtype:other', 'hub'),
            'highschooldistrict' => get_string('siteorganisationtype:highschooldistrict', 'hub'),
            'government' => get_string('siteorganisationtype:government', 'hub'),
            'charityornotforprofit' => get_string('siteorganisationtype:charityornotforprofit', 'hub'),
            'charterschool' => get_string('siteorganisationtype:charterschool', 'hub'),
            'schooldistrict' => get_string('siteorganisationtype:schooldistrict', 'hub'),
            'hospital' => get_string('siteorganisationtype:hospital', 'hub'),
        ];
    }

    /**
     * Get site registration
     *
     * @return stdClass|null
     */
    protected static function get_registration() {
        global $DB;

        // For PHPUnit tests, always get fresh data.
        if (PHPUNIT_TEST) {
            self::$registration = null;
        }

        if (self::$registration === null) {
            self::$registration = $DB->get_record_sql('SELECT * FROM {tool_moodiyregistration}') ?: null;
        }

        if (self::$registration && !empty(self::$registration->site_uuid)) {
            return self::$registration;
        }

        return null;
    }

    /**
     * Save registration info locally so it can be retrieved when registration needs to be updated
     *
     * @param stdClass $formdata data from {@link site_registration_form}
     */
    public static function save_site_info($formdata) {
        foreach (self::FORM_FIELDS as $field) {
            set_config('site_' . $field, $formdata->$field, 'tool_moodiyregistration');
        }
    }

    /**
     * When was the registration last updated
     *
     * @return int|null timestamp or null if site is not registered
     */
    public static function get_last_updated() {
        if ($registration = self::get_registration()) {
            return $registration->timemodified;
        }
        return null;
    }

    /**
     * Calculates and prepares site information to send to the sites directory as a part of registration.
     *
     * @param array $defaults default values for inputs in the registration form (if site was never registered before)
     * @return array site info
     */
    public static function get_site_info($defaults = []) {
        global $CFG, $DB;

        $siteinfo = [];
        foreach (self::FORM_FIELDS as $field) {
            $siteinfo[$field] = get_config('tool_moodiyregistration', 'site_'.$field);
            if ($siteinfo[$field] === false) {
                $siteinfo[$field] = array_key_exists($field, $defaults) ? $defaults[$field] : null;
            }
        }
        $siteinfo['timestamp'] = time();

        // Statistical data.
        $metadata  = self::get_site_metadata($defaults = []);
        return array_merge($siteinfo, $metadata);
    }

    /**
     * Prepare data to send to the sites directory
     *
     * This method prepares data to be sent to the sites directory as a part of registration.
     * It collects all the necessary information and formats it correctly.
     *
     * @param stdClass $formdata data from {@link site_registration_form}
     * @return array prepared data
     */
    public static function get_form_data($formdata) {
        global $CFG;
        $siteinfo = self::get_site_metadata();

        $data = [];
        $data['site_url'] = $CFG->wwwroot;
        $data['site_name'] = $formdata->site_name;
        $data['description'] = $formdata->description;
        $data['language'] = $formdata->language;
        $data['country_code'] = $formdata->country_code;
        $data['admin_email'] = $formdata->admin_email;
        $data['site_listing'] = $formdata->privacy;
        $data['organisation_type'] = $formdata->organisation_type;
        $data['timestamp'] = time();
        $data['site_metadata'] = json_encode($siteinfo);
        return $data;
    }

    /**
     * Get site metadata
     *
     * This method collects various statistics and information about the site that will be sent to the sites directory.
     *
     * @param array $defaults default values for inputs in the registration form (if site was never registered before)
     * @return array site metadata
     */
    public static function get_site_metadata($defaults = []) {
        global $CFG, $DB;
        require_once($CFG->libdir . '/badgeslib.php');
        require_once($CFG->dirroot . "/course/lib.php");

        $siteinfo = [];

        // Statistical data.
        $siteinfo['courses'] = $DB->count_records('course') - 1;
        $siteinfo['users'] = $DB->count_records('user', ['deleted' => 0]);
        $siteinfo['activeusers'] = $DB->count_records_select('user', 'deleted = ? AND lastlogin > ?', [0, time() - DAYSECS * 30]);
        $siteinfo['enrolments'] = $DB->count_records('role_assignments');
        $siteinfo['posts'] = $DB->count_records('forum_posts');
        $siteinfo['questions'] = $DB->count_records('question');
        $siteinfo['resources'] = $DB->count_records('resource');
        $siteinfo['badges'] = $DB->count_records_select('badge', 'status <> ' . BADGE_STATUS_ARCHIVED);
        $siteinfo['issuedbadges'] = $DB->count_records('badge_issued');
        $siteinfo['participantnumberaverage'] = average_number_of_participants();
        $siteinfo['activeparticipantnumberaverage'] = average_number_of_participants(true, time() - DAYSECS * 30);
        $siteinfo['modulenumberaverage'] = average_number_of_courses_modules();
        $siteinfo['dbtype'] = $CFG->dbtype;
        $siteinfo['coursesnodates'] = $DB->count_records_select('course', 'enddate = ?', [0]) - 1;
        $siteinfo['sitetheme'] = get_config('core', 'theme');
        $siteinfo['pluginusage'] = json_encode(\core\hub\registration::get_plugin_usage_data());

        // AI usage data.
        $aiusagedata = \core\hub\registration::get_ai_usage_data();
        $siteinfo['aiusage'] = !empty($aiusagedata) ? json_encode($aiusagedata) : '';

        // Primary auth type.
        $primaryauthsql = 'SELECT auth, count(auth) as tc FROM {user} GROUP BY auth ORDER BY tc DESC';
        $siteinfo['primaryauthtype'] = $DB->get_field_sql($primaryauthsql, null, IGNORE_MULTIPLE);

        // Version and url.
        $siteinfo['moodlerelease'] = $CFG->release;
        $siteinfo['site_url'] = $CFG->wwwroot;

        // Mobile related information.
        $siteinfo['mobileservicesenabled'] = 0;
        $siteinfo['mobilenotificationsenabled'] = 0;
        $siteinfo['registereduserdevices'] = 0;
        $siteinfo['registeredactiveuserdevices'] = 0;
        if (!empty($CFG->enablewebservices) && !empty($CFG->enablemobilewebservice)) {
            $siteinfo['mobileservicesenabled'] = 1;
            $siteinfo['registereduserdevices'] = $DB->count_records('user_devices');
            $airnotifierextpath = $CFG->dirroot . '/message/output/airnotifier/externallib.php';
            if (file_exists($airnotifierextpath)) { // Maybe some one uninstalled the plugin.
                require_once($airnotifierextpath);
                $siteinfo['mobilenotificationsenabled'] = \message_airnotifier_external::is_system_configured();
                $siteinfo['registeredactiveuserdevices'] = $DB->count_records('message_airnotifier_devices', ['enable' => 1]);
            }
        }

        // Analytics related data follow.
        $siteinfo['analyticsenabledmodels'] = \core_analytics\stats::enabled_models();
        $siteinfo['analyticspredictions'] = \core_analytics\stats::predictions();
        $siteinfo['analyticsactions'] = \core_analytics\stats::actions();
        $siteinfo['analyticsactionsnotuseful'] = \core_analytics\stats::actions_not_useful();

        // IMPORTANT: any new fields in siteinfo have to be added to the constant CONFIRM_NEW_FIELDS.

        return $siteinfo;
    }

    /**
     * Get the API wrapper instance.
     *
     * @return api_wrapper
     */
    protected static function get_api_wrapper() {
        global $CFG;
        // Allow for test injection.
        if (PHPUNIT_TEST && isset($CFG->tool_moodiyregistration_test_api_wrapper)) {
            return $CFG->tool_moodiyregistration_test_api_wrapper;
        }
        return new api_wrapper();
    }

    /**
     * Registers a site
     *
     * This method will make sure that unconfirmed registration record is created and then redirect to
     * registration script on the sites directory.
     * The sites directory will check that the site is accessible, register it and redirect back
     * to /admin/registration/confirmregistration.php
     *
     * @param string $returnurl
     * @throws \coding_exception
     */
    public static function register($data, $returnurl) {
        global $DB, $SESSION, $CFG;

        if (self::is_registered()) {
            // Caller of this method must make sure that site is not registered.
            throw new \coding_exception('Site already registered');
        }

        $data = self::get_form_data($data);

        $record = self::get_registration(false);
        if (empty($record)) {
            $verificationkey = md5($CFG->wwwroot . microtime(true));
            $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, 'tool_moodiyregistration', 'registration');

            if ($cache->get('verificationkey')) {
                // Delete the old verification key if it exists.
                $cache->delete('verificationkey');
            }
            $cache->set('verificationkey', $verificationkey);
            $data['verification_key'] = $verificationkey;

            try {
                $api = self::get_api_wrapper();
                $response = $api->moodiy_registration($data);

                if (empty($response) || !is_array($response)) {
                    throw new moodle_exception('errorconnect', 'tool_moodiyregistration', '', 'Invalid response from moodiy');
                }
                if (isset($response['data']) && is_array($response['data'])) {
                    $registrationid = $response['data']['id'] ?? 0;
                    $secret = $response['data']['site_uuid'] ?? '';
                }
                // Create a new record in 'tool_moodiyregistration'.
                $record = new stdClass();
                $record->registrationid = $registrationid;
                $record->site_uuid = $secret;
                $record->site_url = $data['site_url'];
                $record->timecreated = time();
                $record->timemodified = time();
                $record->id = $DB->insert_record('tool_moodiyregistration', $record);
                self::$registration = true;
                // Delete the verification key from cache after successful registration.
                $cache->delete('verificationkey');
                // Trigger a site registration event.
                $event = \tool_moodiyregistration\event\moodiy_registration::create([
                    'context' => context_system::instance(),
                    'objectid' => $record->id,
                    'other' => [
                        'registrationid' => $record->registrationid,
                        'site_uuid' => $record->site_uuid,
                    ],
                ]);
                $event->add_record_snapshot('tool_moodiyregistration', $record);
                $event->trigger();

            } catch (\moodle_exception $e) {
                // If the table does not exist, we will create it later.
                if ($e->getMessage() === 'errorconnect') {
                    throw new moodle_exception('errorconnect', 'tool_moodiyregistration', '', $e->getMessage());
                } else {
                    \core\notification::add(get_string('registrationerror', 'tool_moodiyregistration', $e->getMessage()),
                    \core\output\notification::NOTIFY_ERROR);
                    return false;
                }
            }
            if (PHPUNIT_TEST) {
                // In tests we do not redirect, just return the response.
                return $response;
            }
            redirect(new moodle_url('/admin/tool/moodiyregistration/registrationconfirm.php', [
                'confirm' => self::$registration,
            ]));
        }
    }

    /**
     * Updates site registration when "Update reigstration" button is clicked by admin
     */
    public static function update_manual($fomdata) {
        global $DB, $CFG;

        if (!$registration = self::get_registration()) {
            return false;
        }

        $data = self::get_form_data($fomdata);
        $data['site_uuid'] = $registration->site_uuid;

        try {
            $api = self::get_api_wrapper();
            $api->update_registration($registration, $data);
            $DB->update_record('tool_moodiyregistration', ['id' => $registration->id, 'timemodified' => time()]);
            // Trigger a site registration updated event.
            $event = \tool_moodiyregistration\event\moodiyregistration_updated::create([
                'context' => context_system::instance(),
                'objectid' => $registration->id,
                'other' => [
                    'registrationid' => $registration->registrationid,
                    'site_uuid' => $registration->site_uuid,
                ],
            ]);
            $event->add_record_snapshot('tool_moodiyregistration', $registration);
            $event->trigger();
            \core\notification::add(get_string('siteregistrationupdated', 'tool_moodiyregistration'),
            \core\output\notification::NOTIFY_SUCCESS);
        } catch (moodle_exception $e) {
            \core\notification::add(get_string('errorregistrationupdate', 'tool_moodiyregistration', $e->getMessage()),
                \core\output\notification::NOTIFY_ERROR);
            return false;
        }
        self::$registration = null;
        return true;
    }

    /**
     * Unregister site
     *
     * @param bool $unpublishalladvertisedcourses
     * @param bool $unpublishalluploadedcourses
     * @return bool
     */
    public static function unregister() {
        global $DB;

        if (!$registration = self::get_registration()) {
            return true;
        }

        // Unregister the site now.
        try {
            $api = self::get_api_wrapper();
            $api->unregister_site($registration);
            $DB->delete_records('tool_moodiyregistration', ['registrationid' => $registration->registrationid]);
            // Trigger a site unregistration event.
            $event = \tool_moodiyregistration\event\moodiy_unregistration::create([
                'context' => context_system::instance(),
                'objectid' => $registration->id,
                'other' => [
                    'registrationid' => $registration->registrationid,
                    'site_uuid' => $registration->site_uuid,
                ],
            ]);
            $event->add_record_snapshot('tool_moodiyregistration', $registration);
            $event->trigger();
            \core\notification::add('Site deleted successfully from Moodiy.',
                \core\output\notification::NOTIFY_SUCCESS);
            self::$registration = null;
        } catch (moodle_exception $e) {
            \core\notification::add(get_string('unregistrationerror', 'tool_moodiyregistration', $e->getMessage()),
                \core\output\notification::NOTIFY_ERROR);
            return false;
        }

        return true;
    }

    /**
     * Updates the site URL in the registration record.
     *
     * This method checks if the site is registered and if the site URL has changed.
     * If so, it updates the registration record with the new site URL and triggers an event.
     *
     * @return bool|moodle_url Returns true on success, false if not registered, or a moodle_url to redirect to registration page.
     * @throws moodle_exception
     */
    public static function update_registration_siteurl() {
        global $CFG, $DB;

        if (self::is_registered()) {
            $registration = self::get_registration();
            if (strcmp($registration->site_url, $CFG->wwwroot) !== 0) {
                $siteinfo = self::get_site_info();
                $siteinfo['site_uuid'] = $registration->site_uuid;
                $siteinfo['timestamp'] = time();
                try {
                    api::update_registration($registration, $siteinfo);

                    // Update the site URL in the registration record.
                    $registration->site_url = $CFG->wwwroot;
                    $registration->timemodified = time();
                    $DB->update_record('tool_moodiyregistration', $registration);
                    self::$registration = null;
                    mtrace(get_string('siteregistrationurlupdated', 'tool_moodiyregistration'));

                    // Trigger a site registration updated event.
                    $event = \tool_moodiyregistration\event\moodiyregistration_updated::create([
                        'context' => context_system::instance(),
                        'objectid' => $registration->id,
                        'other' => [
                            'registrationid' => $registration->registrationid,
                            'site_uuid' => $registration->site_uuid,
                            'site_url' => $registration->site_url,
                        ],
                    ]);
                    $event->add_record_snapshot('tool_moodiyregistration', $registration);
                    $event->trigger();
                } catch (moodle_exception $e) {
                    mtrace($e->getMessage());
                    return false;
                }
            }
            return true;
        } else {
            return new moodle_url($CFG->wwwroot . '/admin/tool/moodiyregistration/registration.php');
        }
    }

    /**
     * Updates site registration via scheduled task.
     *
     * @throws moodle_exception
     */
    public static function update_registration() {
        global $DB;

        if (!$registration = self::get_registration()) {
            return;
        }

        $siteinfo = self::get_site_info();
        $siteinfo['site_uuid'] = $registration->site_uuid;
        try {
            $api = self::get_api_wrapper();
            $api->update_registration($registration, $siteinfo);
            $DB->update_record('tool_moodiyregistration', ['id' => $registration->id, 'timemodified' => time()]);

            self::$registration = null;
            // Trigger a site registration updated event.
            $event = \tool_moodiyregistration\event\moodiyregistration_updated::create([
                'context' => context_system::instance(),
                'objectid' => $registration->id,
                'other' => [
                    'registrationid' => $registration->registrationid,
                    'site_uuid' => $registration->site_uuid,
                    'site_url' => $registration->site_url,
                ],
            ]);
            $event->add_record_snapshot('tool_moodiyregistration', $registration);
            $event->trigger();
        } catch (moodle_exception $e) {
            debugging('Error updating registration: ' . $e->getMessage());
            return;
        }
    }
}
