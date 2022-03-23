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
 * Grid Format - A topics based format that uses a grid of user selectable images to popup a light box of the section.
 *
 * @package    format_grid
 * @version    See the value of '$plugin->version' in version.php.
 * @copyright  &copy; 2012 G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - {@link http://about.me/gjbarnard} and
 *                           {@link http://moodle.org/user/profile.php?id=442195}
 * @author     Based on code originally written by Paul Krix and Julian Ridden.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_grid\output;

defined('MOODLE_INTERNAL') || die();

use context_course;
use core_text;
use html_writer;
use moodle_url;

require_once($CFG->dirroot . '/course/format/renderer.php');
require_once($CFG->dirroot . '/course/format/grid/lib.php');

class renderer extends \format_section_renderer_base {

    protected $section0attop; // Boolean to state if section zero is at the top (true) or in the grid (false).
    protected $courseformat; // Our course format object as defined in lib.php.
    private $settings; // Settings array.
    private $shadeboxshownarray = array(); // Value of 1 = not shown, value of 2 = shown - to reduce ambiguity in JS.
    private $portable = 0; // 1 = mobile, 2 = tablet.
    protected $initialsection = -1;

    /**
     * Constructor method, calls the parent constructor - MDL-21097
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(\moodle_page $page, $target) {
        parent::__construct($page, $target);
        $this->courseformat = course_get_format($page->course);
        $this->settings = $this->courseformat->get_settings();
        $this->section0attop = $this->courseformat->is_section0_attop();

        /* Since format_grid_renderer::section_edit_controls() only displays the 'Set current section' control when editing
           mode is on we need to be sure that the link 'Turn editing mode on' is available for a user who does not have any
           other managing capability. */
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        return html_writer::start_tag('ul', array('class' => 'gtopics', 'id' => 'gtopics'));
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the title for this section page
     * @return string the page title
     */
    protected function page_title() {
        return get_string('sectionname', 'format_grid');
    }

    /**
     * Generate the section title, wraps it in a link to the section page if page is to be displayed on a separate page
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title($section, $course) {
        return $this->render($this->courseformat->inplace_editable_render_section_name($section));
    }
    /**
     * Generate the section title to be displayed on the section page, without a link
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title_without_link($section, $course) {
        return $this->render($this->courseformat->inplace_editable_render_section_name($section, false));
    }

    /**
     * Generate next/previous section links for naviation
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param int $sectionno The section number in the coruse which is being dsiplayed
     * @return array associative array with previous and next section link
     */
    protected function get_nav_links($course, $sections, $sectionno) {
        // FIXME: This is really evil and should by using the navigation API.
        $course = course_get_format($course)->get_course();
        $canviewhidden = has_capability('moodle/course:viewhiddensections', context_course::instance($course->id))
            or !$course->hiddensections;

        $links = array('previous' => '', 'next' => '');
        $back = $sectionno - 1;
        if (!$this->section0attop) {
            $buffer = -1;
        } else if ($this->settings['setsection0ownpagenogridonesection'] == 2) {
            $buffer = -1;
        } else {
            $buffer = 0;
        }
        while ($back > $buffer and empty($links['previous'])) {
            if ($canviewhidden || $sections[$back]->uservisible) {
                $params = array();
                if (!$sections[$back]->visible) {
                    $params = array('class' => 'dimmed_text');
                }
                $previouslink = html_writer::tag('span', $this->output->larrow(), array('class' => 'larrow'));
                $previouslink .= get_section_name($course, $sections[$back]);
                $links['previous'] = html_writer::link(course_get_url($course, $back), $previouslink, $params);
            }
            $back--;
        }

        $coursenumsections = $this->courseformat->get_last_section_number();
        $forward = $sectionno + 1;
        while ($forward <= $coursenumsections and empty($links['next'])) {
            if ($canviewhidden || $sections[$forward]->uservisible) {
                $params = array();
                if (!$sections[$forward]->visible) {
                    $params = array('class' => 'dimmed_text');
                }
                $nextlink = get_section_name($course, $sections[$forward]);
                $nextlink .= html_writer::tag('span', $this->output->rarrow(), array('class' => 'rarrow'));
                $links['next'] = html_writer::link(course_get_url($course, $forward), $nextlink, $params);
            }
            $forward++;
        }

        return $links;
    }

    /**
     * Generate the html for the 'Jump to' menu on a single section page.
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param $displaysection the current displayed section number.
     *
     * @return string HTML to output.
     */
    protected function section_nav_selection($course, $sections, $displaysection) {
        $o = '';
        $sectionmenu = array();
        $sectionmenu[course_get_url($course)->out(false)] = get_string('maincoursepage');
        $modinfo = get_fast_modinfo($course);
        $section = 1;
        if (!$this->section0attop) {
            $section = 0;
        } else if ($this->settings['setsection0ownpagenogridonesection'] == 2) {
            $section = 0;
        } else {
            $section = 1;
        }
        $coursenumsections = $this->courseformat->get_last_section_number();
        while ($section <= $coursenumsections) {
            $thissection = $modinfo->get_section_info($section);
            $showsection = $thissection->uservisible or !$course->hiddensections;
            if (($showsection) && ($section != $displaysection) && ($url = course_get_url($course, $section))) {
                $sectionmenu[$url->out(false)] = get_section_name($course, $section);
            }
            $section++;
        }

        $select = new \url_select($sectionmenu, '', array('' => get_string('jumpto')));
        $select->class = 'jumpmenu';
        $select->formid = 'sectionmenu';
        $o .= $this->output->render($select);

        return $o;
    }

    /**
     * Generate the display of the header part of a section before course modules are included.
     *
     * @param stdClass $section The course_section entry from DB.
     * @param stdClass $course The course entry from DB.
     * @param bool $onsectionpage true if being printed on a single-section page.
     * @param int $sectionreturn The section to return to after an action.
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage, $sectionreturn=null) {
        if ($onsectionpage) {
            return $this->section_header_onsectionpage($section, $course, $sectionreturn);
        } else {
            return parent::section_header($section, $course, $onsectionpage, $sectionreturn);
        }
    }

    /**
     * Generate the display of the header part of a section before course modules are included
     * with a single section page.
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param int $sectionreturn The section to return to after an action.
     *
     * @return string HTML to output.
     */
    protected function section_header_onsectionpage($section, $course, $sectionreturn = null) {
        $o = '';
        $sectionstyle = '';

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            } else if (course_get_format($course)->is_section_current($section)) {
                $sectionstyle = ' current';
            }
        }

        $o .= html_writer::start_tag('li', array(
            'id' => 'section-'.$section->section,
            'class' => 'section main clearfix'.$sectionstyle,
            'role' => 'region',
            'aria-labelledby' => "sectionid-{$section->id}-title",
            'data-sectionid' => $section->section,
            'data-sectionreturnid' => $sectionreturn
            )
        );

        // Create a span that contains the section title to be used to create the keyboard section move menu.
        $sectionname = get_section_name($course, $section);
        $o .= html_writer::tag('span', $sectionname, array('class' => 'hidden sectionname', 'id' => "sectionid-{$section->id}-title"));

        $leftcontent = $this->section_left_content($section, $course, true);
        $o .= html_writer::tag('div', $leftcontent, array('class' => 'left side'));

        $rightcontent = $this->section_right_content($section, $course, true);
        $o .= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        $o .= html_writer::start_tag('div', array('class' => 'content'));

        $o .= html_writer::start_tag('div', array('class' => 'summary'));
        $o .= $this->single_section_page_summary($section, $sectionname, $course->id);
        $o .= html_writer::end_tag('div');

        $o .= $this->section_availability($section);

        return $o;
    }

    /**
     * Generate the single section page summary.
     *
     * @param stdClass $section The course_section entry from DB.
     * @param string $sectionname The section name.
     * @param int $courseid The course id.
     *
     * @return string HTML to output.
     */
    protected function single_section_page_summary($section, $sectionname, $courseid) {
        $summary = $this->format_summary_text($section);
        $o = '';

        if ($this->settings['singlepagesummaryimage'] > 1) { // I.e. not 'off'.
            if (!empty($summary)) {
                $data = new \stdClass;
                switch($this->settings['singlepagesummaryimage']) {
                    case 2:
                        $data->left = true;
                        break;
                    case 3:
                        $data->centre = true;
                        break;
                    case 4:
                        $data->right = true;
                        break;
                    default:
                        $data->left = true;
                }

                $sectionimage = \format_grid\toolbox::get_image($courseid, $section->id);
                $coursecontextid = context_course::instance($courseid)->id;
                $this->check_displayed_image($sectionimage, $coursecontextid, $courseid);

                $gridimagepath = \format_grid\toolbox::get_image_path();
                $iswebp = (get_config('format_grid', 'defaultdisplayedimagefiletype') == 2);

                $data->image = \format_grid\toolbox::output_section_image(
                    $section->id, $sectionname, $sectionimage, $coursecontextid, $section, $gridimagepath, $this, $iswebp);
                $data->summary = $summary;

                $o = $this->render_from_template('format_grid/singlepagesummaryimage', $data);
            }
        } else {
            $o = $summary;
        }

        return $o;
    }

    /**
     * Output the html for a single section page.
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     * @param int $displaysection The section number in the course which is being displayed
     */
    public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {
        if (($this->section0attop) && ($this->settings['setsection0ownpagenogridonesection'] == 1)) {
            return parent::print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection);
        } else {
            /* Output the single section page without section 0 at the top.
               Thus when section 0 is on its own page, it won't be displayed twice. */
            $modinfo = get_fast_modinfo($course);
            $course = course_get_format($course)->get_course();

            // Can we view the section in question?
            if (!($sectioninfo = $modinfo->get_section_info($displaysection))) {
                // This section doesn't exist.
                print_error('unknowncoursesection', 'error', null, $course->fullname);
                return;
            }

            if (!$sectioninfo->uservisible) {
                if (!$course->hiddensections) {
                    echo $this->start_section_list();
                    echo $this->section_hidden($displaysection, $course->id);
                    echo $this->end_section_list();
                }
                // Can't view this section.
                return;
            }

            // Copy activity clipboard..
            echo $this->course_activity_clipboard($course, $displaysection);

            // Start single-section div.
            echo html_writer::start_tag('div', array('class' => 'single-section'));

            // The requested section page.
            $thissection = $modinfo->get_section_info($displaysection);

            // Title with section navigation links.
            $sectionnavlinks = $this->get_nav_links($course, $modinfo->get_section_info_all(), $displaysection);
            $sectiontitle = '';
            $sectiontitle .= html_writer::start_tag('div', array('class' => 'section-navigation navigationtitle'));
            $sectiontitle .= html_writer::tag('span', $sectionnavlinks['previous'], array('class' => 'mdl-left'));
            $sectiontitle .= html_writer::tag('span', $sectionnavlinks['next'], array('class' => 'mdl-right'));
            // Title attributes.
            $classes = 'sectionname';
            if (!$thissection->visible) {
                $classes .= ' dimmed_text';
            }
            $sectionname = html_writer::tag('span', get_section_name($course, $displaysection));
            $sectiontitle .= $this->output->heading($sectionname, 3, $classes);

            $sectiontitle .= html_writer::end_tag('div');
            echo $sectiontitle;

            // Now the list of sections..
            echo $this->start_section_list();

            echo $this->section_header_onsectionpage($thissection, $course, $displaysection);
            if ($course->enablecompletion) {
                // Show completion help icon.
                $completioninfo = new \completion_info($course);
                echo $completioninfo->display_help_icon();
            }

            echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
            echo $this->courserenderer->course_section_add_cm_control($course, $displaysection, $displaysection);
            echo $this->section_footer();
            echo $this->end_section_list();

            // Display section bottom navigation.
            $sectionbottomnav = '';
            $sectionbottomnav .= html_writer::start_tag('div', array('class' => 'section-navigation mdl-bottom'));
            $sectionbottomnav .= html_writer::tag('span', $sectionnavlinks['previous'], array('class' => 'mdl-left'));
            $sectionbottomnav .= html_writer::tag('span', $sectionnavlinks['next'], array('class' => 'mdl-right'));
            $sectionbottomnav .= html_writer::tag('div', $this->section_nav_selection($course, $sections, $displaysection),
                array('class' => 'mdl-align'));
            $sectionbottomnav .= html_writer::end_tag('div');
            echo $sectionbottomnav;

            // Close single-section div.
            echo html_writer::end_tag('div');
        }
    }

    /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param array $mods
     * @param array $modnames
     * @param array $modnamesused
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $USER;
        if (!empty($USER->profile['accessible'])) {
            return parent::print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused);
        }

        $coursecontext = context_course::instance($course->id);
        $editing = $this->page->user_is_editing();
        $hascapvishidsect = has_capability('moodle/course:viewhiddensections', $coursecontext);

        if ($editing) {
            $streditsummary = get_string('editsummary');
            $urlpicedit = $this->output->image_url('t/edit');
        } else {
            $urlpicedit = false;
            $streditsummary = '';
        }

        echo html_writer::start_tag('div', array('id' => 'gridmiddle-column'));
        echo $this->output->skip_link_target();

        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();

        // Start at 1 to skip the summary block or include the summary block if it's in the grid display.
        if ($this->section0attop) {
            $this->section0attop = $this->make_block_topic0($course, $sections[0], $editing, $urlpicedit,
                    $streditsummary, false);
            // For the purpose of the grid shade box shown array topic 0 is not shown.
            $this->shadeboxshownarray[0] = 1;
        }
        echo html_writer::start_tag('div', array('id' => 'gridiconcontainer', 'role' => 'navigation',
            'aria-label' => get_string('gridimagecontainer', 'format_grid')));

        $gridiconsclass = 'gridicons';
        if ($this->settings['sectiontitleboxposition'] == 1) {
            $gridiconsclass .= ' content_inside';
        }
        $defaultcustommousepointers = get_config('format_grid', 'defaultcustommousepointers');
        if ($defaultcustommousepointers == 2) { // Yes.
            $gridiconsclass .= ' gridcursor';
        }

        echo html_writer::start_tag('ul', array('class' => $gridiconsclass));
        // Print all of the image containers.
        $shownsections = $this->make_block_icon_topics($coursecontext->id, $sections, $course, $editing, $hascapvishidsect, $urlpicedit);
        echo html_writer::end_tag('ul');

        echo html_writer::end_tag('div');

        $rtl = right_to_left();

        $coursenumsections = $this->courseformat->get_last_section_number();

        if (!(($course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) && (!$editing))) {
            $gridshadeboxattributes = array('id' => 'gridshadebox');
            if ($defaultcustommousepointers == 2) { // Yes.
                $gridshadeboxattributes['class'] = 'gridcursor';
            }
            echo html_writer::start_tag('div', $gridshadeboxattributes);
            echo html_writer::tag('div', '', array('id' => 'gridshadebox_overlay', 'style' => 'display: none;'));

            $gridshadeboxcontentclasses = array('hide_content');
            if (!$editing) {
                if ($this->settings['fitsectioncontainertowindow'] == 2) {
                    $gridshadeboxcontentclasses[] = 'fit_to_window';
                } else {
                    $gridshadeboxcontentclasses[] = 'absolute';
                }
            }

            echo html_writer::start_tag('div', array('id' => 'gridshadebox_content', 'class' => implode(' ',
                $gridshadeboxcontentclasses),
                'role' => 'region',
                'aria-label' => get_string('shadeboxcontent', 'format_grid')));

            $deviceextra = '';
            switch ($this->portable) {
                case 1: // Mobile.
                    $deviceextra = ' gridshadebox_mobile';
                break;
                case 2: // Tablet.
                    $deviceextra = ' gridshadebox_tablet';
                break;
                default:
                break;
            }
            $closeshadebox = get_string('closeshadebox', 'format_grid');
            echo html_writer::tag('img', '', array('id' => 'gridshadebox_close', 'style' => 'display: none;',
                'class' => $deviceextra,
                'src' => $this->output->image_url('close', 'format_grid'),
                'role' => 'link',
                'alt' => $closeshadebox,
                'aria-label' => $closeshadebox));

            // Only show the arrows if there is more than one box shown.
            if (($coursenumsections > 1) || (($coursenumsections == 1) && (!$this->section0attop))) {
                if ($rtl) {
                    $previcon = 'right';
                    $nexticon = 'left';
                    $areadir = 'rtl';
                } else {
                    $previcon = 'left';
                    $nexticon = 'right';
                    $areadir = 'ltr';
                }
                $previoussection = get_string('previoussection', 'format_grid');
                $prev = html_writer::start_tag('div', array('id' => 'gridshadebox_previous',
                    'class' => 'gridshadebox_area gridshadebox_previous_area '.$areadir,
                    'style' => 'display: none;',
                    'role' => 'link',
                    'aria-label' => $previoussection)
                );
                $prev .= html_writer::tag('img', '', array('class' => 'gridshadebox_arrow gridshadebox_previous'.$deviceextra,
                    'src' => $this->output->image_url('fa-arrow-circle-'.$previcon.'-w', 'format_grid'),
                    'alt' => $previoussection,
                    'aria-label' => $previoussection
                    )
                );
                $prev .= html_writer::end_tag('div');
                $nextsection = get_string('nextsection', 'format_grid');
                $next = html_writer::start_tag('div', array('id' => 'gridshadebox_next',
                    'class' => 'gridshadebox_area gridshadebox_next_area '.$areadir,
                    'style' => 'display: none;',
                    'role' => 'link',
                    'aria-label' => $nextsection)
                );
                $next .= html_writer::tag('img', '', array('class' => 'gridshadebox_arrow gridshadebox_next'.$deviceextra,
                    'src' => $this->output->image_url('fa-arrow-circle-'.$nexticon.'-w', 'format_grid'),
                    'alt' => $nextsection,
                    'aria-label' => $nextsection
                    )
                );
                $next .= html_writer::end_tag('div');

                if ($rtl) {
                    echo $next.$prev;
                } else {
                    echo $prev.$next;
                }
            }

            echo $this->start_section_list();
            // If currently moving a file then show the current clipboard.
            $this->make_block_show_clipboard_if_file_moving($course);

            // Print Section 0 with general activities.
            if (!$this->section0attop) {
                $this->make_block_topic0($course, $sections[0], $editing, $urlpicedit, $streditsummary, false);
            }

            /* Now all the normal modules by topic.
               Everything below uses "section" terminology - each "section" is a topic/module. */
            $this->make_block_topics($course, $sections, $modinfo, $editing, $hascapvishidsect, $streditsummary,
                $urlpicedit, false);

            echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
            echo html_writer::tag('div', '&nbsp;', array('class' => 'clearer'));
        }
        echo html_writer::end_tag('div');

        $sectionredirect = null;
        if ($course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
            // Get the redirect URL prefix for keyboard control with the 'Show one section per page' layout.
            $sectionredirect = $this->courseformat->get_view_url(null)->out(true);
        }

        if ($shownsections) {
            // Initialise the shade box functionality:...
            $this->page->requires->js_init_call('M.format_grid.init', array(
                $this->page->user_is_editing(),
                $sectionredirect,
                count($this->shadeboxshownarray),
                $this->initialsection,
                json_encode($this->shadeboxshownarray)));
            if (!$this->page->user_is_editing()) {
                // Initialise the key control functionality...
                $this->page->requires->yui_module('moodle-format_grid-gridkeys', 'M.format_grid.gridkeys.init',
                    array(array('rtl' => $rtl)), null, true);
            }
        }
    }

    /**
     * Generate the edit controls of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of links with edit controls
     */
    protected function section_edit_control_items($course, $section, $onsectionpage = false) {

        if (!$this->page->user_is_editing()) {
            return array();
        }

        $coursecontext = context_course::instance($course->id);

        if ($onsectionpage) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        $controls = array();
        if ($section->section && has_capability('moodle/course:setcurrentsection', $coursecontext)) {
            if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                $url->param('marker', 0);
                $highlightoff = get_string('highlightoff');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marked',
                                               'name' => $highlightoff,
                                               'pixattr' => array('class' => ''),
                                               'attr' => array('class' => 'editing_highlight',
                                               'data-action' => 'removemarker'));
                $url->param('marker', 0);
            } else {
                $url->param('marker', $section->section);
                $highlight = get_string('highlight');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marker',
                                               'name' => $highlight,
                                               'pixattr' => array('class' => ''),
                                               'attr' => array('class' => 'editing_highlight',
                                               'data-action' => 'setmarker'));
            }
        }

        $parentcontrols = parent::section_edit_control_items($course, $section, $onsectionpage);

        // If the edit key exists, we are going to insert our controls after it.
        if (array_key_exists("edit", $parentcontrols)) {
            $merged = array();
            // We can't use splice because we are using associative arrays.
            // Step through the array and merge the arrays.
            foreach ($parentcontrols as $key => $action) {
                $merged[$key] = $action;
                if ($key == "edit") {
                    // If we have come to the edit key, merge these controls here.
                    $merged = array_merge($merged, $controls);
                }
            }

            return $merged;
        } else {
            return array_merge($controls, $parentcontrols);
        }
    }

    // Grid format specific code.
    /**
     * Makes section zero.
     */
    private function make_block_topic0($course, $sectionzero, $editing, $urlpicedit, $streditsummary,
            $onsectionpage) {

        if ($this->section0attop) {
            echo html_writer::start_tag('ul', array('class' => 'gtopics-0'));
        }

        $sectionname = $this->courseformat->get_section_name($sectionzero);
        echo html_writer::start_tag('li', array(
            'id' => 'section-0',
            'class' => 'section main' . ($this->section0attop ? '' : ' grid_section hide_section'),
            'role' => 'region',
            'aria-label' => $sectionname)
        );

        echo html_writer::start_tag('div', array('class' => 'content'));

        if (!$onsectionpage) {
            echo $this->output->heading($sectionname, 3, 'sectionname');
        }

        echo html_writer::start_tag('div', array('class' => 'summary'));

        echo $this->format_summary_text($sectionzero);

        if ($editing) {
            echo html_writer::link(
                            new moodle_url('editsection.php', array('id' => $sectionzero->id)),
                                html_writer::empty_tag('img', array('src' => $urlpicedit,
                                                                     'alt' => $streditsummary,
                                                                     'class' => 'iconsmall edit')),
                                                        array('title' => $streditsummary));
        }
        echo html_writer::end_tag('div');

        echo $this->courserenderer->course_section_cm_list($course, $sectionzero, 0);

        if ($editing) {
            echo $this->courserenderer->course_section_add_cm_control($course, $sectionzero->section, 0, 0);

            if ($this->section0attop) {
                $strhidesummary = get_string('hide_summary', 'format_grid');
                $strhidesummaryalt = get_string('hide_summary_alt', 'format_grid');

                echo html_writer::link(
                        \format_grid\toolbox::grid_moodle_url('mod_summary.php', array(
                            'sesskey' => sesskey(),
                            'course' => $course->id,
                            'showsummary' => 0)), html_writer::empty_tag('img', array(
                            'src' => $this->output->image_url('into_grid', 'format_grid'),
                            'alt' => $strhidesummaryalt)) . '&nbsp;' . $strhidesummary, array('title' => $strhidesummaryalt));
            }
        }
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('li');

        if ($this->section0attop) {
            echo html_writer::end_tag('ul');
        }
        return true;
    }

    /**
     * States if the icon is to be greyed out.
     *
     * For logic see: section_availability_message() + a bit more!
     *
     * @param stdClass $course The course entry from DB
     * @param section_info $section The course_section entry from DB
     * @param bool $canviewhidden True if user can view hidden sections
     * @return bool Grey out the section icon, true or false?
     */
    protected function section_greyedout($course, $section, $canviewhidden) {
        global $CFG;
        $sectiongreyedout = false;
        if (!$section->visible) {
            if ($canviewhidden) {
                $sectiongreyedout = true;
            } else if (!$course->hiddensections) { // Hidden sections in collapsed form.
                $sectiongreyedout = true;
            }
        } else if (!$section->uservisible) {
            if (($section->availableinfo) && ((!$course->hiddensections) || ($canviewhidden))) { // Hidden sections in collapsed form.
                // Note: We only get to this function if availableinfo is non-empty,
                // so there is definitely something to print.
                $sectiongreyedout = true;
            }
        } else if ($canviewhidden && !empty($CFG->enableavailability)) {
            // Check if there is an availability restriction.
            $ci = new \core_availability\info_section($section);
            $fullinfo = $ci->get_full_information();
            $information = '';
            if ($fullinfo && (!$ci->is_available($information))) {
                $sectiongreyedout = true;
                $information = '';
            }
        }
        return $sectiongreyedout;
    }

    /**
     * Makes the grid image containers.
     *
     * @return int Number of shown sections.
     */
    private function make_block_icon_topics($contextid, $sections, $course, $editing, $hascapvishidsect,
            $urlpicedit) {
        global $CFG;

        if ($this->settings['newactivity'] == 2) {
            $currentlanguage = current_language();
            if (!file_exists("$CFG->dirroot/course/format/grid/pix/new_activity_".$currentlanguage.".png")) {
                $currentlanguage = 'en';
            }
            $urlpicnewactivity = $this->output->image_url('new_activity_'.$currentlanguage, 'format_grid');

            // Get all the section information about which items should be marked with the NEW picture.
            $sectionupdated = $this->new_activity($course);
        }

        // Get the section images for the course.
        $sectionimages = \format_grid\toolbox::get_images($course->id);

        // CONTRIB-4099:...
        $gridimagepath = \format_grid\toolbox::get_image_path();

        if ($course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
            $singlepageurl = $this->courseformat->get_view_url(null)->out(true);
        }

        if ($this->settings['showsectiontitlesummary'] == 2) {
            $this->page->requires->js_call_amd('format_grid/tooltip', 'init');
        }

        // Start at 1 to skip the summary block or include the summary block if it's in the grid display.
        $coursenumsections = $this->courseformat->get_last_section_number();

        // Are we using WebP for the displayed image?
        $iswebp = (get_config('format_grid', 'defaultdisplayedimagefiletype') == 2);

        $shownsections = 0;
        foreach ($sections as $section => $thissection) {
            if ((($this->section0attop) && ($section == 0)) || ($section > $coursenumsections)) {
                continue;  // Section 0 at the top and not in the grid / orphaned section.
            }

            // Check if section is visible to user.
            $showsection = $thissection->uservisible ||
                ($thissection->visible && !$thissection->available &&
                !empty($thissection->availableinfo) && ((!$course->hiddensections) || ($hascapvishidsect)));

            // If we should grey it out, flag that here.
            $sectiongreyedout = false;
            if ($this->settings['greyouthidden'] == 2) {
                $sectiongreyedout = $this->section_greyedout($course, $thissection, $hascapvishidsect);
            }

            if ($showsection || $sectiongreyedout) {
                $shownsections++;
                // We now know the value for the grid shade box shown array.
                $this->shadeboxshownarray[$section] = 2;

                $sectionname = $this->courseformat->get_section_name($thissection);
                $sectiontitleattribues = array();
                if ($this->settings['hidesectiontitle'] == 1) {
                    $displaysectionname = $sectionname;
                } else {
                    $displaysectionname = '';
                    $sectiontitleattribues['aria-label'] = $sectionname;
                }
                if ($this->settings['sectiontitlegridlengthmaxoption'] != 0) {
                    $sectionnamelen = core_text::strlen($displaysectionname);
                    if ($sectionnamelen !== false) {
                        if ($sectionnamelen > $this->settings['sectiontitlegridlengthmaxoption']) {
                            $displaysectionname = core_text::substr($displaysectionname, 0, $this->settings['sectiontitlegridlengthmaxoption']).'...';
                        }
                    }
                }
                $sectiontitleclass = 'icon_content m-1';
                if ($this->settings['sectiontitleboxposition'] == 1) {
                    // Only bother if there is a section name to show.
                    $canshow = false;
                    $sectionnamelen = core_text::strlen($displaysectionname);
                    if (($sectionnamelen !== false) && ($sectionnamelen > 0)) {
                        if ($sectionnamelen == 1) {
                            if ($displaysectionname[0] != ' ') {
                                $canshow = true;
                            }
                        } else {
                            $canshow = true;
                        }
                    }
                    if ($canshow) {
                        $sectiontitleclass .= ' content_inside ';
                        switch ($this->settings['sectiontitleboxinsideposition']) {
                            case 1:
                                $sectiontitleclass .= 'top';
                                break;
                            case 2:
                                $sectiontitleclass .= 'middle';
                                break;
                            case 3:
                                $sectiontitleclass .= 'bottom';
                                break;
                        }
                    }
                }

                $sectiontitleattribues['id'] = 'gridsectionname-'.$thissection->section;
                $sectiontitleattribues['class'] = $sectiontitleclass;
                if ($this->settings['showsectiontitlesummary'] == 2) {
                    $summary = strip_tags($thissection->summary);
                    $summary = str_replace("&nbsp;", ' ', $summary);
                    $summarylen = core_text::strlen($summary);
                    if ($summarylen > 0) {
                        if ($this->settings['sectiontitlesummarymaxlength'] != 0) {
                            if ($summarylen > $this->settings['sectiontitlesummarymaxlength']) {
                                $summary = core_text::substr($summary, 0, $this->settings['sectiontitlesummarymaxlength']).'...';
                            }
                        }
                        $sectiontitleattribues['title'] = $summary;
                        $sectiontitleattribues['data-toggle'] = 'gridtooltip';
                        $sectiontitleattribues['data-container'] = '#gridbox-'.$thissection->section;
                        $sectiontitleattribues['data-placement'] = $this->courseformat->get_set_show_section_title_summary_position();
                    }
                }

                /* Roles info on based on: http://www.w3.org/TR/wai-aria/roles.
                   Looked into the 'grid' role but that requires 'row' before 'gridcell' and there are none as the grid
                   is responsive, so as the container is a 'navigation' then need to look into converting the containing
                   'div' to a 'nav' tag (www.w3.org/TR/2010/WD-html5-20100624/sections.html#the-nav-element) when I'm
                   that all browsers support it against the browser requirements of Moodle. */
                $liattributes = array(
                    'id' => 'gridbox-'.$thissection->section,
                    'role' => 'region',
                    'aria-labelledby' => 'gridsectionname-'.$thissection->section
                );
                if ($this->courseformat->is_section_current($section)) {
                    $liattributes['class'] = 'currenticon';
                }
                if (!empty($summary)) {
                    $liattributes['aria-describedby'] = 'gridsectionsummary-'.$thissection->section;
                }
                echo html_writer::start_tag('li', $liattributes);

                // Ensure the record exists.
                if (($sectionimages === false) || (!array_key_exists($thissection->id, $sectionimages))) {
                    // Method get_image has 'repair' functionality for when there are issues with the data.
                    $sectionimage = \format_grid\toolbox::get_image($course->id, $thissection->id);
                } else {
                    $sectionimage = $sectionimages[$thissection->id];
                }
                $this->check_displayed_image($sectionimage, $contextid, $course->id);

                if ($course->coursedisplay != COURSE_DISPLAY_MULTIPAGE) {
                    if (($editing) && ($section == 0)) {
                        $this->make_block_icon_topic0_editing($course);
                    }

                    echo html_writer::start_tag('a', array(
                        'href' => '#section-'.$thissection->section,
                        'id' => 'gridsection-'.$thissection->section,
                        'class' => 'gridicon_link',
                        'role' => 'link')
                    );

                    if ($this->settings['sectiontitleboxposition'] == 2) {
                        echo html_writer::tag('div', $displaysectionname, $sectiontitleattribues);
                    }

                    if (($this->settings['newactivity'] == 2) && (isset($sectionupdated[$thissection->id]))) {
                        // The section has been updated since the user last visited this course, add NEW label.
                        echo html_writer::empty_tag('img', array(
                            'class' => 'new_activity',
                            'src' => $urlpicnewactivity,
                            'alt' => ''));
                    }

                    $imageclass = 'image_holder';
                    if ($sectiongreyedout) {
                        $imageclass .= ' inaccessible';
                    }
                    echo html_writer::start_tag('div', array('class' => $imageclass));

                    if ($this->settings['sectiontitleboxposition'] == 1) {
                        echo html_writer::tag('div', $displaysectionname, $sectiontitleattribues);
                    }

                    if (!empty($summary)) {
                        echo html_writer::tag('div', '', array('id' => 'gridsectionsummary-'.$thissection->section,
                            'hidden' => true, 'aria-label' => $summary));
                    }

                    echo \format_grid\toolbox::output_section_image(
                        $section, $sectionname, $sectionimage, $contextid, $thissection, $gridimagepath, $this->output, $iswebp);

                    echo html_writer::end_tag('div');

                    // Title under the image.
                    if ($this->settings['sectiontitleboxposition'] == 3) {
                        echo html_writer::tag('div', $displaysectionname, $sectiontitleattribues);
                    }

                    echo html_writer::end_tag('a');

                    if ($editing) {
                        $this->make_block_icon_topics_editing($thissection, $contextid, $urlpicedit);
                    }
                    echo html_writer::end_tag('li');
                } else {
                    $content = '';
                    if ($this->settings['sectiontitleboxposition'] == 2) {
                        $content .= html_writer::tag('div', $displaysectionname, $sectiontitleattribues);
                    }

                    if (($this->settings['newactivity'] == 2) && (isset($sectionupdated[$thissection->id]))) {
                        $content .= html_writer::empty_tag('img', array(
                                    'class' => 'new_activity',
                                    'src' => $urlpicnewactivity));
                    }

                    // Grey out code: Justin 2016/05/14.
                    $imageclass = 'image_holder';
                    if ($sectiongreyedout) {
                        $imageclass .= ' inaccessible';
                    }
                    $content .= html_writer::start_tag('div', array('class' => $imageclass));

                    if ($this->settings['sectiontitleboxposition'] == 1) {
                        $content .= html_writer::tag('div', $displaysectionname, $sectiontitleattribues);
                    }

                    if (!empty($summary)) {
                        $content .= html_writer::tag('div', '', array('id' => 'gridsectionsummary-'.$thissection->section,
                            'hidden' => true, 'aria-label' => $summary));
                    }

                    $content .= \format_grid\toolbox::output_section_image(
                        $section, $sectionname, $sectionimage, $contextid, $thissection, $gridimagepath, $this->output, $iswebp);

                    $content .= html_writer::end_tag('div');

                    // Title below the image.
                    if ($this->settings['sectiontitleboxposition'] == 3) {
                        $content .= html_writer::tag('div', $displaysectionname, $sectiontitleattribues);
                    }


                    if ($editing) {
                        if ($section == 0) {
                            $this->make_block_icon_topic0_editing($course);
                        }
                        // Section greyed out by Justin 2016/05/14.
                        if (!$sectiongreyedout) {
                            echo html_writer::link($singlepageurl.'#section-'.$thissection->section, $content, array(
                                'id' => 'gridsection-'.$thissection->section,
                                'class' => 'gridicon_link',
                                'role' => 'link'));
                        } else {
                            // Need an enclosing 'span' for IE.
                            echo html_writer::tag('span', $content);
                        }
                        $this->make_block_icon_topics_editing($thissection, $contextid, $urlpicedit);
                    } else {
                        if (!$sectiongreyedout) {
                            echo html_writer::link($singlepageurl.'&section='.$thissection->section, $content, array(
                                'id' => 'gridsection-'.$thissection->section,
                                'class' => 'gridicon_link',
                                'role' => 'link'));
                        } else {
                            // Need an enclosing 'span' for IE.
                            echo html_writer::tag('span', $content);
                        }
                    }
                    echo html_writer::end_tag('li');
                }
            } else {
                // We now know the value for the grid shade box shown array.
                $this->shadeboxshownarray[$section] = 1;
            }
        }

        return $shownsections;
    }

    private function make_block_icon_topics_editing($thissection, $contextid, $urlpicedit) {
        global $USER;

        $streditimage = get_string('editimage', 'format_grid');
        $streditimagealt = get_string('editimage_alt', 'format_grid');

        echo html_writer::link(
            \format_grid\toolbox::grid_moodle_url('editimage.php', array(
                'sectionid' => $thissection->id,
                'contextid' => $contextid,
                'userid' => $USER->id,
                'role' => 'link',
                'aria-label' => $streditimagealt)
            ),
            $this->output->pix_icon('t/edit', $streditimagealt).'&nbsp;'.$streditimage,
            array('title' => $streditimagealt)
        );
    }

    private function make_block_icon_topic0_editing($course) {
        $strdisplaysummary = get_string('display_summary', 'format_grid');
        $strdisplaysummaryalt = get_string('display_summary_alt', 'format_grid');

        echo html_writer::link(
            \format_grid\toolbox::grid_moodle_url('mod_summary.php', array(
                'sesskey' => sesskey(),
                'course' => $course->id,
                'showsummary' => 1,
                'role' => 'link',
                'aria-label' => $strdisplaysummaryalt)
            ),
            html_writer::empty_tag('img', array(
                'src' => $this->output->image_url('out_of_grid', 'format_grid'),
                'alt' => $strdisplaysummaryalt,
                'role' => 'img',
                'aria-label' => $strdisplaysummaryalt)) . '&nbsp;' . $strdisplaysummary,
                array('title' => $strdisplaysummaryalt)
        );
    }

    /**
     * If currently moving a file then show the current clipboard.
     */
    private function make_block_show_clipboard_if_file_moving($course) {
        global $USER;

        if (is_object($course) && ismoving($course->id)) {
            $strcancel = get_string('cancel');

            $stractivityclipboard = clean_param(format_string(
                            get_string('activityclipboard', '', $USER->activitycopyname)), PARAM_NOTAGS);
            $stractivityclipboard .= '&nbsp;&nbsp;('
                    . html_writer::link(new moodle_url('/mod.php', array(
                        'cancelcopy' => 'true',
                        'sesskey' => sesskey())), $strcancel);

            echo html_writer::tag('li', $stractivityclipboard, array('class' => 'clipboard'));
        }
    }

    /**
     * Makes the list of sections to show.
     */
    private function make_block_topics($course, $sections, $modinfo, $editing, $hascapvishidsect, $streditsummary,
            $urlpicedit, $onsectionpage) {
        $coursecontext = context_course::instance($course->id);
        unset($sections[0]);

        $coursenumsections = $this->courseformat->get_last_section_number();

        foreach ($sections as $section => $thissection) {
            if (!$hascapvishidsect && !$thissection->visible && $course->hiddensections) {
                unset($sections[$section]);
                continue;
            }
            if ($section > $coursenumsections) {
                // Orphaned section.
                continue;
            }

            $sectionstyle = 'section main';
            if (!$thissection->visible) {
                $sectionstyle .= ' hidden';
            }
            if ($this->courseformat->is_section_current($section)) {
                $sectionstyle .= ' current';
            }
            $sectionstyle .= ' grid_section hide_section';

            $sectionname = $this->courseformat->get_section_name($thissection);
            if ($editing) {
                $title = $this->section_title($thissection, $course);
            } else {
                $title = $sectionname;
            }
            echo html_writer::start_tag('li', array(
                'id' => 'section-' . $section,
                'class' => $sectionstyle,
                'role' => 'region',
                'aria-label' => $sectionname,
                'data-sectionid' => $section
                )
            );

            if ($editing) {
                // Note, 'left side' is BEFORE content.
                $leftcontent = $this->section_left_content($thissection, $course, $onsectionpage);
                echo html_writer::tag('div', $leftcontent, array('class' => 'left side'));
                // Note, 'right side' is BEFORE content.
                $rightcontent = $this->section_right_content($thissection, $course, $onsectionpage);
                echo html_writer::tag('div', $rightcontent, array('class' => 'right side'));
            }

            echo html_writer::start_tag('div', array('class' => 'content'));
            if ($hascapvishidsect || ($thissection->visible && $thissection->available)) {
                // If visible.
                echo $this->output->heading($title, 3, 'sectionname');

                echo html_writer::start_tag('div', array('class' => 'summary'));

                echo $this->format_summary_text($thissection);

                if ($editing) {
                    echo html_writer::link(
                            new moodle_url('editsection.php', array('id' => $thissection->id)),
                            html_writer::empty_tag('img', array('src' => $urlpicedit, 'alt' => $streditsummary,
                                'class' => 'iconsmall edit')), array('title' => $streditsummary));
                }
                echo html_writer::end_tag('div');

                echo $this->section_availability_message($thissection, has_capability('moodle/course:viewhiddensections',
                        $coursecontext));

                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->courserenderer->course_section_add_cm_control($course, $thissection->section, 0);
            } else {
                echo html_writer::tag('h2', $this->get_title($thissection));
                echo html_writer::tag('p', get_string('hidden_topic', 'format_grid'));

                echo $this->section_availability_message($thissection, has_capability('moodle/course:viewhiddensections',
                        $coursecontext));
            }

            echo html_writer::end_tag('div');
            echo html_writer::end_tag('li');

            unset($sections[$section]);
        }

        if ($editing) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $coursenumsections or empty($modinfo->sections[$section])) {
                    // This is not stealth section or it is empty.
                    continue;
                }
                echo $this->stealth_section_header($section);
                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->stealth_section_footer();
            }

            echo $this->end_section_list();

            echo $this->change_number_sections($course, 0);
        } else {
            echo $this->end_section_list();
        }
    }

    /**
     * Attempts to return a 40 character title for the section image container.
     * If section names are set, they are used. Otherwise it scans
     * the summary for what looks like the first line.
     */
    private function get_title($section) {
        $title = is_object($section) && isset($section->name) &&
                is_string($section->name) ? trim($section->name) : '';

        if (!empty($title)) {
            // Apply filters and clean tags.
            $title = trim(format_string($section->name, true));
        }

        if (empty($title)) {
            $title = trim(format_text($section->summary));

            // Finds first header content. If it is not found, then try to find the first paragraph.
            foreach (array('h[1-6]', 'p') as $tag) {
                if (preg_match('#<(' . $tag . ')\b[^>]*>(?P<text>.*?)</\1>#si', $title, $m)) {
                    if (!$this->is_empty_text($m['text'])) {
                        $title = $m['text'];
                        break;
                    }
                }
            }
            $title = trim(clean_param($title, PARAM_NOTAGS));
        }

        if (core_text::strlen($title) > 40) {
            $title = $this->text_limit($title, 40);
        }

        return $title;
    }

    /**
     * States if the text is empty.
     * @param type $text The text to test.
     * @return boolean Yes(true) or No(false).
     */
    public function is_empty_text($text) {
        return empty($text) ||
                preg_match('/^(?:\s|&nbsp;)*$/si', htmlentities($text, 0 /* ENT_HTML401 */, 'UTF-8', true));
    }

    /**
     * Cuts long texts up to certain length without breaking words.
     */
    private function text_limit($text, $length, $replacer = '...') {
        if (core_text::strlen($text) > $length) {
            $text = wordwrap($text, $length, "\n", true);
            $pos = strpos($text, "\n");
            if ($pos === false) {
                $pos = $length;
            }
            $text = trim(core_text::substr($text, 0, $pos)) . $replacer;
        }
        return $text;
    }

    /**
     * Checks whether there has been new activity.
     */
    private function new_activity($course) {
        global $CFG, $USER, $DB;

        $sectionsedited = array();
        if (isset($USER->lastcourseaccess[$course->id])) {
            $course->lastaccess = $USER->lastcourseaccess[$course->id];
        } else {
            $course->lastaccess = 0;
        }

        $sql = "SELECT id, section FROM {$CFG->prefix}course_modules " .
                "WHERE course = :courseid AND added > :lastaccess";

        $params = array(
            'courseid' => $course->id,
            'lastaccess' => $course->lastaccess);

        $activity = $DB->get_records_sql($sql, $params);
        foreach ($activity as $record) {
            $sectionsedited[$record->section] = true;
        }

        return $sectionsedited;
    }

    public function set_portable($portable) {
        $this->portable = $portable;
    }

    public function set_initialsection($initialsection) {
        $this->initialsection = $initialsection;
    }

    /**
     * Checks the displayed image.
     *
     * @param array $sectionimage The section image from the 'format_grid_icon' table.
     * @param int $coursecontextid The context id.
     * @param int $courseid The course id.
     */
    private function check_displayed_image($sectionimage, $coursecontextid, $courseid) {
        /* If the image is set then check that displayedimageindex is greater than 0 otherwise create the displayed image.
        This is a catch-all for existing courses. */
        if (isset($sectionimage->image) && (($sectionimage->displayedimageindex < 1) || ($sectionimage->updatedisplayedimage == 1))) {
            // Set up the displayed image:...
            $sectionimage->newimage = $sectionimage->image;
            $sectionimage = \format_grid\toolbox::setup_displayed_image($sectionimage, $coursecontextid,
                $courseid, $this->settings);
        }
    }
}
