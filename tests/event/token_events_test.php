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

namespace tool_openapi\event;

/**
 * Tests for the token events.
 *
 * These are the only lasting record that a token existed, since deleting
 * one removes the row outright, so what they carry -- and what they must
 * never carry -- is worth pinning down.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\event\token_created
 * @covers     \tool_openapi\event\token_deleted
 */
final class token_events_test extends \advanced_testcase {
    /**
     * Creating a token is logged, with the name but nothing secret.
     */
    public function test_token_created_is_logged(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $sink = $this->redirectEvents();

        token_created::create([
            'objectid' => 42,
            'context' => \context_system::instance(),
            'other' => ['name' => 'Integration'],
        ])->trigger();

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(token_created::class, $events[0]);
        $this->assertSame(42, (int) $events[0]->objectid);
        $this->assertSame('Integration', $events[0]->other['name']);
        $this->assertStringNotContainsString('tokenhash', json_encode($events[0]->get_data()));
    }

    /**
     * Deleting a token is logged, and the snapshot taken beforehand keeps
     * the row readable to an observer after it is gone.
     */
    public function test_token_deleted_is_logged_with_a_snapshot(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $id = $DB->insert_record('tool_openapi_tokens', (object) [
            'name' => 'Doomed',
            'tokenhash' => hash('sha256', 'whatever'),
            'allowedfunctions' => null,
            'iprestriction' => null,
            'createdby' => get_admin()->id,
            'timecreated' => time(),
            'lastused' => null,
        ]);
        $token = $DB->get_record('tool_openapi_tokens', ['id' => $id]);

        $sink = $this->redirectEvents();

        $event = token_deleted::create([
            'objectid' => $id,
            'context' => \context_system::instance(),
            'other' => ['name' => $token->name],
        ]);
        $event->add_record_snapshot('tool_openapi_tokens', $token);
        $DB->delete_records('tool_openapi_tokens', ['id' => $id]);
        $event->trigger();

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertSame('Doomed', $events[0]->get_record_snapshot('tool_openapi_tokens', $id)->name);
        $this->assertStringContainsString('tokens/index.php', $events[0]->get_url()->out(false));
    }
}
