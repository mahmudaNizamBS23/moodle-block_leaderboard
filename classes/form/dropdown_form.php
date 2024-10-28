<?php
/**
 * Block definition class for the block_pluginname plugin.
 *
 * @package   block_leaderboard
 * @copyright 2024, Brain Station-23 PLC 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("$CFG->libdir/formslib.php");

class dropdown_form extends moodleform {

    // Define the form elements
    public function definition() {
        $mform = $this->_form;

        // Add a dropdown for courses
        $courses = $this->get_courses();
        if (empty($courses)) {
            $courses = [0 => get_string('nocoursesavailable', 'block_leaderboard')];
        }

        $mform->addElement('select', 'course', get_string('selectcourse', 'block_leaderboard'), $courses);
        $mform->addRule('course', null, 'required', null, 'client');

        // Group the submit and reset buttons together
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('submit', 'block_leaderboard'));
        $buttonarray[] = $mform->createElement(
            'button',
            'resetbutton',
            get_string('reset', 'block_leaderboard'),
            array('onclick' => "location.reload(); return false;")
        );

        // Add the button group to the form
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }

    // Get courses for the dropdown
    private function get_courses() {
        global $DB,$USER;
        $userid = $USER->id;
        $currenttimestamp = time();
        $sql = "SELECT c.id, c.fullname 
                FROM {course} c
                JOIN {enrol} e ON e.courseid = c.id
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE ue.userid = :userid
                AND c.visible = 1
                AND (c.enddate = 0 OR c.enddate > :currenttimestamp);";
        $courses = $DB->get_records_sql($sql, ['userid' => $userid,'currenttimestamp' => $currenttimestamp]);
        
        $options = [];
        foreach ($courses as $course) {
            $options[$course->id] = $course->fullname;
        }

        return $options;
    }
}
