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
 * WinCache cache store main library.
 *
 * @package    cachestore_wincache
 * @category   cache
 * @copyright  2013 Ryan Panning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class cachestore_wincache extends cache_store implements cache_is_key_aware {

    /**
     * The name of this store instance.
     * @var string
     */
    protected $name;

    /**
     * The definition used when this instance was initialised.
     * @var cache_definition
     */
    protected $definition;

    /**
     * Static method to check that the WinCache stores requirements have been met.
     *
     * It checks that the WinCache extension has been loaded and that user cache has been enabled.
     *
     * @return bool True if the stores software/hardware requirements have been met and it can be used. False otherwise.
     */
    public static function are_requirements_met() {
        return extension_loaded('wincache') && version_compare(phpversion('wincache'), '1.1.0', '>=') && ini_get('wincache.ucenabled');
    }

    /**
     * Returns the supported modes as a binary flag.
     *
     * @param array $configuration The configuration of a store to consider specifically.
     * @return int The supported modes.
     */
    public static function get_supported_modes(array $configuration = array()) {
        return self::MODE_APPLICATION;
    }

    /**
     * Returns the supported features as a binary flag.
     *
     * @param array $configuration The configuration of a store to consider specifically.
     * @return int The supported features.
     */
    public static function get_supported_features(array $configuration = array()) {
        return self::SUPPORTS_DATA_GUARANTEE + self::SUPPORTS_NATIVE_TTL;
    }

    /**
     * Static method to check if a store is usable with the given mode.
     *
     * @param int $mode One of cache_store::MODE_*
     * @return bool True if the mode is supported.
     */
    public static function is_supported_mode($mode) {
        return !($mode & self::get_supported_features());
    }

    /**
     * Used to control the ability to add an instance of this store through the admin interfaces.
     *
     * @return bool True if the user can add an instance, false otherwise.
     */
    public static function can_add_instance() {
        // This method doesn't exist in the API at the time of writing this plugin.
        if (method_exists('cache_helper', 'count_store_instances')) {
            $count = cache_helper::count_store_instances('wincache');
        } else {
            $factory = cache_factory::instance();
            $config = $factory->create_config_instance();
            $count = 0;
            foreach ($config->get_all_stores() as $store) {
                if ($store['plugin'] === 'wincache') {
                    $count ++;
                }
            }
        }
        return $count === 0; // Can only add one instance.
    }

    /**
     * Constructs an instance of the cache store.
     *
     * This method should not create connections or perform and processing, it should be used
     *
     * @param string $name The name of the cache store
     * @param array $configuration The configuration for this store instance.
     */
    public function __construct($name, array $configuration = array()) {
        $this->name = $name;
    }

    /**
     * Returns the name of this store instance.
     * @return string
     */
    public function my_name() {
        return $this->name;
    }

    /**
     * Initialises a new instance of the cache store given the definition the instance is to be used for.
     *
     * This function should prepare any given connections etc.
     *
     * @param cache_definition $definition
     * @return bool
     */
    public function initialise(cache_definition $definition) {
        $this->definition = $definition;
        return true;
    }

    /**
     * Returns true if this cache store instance has been initialised.
     * @return bool
     */
    public function is_initialised() {
        return (bool)$this->definition; // Objects cast as true.
    }

    /**
     * Returns true if this cache store instance is ready to use.
     * @return bool
     */
    public function is_ready() {
        // No setup is actually required, providing WinCache is installed and enabled.
        return self::are_requirements_met();
    }

    /**
     * Test is a cache has a key.
     *
     * @param string $key
     * @return bool True if the cache has the requested key, false otherwise.
     */
    public function has($key) {
        return wincache_ucache_exists($key);
    }

    /**
     * Test if a cache has at least one of the given keys.
     *
     * @param array $keys
     * @return bool True if the cache has at least one of the given keys
     */
    public function has_any(array $keys) {
        foreach ($keys as $key) {
            if ($this->has($key)) {
                return true; // Only need to find one key.
            }
        }
        return false; // If we get here then no keys were found.
    }

    /**
     * Test is a cache has all of the given keys.
     *
     * @param array $keys
     * @return bool True if the cache has all of the given keys, false otherwise.
     */
    public function has_all(array $keys) {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                return false; // There cannot be a missing key..
            }
        }
        return true; // If we get here then ALL keys were found.
    }

    /**
     * Retrieves an item from the cache store given its key.
     *
     * @param string $key The key to retrieve
     * @return mixed The data that was associated with the key, or false if the key did not exist.
     */
    public function get($key) {
        $outcome = wincache_ucache_get($key, $success);
        return $success ? $outcome : false;
    }

    /**
     * Retrieves several items from the cache store in a single transaction.
     *
     * If not all of the items are available in the cache then the data value for those that are missing will be set to false.
     *
     * @param array $keys The array of keys to retrieve
     * @return array An array of items from the cache. There will be an item for each key, those that were not in the store will
     *      be set to false.
     */
    public function get_many($keys) {
        $outcomes = array_fill_keys($keys, false); // Default all outcomes to false (ex: not successful).
        $results  = wincache_ucache_get($keys);    // Attempt to retrieve key values from WinCache.
        return array_merge($outcomes, $results);   // Overwrite default overcomes with successful results.
    }

    /**
     * Sets an item in the cache given its key and data value.
     *
     * @param string $key The key to use.
     * @param mixed $data The data to set.
     * @return bool True if the operation was a success false otherwise.
     */
    public function set($key, $data) {
        return wincache_ucache_set($key, $data, (int)$this->definition->get_ttl());
    }

    /**
     * Sets many items in the cache in a single transaction.
     *
     * @param array $keyvaluearray An array of key value pairs. Each item in the array will be an associative array with two
     *      keys, 'key' and 'value'.
     * @return int The number of items successfully set. It is up to the developer to check this matches the number of items
     *      sent ... if they care that is.
     */
    public function set_many(array $keyvaluearray) {
        $faileditems = wincache_ucache_set($keyvaluearray, null, (int)$this->definition->get_ttl());
        if ($faileditems === false) { // If false, ALL items failed..
            $faileditems = $keyvaluearray;
        }
        return count($keyvaluearray) - count($faileditems);
    }

    /**
     * Deletes an item from the cache store.
     *
     * @param string $key The key to delete.
     * @return bool Returns true if the operation was a success, false otherwise.
     */
    public function delete($key) {
        return wincache_ucache_delete($key);
    }

    /**
     * Deletes several keys from the cache in a single action.
     *
     * @param array $keys The keys to delete
     * @return int The number of items successfully deleted.
     */
    public function delete_many(array $keys) {
        $deletedkeys = wincache_ucache_delete($keys);
        if ($deletedkeys === false) { // If false, all keys were NOT deleted..
            $deletedkeys = array();
        }
        return count($deletedkeys);
    }

    /**
     * Purges the cache deleting all items within it.
     *
     * @return boolean True on success. False otherwise.
     */
    public function purge() {
        return wincache_ucache_clear();
    }

    /**
     * Performs any necessary clean up when the store instance is being deleted.
     */
    public function cleanup() {
        parent::cleanup(); // Trigger debug message.
        $this->purge();
    }

    /**
     * Generates an instance of the cache store that can be used for testing.
     *
     * Returns an instance of the cache store, or false if one cannot be created.
     *
     * @param cache_definition $definition
     * @return cache_store
     */
    public static function initialise_test_instance(cache_definition $definition) {
        $cache = new self('WinCache Test');
        $cache->initialise($definition);
        return $cache;
    }
}
