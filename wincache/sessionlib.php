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
 * This file/class is not used by the caching framework directly, but rather for
 * Moodle to use as a session handler class. See the README for more details.
 *
 * @package    cachestore_wincache
 * @category   cache
 * @copyright  2013 Ryan Panning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class wincache_session extends session_stub {

    /**
     * Make sure WinCache PHP extension is loaded before calling session_stub::__construct().
     */
    public function __construct() {
        if (!extension_loaded('wincache')) {
            // Throw generic exception as session_get_instance() does not care in catch()..
            throw new Exception('WinCache PHP extension not loaded!');
        }
        parent::__construct();
    }

    /**
     * Set WinCache as the session handler and define the Moodle session file store location.
     */
    protected function init_session_storage() {
        global $CFG;

        // WinCache has a built-in session handler.
        ini_set('session.save_handler', 'wincache');

        // Some distros disable GC by setting probability to 0.
        // Overriding the PHP default of 1.
        // (gc_probability is divided by gc_divisor, which defaults to 1000).
        if (ini_get('session.gc_probability') == 0) {
            ini_set('session.gc_probability', 1);
        }

        ini_set('session.gc_maxlifetime', $CFG->sessiontimeout);

        // Make sure sessions dir exists and is writable, throws exception if not.
        make_upload_directory('sessions');

        // Need to disable debugging since disk_free_space().
        // will fail on very large partitions (see MDL-19222).
        $freespace = @disk_free_space($CFG->dataroot.'/sessions');
        if (!($freespace > 2048) and $freespace !== false) {
            print_error('sessiondiskfull', 'error');
        }
        ini_set('session.save_path', $CFG->dataroot .'/sessions');
    }

    /**
     * Check for existing session with id $sid
     * @param string $sid
     * @return bool True if session found
     */
    public function session_exists($sid) {
        // Get WinCache current session cache info.
        $wincache = wincache_scache_info();

        // Could be false if failed to retrieve info.
        if (!$wincache) {
            return false;
        }

        // Look for an existing session with the $sid.
        foreach ($wincache['scache_entries'] as $entry) {
            if ($entry['key_name'] == $sid) {
                return true;
            }
        }

        // Session id was NOT found.
        return false;
    }
}
