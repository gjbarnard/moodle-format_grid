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
 * @package   format_grid
 * @copyright &copy; 2021-onwards G J Barnard based upon work done by Marina Glancy.
 * @author    G J Barnard - {@link http://about.me/gjbarnard} and
 *                          {@link http://moodle.org/user/profile.php?id=442195}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_grid;

use context_course;
use core\lock\lock_config;
use core\url;
use Exception;
use moodle_exception;
use stdClass;

/**
 * The format's toolbox.
 *
 * @copyright  &copy; 2021-onwards G J Barnard.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class toolbox {
    /** @var toolbox $instance Singleton instance of us. */
    protected static $instance = null;

    /** @var array $imagecontainerwidths Width constants - 128, 192, 210, 256, 320, 384, 448, 512, 576, 640, 704 and 768:... */
    private static $imagecontainerwidths = [128 => '128', 192 => '192', 210 => '210', 256 => '256', 320 => '320',
        384 => '384', 448 => '448', 512 => '512', 576 => '576', 640 => '640', 704 => '704', 768 => '768', ];
    /** @var array $imagecontainerratios Ratio constants - 3-2, 3-1, 3-3, 2-3, 1-3, 4-3 and 3-4:... */
    private static $imagecontainerratios = [
        1 => '3-2', 2 => '3-1', 3 => '3-3', 4 => '2-3', 5 => '1-3', 6 => '4-3', 7 => '3-4', ];

    /**
     * This is a lonely object.
     */
    private function __construct() {
    }

    /**
     * Gets the toolbox singleton.
     *
     * @return toolbox The toolbox instance.
     */
    public static function get_instance() {
        if (!is_object(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prevents ability to change a static variable outside of the class.
     * @return array Array of image container widths.
     */
    public static function get_image_container_widths() {
        return self::$imagecontainerwidths;
    }

    /**
     * Gets the default image container width.
     * @return int Default image container width.
     */
    public static function get_default_image_container_width() {
        return 210;
    }

    /**
     * Prevents ability to change a static variable outside of the class.
     * @return array Array of image container ratios.
     */
    public static function get_image_container_ratios() {
        return self::$imagecontainerratios;
    }

    /**
     * Gets the default image container ratio.
     * @return int Default image container ratio.
     */
    public static function get_default_image_container_ratio() {
        return 1; // Ratio of '3-2'.
    }

    /**
     * Get the displayed image URI.
     * @param stdClass $coursesectionimage Section information from its row in the 'format_grid_image' table.
     * @param int $coursecontextid Course context id.
     * @param int $sectionid Section id.
     * @param bool $displayediswebp The displayed image is in webp format.
     *
     * @return string Image URI.
     */
    public function get_displayed_image_uri($coursesectionimage, $coursecontextid, $sectionid, $displayediswebp) {
        $filename = $coursesectionimage->image;

        if ($displayediswebp) {
            $filetype = strtolower(pathinfo($filename ?? '', PATHINFO_EXTENSION));
            if (!empty($filetype)) {
                if ($filetype != 'webp') {
                    $filename .= '.webp';
                }
            } else {
                $filename .= '.webp';
            }
        }
        $image = url::make_pluginfile_url(
            $coursecontextid,
            'format_grid',
            'displayedsectionimage',
            $sectionid,
            '/' . $coursesectionimage->displayedimagestate . '/',
            $filename
        );
        return $image->out();
    }

    /**
     * Check the displayed image.
     * @param array $sectionimage Section information from its row in the 'format_grid_image' table.
     * @param stored_file $sectionfile Section file.
     * @param int $courseid The course id to which the image relates.
     * @param int $coursecontextid The course context id to which the image relates.
     * @param int $sectionid The section id to which the image relates.
     * @param stdClass $format The course format image that the image belongs to.
     * @param stdClass $fs File storage.
     * @return array|bool The updated $sectionimage data or false if not.
     */
    public function check_displayed_image($sectionimage, $courseid, $coursecontextid, $sectionid, $format, $fs) {
        $newsectionimage = false;

        if (empty($sectionimage->displayedimagestate)) {
            $lock = true;
            if (!defined('BEHAT_SITE_RUNNING')) {
                $lockfactory = lock_config::get_lock_factory('format_grid');
                $lock = $lockfactory->get_lock('sectionid' . $sectionid, 5);
            }
            if ($lock) {
                $files = $fs->get_area_files($coursecontextid, 'format_grid', 'sectionimage', $sectionimage->sectionid);
                foreach ($files as $file) {
                    if (!$file->is_directory()) {
                        try {
                            $newsectionimage = $this->setup_displayed_image($sectionimage, $file, $courseid, $sectionid, $format);
                        } catch (Exception $e) {
                            $lock->release();
                            throw $e;
                        }
                    }
                }
                if (!defined('BEHAT_SITE_RUNNING')) {
                    $lock->release();
                }
            } else {
                throw new moodle_exception(
                    'imagemanagement',
                    'format_grid',
                    '',
                    get_string('cannotgetmanagesectionimagelock', 'format_grid')
                );
            }
        }
        return $newsectionimage;
    }

    /**
     * Set up the displayed image.
     * @param array $sectionimage Section information from its row in the 'format_grid_image' table.
     * @param stored_file $sectionfile Section file.
     * @param int $courseid The course id to which the image relates.
     * @param int $sectionid The section id to which the image relates.
     * @param stdClass $format The course format image that the image belongs to.
     * @return array The updated $sectionimage data.
     */
    public function setup_displayed_image($sectionimage, $sectionfile, $courseid, $sectionid, $format) {
        global $CFG, $DB;

        // Get the settings!
        $settings = $format->get_settings();

        if (!empty($sectionfile)) {
            $fs = get_file_storage();
            $mime = $sectionfile->get_mimetype();
            $filename = $sectionfile->get_filename();

            // Core does not natively support webp.
            if ($mime == 'document/unknown') {
                $filetype = strtolower(pathinfo($filename ?? '', PATHINFO_EXTENSION));
                if ((!empty($filetype)) && ($filetype == 'webp')) {
                    $mime = 'image/webp';
                    $updatedrecord = new \stdClass();
                    $updatedrecord->id = $sectionfile->get_id();
                    $updatedrecord->mimetype = $mime;
                    $DB->update_record('files', $updatedrecord);
                }
            }

            $displayedimageinfo = $this->get_displayed_image_container_properties($settings);
            $tmproot = make_temp_directory('gridformatdisplayedimagecontainer');
            $tmpfilepath = $tmproot . '/' . $sectionfile->get_contenthash();
            $sectionfile->copy_content_to($tmpfilepath);

            $crop = ($settings['imageresizemethod'] == 1) ? false : true;

            $newmime = $mime;
            $isdisplayedwebponly = false;
            if ($mime != 'image/webp') {
                $isdisplayedwebponly = (get_config('format_grid', 'defaultdisplayedimagefiletype') == 2);
                if ($isdisplayedwebponly) { // WebP.
                    $newmime = 'image/webp';
                }
            }

            $debugdata = [
                'id' => $sectionfile->get_id(),
                'itemid' => $sectionfile->get_itemid(),
                'filename' => $filename,
                'filemime' => $sectionfile->get_mimetype(),
                'filesize' => $sectionfile->get_filesize(),
                'sectionid' => $sectionid,
            ];
            $data = self::generate_image(
                $tmpfilepath,
                $displayedimageinfo['width'],
                $displayedimageinfo['height'],
                $crop,
                $newmime,
                $debugdata
            );
            if (!empty($data)) {
                // Updated image.
                $coursecontext = context_course::instance($courseid);

                // Remove existing displayed image.
                $existingfiles = $fs->get_area_files($coursecontext->id, 'format_grid', 'displayedsectionimage', $sectionid);
                foreach ($existingfiles as $existingfile) {
                    if (!$existingfile->is_directory()) {
                        $existingfile->delete();
                    }
                }

                $created = time();
                $displayedimagefilerecord = [
                    'contextid' => $coursecontext->id,
                    'component' => 'format_grid',
                    'filearea' => 'displayedsectionimage',
                    'itemid' => $sectionid,
                    'filepath' => '/',
                    'filename' => $filename,
                    'userid' => $sectionfile->get_userid(),
                    'author' => $sectionfile->get_author(),
                    'license' => $sectionfile->get_license(),
                    'timecreated' => $created,
                    'timemodified' => $created,
                    'mimetype' => $mime,
                ];

                if ($isdisplayedwebponly) { // Displayed WebP image from non-WebP original.
                    // Displayed image is a webp image from the original, so change a few things.
                    $displayedimagefilerecord['filename'] = $displayedimagefilerecord['filename'] . '.webp';
                    $displayedimagefilerecord['mimetype'] = $newmime;
                }
                $fs->create_file_from_string($displayedimagefilerecord, $data);
                $sectionimage->displayedimagestate++; // Generated.
            } else {
                $sectionimage->displayedimagestate = -1; // Cannot generate.
            }
            unlink($tmpfilepath);

            $DB->set_field(
                'format_grid_image',
                'displayedimagestate',
                $sectionimage->displayedimagestate,
                ['sectionid' => $sectionid]
            );
            if ($sectionimage->displayedimagestate == -1) {
                throw new moodle_exception(
                    'imagemanagement',
                    'format_grid',
                    '',
                    get_string(
                        'cannotconvertuploadedimagetodisplayedimage',
                        'format_grid',
                        $CFG->wwwroot . "/course/view.php?id=" . $courseid .
                        ', SI: ' . var_export($displayedimagefilerecord, true) .
                        ', DII: ' . var_export($displayedimageinfo, true)
                    )
                );
            }
        }

        return $sectionimage;
    }

    /**
     * Gets the image container properties given the settings.
     * @param array $settings Must have integer keys for 'imagecontainerwidth' and 'imagecontainerratio'.
     * @return array with the key => value of 'height' and 'width' for the container.
     */
    public function get_displayed_image_container_properties($settings) {
        return ['height' => self::calculate_height($settings['imagecontainerwidth'], $settings['imagecontainerratio']),
            'width' => $settings['imagecontainerwidth'], ];
    }

    /**
     * Calculates height given the width and ratio.
     * @param int $width The width.
     * @param int $ratio The ratio.
     * @return int Height.
     */
    private static function calculate_height($width, $ratio) {
        $basewidth = $width;

        // Ratios 1 => '3-2', 2 => '3-1', 3 => '3-3', 4 => '2-3', 5 => '1-3', 6 => '4-3', 7 => '3-4'.
        switch ($ratio) {
            case 1: // 3-2.
            case 2: // 3-1.
            case 3: // 3-3.
            case 7: // 3-4.
                $basewidth = $width / 3;
                break;
            case 4: // 2-3.
                $basewidth = $width / 2;
                break;
            case 5: // 1-3.
                $basewidth = $width;
                break;
            case 6: // 4-3.
                $basewidth = $width / 4;
                break;
        }

        $height = $basewidth;
        switch ($ratio) {
            case 2: // 3-1.
                $height = $basewidth;
                break;
            case 1: // 3-2.
                $height = $basewidth * 2;
                break;
            case 3: // 3-3.
            case 4: // 2-3.
            case 5: // 1-3.
            case 6: // 4-3.
                $height = $basewidth * 3;
                break;
            case 7: // 3-4.
                $height = $basewidth * 4;
                break;
        }

        return round($height);
    }

    /**
     * Generates a thumbnail for the given image
     *
     * If the GD library has at least version 2 and PNG support is available, the returned data
     * is the content of a transparent PNG file containing the thumbnail. Otherwise, the function
     * returns contents of a JPEG file with black background containing the thumbnail.
     *
     * @param string $filepath the full path to the original image file
     * @param int $requestedwidth the width of the requested image.
     * @param int $requestedheight the height of the requested image.
     * @param bool $crop false = scale, true = crop.
     * @param string $mime The mime type.
     * @param array $debugdata Debug data if the image generation fails.
     *
     * @return string|bool false if a problem occurs or the image data.
     */
    private static function generate_image($filepath, $requestedwidth, $requestedheight, $crop, $mime, $debugdata) {
        if (empty($filepath) || empty($requestedwidth) || empty($requestedheight)) {
            return false;
        }

        global $CFG;
        require_once($CFG->libdir . '/gdlib.php');

        $imageinfo = getimagesize($filepath);

        if (empty($imageinfo)) {
            unlink($filepath);
            throw new moodle_exception(
                'imagemanagement',
                'format_grid',
                '',
                get_string(
                    'noimageinformation',
                    'format_grid',
                    self::debugdata_decode($debugdata)
                )
            );
        }

        $originalwidth = $imageinfo[0];
        $originalheight = $imageinfo[1];

        if (empty($originalheight)) {
            unlink($filepath);
            throw new moodle_exception(
                'imagemanagement',
                'format_grid',
                '',
                get_string(
                    'originalheightempty',
                    'format_grid',
                    self::debugdata_decode($debugdata)
                )
            );
        }
        if (empty($originalwidth)) {
            unlink($filepath);
            throw new moodle_exception(
                'imagemanagement',
                'format_grid',
                '',
                get_string(
                    'originalwidthempty',
                    'format_grid',
                    self::debugdata_decode($debugdata)
                )
            );
        }

        $original = imagecreatefromstring(file_get_contents($filepath)); // Need to alter / check for webp support.

        $imageargs = [
            1 => null, // File.
        ];
        switch ($mime) {
            case 'image/png':
                if (function_exists('imagepng')) {
                    $imagefnc = 'imagepng';
                    $imageargs[2] = 1; // Quality.
                    $imageargs[3] = PNG_NO_FILTER; // Filter.
                } else {
                    unlink($filepath);
                    throw new moodle_exception(
                        'imagemanagement',
                        'format_grid',
                        '',
                        get_string(
                            'formatnotsupported',
                            'format_grid',
                            'PNG, ' . self::debugdata_decode($debugdata)
                        )
                    );
                }
                break;
            case 'image/jpeg':
                if (function_exists('imagejpeg')) {
                    $imagefnc = 'imagejpeg';
                    $imageargs[2] = 90; // Quality.
                } else {
                    unlink($filepath);
                    throw new moodle_exception(
                        'imagemanagement',
                        'format_grid',
                        '',
                        get_string(
                            'formatnotsupported',
                            'format_grid',
                            'JPG, ' . self::debugdata_decode($debugdata)
                        )
                    );
                }
                break;
            /* Moodle does not yet natively support webp as a mime type, but have here for us on the displayed image and
               the source image. */
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    $imagefnc = 'imagewebp';
                    $imageargs[2] = 90; // Quality.
                } else {
                    unlink($filepath);
                    throw new moodle_exception(
                        'imagemanagement',
                        'format_grid',
                        '',
                        get_string(
                            'formatnotsupported',
                            'format_grid',
                            'WEBP, ' . self::debugdata_decode($debugdata)
                        )
                    );
                }
                break;
            case 'image/gif':
                if (function_exists('imagegif')) {
                    $imagefnc = 'imagegif';
                } else {
                    unlink($filepath);
                    throw new moodle_exception(
                        'imagemanagement',
                        'format_grid',
                        '',
                        get_string(
                            'formatnotsupported',
                            'format_grid',
                            'GIF, ' . self::debugdata_decode($debugdata)
                        )
                    );
                }
                break;
            default:
                unlink($filepath);
                throw new moodle_exception(
                    'imagemanagement',
                    'format_grid',
                    '',
                    get_string(
                        'mimetypenotsupported',
                        'format_grid',
                        $mime . ', ' . self::debugdata_decode($debugdata)
                    )
                );
        }

        if ($crop) {
            $ratio = $requestedwidth / $requestedheight;
            $originalratio = $originalwidth / $originalheight;
            if ($originalratio < $ratio) {
                // Change the supplied height - 'resizeToWidth'.
                $ratio = $requestedwidth / $originalwidth;
                $width = $requestedwidth;
                $height = intval($originalheight * $ratio);
                $cropheight = true;
            } else {
                // Change the supplied width - 'resizeToHeight'.
                $ratio = $requestedheight / $originalheight;
                $width = intval($originalwidth * $ratio);
                $height = $requestedheight;
                $cropheight = false;
            }

            if (function_exists('imagecreatetruecolor')) {
                $tempimage = imagecreatetruecolor($width, $height);
                if ($imagefnc === 'imagepng') {
                    imagealphablending($tempimage, false);
                    imagesavealpha($tempimage, true);
                }
            } else {
                $tempimage = imagecreate($width, $height);
            }

            // First step, resize.
            if (!imagecopyresampled($tempimage, $original, 0, 0, 0, 0, $width, $height, $originalwidth, $originalheight)) {
                imagedestroy($tempimage);
                imagedestroy($original);
                unlink($filepath);
                throw new moodle_exception(
                    'imagemanagement',
                    'format_grid',
                    '',
                    get_string(
                        'imagecopyresampledfailed',
                        'format_grid',
                        self::debugdata_decode($debugdata)
                    )
                );
            }

            // Second step, crop.
            if ($cropheight) {
                // This is 'cropCenterHeight'.
                $srcoffset = ($height / 2) - ($requestedheight / 2);
                $height = $requestedheight;
            } else {
                // This is 'cropCenterWidth'.
                $srcoffset = ($width / 2) - ($requestedwidth / 2);
                $width = $requestedwidth;
            }
            $srcoffset = intval($srcoffset);

            if (function_exists('imagecreatetruecolor')) {
                $finalimage = imagecreatetruecolor($width, $height);
                if ($imagefnc === 'imagepng') {
                    imagealphablending($finalimage, false);
                    imagesavealpha($finalimage, true);
                }
            } else {
                $finalimage = imagecreate($width, $height);
            }

            if ($cropheight) {
                // This is 'cropCenterHeight'.
                $srcx = 0;
                $srcy = $srcoffset;
            } else {
                // This is 'cropCenterWidth'.
                $srcx = $srcoffset;
                $srcy = 0;
            }

            if (!imagecopyresampled($finalimage, $tempimage, 0, 0, $srcx, $srcy, $width, $height, $width, $height)) {
                imagedestroy($finalimage);
                imagedestroy($tempimage);
                imagedestroy($original);
                unlink($filepath);
                throw new moodle_exception(
                    'imagemanagement',
                    'format_grid',
                    '',
                    get_string(
                        'imagecopyresampledfailed',
                        'format_grid',
                        self::debugdata_decode($debugdata)
                    )
                );
            }
            imagedestroy($tempimage);
        } else { // Scale.
            $ratio = min($requestedwidth / $originalwidth, $requestedheight / $originalheight);

            if ($ratio < 1) {
                $targetwidth = floor($originalwidth * $ratio);
                $targetheight = floor($originalheight * $ratio);
            } else {
                // Do not enlarge the original file if it is smaller than the requested thumbnail size.
                $targetwidth = $originalwidth;
                $targetheight = $originalheight;
            }

            if (function_exists('imagecreatetruecolor')) {
                $finalimage = imagecreatetruecolor($targetwidth, $targetheight);
                if ($imagefnc === 'imagepng') {
                    imagealphablending($finalimage, false);
                    imagesavealpha($finalimage, true);
                }
            } else {
                $finalimage = imagecreate($targetwidth, $targetheight);
            }

            if (!imagecopyresampled($finalimage, $original, 0, 0, 0, 0, $targetwidth, $targetheight, $originalwidth, $originalheight)) {
                imagedestroy($finalimage);
                imagedestroy($original);
                unlink($filepath);
                throw new moodle_exception(
                    'imagemanagement',
                    'format_grid',
                    '',
                    get_string(
                        'imagecopyresampledfailed',
                        'format_grid',
                        self::debugdata_decode($debugdata)
                    )
                );
            }
        }

        ob_start();
        $imageargs[0] = $finalimage; // GdImage.
        ksort($imageargs);
        if (!call_user_func_array($imagefnc, $imageargs)) {
            ob_end_clean();
            unlink($filepath);
            throw new moodle_exception(
                'imagemanagement',
                'format_grid',
                '',
                get_string(
                    'functionfailed',
                    'format_grid',
                    $imagefnc . ', ' . self::debugdata_decode($debugdata)
                )
            );
        }
        $data = ob_get_clean();

        imagedestroy($original);
        imagedestroy($finalimage);

        return $data;
    }

    /**
     * Decode the debug data.
     *
     * @param array $debugdata Debug data.
     *
     * @return string Debug information.
     */
    private static function debugdata_decode($debugdata) {
        $o = 'Files table id: ' . $debugdata['id'];
        $o .= ', itemid: ' . $debugdata['itemid'];
        $o .= ', filename:  ' . $debugdata['filename'];
        $o .= ', filemime: ' . $debugdata['filemime'];
        $o .= ', filesize: ' . $debugdata['filesize'];
        $o .= ' and sectionid: ' . $debugdata['sectionid'] . '.  ';
        $o .= get_string('reporterror', 'format_grid');
        return $o;
    }

    /**
     * Update images callback.
     */
    public static function update_displayed_images_callback() {
        self::update_the_displayed_images();
    }

    /**
     * Update images.
     *
     * @param int $courseid The course id.
     *
     */
    public static function update_displayed_images($courseid) {
        self::update_the_displayed_images($courseid);
    }

    /**
     * Update images.
     *
     * @param int $courseid The course id.
     */
    private static function update_the_displayed_images($courseid = null) {
        global $DB;

        if (!empty($courseid)) {
            $coursesectionimages = $DB->get_records('format_grid_image', ['courseid' => $courseid]);
        } else {
            $coursesectionimages = $DB->get_records('format_grid_image');
        }
        if (!empty($coursesectionimages)) {
            $fs = get_file_storage();
            $lockfactory = null;
            $lock = null;
            $format = null;
            $coursecontext = null;

            if (!defined('BEHAT_SITE_RUNNING')) {
                $lockfactory = lock_config::get_lock_factory('format_grid');
            }
            $toolbox = self::get_instance();
            $courseid = -1;
            foreach ($coursesectionimages as $coursesectionimage) {
                if ($courseid != $coursesectionimage->courseid) {
                    $courseid = $coursesectionimage->courseid;
                    // Instead of course_get_format() for CLI usage.
                    $format = \core_courseformat\base::instance($courseid);
                    if (get_class($format) != 'format_grid') {
                        // Not currently in the Grid format, but was.
                        $format = null;
                        continue;
                    }
                    $coursecontext = context_course::instance($courseid);
                }
                if (!empty($format)) {
                    if (!defined('BEHAT_SITE_RUNNING')) {
                        $lock = $lockfactory->get_lock('sectionid' . $coursesectionimage->sectionid, 5);
                    }
                    if (($lock instanceof \core\lock\lock) || (defined('BEHAT_SITE_RUNNING'))) {
                        try {
                            $files = $fs->get_area_files($coursecontext->id, 'format_grid', 'sectionimage',
                                $coursesectionimage->sectionid);
                            foreach ($files as $file) {
                                if (!$file->is_directory()) {
                                        $coursesectionimage = $toolbox->setup_displayed_image(
                                            $coursesectionimage,
                                            $file,
                                            $courseid,
                                            $coursesectionimage->sectionid,
                                            $format
                                        );
                                }
                            }
                        } catch (Exception $e) {
                            if (!defined('BEHAT_SITE_RUNNING')) {
                                $lock->release();
                            }
                            throw $e;
                        }
                        if (!defined('BEHAT_SITE_RUNNING')) {
                            $lock->release();
                        }
                    } else {
                        throw new moodle_exception(
                            'imagemanagement',
                            'format_grid',
                            '',
                            get_string('cannotgetmanagesectionimagelock', 'format_grid')
                        );
                    }
                }
            }
        }
    }

    /**
     * Delete images.
     *
     * @param int $courseid The course id.
     */
    public static function delete_images($courseid) {
        global $DB;

        $fs = get_file_storage();
        $coursecontext = context_course::instance($courseid);
        // Images.
        $images = $fs->get_area_files($coursecontext->id, 'format_grid', 'sectionimage');
        foreach ($images as $image) {
            $image->delete();
        }

        // Displayed images.
        $displayedimages = $fs->get_area_files($coursecontext->id, 'format_grid', 'sectionimage');
        foreach ($displayedimages as $displayedimage) {
            $displayedimage->delete();
        }

        $DB->delete_records("format_grid_image", ['courseid' => $courseid]);
    }

    /**
     * Delete image.
     *
     * @param int $sectionid The section id.
     * @param int $courseid The course id.
     */
    public static function delete_image($sectionid, $courseid) {
        global $DB;

        $coursesectionimage = $DB->get_record('format_grid_image', ['courseid' => $courseid, 'sectionid' => $sectionid]);
        if (!empty($coursesectionimage)) {
            $fs = get_file_storage();

            $lockfactory = null;
            $lock = true;
            if (!defined('BEHAT_SITE_RUNNING')) {
                $lockfactory = lock_config::get_lock_factory('format_grid');
                $lock = $lockfactory->get_lock('sectionid' . $coursesectionimage->sectionid, 5);
            }
            if ($lock) {
                $coursecontext = context_course::instance($courseid);
                $files = $fs->get_area_files($coursecontext->id, 'format_grid', 'sectionimage', $coursesectionimage->sectionid);
                foreach ($files as $file) {
                    try {
                        $file->delete();
                    } catch (Exception $e) {
                        $lock->release();
                        throw $e;
                    }
                }
                // Remove existing displayed image.
                $displayedfiles = $fs->get_area_files($coursecontext->id, 'format_grid', 'displayedsectionimage', $sectionid);
                foreach ($displayedfiles as $displayedfile) {
                    try {
                        $displayedfile->delete();
                    } catch (Exception $e) {
                        $lock->release();
                        throw $e;
                    }
                }
                if (!defined('BEHAT_SITE_RUNNING')) {
                    $lock->release();
                }
            } else {
                throw new moodle_exception(
                    'imagemanagement',
                    'format_grid',
                    '',
                    get_string('cannotgetmanagesectionimagelock', 'format_grid')
                );
            }
            $DB->delete_records("format_grid_image", ['courseid' => $courseid, 'sectionid' => $sectionid]);
        }
    }
}
