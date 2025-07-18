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
 * Unit tests for Moodiy Registration events.
 *
 * @package     tool_moodiyregistration
 * @category    test
 * @copyright   2025 VidyaMantra <pinky@vidyamantra.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \tool_moodiyregistration\event
 */

namespace tool_moodiyregistration;

use tool_moodiyregistration\event\moodiy_registration;
use tool_moodiyregistration\event\moodiy_unregistration;
use tool_moodiyregistration\event\moodiyregistration_updated;
use tool_moodiyregistration\event\update_request;

/**
 * Unit tests for event functionality.
 */
class events_test extends \advanced_testcase {

    /**
     * Set up tests.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test the moodiy_registration event.
     * @covers ::moodiy_registration
     */
    public function test_moodiy_registration_event(): void {
        global $DB;

        // Create a test record.
        $record = new \stdClass();
        $record->registrationid = 12345;
        $record->site_uuid = 'test-uuid-123456789';
        $record->site_url = 'https://example.moodle.org';
        $record->timecreated = time();
        $record->timemodified = time();
        $recordid = $DB->insert_record('tool_moodiyregistration', $record);

        // Create the event.
        $event = moodiy_registration::create([
            'context' => \context_system::instance(),
            'objectid' => $recordid,
            'other' => [
                'registrationid' => $record->registrationid,
                'site_uuid' => $record->site_uuid,
            ],
        ]);

        // Add record snapshot.
        $record->id = $recordid;
        $event->add_record_snapshot('tool_moodiyregistration', $record);

        // Test the event data.
        $this->assertEquals('tool_moodiyregistration', $event->objecttable);
        $this->assertEquals($recordid, $event->objectid);
        $this->assertEquals('c', $event->crud);
        $this->assertEquals(\core\event\base::LEVEL_OTHER, $event->edulevel);

        // Trigger the event and capture it.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $sink->close();

        // Check that the event was triggered.
        $this->assertCount(1, $events);
        $triggeredevent = reset($events);
        $this->assertInstanceOf('\tool_moodiyregistration\event\moodiy_registration', $triggeredevent);
        $this->assertEquals($recordid, $triggeredevent->objectid);
    }

    /**
     * Test the moodiy_unregistration event.
     * @covers ::moodiy_unregistration
     */
    public function test_moodiy_unregistration_event(): void {
        global $DB;

        // Create a test record.
        $record = new \stdClass();
        $record->registrationid = 12345;
        $record->site_uuid = 'test-uuid-123456789';
        $record->site_url = 'https://example.moodle.org';
        $record->timecreated = time();
        $record->timemodified = time();
        $recordid = $DB->insert_record('tool_moodiyregistration', $record);

        // Create the event.
        $event = moodiy_unregistration::create([
            'context' => \context_system::instance(),
            'objectid' => $recordid,
            'other' => [
                'registrationid' => $record->registrationid,
                'site_uuid' => $record->site_uuid,
            ],
        ]);

        // Add record snapshot.
        $record->id = $recordid;
        $event->add_record_snapshot('tool_moodiyregistration', $record);

        // Trigger the event and capture it.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $sink->close();

        // Check that the event was triggered.
        $this->assertCount(1, $events);
        $triggeredevent = reset($events);
        $this->assertInstanceOf('\tool_moodiyregistration\event\moodiy_unregistration', $triggeredevent);
        $this->assertEquals($recordid, $triggeredevent->objectid);
        $this->assertEquals('d', $triggeredevent->crud);
    }

    /**
     * Test the moodiyregistration_updated event.
     * @covers ::moodiyregistration_updated
     */
    public function test_moodiyregistration_updated_event(): void {
        global $DB;

        // Create a test record.
        $record = new \stdClass();
        $record->registrationid = 12345;
        $record->site_uuid = 'test-uuid-123456789';
        $record->site_url = 'https://example.moodle.org';
        $record->timecreated = time();
        $record->timemodified = time();
        $recordid = $DB->insert_record('tool_moodiyregistration', $record);

        // Create the event.
        $event = moodiyregistration_updated::create([
            'context' => \context_system::instance(),
            'objectid' => $recordid,
            'other' => [
                'registrationid' => $record->registrationid,
                'site_uuid' => $record->site_uuid,
            ],
        ]);

        // Add record snapshot.
        $record->id = $recordid;
        $event->add_record_snapshot('tool_moodiyregistration', $record);

        // Trigger the event and capture it.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $sink->close();

        // Check that the event was triggered.
        $this->assertCount(1, $events);
        $triggeredevent = reset($events);
        $this->assertInstanceOf('\tool_moodiyregistration\event\moodiyregistration_updated', $triggeredevent);
        $this->assertEquals($recordid, $triggeredevent->objectid);
        $this->assertEquals('u', $triggeredevent->crud);
    }

    /**
     * Test the update_request event.
     * @covers ::update_request
     */
    public function test_update_request_event(): void {
        global $DB;

        // Create a test record.
        $record = new \stdClass();
        $record->registrationid = 12345;
        $record->site_uuid = 'test-uuid-123456789';
        $record->site_url = 'https://example.moodle.org';
        $record->timecreated = time();
        $record->timemodified = time();
        $recordid = $DB->insert_record('tool_moodiyregistration', $record);

        // Create the event.
        $event = update_request::create([
            'context' => \context_system::instance(),
            'objectid' => $recordid,
            'other' => [
                'registrationid' => $record->registrationid,
                'site_uuid' => $record->site_uuid,
            ],
        ]);

        // Trigger the event and capture it.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $sink->close();

        // Check that the event was triggered.
        $this->assertCount(1, $events);
        $triggeredevent = reset($events);
        $this->assertInstanceOf('\tool_moodiyregistration\event\update_request', $triggeredevent);
        $this->assertEquals($recordid, $triggeredevent->objectid);
        $this->assertEquals('r', $triggeredevent->crud);
    }
}
