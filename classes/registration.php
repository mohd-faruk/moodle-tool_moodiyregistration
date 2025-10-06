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

    /** @var string Moodiy API URL */
    const MOODIYURL = 'https://moodiycloud.com';

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
     * Summary of data that will be sent to the sites directory.
     *
     * @param array $siteinfo result of get_site_info()
     * @return string
     */
    public static function get_stats_summary($siteinfo) {
        $fieldsneedconfirm = \core\hub\registration::get_new_registration_fields();
        $summary = html_writer::tag('p', get_string('sendfollowinginfo_help', 'tool_moodiyregistration')) .
            html_writer::start_tag('ul');

        $mobileservicesenabled = $siteinfo['mobileservicesenabled'] ? get_string('yes') : get_string('no');
        $mobilenotificationsenabled = $siteinfo['mobilenotificationsenabled'] ? get_string('yes') : get_string('no');
        $moodlerelease = $siteinfo['moodlerelease'];
        if (preg_match('/^(\d+\.\d.*?)[\. ]/', $moodlerelease, $matches)) {
            $moodlerelease = $matches[1];
        }
        $pluginusagelinks = [
            'overview' => new moodle_url('/admin/plugins.php'),
            'activities' => new moodle_url('/admin/modules.php'),
            'blocks' => new moodle_url('/admin/blocks.php'),
        ];
        $senddata = [
            'moodlerelease' => get_string('sitereleasenum', 'hub', $moodlerelease),
            'courses' => get_string('coursesnumber', 'hub', $siteinfo['courses']),
            'users' => get_string('usersnumber', 'hub', $siteinfo['users']),
            'activeusers' => get_string('activeusersnumber', 'hub', $siteinfo['activeusers']),
            'enrolments' => get_string('roleassignmentsnumber', 'hub', $siteinfo['enrolments']),
            'posts' => get_string('postsnumber', 'hub', $siteinfo['posts']),
            'questions' => get_string('questionsnumber', 'hub', $siteinfo['questions']),
            'resources' => get_string('resourcesnumber', 'hub', $siteinfo['resources']),
            'badges' => get_string('badgesnumber', 'hub', $siteinfo['badges']),
            'issuedbadges' => get_string('issuedbadgesnumber', 'hub', $siteinfo['issuedbadges']),
            'participantnumberaverage' => get_string('participantnumberaverage', 'hub',
                format_float($siteinfo['participantnumberaverage'], 2)),
            'activeparticipantnumberaverage' => get_string('activeparticipantnumberaverage', 'hub',
                format_float($siteinfo['activeparticipantnumberaverage'], 2)),
            'modulenumberaverage' => get_string('modulenumberaverage', 'hub',
                format_float($siteinfo['modulenumberaverage'], 2)),
            'mobileservicesenabled' => get_string('mobileservicesenabled', 'hub', $mobileservicesenabled),
            'mobilenotificationsenabled' => get_string('mobilenotificationsenabled', 'hub', $mobilenotificationsenabled),
            'registereduserdevices' => get_string('registereduserdevices', 'hub', $siteinfo['registereduserdevices']),
            'registeredactiveuserdevices' => get_string('registeredactiveuserdevices', 'hub',
             $siteinfo['registeredactiveuserdevices']),
            'analyticsenabledmodels' => get_string('analyticsenabledmodels', 'hub', $siteinfo['analyticsenabledmodels']),
            'analyticspredictions' => get_string('analyticspredictions', 'hub', $siteinfo['analyticspredictions']),
            'analyticsactions' => get_string('analyticsactions', 'hub', $siteinfo['analyticsactions']),
            'analyticsactionsnotuseful' => get_string('analyticsactionsnotuseful', 'hub', $siteinfo['analyticsactionsnotuseful']),
            'dbtype' => get_string('dbtype', 'hub', $siteinfo['dbtype']),
            'coursesnodates' => get_string('coursesnodates', 'hub', $siteinfo['coursesnodates']),
            'sitetheme' => get_string('sitetheme', 'hub', $siteinfo['sitetheme']),
            'primaryauthtype' => get_string('primaryauthtype', 'hub', $siteinfo['primaryauthtype']),
            'pluginusage' => get_string('pluginusagedata', 'hub', $pluginusagelinks),
            'aiusage' => get_string('aiusagestats', 'hub', self::get_ai_usage_time_range(true)),
        ];

        foreach ($senddata as $key => $str) {
            $class = in_array($key, $fieldsneedconfirm) ? ' needsconfirmation mark' : '';
            $summary .= html_writer::tag('li', $str, ['class' => 'site' . $key . $class]);
        }
        $summary .= html_writer::end_tag('ul');
        return $summary;
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
     * Prepare site information.
     *
     * @param array $defaults default values for inputs in the registration form (if site was never registered before)
     * @return array site info
     */
    public static function get_saved_form_data($defaults = []) {
        $siteinfo = [];
        foreach (self::FORM_FIELDS as $field) {
            $siteinfo[$field] = get_config('tool_moodiyregistration', 'site_'.$field);
            if ($siteinfo[$field] === false) {
                $siteinfo[$field] = array_key_exists($field, $defaults) ? $defaults[$field] : null;
            }
        }
        return $siteinfo;
    }

    /**
     * Calculates and prepares site information for the registration form.
     *
     * @param array $defaults default values for inputs in the registration form (if site was never registered before)
     * @return array site info
     */
    public static function get_site_info($defaults = []) {
        global $CFG, $DB;

        $siteinfo = self::get_saved_form_data($defaults);

        // Statistical data.
        $metadata  = self::get_site_metadata($defaults);
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
        $aiusagedata = self::get_ai_usage_data();
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
                    $secret = $response['data']['site_uuid'] ?? '';
                }
                // Create a new record in 'tool_moodiyregistration'.
                $record = new stdClass();
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
            $DB->delete_records('tool_moodiyregistration', ['site_uuid' => $registration->site_uuid]);
            // Trigger a site unregistration event.
            $event = \tool_moodiyregistration\event\moodiy_unregistration::create([
                'context' => context_system::instance(),
                'objectid' => $registration->id,
                'other' => [
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
     * Get the time range to use in collected and reporting AI usage data.
     *
     * @param bool $format Use true to format timestamp.
     * @return array
     */
    private static function get_ai_usage_time_range(bool $format = false): array {
        global $DB, $CFG;

        // We will try and use the last time this site was last registered for our 'from' time.
        // Otherwise, default to using one week's worth of data to roughly match the site rego scheduled task.
        $timenow = \core\di::get(\core\clock::class)->time();
        $defaultfrom = $timenow - WEEKSECS;
        $timeto = $timenow;
        $params = [
            'site_url' => $CFG->wwwroot,
        ];
        $lastregistered = $DB->get_field('tool_moodiyregistration', 'timemodified', $params);
        $timefrom = $lastregistered ? (int)$lastregistered : $defaultfrom;

        if ($format) {
            $timefrom = userdate($timefrom);
            $timeto = userdate($timeto);
        }

        return [
            'timefrom' => $timefrom,
            'timeto' => $timeto,
        ];
    }

    /**
     * Get AI usage data.
     *
     * @return array
     */
    public static function get_ai_usage_data(): array {
        global $DB;

        $params = self::get_ai_usage_time_range();

        $sql = "SELECT aar.*
                  FROM {ai_action_register} aar
                 WHERE aar.timecompleted >= :timefrom
                   AND aar.timecompleted <= :timeto";

        $actions = $DB->get_records_sql($sql, $params);

        // Build data for site info reporting.
        $data = [];

        foreach ($actions as $action) {
            $provider = $action->provider;
            $actionname = $action->actionname;

            // Initialise data structure.
            if (!isset($data[$provider][$actionname])) {
                $data[$provider][$actionname] = [
                    'success_count' => 0,
                    'fail_count' => 0,
                    'times' => [],
                    'errors' => [],
                ];
            }

            if ($action->success === '1') {
                $data[$provider][$actionname]['success_count'] += 1;
                // Collect AI processing times for averaging.
                $data[$provider][$actionname]['times'][] = (int)$action->timecompleted - (int)$action->timecreated;

            } else {
                $data[$provider][$actionname]['fail_count'] += 1;
                // Collect errors for determing the predominant one.
                $data[$provider][$actionname]['errors'][] = $action->errorcode;
            }
        }

        // Parse the errors and everage the times, then add them to the data.
        foreach ($data as $p => $provider) {
            foreach ($provider as $a => $actionname) {
                if (isset($data[$p][$a]['errors'])) {
                    // Create an array with the error codes counted.
                    $errors = array_count_values($data[$p][$a]['errors']);
                    if (!empty($errors)) {
                        // Sort values descending and convert to an array of error codes (most predominant will be at start).
                        arsort($errors);
                        $errors = array_keys($errors);
                        $data[$p][$a]['predominant_error'] = $errors[0];
                    }
                    unset($data[$p][$a]['errors']);
                }

                if (isset($data[$p][$a]['times'])) {
                    $count = count($data[$p][$a]['times']);
                    if ($count > 0) {
                        // Average the time to perform the action (seconds).
                        $totaltime = array_sum($data[$p][$a]['times']);
                        $data[$p][$a]['average_time'] = round($totaltime / $count);

                    }
                }
                unset($data[$p][$a]['times']);
            }
        }

        // Include the time range used to help interpret the data.
        if (!empty($data)) {
            $data['time_range'] = $params;
        }

        return $data;
    }

    /**
     * Calculates and prepares site information to send to the moodiy as a part of registration.
     * Metadata should be json encoded.
     *
     * @return array site info
     */
    public static function get_siteinfo() {
        global $CFG;
        $siteinfo = self::get_saved_form_data();
        $siteinfo['site_url'] = $CFG->wwwroot;
        $siteinfo['timestamp'] = time();

        // Statistical data.
        $metadata  = self::get_site_metadata();
        $siteinfo['site_metadata'] = json_encode($metadata);
        return $siteinfo;
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
                $siteinfo = self::get_siteinfo();
                $siteinfo['site_uuid'] = $registration->site_uuid;
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

        $siteinfo = self::get_siteinfo();
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

    /**
     * Registers an internal site with Moodiy.
     *
     * @param string $uuid The UUID of the site.
     * @return bool True on success, false on failure.
     */
    public static function register_internal_site($uuid) {
        global $DB, $CFG;

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
        self::save_site_info($sitedata);

        // Create a new record in 'tool_moodiyregistration'.
        $record = new \stdClass();
        $record->site_uuid = $uuid;
        $record->site_url = $CFG->wwwroot;
        $record->timecreated = time();
        $record->timemodified = time();

        return $DB->insert_record('tool_moodiyregistration', $record);
    }

    /**
     * Get the site UUID.
     *
     * This method retrieves the site UUID from the registration record.
     *
     * @return string|null The site UUID or null if not registered.
     */
    public static function get_siteuuid() {
        global $DB;

        $registration = self::get_registration();
        if ($registration) {
            return $registration->site_uuid;
        }
        return null;
    }

    /**
     * Check for Moodiycloud products, if found prevent unregistration.
     *
     * @return bool
     */
    public static function can_unregister(): bool {
        return !get_config('tool_moodiymobile', 'enabled');
    }

    /**
     * Display a warning box with a single continue button.
     *
     * @param string $message The warning message to display.
     * @param string|moodle_url|\single_button $continue The URL or button for the continue action.
     * @param array $displayoptions Optional display options:
     *      - confirmtitle: Title of the warning box (default: 'Warning').
     *      - continuestr: Text for the continue button (default: 'Continue').
     *      - type: Button type for the continue button (default: primary).
     *
     * @return string The HTML output of the warning box.
     * @throws coding_exception If the $continue parameter is invalid.
     */
    public static function warningbox($message, $continue, array $displayoptions = []) {
        global $OUTPUT;
        // Check existing displayoptions.
        $displayoptions['confirmtitle'] = $displayoptions['confirmtitle'] ?? get_string('warning', 'core');
        $displayoptions['continuestr'] = $displayoptions['continuestr'] ?? get_string('continue');

        if ($continue instanceof \single_button) {
            // Continue button should be primary if set to secondary type as it is the fefault.
            if ($continue->type === \single_button::BUTTON_SECONDARY) {
                $continue->type = \single_button::BUTTON_PRIMARY;
            }
        } else if (is_string($continue)) {
            $continue = new \single_button(
                new moodle_url($continue),
                $displayoptions['continuestr'],
                'post',
                $displayoptions['type'] ?? \single_button::BUTTON_PRIMARY
            );
        } else if ($continue instanceof moodle_url) {
            $continue = new \single_button(
                $continue,
                $displayoptions['continuestr'],
                'post',
                $displayoptions['type'] ?? \single_button::BUTTON_PRIMARY
            );
        } else {
            throw new coding_exception('The continue param to $OUTPUT->confirm() must be either
             a URL (string/moodle_url) or a single_button instance.');
        }
        // Prepare the modal dialog.
        $attributes = [
            'role' => 'alertdialog',
            'aria-labelledby' => 'modal-header',
            'aria-describedby' => 'modal-body',
            'aria-modal' => 'true',
        ];

        $output = $OUTPUT->box_start('generalbox modal modal-dialog modal-in-page show', 'notice', $attributes);
        $output .= $OUTPUT->box_start('modal-content', 'modal-content');
        $output .= $OUTPUT->box_start('modal-header px-3', 'modal-header');
        $output .= html_writer::tag('h4', $displayoptions['confirmtitle']);
        $output .= $OUTPUT->box_end();
        $attributes = [
            'role' => 'alert',
            'data-aria-autofocus' => 'true',
        ];
        $output .= $OUTPUT->box_start('modal-body', 'modal-body', $attributes);
        $output .= html_writer::tag('p', $message);
        $output .= $OUTPUT->box_end();
        $output .= $OUTPUT->box_start('modal-footer', 'modal-footer');
        $output .= html_writer::tag('div', $OUTPUT->render($continue), ['class' => 'buttons']);
        $output .= $OUTPUT->box_end();
        $output .= $OUTPUT->box_end();
        $output .= $OUTPUT->box_end();
        return $output;
    }

}
