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
 * The userprofile_updated event.
 *
 * @package    block_userprofile_update
 * @copyright  2015 edulabs.org - David Bogner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_userprofile_update\event;
defined('MOODLE_INTERNAL') || die();
/**
 * The userprofile_updated event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      The userprofile was updated.
 * }
 *
 * @since     Moodle 2.7
 * @copyright 2015 David Bogner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class user_updated extends \core\event\user_updated {
    // No need to override any method.
}