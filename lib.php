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
 * @copyright  &copy; 2012+ G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - {@link http://about.me/gjbarnard} and
 *                           {@link http://moodle.org/user/profile.php?id=442195}
 * @author     Based on code originally written by Paul Krix and Julian Ridden.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/lib.php'); // For format_base.

class format_grid extends format_base {

    private $settings = null;
    private $section0attop = null; // Boolean to state if section zero is at the top (true) or in the grid (false), if null then uninitialized.

    /**
     * Creates a new instance of class
     *
     * Please use {@link course_get_format($courseorid)} to get an instance of the format class
     *
     * @param string $format
     * @param int $courseid
     * @return format_grid
     */
    protected function __construct($format, $courseid) {
        if ($courseid === 0) {
            global $COURSE;
            $courseid = $COURSE->id;  // Save lots of global $COURSE as we will never be the site course.
        }
        parent::__construct($format, $courseid);
    }

    /**
     * States if section 0 is at the top.
     *
     * @param stdClass $section Section object from database or just field course_sections section
     * @return string The default value for the section name.
     */
    public function is_section0_attop() {
        if (is_null($this->section0attop)) {
            $this->section0attop = \format_grid\toolbox::get_summary_visibility($this->courseid, $this)->showsummary == 1;
        }
        return $this->section0attop;
    }

    /**
     * Returns the default section name for the format.
     *
     * @param stdClass $section Section object from database or just field course_sections section
     * @return string The default value for the section name.
     */
    public function get_default_section_name($section) {
        /* Follow the same logic so that this method is supported.  The MDL-51610 enchancement refactored things,
          but that is not appropriate for us. */
        return $this->get_section_name($section);
    }

    /**
     * Returns the format's settings and gets them if they do not exist.
     * @param bool $invalidate Invalidate the existing known settings and get a fresh set.  Set when you know the settings have changed.
     * @return array The settings as an array.
     */
    public function get_settings($invalidate = false) {
        if ($invalidate) {
            $this->settings = null;
        }
        if (empty($this->settings) == true) {
            $this->settings = $this->get_format_options();
            foreach ($this->settings as $settingname => $settingvalue) {
                if (isset($settingvalue)) {
                    $settingvtype = gettype($settingvalue);
                    if ((($settingvtype == 'string') && ($settingvalue === '-')) ||
                        (($settingvtype == 'integer') && ($settingvalue === 0))) {
                        // Default value indicator is a hyphen or a number equal to 0.
                        $this->settings[$settingname] = get_config('format_grid', 'default'.$settingname);
                    }
                }
            }
        }
        return $this->settings;
    }

    /**
     * Returns the mapped value of the 'setshowsectiontitlesummaryposition' setting.
     * @return string One of 'top', 'bottom', 'left' or 'right'.
     */
    public function get_set_show_section_title_summary_position() {
        $settings = $this->get_settings();
        $returnvalue = 'top';

        switch($settings['setshowsectiontitlesummaryposition']) {
            case 1:
                $returnvalue = 'top';
                break;
            case 2:
                $returnvalue = 'bottom';
                break;
            case 3:
                $returnvalue = 'left';
                break;
            case 4:
                $returnvalue = 'right';
                break;
        }

        return $returnvalue;
    }

    /**
     * Gets the name for the provided section.
     *
     * @param stdClass $section The section.
     * @return string The section name.
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if (!empty($section->name)) {
            return format_string($section->name, true, array('context' => self::get_context($this)));
        } if ($section->section == 0) {
            return get_string('topic0', 'format_grid');
        } else {
            return get_string('topic', 'format_grid').' '. $section->section;
        }
    }

    /**
     * Indicates this format uses sections.
     *
     * @return bool Returns true
     */
    public function uses_sections() {
        return true;
    }

    /**
     * The URL to use for the specified course (with section)
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = array()) {
        $course = $this->get_course();
        $url = new moodle_url('/course/view.php', array('id' => $course->id));

        $sr = null;
        if (array_key_exists('sr', $options)) {
            $sr = $options['sr'];
        }
        if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }
        if ($sectionno !== null) {
            if ($sr !== null) {
                if ($sr) {
                    $usercoursedisplay = COURSE_DISPLAY_MULTIPAGE;
                    $sectionno = $sr;
                } else {
                    $usercoursedisplay = COURSE_DISPLAY_SINGLEPAGE;
                }
            } else {
                $usercoursedisplay = $course->coursedisplay;
            }
            if ($sectionno != 0 && $usercoursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                $url->param('section', $sectionno);
            } else if ($sectionno == 0 && $usercoursedisplay == COURSE_DISPLAY_MULTIPAGE && (!$this->is_section0_attop())) {
                $url->param('section', $sectionno);
            } else if ($sectionno == 0 && $usercoursedisplay == COURSE_DISPLAY_MULTIPAGE && $this->is_section0_attop() &&
                   ($this->get_settings()['setsection0ownpagenogridonesection'] == 2)) {
                $url->param('section', $sectionno);
            } else {
                $url->set_anchor('section-' . $sectionno);
            }
        }
        return $url;
    }

    /**
     * Returns the information about the ajax support in the given source format
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     * The property (array)testedbrowsers can be used as a parameter for {@link ajaxenabled()}.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Custom action after section has been moved in AJAX mode
     *
     * Used in course/rest.php
     *
     * @return array This will be passed in ajax respose
     */
    public function ajax_section_move() {
        global $PAGE;
        $titles = array();
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $course);
            }
        }
        return array('sectiontitles' => $titles, 'action' => 'move');
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return array(
            BLOCK_POS_LEFT => array(),
            BLOCK_POS_RIGHT => array('search_forums', 'news_items', 'calendar_upcoming', 'recent_activity')
        );
    }

    /**
     * Definitions of the additional options that this course format uses for the course.
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;
        $courseconfig = null;

        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseid = $this->get_courseid();
            if ($courseid == 1) { // New course.
                $defaultnumsections = $courseconfig->numsections;
            } else { // Existing course that may not have 'numsections' - see get_last_section().
                global $DB;
                $defaultnumsections = $DB->get_field_sql('SELECT max(section) from {course_sections}
                    WHERE course = ?', array($courseid));
            }
            $courseformatoptions = array(
                'numsections' => array(
                    'default' => $defaultnumsections,
                    'type' => PARAM_INT,
                ),
                'hiddensections' => array(
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT
                ),
                'coursedisplay' => array(
                    'default' => $courseconfig->coursedisplay,
                    'type' => PARAM_INT
                ),
                'imagecontaineralignment' => array(
                    'default' => '-',
                    'type' => PARAM_ALPHANUMEXT
                ),
                'imagecontainerwidth' => array(
                    'default' => 0,
                    'type' => PARAM_INT
                ),
                'imagecontainerratio' => array(
                    'default' => '-',
                    'type' => PARAM_ALPHANUMEXT
                ),
                'imageresizemethod' => array(
                    'default' => 0,
                    'type' => PARAM_INT
                ),
                'bordercolour' => array(
                    'default' => '-',
                    'type' => PARAM_ALPHANUMEXT
                ),
                'borderwidth' => array(
                    'default' => 0,
                    'type' => PARAM_INT
                ),
                'borderradius' => array(
                    'default' => 0,
                    'type' => PARAM_INT
                ),
                'imagecontainerbackgroundcolour' => array(
                    'default' => '-',
                    'type' => PARAM_ALPHANUMEXT
                ),
                'currentselectedsectioncolour' => array(
                    'default' => '-',
                    'type' => PARAM_ALPHANUMEXT
                ),
                'currentselectedimagecontainertextcolour' => array(
                    'default' => '-',
                    'type' => PARAM_ALPHANUMEXT
                ),
                'currentselectedimagecontainercolour' => array(
                    'default' => '-',
                    'type' => PARAM_ALPHANUMEXT
                ),
                'hidesectiontitle' => array(
                    'default' => 0,
                    'type' => PARAM_INT
                ),
                'sectiontitlegridlengthmaxoption' => array(
                    'default' => '-',
                    'type' => PARAM_ALPHANUMEXT
                ),
                'sectiontitleboxposition' => array(
                    'default' => 0,
                    'type' => PARAM_INT
                ),
                'sectiontitleboxinsideposition' => array(
                    'default' => 0,
                    'type' => PARAM_INT
                ),
                'sectiontitleboxheight' => array(
                    'default' => '-',
                    'type' => PARAM_ALPHANUMEXT
                ),
                'sectiontitleboxopacity' => array(
                    'default' => '-',
                    'type' => PARAM_RAW
                ),
                'sectiontitlefontsize' => array(
                    'default' => '-',
                    'type' => PARAM_ALPHANUMEXT
                ),
                'sectiontitlealignment' => array(
                    'default' => '-',
                    'type' => PARAM_ALPHANUMEXT
                ),
                'sectiontitleinsidetitletextcolour' => array(
                    'default' => '-',
                    'type' => PARAM_ALPHANUMEXT
                ),
                'sectiontitleinsidetitlebackgroundcolour' => array(
                    'default' => '-',
                    'type' => PARAM_ALPHANUMEXT
                ),
                'showsectiontitlesummary' => array(
                    'default' => 0,
                    'type' => PARAM_INT
                ),
                'setshowsectiontitlesummaryposition' => array(
                    'default' => 0,
                    'type' => PARAM_INT
                ),
                'sectiontitlesummarymaxlength' => array(
                    'default' => '-',
                    'type' => PARAM_ALPHANUMEXT
                ),
                'sectiontitlesummarytextcolour' => array(
                    'default' => '-',
                    'type' => PARAM_ALPHANUMEXT
                ),
                'sectiontitlesummarybackgroundcolour' => array(
                    'default' => '-',
                    'type' => PARAM_ALPHANUMEXT
                ),
                'sectiontitlesummarybackgroundopacity' => array(
                    'default' => '-',
                    'type' => PARAM_RAW
                ),
                'newactivity' => array(
                    'default' => 0,
                    'type' => PARAM_INT
                ),
                'singlepagesummaryimage' => array(
                    'default' => 0,
                    'type' => PARAM_INT
                ),
                'fitsectioncontainertowindow' => array(
                    'default' => 0,
                    'type' => PARAM_INT
                ),
                'greyouthidden' => array(
                    'default' => 0,
                    'type' => PARAM_INT
                ),
                'setsection0ownpagenogridonesection' => array(
                    'default' => 0,
                    'type' => PARAM_INT
                )
            );
        }
        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            /* Note: Because 'admin_setting_configcolourpicker' in 'settings.php' needs to use a prefixing '#'
              this needs to be stripped off here if it's there for the format's specific colour picker. */
            $defaults = $this->get_course_format_colour_defaults();
            $context = self::get_context($this);

            if (is_null($courseconfig)) {
                $courseconfig = get_config('moodlecourse');
            }
            $sectionmenu = array();
            for ($i = 0; $i <= $courseconfig->maxsections; $i++) {
                $sectionmenu[$i] = "$i";
            }
            $courseformatoptionsedit = array(
                'numsections' => array(
                    'label' => new lang_string('numbersections', 'format_grid'),
                    'element_type' => 'select',
                    'element_attributes' => array($sectionmenu),
                ),
                'hiddensections' => array(
                    'label' => new lang_string('hiddensections'),
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            0 => new lang_string('hiddensectionscollapsed'),
                            1 => new lang_string('hiddensectionsinvisible')
                        )
                    ),
                ),
                'coursedisplay' => array(
                    'label' => new lang_string('coursedisplay'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            COURSE_DISPLAY_SINGLEPAGE => new lang_string('coursedisplay_single'),
                            COURSE_DISPLAY_MULTIPAGE => new lang_string('coursedisplay_multi')
                        )
                    ),
                    'help' => 'coursedisplay',
                    'help_component' => 'moodle'
                )
            );
            if (has_capability('format/grid:changeimagecontaineralignment', $context)) {
                $imagecontaineralignmentvalues = $this->generate_default_entry(
                    'imagecontaineralignment',
                    '-',
                    \format_grid\toolbox::get_horizontal_alignments()
                );
                $courseformatoptionsedit['imagecontaineralignment'] = array(
                    'label' => new lang_string('setimagecontaineralignment', 'format_grid'),
                    'help' => 'setimagecontaineralignment',
                    'help_component' => 'format_grid',
                    'element_type' => 'select',
                    'element_attributes' => array($imagecontaineralignmentvalues)
                );
            } else {
                $courseformatoptionsedit['imagecontaineralignment'] = array(
                    'label' => '-', 'element_type' => 'hidden');
            }

            if (has_capability('format/grid:changeimagecontainersize', $context)) {
                $imagecontainerwidthvalues = $this->generate_default_entry(
                    'imagecontainerwidth',
                    0,
                    \format_grid\toolbox::get_image_container_widths()
                );
                $courseformatoptionsedit['imagecontainerwidth'] = array(
                    'label' => new lang_string('setimagecontainerwidth', 'format_grid'),
                    'help' => 'setimagecontainerwidth',
                    'help_component' => 'format_grid',
                    'element_type' => 'select',
                    'element_attributes' => array($imagecontainerwidthvalues)
                );
                $imagecontainerratiovalues = $this->generate_default_entry(
                    'imagecontainerratio',
                    '-',
                    \format_grid\toolbox::get_image_container_ratios()
                );
                $courseformatoptionsedit['imagecontainerratio'] = array(
                    'label' => new lang_string('setimagecontainerratio', 'format_grid'),
                    'help' => 'setimagecontainerratio',
                    'help_component' => 'format_grid',
                    'element_type' => 'select',
                    'element_attributes' => array($imagecontainerratiovalues)
                );
            } else {
                $courseformatoptionsedit['imagecontainerwidth'] = array(
                    'label' => '-', 'element_type' => 'hidden');
                $courseformatoptionsedit['imagecontainerratio'] = array(
                    'label' => 0, 'element_type' => 'hidden');
            }

            if (has_capability('format/grid:changeimageresizemethod', $context)) {
                $imageresizemethodvalues = $this->generate_default_entry(
                    'imageresizemethod',
                    0,
                    array(
                        1 => new lang_string('scale', 'format_grid'), // Scale.
                        2 => new lang_string('crop', 'format_grid')   // Crop.
                    )
                );
                $courseformatoptionsedit['imageresizemethod'] = array(
                    'label' => new lang_string('setimageresizemethod', 'format_grid'),
                    'help' => 'setimageresizemethod',
                    'help_component' => 'format_grid',
                    'element_type' => 'select',
                    'element_attributes' => array($imageresizemethodvalues)
                );
            } else {
                $courseformatoptionsedit['imageresizemethod'] = array(
                    'label' => 0, 'element_type' => 'hidden');
            }

            if (has_capability('format/grid:changeimagecontainerstyle', $context)) {
                $courseformatoptionsedit['bordercolour'] = array(
                    'label' => new lang_string('setbordercolour', 'format_grid'),
                    'help' => 'setbordercolour',
                    'help_component' => 'format_grid',
                    'element_type' => 'gfcolourpopup',
                    'element_attributes' => array(
                        array(
                            'defaultcolour' => $defaults['defaultbordercolour'],
                            'value' => $defaults['defaultbordercolour']
                        )
                    )
                );

                $courseformatoptionsedit['borderwidth'] = array(
                    'label' => new lang_string('setborderwidth', 'format_grid'),
                    'help' => 'setborderwidth',
                    'help_component' => 'format_grid',
                    'element_type' => 'select',
                    'element_attributes' => array(\format_grid\toolbox::get_border_widths())
                );

                $borderradiusvalues = $this->generate_default_entry(
                    'borderradius',
                    0,
                    array(
                        1 => new lang_string('off', 'format_grid'),
                        2 => new lang_string('on', 'format_grid')
                    )
                );
                $courseformatoptionsedit['borderradius'] = array(
                    'label' => new lang_string('setborderradius', 'format_grid'),
                    'help' => 'setborderradius',
                    'help_component' => 'format_grid',
                    'element_type' => 'select',
                    'element_attributes' => array($borderradiusvalues)
                );

                $courseformatoptionsedit['imagecontainerbackgroundcolour'] = array(
                    'label' => new lang_string('setimagecontainerbackgroundcolour', 'format_grid'),
                    'help' => 'setimagecontainerbackgroundcolour',
                    'help_component' => 'format_grid',
                    'element_type' => 'gfcolourpopup',
                    'element_attributes' => array(
                        array(
                            'defaultcolour' => $defaults['defaultimagecontainerbackgroundcolour'],
                            'value' => $defaults['defaultimagecontainerbackgroundcolour']
                        )
                    )
                );

                $courseformatoptionsedit['currentselectedsectioncolour'] = array(
                    'label' => new lang_string('setcurrentselectedsectioncolour', 'format_grid'),
                    'help' => 'setcurrentselectedsectioncolour',
                    'help_component' => 'format_grid',
                    'element_type' => 'gfcolourpopup',
                    'element_attributes' => array(
                        array(
                            'defaultcolour' => $defaults['defaultcurrentselectedsectioncolour'],
                            'value' => $defaults['defaultcurrentselectedsectioncolour']
                        )
                    )
                );

                $courseformatoptionsedit['currentselectedimagecontainertextcolour'] = array(
                    'label' => new lang_string('setcurrentselectedimagecontainertextcolour', 'format_grid'),
                    'help' => 'setcurrentselectedimagecontainertextcolour',
                    'help_component' => 'format_grid',
                    'element_type' => 'gfcolourpopup',
                    'element_attributes' => array(
                        array(
                            'defaultcolour' => $defaults['defaultcurrentselectedimagecontainertextcolour'],
                            'value' => $defaults['defaultcurrentselectedimagecontainertextcolour']
                        )
                    )
                );

                $courseformatoptionsedit['currentselectedimagecontainercolour'] = array(
                    'label' => new lang_string('setcurrentselectedimagecontainercolour', 'format_grid'),
                    'help' => 'setcurrentselectedimagecontainercolour',
                    'help_component' => 'format_grid',
                    'element_type' => 'gfcolourpopup',
                    'element_attributes' => array(
                        array(
                            'defaultcolour' => $defaults['defaultcurrentselectedimagecontainercolour'],
                            'value' => $defaults['defaultcurrentselectedimagecontainercolour']
                        )
                    )
                );
            } else {
                $courseformatoptionsedit['bordercolour'] = array(
                    'label' => '-', 'element_type' => 'hidden');
                $courseformatoptionsedit['borderwidth'] = array(
                    'label' => 0, 'element_type' => 'hidden');
                $courseformatoptionsedit['borderradius'] = array(
                    'label' => 0, 'element_type' => 'hidden');
                $courseformatoptionsedit['imagecontainerbackgroundcolour'] = array(
                    'label' => '-', 'element_type' => 'hidden');
                $courseformatoptionsedit['currentselectedsectioncolour'] = array(
                    'label' => '-', 'element_type' => 'hidden');
                $courseformatoptionsedit['currentselectedimagecontainertextcolour'] = array(
                    'label' => '-', 'element_type' => 'hidden');
                $courseformatoptionsedit['currentselectedimagecontainercolour'] = array(
                    'label' => '-', 'element_type' => 'hidden');
            }

            if (has_capability('format/grid:changesectiontitleoptions', $context)) {
                $hidesectiontitlevalues = $this->generate_default_entry(
                    'hidesectiontitle',
                    0,
                    array(
                        1 => new lang_string('no'), // No.
                        2 => new lang_string('yes') // Yes.
                    )
                );
                $courseformatoptionsedit['hidesectiontitle'] = array(
                    'label' => new lang_string('hidesectiontitle', 'format_grid'),
                    'element_type' => 'select',
                    'element_attributes' => array($hidesectiontitlevalues),
                    'help' => 'hidesectiontitle',
                    'help_component' => 'format_grid'
                );

                $courseformatoptionsedit['sectiontitlegridlengthmaxoption'] = array(
                    'label' => new lang_string('sectiontitlegridlengthmaxoption', 'format_grid'),
                    'element_type' => 'text',
                    'element_attributes' => array(array('size' => 3)),
                    'help' => 'sectiontitlegridlengthmaxoption',
                    'help_component' => 'format_grid'
                );

                $sectiontitleboxpositionvalues = $this->generate_default_entry(
                    'sectiontitleboxposition',
                    0,
                    array(
                        1 => new lang_string('sectiontitleboxpositioninside', 'format_grid'),
                        2 => new lang_string('sectiontitleboxpositionoutside', 'format_grid'),
                        3 => new lang_string('sectiontitleboxpositionbelow', 'format_grid')
                    )
                );
                $courseformatoptionsedit['sectiontitleboxposition'] = array(
                    'label' => new lang_string('sectiontitleboxposition', 'format_grid'),
                    'element_type' => 'select',
                    'element_attributes' => array($sectiontitleboxpositionvalues),
                    'help' => 'sectiontitleboxposition',
                    'help_component' => 'format_grid'
                );

                $sectiontitleboxinsidepositionvalues = $this->generate_default_entry(
                    'sectiontitleboxinsideposition',
                    0,
                    array(
                        1 => new lang_string('sectiontitleboxinsidepositiontop', 'format_grid'),
                        2 => new lang_string('sectiontitleboxinsidepositionmiddle', 'format_grid'),
                        3 => new lang_string('sectiontitleboxinsidepositionbottom', 'format_grid')
                    )
                );
                $courseformatoptionsedit['sectiontitleboxinsideposition'] = array(
                    'label' => new lang_string('sectiontitleboxinsideposition', 'format_grid'),
                    'element_type' => 'select',
                    'element_attributes' => array($sectiontitleboxinsidepositionvalues),
                    'help' => 'sectiontitleboxinsideposition',
                    'help_component' => 'format_grid'
                );

                $courseformatoptionsedit['sectiontitleboxheight'] = array(
                    'label' => new lang_string('sectiontitleboxheight', 'format_grid'),
                    'element_type' => 'text',
                    'element_attributes' => array(array('size' => 3)),
                    'help' => 'sectiontitleboxheight',
                    'help_component' => 'format_grid'
                );

                $opacityvalues = array();
                $opacityvalues['-'] = new lang_string('default', 'format_grid', get_config('format_grid', 'defaultsectiontitleboxopacity'));
                $opacityvalues = array_merge($opacityvalues, \format_grid\toolbox::get_default_opacities());
                $courseformatoptionsedit['sectiontitleboxopacity'] = array(
                    'label' => new lang_string('sectiontitleboxopacity', 'format_grid'),
                    'element_type' => 'select',
                    'element_attributes' => array($opacityvalues),
                    'help' => 'sectiontitleboxopacity',
                    'help_component' => 'format_grid'
                );

                $sectiontitlefontsizevalues = $this->generate_default_entry(
                    'sectiontitlefontsize',
                    '-',
                    \format_grid\toolbox::get_default_section_font_sizes()
                );
                $courseformatoptionsedit['sectiontitlefontsize'] = array(
                    'label' => new lang_string('sectiontitlefontsize', 'format_grid'),
                    'element_type' => 'select',
                    'element_attributes' => array($sectiontitlefontsizevalues),
                    'help' => 'sectiontitlefontsize',
                    'help_component' => 'format_grid'
                );

                $sectiontitlealignmentvalues = $this->generate_default_entry(
                    'sectiontitlealignment',
                    '-',
                    \format_grid\toolbox::get_horizontal_alignments()
                );
                $courseformatoptionsedit['sectiontitlealignment'] = array(
                    'label' => new lang_string('sectiontitlealignment', 'format_grid'),
                    'help' => 'sectiontitlealignment',
                    'help_component' => 'format_grid',
                    'element_type' => 'select',
                    'element_attributes' => array($sectiontitlealignmentvalues)
                );

                $courseformatoptionsedit['sectiontitleinsidetitletextcolour'] = array(
                    'label' => new lang_string('sectiontitleinsidetitletextcolour', 'format_grid'),
                    'help' => 'sectiontitleinsidetitletextcolour',
                    'help_component' => 'format_grid',
                    'element_type' => 'gfcolourpopup',
                    'element_attributes' => array(
                        array(
                            'defaultcolour' => $defaults['defaultsectiontitleinsidetitletextcolour'],
                            'value' => $defaults['defaultsectiontitleinsidetitletextcolour']
                        )
                    )
                );

                $courseformatoptionsedit['sectiontitleinsidetitlebackgroundcolour'] = array(
                    'label' => new lang_string('sectiontitleinsidetitlebackgroundcolour', 'format_grid'),
                    'help' => 'sectiontitleinsidetitlebackgroundcolour',
                    'help_component' => 'format_grid',
                    'element_type' => 'gfcolourpopup',
                    'element_attributes' => array(
                        array(
                            'defaultcolour' => $defaults['defaultsectiontitleinsidetitlebackgroundcolour'],
                            'value' => $defaults['defaultsectiontitleinsidetitlebackgroundcolour']
                        )
                    )
                );

                $showsectiontitlesummaryvalues = $this->generate_default_entry(
                    'showsectiontitlesummary',
                    0,
                    array(
                        1 => new lang_string('no'), // No.
                        2 => new lang_string('yes') // Yes.
                    )
                );
                $courseformatoptionsedit['showsectiontitlesummary'] = array(
                    'label' => new lang_string('showsectiontitlesummary', 'format_grid'),
                    'element_type' => 'select',
                    'element_attributes' => array($showsectiontitlesummaryvalues),
                    'help' => 'showsectiontitlesummary',
                    'help_component' => 'format_grid'
                );

                $setshowsectiontitlesummarypositionvalues = $this->generate_default_entry(
                    'setshowsectiontitlesummaryposition',
                    0,
                    array(
                        1 => new lang_string('top', 'format_grid'),
                        2 => new lang_string('bottom', 'format_grid'),
                        3 => new lang_string('left', 'format_grid'),
                        4 => new lang_string('right', 'format_grid')
                    )
                );

                $courseformatoptionsedit['setshowsectiontitlesummaryposition'] = array(
                    'label' => new lang_string('setshowsectiontitlesummaryposition', 'format_grid'),
                    'element_type' => 'select',
                    'element_attributes' => array($setshowsectiontitlesummarypositionvalues),
                    'help' => 'setshowsectiontitlesummaryposition',
                    'help_component' => 'format_grid'
                );

                $courseformatoptionsedit['sectiontitlesummarymaxlength'] = array(
                    'label' => new lang_string('sectiontitlesummarymaxlength', 'format_grid'),
                    'element_type' => 'text',
                    'element_attributes' => array(array('size' => 3)),
                    'help' => 'sectiontitlesummarymaxlength',
                    'help_component' => 'format_grid'
                );

                $courseformatoptionsedit['sectiontitlesummarytextcolour'] = array(
                    'label' => new lang_string('sectiontitlesummarytextcolour', 'format_grid'),
                    'help' => 'sectiontitlesummarytextcolour',
                    'help_component' => 'format_grid',
                    'element_type' => 'gfcolourpopup',
                    'element_attributes' => array(
                        array(
                            'defaultcolour' => $defaults['defaultsectiontitlesummarytextcolour'],
                            'value' => $defaults['defaultsectiontitlesummarytextcolour']
                        )
                    )
                );

                $courseformatoptionsedit['sectiontitlesummarybackgroundcolour'] = array(
                    'label' => new lang_string('sectiontitlesummarybackgroundcolour', 'format_grid'),
                    'help' => 'sectiontitlesummarybackgroundcolour',
                    'help_component' => 'format_grid',
                    'element_type' => 'gfcolourpopup',
                    'element_attributes' => array(
                        array(
                            'defaultcolour' => $defaults['defaultsectiontitlesummarybackgroundcolour'],
                            'value' => $defaults['defaultsectiontitlesummarybackgroundcolour']
                        )
                    )
                );

                $opacityvalues['-'] = new lang_string('default', 'format_grid', get_config('format_grid', 'defaultsectiontitlesummarybackgroundopacity'));
                $courseformatoptionsedit['sectiontitlesummarybackgroundopacity'] = array(
                    'label' => new lang_string('sectiontitlesummarybackgroundopacity', 'format_grid'),
                    'element_type' => 'select',
                    'element_attributes' => array($opacityvalues),
                    'help' => 'sectiontitlesummarybackgroundopacity',
                    'help_component' => 'format_grid'
                );
            } else {
                $courseformatoptionsedit['hidesectiontitle'] = array(
                    'label' => 0, 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitlegridlengthmaxoption'] = array(
                    'label' => '-', 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitleboxposition'] = array(
                    'label' => 0, 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitleboxinsideposition'] = array(
                    'label' => 0, 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitleboxheight'] = array(
                    'label' => '-', 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitleboxopacity'] = array(
                    'label' => '-', 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitlefontsize'] = array(
                    'label' => 0, 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitlealignment'] = array(
                    'label' => '-', 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitleinsidetitletextcolour'] = array(
                    'label' => '-', 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitleinsidetitlebackgroundcolour'] = array(
                    'label' => '-', 'element_type' => 'hidden');
                $courseformatoptionsedit['showsectiontitlesummary'] = array(
                    'label' => 0, 'element_type' => 'hidden');
                $courseformatoptionsedit['setshowsectiontitlesummaryposition'] = array(
                    'label' => 0, 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitlesummarymaxlength'] = array(
                    'label' => '-', 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitlesummarytextcolour'] = array(
                    'label' => '-', 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitlesummarybackgroundcolour'] = array(
                    'label' => '-', 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitlesummarybackgroundopacity'] = array(
                    'label' => '-', 'element_type' => 'hidden');
            }

            $newactivityvalues = $this->generate_default_entry(
                'newactivity',
                0,
                array(
                    1 => new lang_string('no'), // No.
                    2 => new lang_string('yes') // Yes.
                )
            );
            $courseformatoptionsedit['newactivity'] = array(
                'label' => new lang_string('setnewactivity', 'format_grid'),
                'element_type' => 'select',
                'element_attributes' => array($newactivityvalues),
                'help' => 'setnewactivity',
                'help_component' => 'format_grid'
            );

            $singlepagesummaryimagevalues = $this->generate_default_entry(
                'singlepagesummaryimage',
                0,
                array(
                    1 => new lang_string('off', 'format_grid'),
                    2 => new lang_string('left', 'format_grid'),
                    3 => new lang_string('centre', 'format_grid'),
                    4 => new lang_string('right', 'format_grid')
                )
            );
            $courseformatoptionsedit['singlepagesummaryimage'] = array(
                'label' => new lang_string('singlepagesummaryimage', 'format_grid'),
                'element_type' => 'select',
                'element_attributes' => array($singlepagesummaryimagevalues),
                'help' => 'singlepagesummaryimage',
                'help_component' => 'format_grid'
            );

            $fitsectioncontainertowindowvalues = $this->generate_default_entry(
                'fitsectioncontainertowindow',
                0,
                array(
                    1 => new lang_string('no'), // No.
                    2 => new lang_string('yes') // Yes.
                )
            );
            $courseformatoptionsedit['fitsectioncontainertowindow'] = array(
                'label' => new lang_string('setfitsectioncontainertowindow', 'format_grid'),
                'help' => 'setfitsectioncontainertowindow',
                'help_component' => 'format_grid',
                'element_type' => 'select',
                'element_attributes' => array($fitsectioncontainertowindowvalues)
            );

            $greyouthiddenvalues = $this->generate_default_entry(
                'greyouthidden',
                0,
                array(
                    1 => new lang_string('no'), // No.
                    2 => new lang_string('yes') // Yes.
                )
            );
            $courseformatoptionsedit['greyouthidden'] = array(
                'label' => new lang_string('greyouthidden', 'format_grid'),
                'help' => 'greyouthidden',
                'help_component' => 'format_grid',
                'element_type' => 'select',
                'element_attributes' => array($greyouthiddenvalues)
            );

            if (has_capability('format/grid:changeimagecontainernavigation', $context)) {
                $setsection0ownpagenogridonesectionvalues = $this->generate_default_entry(
                    'setsection0ownpagenogridonesection',
                    0,
                    array(
                        1 => new lang_string('no'), // No.
                        2 => new lang_string('yes') // Yes.
                    )
                );
                $courseformatoptionsedit['setsection0ownpagenogridonesection'] = array(
                    'label' => new lang_string('setsection0ownpagenogridonesection', 'format_grid'),
                    'help' => 'setsection0ownpagenogridonesection',
                    'help_component' => 'format_grid',
                    'element_type' => 'select',
                    'element_attributes' => array($setsection0ownpagenogridonesectionvalues)
                );
            } else {
                $courseformatoptionsedit['setsection0ownpagenogridonesection'] = array(
                    'label' => 0, 'element_type' => 'hidden');
            }

            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }

        return $courseformatoptions;
    }

    protected function get_course_format_colour_defaults() {
        $defaults = array();
        $defaults['defaultbordercolour'] = get_config('format_grid', 'defaultbordercolour');
        if ($defaults['defaultbordercolour'][0] == '#') {
            $defaults['defaultbordercolour'] = substr($defaults['defaultbordercolour'], 1);
        }
        $defaults['defaultimagecontainerbackgroundcolour'] = get_config('format_grid', 'defaultimagecontainerbackgroundcolour');
        if ($defaults['defaultimagecontainerbackgroundcolour'][0] == '#') {
            $defaults['defaultimagecontainerbackgroundcolour'] = substr($defaults['defaultimagecontainerbackgroundcolour'], 1);
        }
        $defaults['defaultcurrentselectedsectioncolour'] = get_config('format_grid', 'defaultcurrentselectedsectioncolour');
        if ($defaults['defaultcurrentselectedsectioncolour'][0] == '#') {
            $defaults['defaultcurrentselectedsectioncolour'] = substr($defaults['defaultcurrentselectedsectioncolour'], 1);
        }
        $defaults['defaultcurrentselectedimagecontainertextcolour'] = get_config('format_grid', 'defaultcurrentselectedimagecontainertextcolour');
        if ($defaults['defaultcurrentselectedimagecontainertextcolour'][0] == '#') {
            $defaults['defaultcurrentselectedimagecontainertextcolour'] = substr($defaults['defaultcurrentselectedimagecontainertextcolour'], 1);
        }
        $defaults['defaultcurrentselectedimagecontainercolour'] = get_config('format_grid', 'defaultcurrentselectedimagecontainercolour');
        if ($defaults['defaultcurrentselectedimagecontainercolour'][0] == '#') {
            $defaults['defaultcurrentselectedimagecontainercolour'] = substr($defaults['defaultcurrentselectedimagecontainercolour'], 1);
        }
        $defaults['defaultsectiontitleinsidetitletextcolour'] = get_config('format_grid', 'defaultsectiontitleinsidetitletextcolour');
        if ($defaults['defaultsectiontitleinsidetitletextcolour'][0] == '#') {
            $defaults['defaultsectiontitleinsidetitletextcolour'] = substr($defaults['defaultsectiontitleinsidetitletextcolour'], 1);
        }
        $defaults['defaultsectiontitleinsidetitlebackgroundcolour'] = get_config('format_grid', 'defaultsectiontitleinsidetitlebackgroundcolour');
        if ($defaults['defaultsectiontitleinsidetitlebackgroundcolour'][0] == '#') {
            $defaults['defaultsectiontitleinsidetitlebackgroundcolour'] = substr($defaults['defaultsectiontitleinsidetitlebackgroundcolour'], 1);
        }
        $defaults['defaultsectiontitlesummarytextcolour'] = get_config('format_grid', 'defaultsectiontitlesummarytextcolour');
        if ($defaults['defaultsectiontitlesummarytextcolour'][0] == '#') {
            $defaults['defaultsectiontitlesummarytextcolour'] = substr($defaults['defaultsectiontitlesummarytextcolour'], 1);
        }
        $defaults['defaultsectiontitlesummarybackgroundcolour'] = get_config('format_grid', 'defaultsectiontitlesummarybackgroundcolour');
        if ($defaults['defaultsectiontitlesummarybackgroundcolour'][0] == '#') {
            $defaults['defaultsectiontitlesummarybackgroundcolour'] = substr($defaults['defaultsectiontitlesummarybackgroundcolour'], 1);
        }
        return $defaults;
    }

    /**
     * Generates the default setting value entry.
     *
     * @param string $settingname Setting name.
     * @param string/int $defaultindex Default index.
     * @param array $values Setting value array to add the default entry to.
     * @return array Updated value array with the added default entry.
     */
    private function generate_default_entry($settingname, $defaultindex, $values) {
        $defaultvalue = get_config('format_grid', 'default'.$settingname);
        $defarray = array($defaultindex => new lang_string('default', 'format_grid', $values[$defaultvalue]));

        return array_replace($defarray, $values);
    }

    /**
     * Adds format options elements to the course/section edit form.
     *
     * This function is called from {@link course_edit_form::definition_after_data()}.
     *
     * @param MoodleQuickForm $mform form the elements are added to.
     * @param bool $forsection 'true' if this is a section edit form, 'false' if this is course edit form.
     * @return array array of references to the added form elements.
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        global $CFG, $OUTPUT, $PAGE, $USER;
        MoodleQuickForm::registerElementType('gfcolourpopup', "$CFG->dirroot/course/format/grid/js/gf_colourpopup.php",
                'MoodleQuickForm_gfcolourpopup');

        $elements = parent::create_edit_form_elements($mform, $forsection);

        /* Increase the number of sections combo box values if the user has increased the number of sections
           using the icon on the course page beyond course 'maxsections' or course 'maxsections' has been
           reduced below the number of sections already set for the course on the site administration course
           defaults page.  This is so that the number of sections is not reduced leaving unintended orphaned
           activities / resources. */
        if (!$forsection) {
            $maxsections = get_config('moodlecourse', 'maxsections');
            $numsections = $mform->getElementValue('numsections');
            $numsections = $numsections[0];
            if ($numsections > $maxsections) {
                $element = $mform->getElement('numsections');
                for ($i = $maxsections + 1; $i <= $numsections; $i++) {
                    $element->addOption("$i", $i);
                }
            }
        }
        $context = self::get_context($this);

        $changeimagecontaineralignment = has_capability('format/grid:changeimagecontaineralignment', $context);
        $changeimagecontainernavigation = has_capability('format/grid:changeimagecontainernavigation', $context);
        $changeimagecontainersize = has_capability('format/grid:changeimagecontainersize', $context);
        $changeimageresizemethod = has_capability('format/grid:changeimageresizemethod', $context);
        $changeimagecontainerstyle = has_capability('format/grid:changeimagecontainerstyle', $context);
        $changesectiontitleoptions = has_capability('format/grid:changesectiontitleoptions', $context);
        $resetall = is_siteadmin($USER); // Site admins only.

        $elements[] = $mform->addElement('header', 'gfreset', get_string('gfreset', 'format_grid'));
        $mform->addHelpButton('gfreset', 'gfreset', 'format_grid', '', true);

        $bsfour = false;
        if (strcmp($PAGE->theme->name, 'boost') === 0) {
            $bsfour = true;
        } else if (!empty($PAGE->theme->parents)) {
            if (in_array('boost', $PAGE->theme->parents) === true) {
                $bsfour = true;
            }
        } else if (strcmp($PAGE->theme->name, 'foundation') === 0) {
            $bsfour = true;
        }

        $resetelements = array();

        if (($changeimagecontaineralignment) ||
            ($changeimagecontainernavigation) ||
            ($changeimagecontainersize) ||
            ($changeimageresizemethod) ||
            ($changeimagecontainerstyle) ||
            ($changesectiontitleoptions)) {

            if ($changeimagecontaineralignment) {
                if ($bsfour) {
                    $checkboxname = get_string('resetimagecontaineralignment', 'format_grid');
                    $resetelements[] = & $mform->createElement('checkbox', 'resetimagecontaineralignment', '', $checkboxname);
                    $resetelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetimagecontaineralignment', 'format_grid'));
                } else {
                    $checkboxname = get_string('resetimagecontaineralignment', 'format_grid').
                        $OUTPUT->help_icon('resetimagecontaineralignment', 'format_grid');
                    $resetelements[] = & $mform->createElement('checkbox', 'resetimagecontaineralignment', '', $checkboxname);
                }
            }

            if ($changeimagecontainernavigation) {
                if ($bsfour) {
                    $checkboxname = get_string('resetimagecontainernavigation', 'format_grid');
                    $resetelements[] = & $mform->createElement('checkbox', 'resetimagecontainernavigation', '', $checkboxname);
                    $resetelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetimagecontainernavigation', 'format_grid'));
                } else {
                    $checkboxname = get_string('resetimagecontainernavigation', 'format_grid').
                        $OUTPUT->help_icon('resetimagecontainernavigation', 'format_grid');
                    $resetelements[] = & $mform->createElement('checkbox', 'resetimagecontainernavigation', '', $checkboxname);
                }
            }

            if ($changeimagecontainersize) {
                if ($bsfour) {
                    $checkboxname = get_string('resetimagecontainersize', 'format_grid');
                    $resetelements[] = & $mform->createElement('checkbox', 'resetimagecontainersize', '', $checkboxname);
                    $resetelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetimagecontainersize', 'format_grid'));
                } else {
                    $checkboxname = get_string('resetimagecontainersize', 'format_grid').
                        $OUTPUT->help_icon('resetimagecontainersize', 'format_grid');
                    $resetelements[] = & $mform->createElement('checkbox', 'resetimagecontainersize', '', $checkboxname);
                }
            }

            if ($changeimageresizemethod) {
                if ($bsfour) {
                    $checkboxname = get_string('resetimageresizemethod', 'format_grid');
                    $resetelements[] = & $mform->createElement('checkbox', 'resetimageresizemethod', '', $checkboxname);
                    $resetelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetimageresizemethod', 'format_grid'));
                } else {
                    $checkboxname = get_string('resetimageresizemethod', 'format_grid').
                        $OUTPUT->help_icon('resetimageresizemethod', 'format_grid');
                    $resetelements[] = & $mform->createElement('checkbox', 'resetimageresizemethod', '', $checkboxname);
                }
            }

            if ($changeimagecontainerstyle) {
                if ($bsfour) {
                    $checkboxname = get_string('resetimagecontainerstyle', 'format_grid');
                    $resetelements[] = & $mform->createElement('checkbox', 'resetimagecontainerstyle', '', $checkboxname);
                    $resetelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetimagecontainerstyle', 'format_grid'));
                } else {
                    $checkboxname = get_string('resetimagecontainerstyle', 'format_grid').
                        $OUTPUT->help_icon('resetimagecontainerstyle', 'format_grid');
                    $resetelements[] = & $mform->createElement('checkbox', 'resetimagecontainerstyle', '', $checkboxname);
                }
            }

            if ($changesectiontitleoptions) {
                if ($bsfour) {
                    $checkboxname = get_string('resetsectiontitleoptions', 'format_grid');
                    $resetelements[] = & $mform->createElement('checkbox', 'resetsectiontitleoptions', '', $checkboxname);
                    $resetelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetsectiontitleoptions', 'format_grid'));
                } else {
                    $checkboxname = get_string('resetsectiontitleoptions', 'format_grid').
                        $OUTPUT->help_icon('resetsectiontitleoptions', 'format_grid');
                    $resetelements[] = & $mform->createElement('checkbox', 'resetsectiontitleoptions', '', $checkboxname);
                }
            }
        }

        if ($bsfour) {
            $checkboxname = get_string('resetnewactivity', 'format_grid');
            $resetelements[] = & $mform->createElement('checkbox', 'resetnewactivity', '', $checkboxname);
            $resetelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetnewactivity', 'format_grid'));

            $checkboxname = get_string('resetsinglepagesummaryimage', 'format_grid');
            $resetelements[] = & $mform->createElement('checkbox', 'resetsinglepagesummaryimage', '', $checkboxname);
            $resetelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetsinglepagesummaryimage', 'format_grid'));

            $checkboxname = get_string('resetfitpopup', 'format_grid');
            $resetelements[] = & $mform->createElement('checkbox', 'resetfitpopup', '', $checkboxname);
            $resetelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetfitpopup', 'format_grid'));

            $checkboxname = get_string('resetgreyouthidden', 'format_grid');
            $resetelements[] = & $mform->createElement('checkbox', 'resetgreyouthidden', '', $checkboxname);
            $resetelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetgreyouthidden', 'format_grid'));
        } else {
            $checkboxname = get_string('resetnewactivity', 'format_grid').
                $OUTPUT->help_icon('resetnewactivity', 'format_grid');
            $resetelements[] = & $mform->createElement('checkbox', 'resetnewactivity', '', $checkboxname);

            $checkboxname = get_string('resetsinglepagesummaryimage', 'format_grid').
                $OUTPUT->help_icon('resetsinglepagesummaryimage', 'format_grid');
            $resetelements[] = & $mform->createElement('checkbox', 'resetsinglepagesummaryimage', '', $checkboxname);

            $checkboxname = get_string('resetfitpopup', 'format_grid').
                $OUTPUT->help_icon('resetfitpopup', 'format_grid');
            $resetelements[] = & $mform->createElement('checkbox', 'resetpopup', '', $checkboxname);

            $checkboxname = get_string('resetgreyouthidden', 'format_grid').
                $OUTPUT->help_icon('resetgreyouthidden', 'format_grid');
            $resetelements[] = & $mform->createElement('checkbox', 'resetgreyouthidden', '', $checkboxname);
        }
        $elements[] = $mform->addGroup($resetelements, 'resetgroup', get_string('resetgrp', 'format_grid'), null, false);

        if ($resetall) {
            $resetallelements = array();

            if ($bsfour) {
                $checkboxname = get_string('resetallimagecontaineralignment', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallimagecontaineralignment', '', $checkboxname);
                $resetallelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetallimagecontaineralignment', 'format_grid'));

                $checkboxname = get_string('resetallimagecontainernavigation', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallimagecontainernavigation', '', $checkboxname);
                $resetallelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetallimagecontainernavigation', 'format_grid'));

                $checkboxname = get_string('resetallimagecontainersize', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallimagecontainersize', '', $checkboxname);
                $resetallelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetallimagecontainersize', 'format_grid'));

                $checkboxname = get_string('resetallimageresizemethod', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallimageresizemethod', '', $checkboxname);
                $resetallelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetallimageresizemethod', 'format_grid'));

                $checkboxname = get_string('resetallimagecontainerstyle', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallimagecontainerstyle', '', $checkboxname);
                $resetallelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetallimagecontainerstyle', 'format_grid'));

                $checkboxname = get_string('resetallsectiontitleoptions', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallsectiontitleoptions', '', $checkboxname);
                $resetallelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetallsectiontitleoptions', 'format_grid'));

                $checkboxname = get_string('resetallnewactivity', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallnewactivity', '', $checkboxname);
                $resetallelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetallnewactivity', 'format_grid'));

                $checkboxname = get_string('resetallsinglepagesummaryimage', 'format_grid');
                $resetelements[] = & $mform->createElement('checkbox', 'resetallsinglepagesummaryimage', '', $checkboxname);
                $resetelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetallsinglepagesummaryimage', 'format_grid'));

                $checkboxname = get_string('resetallfitpopup', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallfitpopup', '', $checkboxname);
                $resetallelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetallfitpopup', 'format_grid'));

                $checkboxname = get_string('resetallgreyouthidden', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallgreyouthidden', '', $checkboxname);
                $resetallelements[] = & $mform->createElement('html', $OUTPUT->help_icon('resetallgreyouthidden', 'format_grid'));
            } else {
                $checkboxname = get_string('resetallimagecontaineralignment', 'format_grid').
                    $OUTPUT->help_icon('resetallimagecontaineralignment', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallimagecontaineralignment', '', $checkboxname);

                $checkboxname = get_string('resetallimagecontainernavigation', 'format_grid').
                    $OUTPUT->help_icon('resetallimagecontainernavigation', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallimagecontainernavigation', '', $checkboxname);

                $checkboxname = get_string('resetallimagecontainersize', 'format_grid').
                    $OUTPUT->help_icon('resetallimagecontainersize', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallimagecontainersize', '', $checkboxname);

                $checkboxname = get_string('resetallimageresizemethod', 'format_grid').
                    $OUTPUT->help_icon('resetallimageresizemethod', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallimageresizemethod', '', $checkboxname);

                $checkboxname = get_string('resetallimagecontainerstyle', 'format_grid').
                    $OUTPUT->help_icon('resetallimagecontainerstyle', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallimagecontainerstyle', '', $checkboxname);

                $checkboxname = get_string('resetallsectiontitleoptions', 'format_grid').
                    $OUTPUT->help_icon('resetallsectiontitleoptions', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallsectiontitleoptions', '', $checkboxname);

                $checkboxname = get_string('resetallnewactivity', 'format_grid').
                    $OUTPUT->help_icon('resetallnewactivity', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallnewactivity', '', $checkboxname);

                $checkboxname = get_string('resetallsinglepagesummaryimage', 'format_grid').
                    $OUTPUT->help_icon('resetallsinglepagesummaryimage', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallsinglepagesummaryimage', '', $checkboxname);

                $checkboxname = get_string('resetallfitpopup', 'format_grid').
                    $OUTPUT->help_icon('resetallfitpopup', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallfitpopup', '', $checkboxname);

                $checkboxname = get_string('resetallgreyouthidden', 'format_grid').
                    $OUTPUT->help_icon('resetallgreyouthidden', 'format_grid');
                $resetallelements[] = & $mform->createElement('checkbox', 'resetallgreyouthidden', '', $checkboxname);
            }

            $elements[] = $mform->addGroup($resetallelements, 'resetallgroup', get_string('resetallgrp', 'format_grid'), null,
                false);
        }

        return $elements;
    }

    /**
     * Override if you need to perform some extra validation of the format options
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param array $errors errors already discovered in edit form validation
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     *         Do not repeat errors from $errors param here
     */
    public function edit_form_validation($data, $files, $errors) {
        $retr = array();

        if ($this->validate_colour($data['bordercolour']) === false) {
            $retr['bordercolour'] = get_string('colourrule', 'format_grid');
        }
        if ($this->validate_colour($data['imagecontainerbackgroundcolour']) === false) {
            $retr['imagecontainerbackgroundcolour'] = get_string('colourrule', 'format_grid');
        }
        if ($this->validate_colour($data['currentselectedsectioncolour']) === false) {
            $retr['currentselectedsectioncolour'] = get_string('colourrule', 'format_grid');
        }
        if ($this->validate_colour($data['currentselectedimagecontainercolour']) === false) {
            $retr['currentselectedimagecontainercolour'] = get_string('colourrule', 'format_grid');
        }
        if ($data['sectiontitlegridlengthmaxoption'] < 0) {
            $retr['sectiontitlegridlengthmaxoption'] = get_string('sectiontitlegridlengthmaxoptionrule', 'format_grid');
        }
        if ($this->validate_colour($data['sectiontitleinsidetitletextcolour']) === false) {
            $retr['sectiontitleinsidetitletextcolour'] = get_string('colourrule', 'format_grid');
        }
        if ($this->validate_colour($data['sectiontitleinsidetitlebackgroundcolour']) === false) {
            $retr['sectiontitleinsidetitlebackgroundcolour'] = get_string('colourrule', 'format_grid');
        }
        if (\format_grid\toolbox::validate_opacity($data['sectiontitleboxopacity']) === false) {
            $retr['sectiontitleboxopacity'] = get_string('opacityrule', 'format_grid');
        }
        if (\format_grid\toolbox::validate_section_title_font_size($data['sectiontitlefontsize']) === false) {
            $retr['sectiontitlefontsize'] = get_string('sectiontitlefontsizerule', 'format_grid');
        }
        if ($this->validate_colour($data['sectiontitlesummarytextcolour']) === false) {
            $retr['sectiontitlesummarytextcolour'] = get_string('colourrule', 'format_grid');
        }
        if ($this->validate_colour($data['sectiontitlesummarybackgroundcolour']) === false) {
            $retr['sectiontitlesummarybackgroundcolour'] = get_string('colourrule', 'format_grid');
        }
        if (\format_grid\toolbox::validate_opacity($data['sectiontitlesummarybackgroundopacity']) === false) {
            $retr['sectiontitlesummarybackgroundopacity'] = get_string('opacityrule', 'format_grid');
        }
        return $retr;
    }

    /**
     * Validates the colour that was entered by the user.
     * Borrowed from 'admin_setting_configcolourpicker' in '/lib/adminlib.php'.
     *
     * I'm not completely happy with this solution as would rather embed in the colour
     * picker code in the form, however I find this area rather fraut and I hear that
     * Dan Poltawski (via MDL-42270) will be re-writing the forms lib so hopefully more
     * developer friendly.
     *
     * Note: Colour names removed, but might consider putting them back in if asked, but
     *       at the moment that would require quite a few changes and coping with existing
     *       settings.  Either convert the names to hex or allow them as valid values and
     *       fix the colour picker code and the CSS code in 'format.php' for the setting.
     *
     * Colour name to hex on: http://www.w3schools.com/cssref/css_colornames.asp.
     *
     * @param string $data the colour string to validate.
     * @return true|false
     */
    private function validate_colour($data) {
        if ($data == '-') {
            return true;
        } else if (preg_match('/^#?([[:xdigit:]]{3}){1,2}$/', $data)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Updates format options for a course
     *
     * In case if course format was changed to 'Grid', we try to copy options
     * 'coursedisplay', 'numsections' and 'hiddensections' from the previous format.
     * The layout and colour defaults will come from 'course_format_options'.
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data.
     * @param stdClass $oldcourse if this function is called from {@link update_course()}
     *     this object contains information about the course before update.
     * @return bool whether there were any changes to the options values.
     */
    public function update_course_format_options($data, $oldcourse = null) {
        global $DB; // MDL-37976.
        /*
         * Notes: Using 'unset' to really ensure that the reset form elements never get into the database.
         *        This has to be done here so that the reset occurs after we have done updates such that the
         *        reset itself is not seen as an update.
         */
        $resetimagecontaineralignment = false;
        $resetimagecontainernavigation = false;
        $resetimagecontainersize = false;
        $resetimageresizemethod = false;
        $resetimagecontainerstyle = false;
        $resetsectiontitleoptions = false;
        $resetnewactivity = false;
        $resetsinglepagesummaryimage = false;
        $resetfitpopup = false;
        $resetgreyouthidden = false;
        $resetallimagecontaineralignment = false;
        $resetallimagecontainernavigation = false;
        $resetallimagecontainersize = false;
        $resetallimageresizemethod = false;
        $resetallimagecontainerstyle = false;
        $resetallsectiontitleoptions = false;
        $resetallnewactivity = false;
        $resetallsinglepagesummaryimage = false;
        $resetallfitpopup = false;
        $resetallgreyouthidden = false;
        if (isset($data->resetimagecontaineralignment) == true) {
            $resetimagecontaineralignment = true;
            unset($data->resetimagecontaineralignment);
        }
        if (isset($data->resetimagecontainernavigation) == true) {
            $resetimagecontainernavigation = true;
            unset($data->resetimagecontainernavigation);
        }
        if (isset($data->resetimagecontainersize) == true) {
            $resetimagecontainersize = true;
            unset($data->resetimagecontainersize);
        }
        if (isset($data->resetimageresizemethod) == true) {
            $resetimageresizemethod = true;
            unset($data->resetimageresizemethod);
        }
        if (isset($data->resetimagecontainerstyle) == true) {
            $resetimagecontainerstyle = true;
            unset($data->resetimagecontainerstyle);
        }
        if (isset($data->resetsectiontitleoptions) == true) {
            $resetsectiontitleoptions = true;
            unset($data->resetsectiontitleoptions);
        }
        if (isset($data->resetnewactivity) == true) {
            $resetnewactivity = true;
            unset($data->resetnewactivity);
        }
        if (isset($data->resetsinglepagesummaryimage) == true) {
            $resetsinglepagesummaryimage = true;
            unset($data->resetsinglepagesummaryimage);
        }
        if (isset($data->resetfitpopup) == true) {
            $resetfitpopup = true;
            unset($data->resetfitpopup);
        }
        if (isset($data->resetgreyouthidden) == true) {
            $resetgreyouthidden = true;
            unset($data->resetgreyouthidden);
        }
        if (isset($data->resetallimagecontaineralignment) == true) {
            $resetallimagecontaineralignment = true;
            unset($data->resetallimagecontaineralignment);
        }
        if (isset($data->resetallimagecontainernavigation) == true) {
            $resetallimagecontainernavigation = true;
            unset($data->resetallimagecontainernavigation);
        }
        if (isset($data->resetallimagecontainersize) == true) {
            $resetallimagecontainersize = true;
            unset($data->resetallimagecontainersize);
        }
        if (isset($data->resetallimageresizemethod) == true) {
            $resetallimageresizemethod = true;
            unset($data->resetallimageresizemethod);
        }
        if (isset($data->resetallimagecontainerstyle) == true) {
            $resetallimagecontainerstyle = true;
            unset($data->resetallimagecontainerstyle);
        }
        if (isset($data->resetallsectiontitleoptions) == true) {
            $resetallsectiontitleoptions = true;
            unset($data->resetallsectiontitleoptions);
        }
        if (isset($data->resetallnewactivity) == true) {
            $resetallnewactivity = true;
            unset($data->resetallnewactivity);
        }
        if (isset($data->resetallsinglepagesummaryimage) == true) {
            $resetallsinglepagesummaryimage = true;
            unset($data->resetallsinglepagesummaryimage);
        }
        if (isset($data->resetallfitpopup) == true) {
            $resetfitpopup = true;
            unset($data->resetallfitpopup);
        }
        if (isset($data->resetallgreyouthidden) == true) {
            $resetallgreyouthidden = true;
            unset($data->resetallgreyouthidden);
        }

        $currentsettings = $this->get_settings();
        $data = (array) $data;
        if ($oldcourse !== null) {
            $oldcourse = (array) $oldcourse;
            $options = $this->course_format_options();

            foreach ($options as $key => $unused) {
                if (!array_key_exists($key, $data)) {
                    if (array_key_exists($key, $oldcourse)) {
                        $data[$key] = $oldcourse[$key];
                    } else if ($key === 'numsections') {
                        /* If previous format does not have the field 'numsections' and $data['numsections'] is not set,
                           we fill it with the maximum section number from the DB. */
                        $maxsection = $DB->get_field_sql('SELECT max(section) from {course_sections}
                            WHERE course = ?', array($this->courseid));
                        if ($maxsection) {
                            // If there are no sections, or just default 0-section, 'numsections' will be set to default.
                            $data['numsections'] = $maxsection;
                        }
                    }
                }
            }
        }
        $changes = $this->update_format_options($data);

        if ($changes && array_key_exists('numsections', $data)) {
            // If the numsections was decreased, try to completely delete the orphaned sections (unless they are not empty).
            $numsections = (int)$data['numsections'];
            $maxsection = $DB->get_field_sql('SELECT max(section) from {course_sections}
                WHERE course = ?', array($this->courseid));
            for ($sectionnum = $maxsection; $sectionnum > $numsections; $sectionnum--) {
                if (!$this->delete_section($sectionnum, false)) {
                    break;
                }
            }
        }

        // Now we can do the reset.
        $ourimageschanged = false;
        if (($resetallimagecontaineralignment) ||
            ($resetallimagecontainernavigation) ||
            ($resetallimagecontainersize) ||
            ($resetallimageresizemethod) ||
            ($resetallimagecontainerstyle) ||
            ($resetallsectiontitleoptions) ||
            ($resetallnewactivity) ||
            ($resetallsinglepagesummaryimage) ||
            ($resetallfitpopup) ||
            ($resetallgreyouthidden)) {
            $ourimageschanged = $this->reset_grid_setting(0, $currentsettings, $resetallimagecontaineralignment,
                $resetallimagecontainernavigation, $resetallimagecontainersize, $resetallimageresizemethod,
                $resetallimagecontainerstyle, $resetallsectiontitleoptions, $resetallnewactivity,
                $resetallsinglepagesummaryimage, $resetallfitpopup, $resetallgreyouthidden);
            $changes = true;
        } else if (
            ($resetimagecontaineralignment) ||
            ($resetimagecontainernavigation) ||
            ($resetimagecontainersize) ||
            ($resetimageresizemethod) ||
            ($resetimagecontainerstyle) ||
            ($resetsectiontitleoptions) ||
            ($resetnewactivity) ||
            ($resetsinglepagesummaryimage) ||
            ($resetfitpopup) ||
            ($resetgreyouthidden)) {
            $ourimageschanged = $this->reset_grid_setting($this->courseid, $currentsettings, $resetimagecontaineralignment,
                $resetimagecontainernavigation, $resetimagecontainersize, $resetimageresizemethod, $resetimagecontainerstyle,
                $resetsectiontitleoptions, $resetnewactivity, $resetsinglepagesummaryimage, $resetfitpopup, $resetgreyouthidden);
            $changes = true;
        }

        if (!$ourimageschanged) {
            // A reset has not changed the images, so check if they need to be.
            $newsettings = $this->get_settings(true); // Ensure we get the new values.

            if (($currentsettings['imagecontainerwidth'] != $newsettings['imagecontainerwidth']) ||
                ($currentsettings['imagecontainerratio'] != $newsettings['imagecontainerratio'])) {
                $performimagecontainersize = true;
            } else {
                $performimagecontainersize = false;
            }

            if (($currentsettings['imageresizemethod'] != $newsettings['imageresizemethod'])) {
                $performimageresizemethod = true;
            } else {
                $performimageresizemethod = false;
            }

            if (($performimagecontainersize) || ($performimageresizemethod)) {
                \format_grid\toolbox::update_displayed_images($this->courseid);
            }
        }

        return $changes;
    }

    /**
     * Deletes a section
     *
     * Do not call this function directly, instead call {@link course_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @param bool $forcedeleteifnotempty if set to false section will not be deleted if it has modules in it.
     * @return bool whether section was deleted
     */
    public function delete_section($section, $forcedeleteifnotempty = false) {
        if (!$this->uses_sections()) {
            // Not possible to delete section if sections are not used.
            return false;
        }
        if (!is_object($section)) {
            global $DB;
            $section = $DB->get_record('course_sections', array('course' => $this->get_courseid(), 'section' => $section),
                'id,section,sequence,summary');
        }
        if (!$section || !$section->section) {
            // Not possible to delete 0-section.
            return false;
        }

        if (!$forcedeleteifnotempty && (!empty($section->sequence) || !empty($section->summary))) {
            return false;
        }
        if (parent::delete_section($section, $forcedeleteifnotempty)) {
            \format_grid\toolbox::delete_image($section->id, self::get_contextid($this), $this->get_courseid());
            return true;
        }
        return false;
    }

    /**
     * Whether this format allows to delete sections
     *
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Updates format options for a section
     *
     * Section id is expected in $data->id (or $data['id'])
     * If $data does not contain property with the option name, the option will not be updated
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     *
     * @return bool whether there were any changes to the options values
     */
    public function update_section_format_options($data) {
        $data = (array) $data;

        /* Resets the displayed image because changing the section name / details deletes the file.
           See CONTRIB-4784. */
        $sectionimage = \format_grid\toolbox::get_image($this->courseid, $data['id']);
        if ($sectionimage) {
            // Set up our table to get the displayed image back.  The 'auto repair' on page reload will do the rest.
            global $DB;
            $DB->set_field('format_grid_icon', 'displayedimageindex', 0, array('sectionid' => $sectionimage->sectionid));
            // We know the file is normally deleted, but just in case...
            $contextid = self::get_contextid($this);
            $fs = get_file_storage();
            $gridimagepath = \format_grid\toolbox::get_image_path();
            \format_grid\toolbox::delete_displayed_image($contextid, $sectionimage, $gridimagepath, $fs);
        }

        return parent::update_section_format_options($data);
    }

    /**
     * Resets the format setting to the default.
     * @param int $courseid If not 0, then a specific course to reset.
     * @param int $imagecontaineralignmentreset If true, reset the alignment to the default in the settings for the format.
     * @param int $imagecontainernavigationreset If true, reset the alignment to the default in the settings for the format.
     * @param int $imagecontainersizereset If true, reset the layout to the default in the settings for the format.
     * @param int $imageresizemethodreset If true, reset the image resize method to the default in the settings for the format.
     * @param int $imagecontainerstylereset If true, reset the colour to the default in the settings for the format.
     * @param int $sectiontitleoptionsreset If true, reset the section title options to the default in the settings for the format.
     * @param int $newactivityreset If true, reset the new activity to the default in the settings for the format.
     * @param int $singlepagesummaryimagereset If true, reset the single page summary image to the default in the settings for the format.
     * @param int $fitpopupreset If true, reset the fit popup to the default in the settings for the format.
     * @param int $greyouthidden If true, reset the greyout hidden to the default in the settings for the format.
     *
     * @return bool If our images have changed.
     */
    private function reset_grid_setting($courseid, $ourcurrentsettings, $imagecontaineralignmentreset,
        $imagecontainernavigationreset, $imagecontainersizereset, $imageresizemethodreset, $imagecontainerstylereset,
        $sectiontitleoptionsreset, $newactivityreset, $singlepagesummaryimagereset, $fitpopupreset, $greyouthidden) {
        global $DB, $USER;

        $context = self::get_context($this);

        if ($courseid == 0) {
            $records = $DB->get_records('course', array('format' => $this->format), '', 'id');
        } else {
            $records = $DB->get_records('course', array('id' => $courseid, 'format' => $this->format), '', 'id');
        }

        $resetallifall = ((is_siteadmin($USER)) || ($courseid != 0)); // Will be true if reset all capability or a single course.

        $updatedata = array();
        $updateimagecontaineralignment = false;
        $updateimagecontainernavigation = false;
        $updateimagecontainersize = false;
        $updateimageresizemethod = false;
        $updateimagecontainerstyle = false;
        $updatesectiontitleoptions = false;
        $updatenewactivity = false;
        $updatesinglepagesummaryimage = false;
        $updatefitpopup = false;
        $updategreyouthidden = false;
        if ($imagecontaineralignmentreset && has_capability('format/grid:changeimagecontaineralignment', $context) && $resetallifall) {
            $updatedata['imagecontaineralignment'] = '-';
            $updateimagecontaineralignment = true;
        }
        if ($imagecontainernavigationreset && has_capability('format/grid:changeimagecontainernavigation', $context) && $resetallifall) {
            $updatedata['setsection0ownpagenogridonesection'] = 0;
            $updateimagecontainernavigation = true;
        }
        if ($imagecontainersizereset && has_capability('format/grid:changeimagecontainersize', $context) && $resetallifall) {
            $updatedata['imagecontainerwidth'] = 0;
            $updatedata['imagecontainerratio'] = '-';
            $updateimagecontainersize = true;
        }
        if ($imageresizemethodreset && has_capability('format/grid:changeimageresizemethod', $context) && $resetallifall) {
            $updatedata['imageresizemethod'] = 0;
            $updateimageresizemethod = true;
        }
        if ($imagecontainerstylereset && has_capability('format/grid:changeimagecontainerstyle', $context) && $resetallifall) {
            $updatedata['bordercolour'] = '-';
            $updatedata['borderwidth'] = 0;
            $updatedata['borderradius'] = 0;
            $updatedata['imagecontainerbackgroundcolour'] = '-';
            $updatedata['currentselectedsectioncolour'] = '-';
            $updatedata['currentselectedimagecontainertextcolour'] = '-';
            $updatedata['currentselectedimagecontainercolour'] = '-';
            $updateimagecontainerstyle = true;
        }
        if ($sectiontitleoptionsreset && has_capability('format/grid:changesectiontitleoptions', $context) && $resetallifall) {
            $updatedata['hidesectiontitle'] = 0;
            $updatedata['sectiontitlegridlengthmaxoption'] = '-';
            $updatedata['sectiontitleboxposition'] = 0;
            $updatedata['sectiontitleboxinsideposition'] = 0;
            $updatedata['sectiontitleboxheight'] = '-';
            $updatedata['sectiontitleboxopacity'] = '-';
            $updatedata['sectiontitlefontsize'] = '-';
            $updatedata['sectiontitlealignment'] = '-';
            $updatedata['sectiontitleinsidetitletextcolour'] = '-';
            $updatedata['sectiontitleinsidetitlebackgroundcolour'] = '-';
            $updatedata['showsectiontitlesummary'] = 0;
            $updatedata['setshowsectiontitlesummaryposition'] = 0;
            $updatedata['sectiontitlesummarymaxlength'] = '-';
            $updatedata['sectiontitlesummarytextcolour'] = '-';
            $updatedata['sectiontitlesummarybackgroundcolour'] = '-';
            $updatedata['sectiontitlesummarybackgroundopacity'] = '-';
            $updatesectiontitleoptions = true;
        }
        if ($newactivityreset && $resetallifall) {
            $updatedata['newactivity'] = 0;
            $updatenewactivity = true;
        }
        if ($singlepagesummaryimagereset && $resetallifall) {
            $updatedata['singlepagesummaryimage'] = 0;
            $updatesinglepagesummaryimage = true;
        }
        if ($fitpopupreset && $resetallifall) {
            $updatedata['fitsectioncontainertowindow'] = 0;
            $updatefitpopup = true;
        }
        if ($greyouthidden && $resetallifall) {
            $updatedata['greyouthidden'] = 0;
            $updategreyouthidden = true;
        }

        $ourimagesupdated = false;
        foreach ($records as $record) {
            if (($updateimagecontaineralignment) ||
                ($updateimagecontainernavigation) ||
                ($updateimagecontainersize) ||
                ($updateimageresizemethod) ||
                ($updateimagecontainerstyle) ||
                ($updatesectiontitleoptions) ||
                ($updatenewactivity) ||
                ($updatefitpopup) ||
                ($updategreyouthidden)) {

                if ($record->id !== $this->courseid) {
                    $courseformat = course_get_format($record->id);
                } else {
                    $courseformat = $this;
                }
                if (($updateimagecontainersize) || ($updateimageresizemethod)) {
                    if ($record->id === $this->courseid) {
                        $currentsettings = $ourcurrentsettings;
                    } else {
                        $currentsettings = $courseformat->get_settings();
                    }
                    $courseformat->update_format_options($updatedata);
                    $newsettings = $courseformat->get_settings(true); // Ensure we get the new values.

                    if (($updateimagecontainersize) &&
                        (($currentsettings['imagecontainerwidth'] != $newsettings['imagecontainerwidth']) ||
                         ($currentsettings['imagecontainerratio'] != $newsettings['imagecontainerratio']))) {
                        $performimagecontainersize = true;
                    } else {
                        $performimagecontainersize = false;
                    }

                    if (($updateimageresizemethod) &&
                        ($currentsettings['imageresizemethod'] != $newsettings['imageresizemethod'])) {
                        $performimageresizemethod = true;
                    } else {
                        $performimageresizemethod = false;
                    }

                    if (($performimagecontainersize) || ($performimageresizemethod)) {
                        \format_grid\toolbox::update_displayed_images($record->id);
                        if ($record->id === $this->courseid) {
                            $ourimagesupdated = true;
                        }
                    }
                } else {
                    $courseformat->update_format_options($updatedata);
                }
            }
        }

        return $ourimagesupdated;
    }

    // Grid specific methods...
    /**
     * Class instance update images callback.
     */
    public static function update_displayed_images_callback() {
        \format_grid\toolbox::update_displayed_images_callback();
    }

    /**
     * Returns a new instance of us so that specialised methods can be called.
     * @param int $courseid The course id of the course.
     * @return format_grid object.
     */
    public static function get_instance($courseid) {
        return new format_grid('grid', $courseid);
    }

    /**
     * Prepares the templateable object to display section name.
     *
     * @param \section_info|\stdClass $section
     * @param bool $linkifneeded
     * @param bool $editable
     * @param null|lang_string|string $edithint
     * @param null|lang_string|string $editlabel
     * @return \core\output\inplace_editable
     */
    public function inplace_editable_render_section_name($section, $linkifneeded = true, $editable = null, $edithint = null,
            $editlabel = null) {
        if (empty($edithint)) {
            $edithint = new lang_string('editsectionname', 'format_grid');
        }
        if (empty($editlabel)) {
            $title = $this->get_section_name($section);
            $editlabel = new lang_string('newsectionname', 'format_grid', $title);
        }
        return parent::inplace_editable_render_section_name($section, $linkifneeded, $editable, $edithint, $editlabel);
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section) {
        // Allow the third visibility state inside visible sections or in section 0, not allow in orphaned sections.
        return !$section->section || ($section->visible && $section->section <= $this->get_course()->numsections);
    }

    public function section_action($section, $action, $sr) {
        if ($section->section && ($action === 'setmarker' || $action === 'removemarker')) {
            // Format 'grid' allows to set and remove markers in addition to common section actions.
            require_capability('moodle/course:setcurrentsection', context_course::instance($this->courseid));
            course_set_marker($this->courseid, ($action === 'setmarker') ? $section->section : 0);
            return null;
        }

        // For show/hide actions call the parent method and return the new content for .section_availability element.
        global $PAGE;
        $rv = parent::section_action($section, $action, $sr);
        $renderer = $PAGE->get_renderer('format_grid');
        $rv['section_availability'] = $renderer->section_availability($this->get_section($section));
        return $rv;
    }

    /**
     * Restores the numsections if was not in the backup.
     * @param int $numsections The number of sections.
     */
    public function restore_numsections($numsections) {
        $data = array('numsections' => $numsections);
        $this->update_course_format_options($data);
    }

    public static function get_context($us) {
        global $SITE;

        if ($SITE->id == $us->courseid) {
            // Use the context of the page which should be the course category.
            global $PAGE;
            return $PAGE->context;
        } else {
            return context_course::instance($us->courseid);
        }
    }

    public static function get_contextid($us) {
        return self::get_context($us)->id;
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable
 */
function format_grid_inplace_editable($itemtype, $itemid, $newvalue) {
    global $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        global $DB;
        $section = $DB->get_record_sql(
                'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
                array($itemid, 'grid'), MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}

/**
 * Indicates this format uses sections.
 *
 * @return bool Returns true
 */
function callback_grid_uses_sections() {
    return true;
}

/**
 * Used to display the course structure for a course where format=grid
 *
 * This is called automatically by {@link load_course()} if the current course
 * format = weeks.
 *
 * @param array $path An array of keys to the course node in the navigation
 * @param stdClass $modinfo The mod info object for the current course
 * @return bool Returns true
 */
function callback_grid_load_content(&$navigation, $course, $coursenode) {
    return $navigation->load_generic_course_sections($course, $coursenode, 'grid');
}

/**
 * The string that is used to describe a section of the course
 * e.g. Topic, Week...
 *
 * @return string
 */
function callback_grid_definition() {
    return get_string('topic', 'format_grid');
}
