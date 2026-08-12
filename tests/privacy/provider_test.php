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

namespace tool_openapi\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Tests for the privacy provider.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Inserts a token issued by the given user.
     *
     * @param int $userid
     * @param string $name
     * @return int
     */
    private function create_token(int $userid, string $name = 'Integration'): int {
        global $DB;

        return $DB->insert_record('tool_openapi_tokens', (object) [
            'name' => $name,
            'tokenhash' => hash('sha256', $name . $userid),
            'allowedfunctions' => null,
            'iprestriction' => '192.0.2.0/24',
            'createdby' => $userid,
            'timecreated' => time(),
            'lastused' => null,
        ]);
    }

    /**
     * The token table is declared, so an administrator reading the site's
     * privacy registry can see what this plugin keeps.
     */
    public function test_get_metadata_declares_the_token_table(): void {
        $this->resetAfterTest();

        $items = provider::get_metadata(new \core_privacy\local\metadata\collection('tool_openapi'))->get_collection();

        $this->assertCount(1, $items);
        $this->assertSame('tool_openapi_tokens', $items[0]->get_name());
        $this->assertArrayHasKey('createdby', $items[0]->get_privacy_fields());
    }

    /**
     * A user who issued a token has data in their own user context.
     */
    public function test_get_contexts_for_userid(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $this->create_token($user->id);

        $contexts = provider::get_contexts_for_userid($user->id)->get_contexts();

        $this->assertCount(1, $contexts);
        $this->assertEquals(\context_user::instance($user->id)->id, $contexts[0]->id);
        $this->assertCount(0, provider::get_contexts_for_userid($other->id)->get_contexts());
    }

    /**
     * The user context of someone who issued a token lists them, and
     * nobody else's does.
     */
    public function test_get_users_in_context(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $this->create_token($user->id);

        $userlist = new userlist(\context_user::instance($user->id), 'tool_openapi');
        provider::get_users_in_context($userlist);
        $this->assertEquals([$user->id], $userlist->get_userids());

        $empty = new userlist(\context_user::instance($other->id), 'tool_openapi');
        provider::get_users_in_context($empty);
        $this->assertSame([], $empty->get_userids());
    }

    /**
     * The export describes the token without handing over a working
     * credential.
     */
    public function test_export_user_data_never_exports_the_token(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->create_token($user->id, 'Reporting');
        $context = \context_user::instance($user->id);

        provider::export_user_data(new approved_contextlist($user, 'tool_openapi', [$context->id]));

        $exported = writer::with_context($context)->get_data([
            get_string('pluginname', 'tool_openapi'),
            get_string('managetokens', 'tool_openapi'),
        ]);

        $this->assertCount(1, $exported->tokens);
        $this->assertSame('Reporting', $exported->tokens[0]['name']);
        $this->assertSame(get_string('privacy:tokennotexported', 'tool_openapi'), $exported->tokens[0]['token']);
        $this->assertSame('192.0.2.0/24', $exported->tokens[0]['iprestriction']);
    }

    /**
     * Deleting a user's data detaches their tokens instead of destroying
     * credentials some integration is still using -- and leaves everyone
     * else's alone.
     */
    public function test_delete_data_for_user_detaches_the_token(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $id = $this->create_token($user->id);
        $otherid = $this->create_token($other->id, 'Someone else');

        provider::delete_data_for_user(
            new approved_contextlist($user, 'tool_openapi', [\context_user::instance($user->id)->id])
        );

        $this->assertTrue($DB->record_exists('tool_openapi_tokens', ['id' => $id]));
        $this->assertEquals(0, $DB->get_field('tool_openapi_tokens', 'createdby', ['id' => $id]));
        $this->assertEquals($other->id, $DB->get_field('tool_openapi_tokens', 'createdby', ['id' => $otherid]));
    }

    /**
     * Deleting everything in a user context detaches that user's tokens.
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $id = $this->create_token($user->id);

        provider::delete_data_for_all_users_in_context(\context_user::instance($user->id));

        $this->assertEquals(0, $DB->get_field('tool_openapi_tokens', 'createdby', ['id' => $id]));
    }

    /**
     * Deleting an approved list of users detaches only those users.
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $id = $this->create_token($user->id);
        $otherid = $this->create_token($other->id, 'Someone else');

        provider::delete_data_for_users(
            new approved_userlist(\context_user::instance($user->id), 'tool_openapi', [$user->id])
        );

        $this->assertEquals(0, $DB->get_field('tool_openapi_tokens', 'createdby', ['id' => $id]));
        $this->assertEquals($other->id, $DB->get_field('tool_openapi_tokens', 'createdby', ['id' => $otherid]));
    }
}
