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

use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for tool_openapi.
 *
 * One field in this plugin is personal data: tool_openapi_tokens.createdby,
 * the administrator who issued a token. Nothing else is -- an IP rule is a
 * statement about a network, and a token's own hash identifies the
 * integration holding it, not a Moodle user. There are no user preferences
 * and no files.
 *
 * The token itself is never exported, only that it exists: exporting a live
 * credential into a downloadable archive would hand out working access to
 * whoever ends up with the archive. Core does the same for its own
 * webservice tokens (core_external's provider, via
 * 'privacy:request:notexportedsecurity').
 *
 * Deletion severs the link instead of removing the row, which is the one
 * place this deliberately differs from a table of the user's own content. A
 * token is site infrastructure: some integration is authenticating with it
 * right now, and that integration has nothing to do with whoever happened
 * to press the create button. Deleting the person's account should not
 * silently break it -- core reaches the same conclusion and simply never
 * deletes on creatorid alone. Setting createdby to 0 removes the only
 * personal datum here while leaving the credential working; an
 * administrator who does want the credential gone deletes it from the
 * Tokens page, which is a separate, deliberate act.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describes the data this plugin stores.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('tool_openapi_tokens', [
            'name' => 'privacy:metadata:tokens:name',
            'createdby' => 'privacy:metadata:tokens:createdby',
            'iprestriction' => 'privacy:metadata:tokens:iprestriction',
            'timecreated' => 'privacy:metadata:tokens:timecreated',
            'lastused' => 'privacy:metadata:tokens:lastused',
        ], 'privacy:metadata:tokens');

        return $collection;
    }

    /**
     * The contexts holding data for a user: their own user context, if they
     * ever issued a token.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {tool_openapi_tokens} t
                  JOIN {context} ctx ON ctx.instanceid = t.createdby AND ctx.contextlevel = :userlevel
                 WHERE t.createdby = :userid";
        $contextlist->add_from_sql($sql, ['userlevel' => CONTEXT_USER, 'userid' => $userid]);

        return $contextlist;
    }

    /**
     * The users with data in a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_user) {
            return;
        }

        if ($DB->record_exists('tool_openapi_tokens', ['createdby' => $context->instanceid])) {
            $userlist->add_user($context->instanceid);
        }
    }

    /**
     * Exports the tokens a user issued, without the tokens themselves.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_user || $context->instanceid != $userid) {
                continue;
            }

            $tokens = $DB->get_records('tool_openapi_tokens', ['createdby' => $userid], 'timecreated ASC');
            if (!$tokens) {
                continue;
            }

            $data = [];
            foreach ($tokens as $token) {
                $data[] = [
                    'name' => $token->name,
                    'token' => get_string('privacy:tokennotexported', 'tool_openapi'),
                    'iprestriction' => $token->iprestriction,
                    'timecreated' => transform::datetime($token->timecreated),
                    'lastused' => $token->lastused ? transform::datetime($token->lastused) : null,
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'tool_openapi'), get_string('managetokens', 'tool_openapi')],
                (object) ['tokens' => $data]
            );
        }
    }

    /**
     * Detaches every token in a context from the user it belongs to.
     *
     * @param context $context
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        if (!$context instanceof \context_user) {
            return;
        }

        self::detach_tokens($context->instanceid);
    }

    /**
     * Detaches a user's tokens.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_user && $context->instanceid == $userid) {
                self::detach_tokens($userid);
                break;
            }
        }
    }

    /**
     * Detaches the approved users' tokens.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_user) {
            return;
        }

        foreach ($userlist->get_userids() as $userid) {
            if ($userid == $context->instanceid) {
                self::detach_tokens($userid);
            }
        }
    }

    /**
     * Points a user's tokens at nobody, keeping the credentials themselves
     * working -- see the class docblock for why they are not deleted.
     *
     * @param int $userid
     */
    private static function detach_tokens(int $userid): void {
        global $DB;

        $DB->set_field('tool_openapi_tokens', 'createdby', 0, ['createdby' => $userid]);
    }
}
