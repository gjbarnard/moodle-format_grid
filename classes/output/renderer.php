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
 * Grid Format.
 *
 * @package    format_grid
 * @copyright  &copy; 2012 G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - {@link http://about.me/gjbarnard} and
 *                           {@link http://moodle.org/user/profile.php?id=442195}
 * @author     Based on code originally written by Paul Krix and Julian Ridden.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_grid\output;

use core_courseformat\output\section_renderer;
use moodle_page;

/**
 * Basic renderer for grid format.
 *
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends section_renderer {
    /**
     * Constructor method, calls the parent constructor.
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        // Since format_topics_renderer::section_edit_control_items() only displays the 'Highlight' control
        // when editing mode is on we need to be sure that the link 'Turn editing mode on' is available for a user
        // who does not have any other managing capability.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Outputs the custom CSS for the grid format.
     *
     * @param stdClass $course The course object
     * @return string The HTML for the custom CSS
     */
    public function print_custom_css($course) {
        $defaults = [
            'completion_colour_low_bg' => '#FFFFFF',
            'completion_colour_low_text' => '#1a1a1a',
            'completion_colour_middle_bg' => '#FFFFFF',
            'completion_colour_middle_text' => '#1a1a1a',
            'completion_colour_high_bg' => '#FFFFFF',
            'completion_colour_high_text' => '#1a1a1a'
        ];

        $formatoptions = [];
        foreach ($defaults as $key => $default) {
            $value = get_config('format_grid', $key);
            $formatoptions[$key] = $value !== false ? $value : $default;
        }

        $css = "
            .format-grid .grid-completion.grid-completion-colour-low {
                background-color: {$formatoptions['completion_colour_low_bg']};
                color: {$formatoptions['completion_colour_low_text']};
            }
            .format-grid .grid-completion.grid-completion-colour-middle {
                background-color: {$formatoptions['completion_colour_middle_bg']};
                color: {$formatoptions['completion_colour_middle_text']};
            }
            .format-grid .grid-completion.grid-completion-colour-high {
                background-color: {$formatoptions['completion_colour_high_bg']};
                color: {$formatoptions['completion_colour_high_text']};
            }
        ";
            return \html_writer::tag('style', $css);
    }
    /**
     * Generate the section title, wraps it in a link to the section page if page is to be displayed on a separate page.
     *
     * @param section_info|stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title($section, $course) {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section));
    }

    /**
     * Generate the section title to be displayed on the section page, without a link.
     *
     * @param section_info|stdClass $section The course_section entry from DB
     * @param int|stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title_without_link($section, $course) {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section, false));
    }
}
