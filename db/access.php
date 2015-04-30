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
 * Defines capabilities for the plugin.
 *
 * @package    block
 * @subpackage userprofile_update
 * @author     David Bogner <info@edulabs.org>
 * @copyright  2014 www.edulabs.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'block/userprofile_update:addinstance' => array(
    	'riskbitmask'  =>  RISK_PERSONAL, RISK_DATALOSS, RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
        	'manager' => CAP_ALLOW
        ),
    	'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
    'block/userprofile_update:updateuserprofile' => array(
      'riskbitmask'  =>  RISK_PERSONAL, RISK_DATALOSS, RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_COURSE,
      'archetypes' => array()
    ),
	'block/userprofile_update:createuser' => array(
			'riskbitmask'  =>  RISK_PERSONAL, RISK_DATALOSS, RISK_SPAM,
			'captype' => 'write',
			'contextlevel' => CONTEXT_COURSE,
			'archetypes' => array()
	),
);
