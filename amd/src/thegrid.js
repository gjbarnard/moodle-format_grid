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
 * JS module for the grid.
 *
 * @module      format_grid/thegrid
 * @copyright   &copy; 2023-onwards G J Barnard.
 * @author      G J Barnard - gjbarnard at gmail dot com and {@link http://moodle.org/user/profile.php?id=442195}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as CourseEvents from 'core_course/events';
import jQuery from 'jquery';
import log from 'core/log';

/**
 * Whether the event listener has already been registered for this module.
 *
 * @type {boolean}
 */
let registered = false;

/**
 * If the manualCompletionToggled event has fired.
 *
 * @type {boolean}
 */
let mctFired = false;

/**
 * Function to intialise and register event listeners for this module.
 *
 * @param {array} sectionnumbers Show completion is on.
 * @param {boolean} popup Popup is used.
 * @param {boolean} showcompletion Show completion is on.
 */
export const init = (sectionnumbers, popup, showcompletion) => {
    log.debug('Grid thegrid JS init');
    if (registered) {
        log.debug('Grid thegrid JS init already registered');
        return;
    }
    if (popup) {
        log.debug('Grid thegrid sectionnumbers ' + sectionnumbers);

        // Listen for toggled manual completion states of activities.
        document.addEventListener(CourseEvents.manualCompletionToggled, () => {
            mctFired = true;
        });
        registered = true;

        // Modal.
        var currentmodalsection = null;
        var modalshown = false;

        // Grid current section.
        var currentsection = null;
        var currentsectionshown = false;
        var endsection = sectionnumbers.length - 1;

        /**
         * Change the current selected section, arrow keys only.
         * If the modal is shown then will also move the carousel.
         * @param {int} direction -1 = left and 1 = right.
         */
        var sectionchange = function (direction) {
            if (currentsection === null) {
                if (direction < 0) {
                    currentsection = endsection;
                } else {
                    currentsection = 0;
                }
            }
            if (currentsection !== null) {
                jQuery('#section-' + sectionnumbers[currentsection]).removeClass('grid-current-section');
                currentsection = currentsection + direction;
                if (currentsection < 0) {
                    currentsection = endsection;
                } else if (currentsection > endsection) {
                    currentsection = 0;
                }
                jQuery('#section-' + sectionnumbers[currentsection]).addClass('grid-current-section');
            }
        };

        var updatecurrentsection = function () {
            if (currentsectionshown) {
                jQuery('#section-' + sectionnumbers[currentsection]).removeClass('grid-current-section');
            }
            currentsection = currentmodalsection - 1;
            if (currentsectionshown) {
                jQuery('#section-' + sectionnumbers[currentsection]).addClass('grid-current-section');
            }
        };

        jQuery('#gridPopup').on('show.bs.modal', function (event) {
            modalshown = true;
            if (currentmodalsection === null) {
                var trigger = jQuery(event.relatedTarget);
                currentmodalsection = trigger.data('section');
            }

            updatecurrentsection();

            var gml = jQuery('#gridPopupLabel');
            var triggersectionname = jQuery('#gridpopupsection-' + currentmodalsection).data('sectiontitle');
            gml.text(triggersectionname);

            var modal = jQuery(this);
            modal.find('#gridpopupsection-' + currentmodalsection).addClass('active');

            jQuery('#gridPopupCarousel').on('slid.bs.carousel', function (event) {
                var item = jQuery('.gridcarousel-item.active');
                var st = item.data('sectiontitle');
                gml.text(st);
                log.debug("Carousel direction: " + event.direction);
                currentmodalsection = item.data('section');
                updatecurrentsection();
            });
        });

        jQuery('#gridPopup').on('hidden.bs.modal', function () {
            updatecurrentsection();

            if (currentmodalsection !== null) {
                currentmodalsection = null;
            }
            jQuery('.gridcarousel-item').removeClass('active');
            if (showcompletion && mctFired) {
                mctFired = false;
                window.location.reload();
            }
            modalshown = false;
        });

        jQuery(".grid-section .grid-modal").on('keydown click', function (event) {
            // Clicked within the modal
            if ((event.which == 13) || (event.which == 27) || (event.type == 'click')) {
                event.preventDefault();
                var trigger = jQuery(event.currentTarget);
                currentmodalsection = trigger.data('section');
                jQuery('#gridPopup').modal('show');
            }
        });

        jQuery(document).on('keydown', function (event) {
            if (event.which == 37) {
                // Left.
                event.preventDefault();
                currentsectionshown = true;
                sectionchange(-1);
                log.debug("Left: " + sectionnumbers[currentsection]);
                if (modalshown) {
                    jQuery('#gridPopupCarouselLeft').trigger('click');
                }
            } else if (event.which == 39) {
                // Right.
                event.preventDefault();
                currentsectionshown = true;
                sectionchange(1);
                log.debug("Right: " + sectionnumbers[currentsection]);
                if (modalshown) {
                    jQuery('#gridPopupCarouselRight').trigger('click');
                }
            } else if ((event.which == 13) || (event.which == 27)) {
                // Enter (13) and ESC keys (27).
                if ((!modalshown) && (currentsectionshown)) {
                    if (currentmodalsection === null) {
                        currentmodalsection = sectionnumbers[currentsection];
                    }
                    jQuery('#gridPopup').modal('show');
                }
            }
        });
    }
};
