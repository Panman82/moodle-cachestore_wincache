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
 * WinCache store version information.
 *
 * @package    cachestore_wincache
 * @category   cache
 * @copyright  2013 Ryan Panning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2013041800;
$plugin->requires  = 2012120300; // Moodle 2.4.0+
$plugin->cron      = 0; // No cron jobs to run here
$plugin->component = 'cachestore_wincache';
$plugin->maturity  = MATURITY_RC;
$plugin->release   = '1.0 (Build: 2013041800)';
$plugin->dependencies = array(); // No dependencies
