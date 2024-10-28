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
 * Block definition class for the block_pluginname plugin.
 *
 * @package   block_leaderboard
 * @copyright 2024, Brain Station-23 PLC 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_leaderboard extends block_base {

    /**
     * Initialises the block.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('leaderboard', 'block_leaderboard');
    }

    /**
     * Gets the block contents.
     *
     * @return string The block HTML.
     */
    public function get_content() {
        global $OUTPUT, $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        // Create new content object
        $this->content = new stdClass();

        // Ensure the content.php file is included
        $content_file = $CFG->dirroot . '/blocks/leaderboard/content.php';
        if (file_exists($content_file)) {
            // Start output buffering to capture output of content.php
            ob_start();
            include($content_file);
            $form_html = ob_get_clean();
        } else {
            $form_html = 'Error: Content file not found.';
        }

        // Assign the form or content to the block's content
        $this->content->text = $form_html;

        // Footer content if any (optional)
        $this->content->footer = '';

        return $this->content;

    }

    /**
     * Defines in which pages this block can be added.
     *
     * @return array of the pages where the block can be added.
     */
    public function applicable_formats() {
        return [
            'admin' => false,
            'site-index' => true,
            'course-view' => true,
            'mod' => false,
            'my' => true,
        ];
    }
}