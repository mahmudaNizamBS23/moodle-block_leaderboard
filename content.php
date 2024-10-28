<?php
/**
 * Block definition class for the block_pluginname plugin.
 *
 * @package   block_leaderboard
 * @copyright 2024, Brain Station-23 PLC 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This is the content.php file for the Leaderboard block.

defined('MOODLE_INTERNAL') || die();

global $CFG, $OUTPUT;

// In content.php
require_once($CFG->dirroot . '/blocks/leaderboard/classes/form/dropdown_form.php');
require_once($CFG->dirroot . '/blocks/leaderboard/lib.php');


// Create the form.
$mform = new dropdown_form();
$mform->display();
echo '<br>';
// Check for form submission.
if ($mform->is_submitted() && $mform->is_validated()) {
    block_leaderboard_process_form($mform);
} else {
    echo $OUTPUT->heading(get_string('message', 'block_leaderboard'));
}
