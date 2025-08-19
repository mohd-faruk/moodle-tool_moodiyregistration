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
 * Unit tests for Moodiy Registration.
 *
 * @package     tool_moodiyregistration
 * @category    test
 * @copyright   2025 VidyaMantra <pinky@vidyamantra.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \tool_moodiyregistration\registration
 */

namespace tool_moodiyregistration;

use tool_moodiyregistration\registration;
use tool_moodiyregistration\api;

/**
 * Unit tests for registration functionality.
 */
class registration_test extends \advanced_testcase {

    /**
     * @var \stdClass Admin user for tests.
     */
    public $admin;
    /**
     * Set up tests.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);

        // Create an admin user for tests that require permissions.
        $uniquename = 'testadmin_' . uniqid();
        $this->admin = $this->getDataGenerator()->create_user([
            'username' => $uniquename,
            'email' => $uniquename . '@example.com',
            'country' => 'AU',
        ]);
        $this->setAdminUser($this->admin);

        // Set a test API URL.
        set_config('apiurl', 'https://test-api.moodiycloud.com', 'tool_moodiyregistration');
    }


    /**
     * Test site registration functionality.
     * @covers ::register
     */
    public function test_site_registration(): void {
        global $DB, $CFG;

        // Check that site is not registered initially.
        $this->assertFalse(registration::is_registered());

        // Create test registration data.
        $data = new \stdClass();
        $data->site_name = 'Test Moodle Site';
        $data->description = 'Test site description';
        $data->admin_email = 'admin@example.com';
        $data->country_code = 'AU';
        $data->language = 'en';
        $data->privacy = 'notdisplayed';
        $data->organisation_type = 'university';
        $data->policyagreed = 1;
        $data->site_url = $CFG->wwwroot;

        // Mock the verification key.
        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, 'tool_moodiyregistration', 'registration');
        $verificationkey = md5($CFG->wwwroot . microtime(true));
        $cache->set('verificationkey', $verificationkey);

        // Create a mock for the api class to prevent actual API calls.
        $apiwrapper = $this->createMock(\tool_moodiyregistration\api_wrapper::class);

        // Configure the mock.
        $apiwrapper->method('moodiy_registration')
            ->willReturn([
                'success' => true,
                'data' => [
                    'id' => 12345,
                    'site_uuid' => 'test-uuid-123456789',
                ],
            ]);

        // Set the mock for tests.
        $CFG->tool_moodiyregistration_test_api_wrapper = $apiwrapper;

        // Save site info.
        registration::save_site_info($data);

        // Perform the registration.
        $response = registration::register($data, '/admin/tool/moodiyregistration/index.php');

        // Mocked response to be returned.
        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertEquals(12345, $response['data']['id']);

        // Check that site is now registered.
        $record = $DB->get_record('tool_moodiyregistration', []);
        $this->assertNotEmpty($record);
        $this->assertEquals('test-uuid-123456789', $record->site_uuid);
        $this->assertEquals($CFG->wwwroot, $record->site_url);
    }

    /**
     * Test site unregistration functionality.
     * @covers ::unregister
     */
    public function test_site_unregistration(): void {
        global $DB, $CFG;

        // Insert a test record to simulate a registered site.
        $record = new \stdClass();
        $record->site_uuid = 'test-uuid-123456789';
        $record->site_url = 'https://example.moodle.org';
        $record->timecreated = time();
        $record->timemodified = time();
        $DB->insert_record('tool_moodiyregistration', $record);

        // Verify site is registered.
        $this->assertTrue(registration::is_registered());

        // Create a mock for the api class.
        $apiwrapper = $this->createMock(\tool_moodiyregistration\api_wrapper::class);
        $apiwrapper->expects($this->once())->method('unregister_site')->willReturn([
            'success' => true,
            'message' => 'Site unregistered successfully',
        ]);

        // Set the mock for tests.
        $CFG->tool_moodiyregistration_test_api_wrapper = $apiwrapper;

        // Unregister the site.
        $result = registration::unregister();
        // Check the result.
        $this->assertTrue($result);

        // Verify site is no longer registered.
        $this->assertFalse(registration::is_registered());
        $this->assertEquals(0, $DB->count_records('tool_moodiyregistration'));
    }
    /**
     * Test updating site registration.
     * @covers ::update_manual
     */
    public function test_update_manual(): void {
        global $DB, $CFG;

        // Insert a test record to simulate a registered site.
        $record = new \stdClass();
        $record->site_uuid = 'test-uuid-123456789';
        $record->site_url = 'https://example.moodle.org';
        $record->timecreated = time();
        $record->timemodified = time() - 86400; // 1 day ago.
        $recordid = $DB->insert_record('tool_moodiyregistration', $record);

        // Create updated registration data.
        $data = new \stdClass();
        $data->site_name = 'Updated Test Moodle Site';
        $data->description = 'Updated site description';
        $data->admin_email = 'updated_admin@example.com';
        $data->country_code = 'US';
        $data->language = 'en';
        $data->privacy = 'notdisplayed';
        $data->organisation_type = 'university';
        $data->policyagreed = 1;

        // Create a mock for the api class.
        $apiwrapper = $this->createMock(\tool_moodiyregistration\api_wrapper::class);
        $apiwrapper->method('update_registration')->willReturn([
            'success' => true,
            'message' => 'Site registration updated successfully',
        ]);

        // Set the mock for tests.
        $CFG->tool_moodiyregistration_test_api_wrapper = $apiwrapper;

        // Update the registration.
        $result = registration::update_manual($data);

        // Check the result.
        $this->assertTrue($result);

        // Verify the record was updated.
        $updated = $DB->get_record('tool_moodiyregistration', ['id' => $recordid]);
        $this->assertGreaterThan($record->timemodified, $updated->timemodified);
    }

    /**
     * Test updating site registration.
     * @covers ::update_registration
     */
    public function test_update_registration(): void {
        global $DB, $CFG;

        // Insert a test record to simulate a registered site.
        $record = new \stdClass();
        $record->site_uuid = 'test-uuid-123456789';
        $record->site_url = 'https://example.moodle.org';
        $record->timecreated = time();
        $record->timemodified = time() - 86400; // 1 day ago.
        $recordid = $DB->insert_record('tool_moodiyregistration', $record);

        // Create a mock for the api class.
        $apiwrapper = $this->createMock(\tool_moodiyregistration\api_wrapper::class);
        $apiwrapper->method('update_registration')->willReturn([
            'success' => true,
            'message' => 'Site registration updated successfully',
        ]);

        // Set the mock for tests.
        $CFG->tool_moodiyregistration_test_api_wrapper = $apiwrapper;

        // Update the registration.
        $result = registration::update_registration();

        // Verify the record was updated.
        $updated = $DB->get_record('tool_moodiyregistration', ['id' => $recordid]);
        $this->assertGreaterThanOrEqual($record->timemodified, $updated->timemodified);
    }

    /**
     * Test API URL configuration.
     * @covers \tool_moodiyregistration\api::get_apiurl
     */
    public function test_api_url_config(): void {
        // Test default API URL.
        set_config('apiurl', null, 'tool_moodiyregistration');
        $this->assertEquals('https://moodiycloud.com/api', api::get_apiurl());

        // Test custom API URL.
        $customurl = 'https://custom.moodiycloud.com';
        set_config('apiurl', $customurl, 'tool_moodiyregistration');
        $this->assertEquals($customurl . '/api', api::get_apiurl());
    }

    /**
     * Test getting site information.
     * @covers ::get_site_info
     */
    public function test_get_site_info(): void {
        global $CFG;
        // Get site info.
        $siteinfo = registration::get_site_info();

        // Check that essential fields are present.
        $this->assertArrayHasKey('site_name', $siteinfo);
        $this->assertArrayHasKey('site_url', $siteinfo);
        $this->assertArrayHasKey('moodlerelease', $siteinfo);
        $this->assertArrayHasKey('language', $siteinfo);
        $this->assertArrayHasKey('country_code', $siteinfo);

        // Verify site URL is correct.
        $this->assertEquals($CFG->wwwroot, $siteinfo['site_url']);

        $siteinfo = registration::get_site_info([
            'site_name' => 'Test site',
            'description' => 'Test description',
            'admin_email' => 'admin@example.com',
            'country_code' => 'US',
            'language' => 'en',
        ]);
        // Check that the provided data is included in the site info.
        $this->assertEquals('Test site', $siteinfo['site_name']);
        $this->assertEquals('Test description', $siteinfo['description']);
        $this->assertEquals('admin@example.com', $siteinfo['admin_email']);
        $this->assertEquals('US', $siteinfo['country_code']);
        $this->assertEquals('en', $siteinfo['language']);
    }

    /**
     * Test getting site metadata.
     * @covers ::get_site_metadata
     */
    public function test_get_site_metadata(): void {
        global $CFG;
        // Create some courses with end dates.
        $generator = $this->getDataGenerator();
        $generator->create_course(['enddate' => time() + 1000]);
        $generator->create_course(['enddate' => time() + 1000]);

        $generator->create_course(); // Course with no end date.
        $siteinfo = registration::get_site_metadata();

        $this->assertEquals(3, $siteinfo['courses']);
        $this->assertEquals($CFG->dbtype, $siteinfo['dbtype']);
        $this->assertEquals('manual', $siteinfo['primaryauthtype']);
        $this->assertEquals(1, $siteinfo['coursesnodates']);
    }
}
