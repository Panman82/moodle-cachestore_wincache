WinCache Store
========================================
WinCache is a PHP extension, similar to other opcode caching extensions, that provides several
caching features for Windows environments. It is a great option for Moodle when hosted on a
Windows Server with IIS. Since this extension allows for user caching in shared memory (RAM),
it is also a great fit for Moodle Universal Caching (MUC). This plugin enables the WinCache
store option in MUC, which can replace the default (and slower) File cache store.


Install and Load WinCache PHP Extension
----------------------------------------
Before using the WinCache store within MUC, the PHP WinCache extension (version 1.1.0+) must
be installed and loaded. Note: When loading the WinCache extension, by default other caching
mechanisms are enabled which should also improve Moodle performance. Visit the documentation
for more details: http://www.php.net/manual/intro.wincache.php

1. Download the proper extension for your environment:
   http://www.iis.net/downloads/microsoft/wincache-extension
2. Open/Run the downloaded .exe to extract the files to a temporary location
3. Copy php_wincache.dll file to your php/ext folder (where other PHP extensions are located)
4. Edit your php.ini file and copy the line below in the Dynamic Extensions section
   extension=php_wincache.dll
5. Restart the App Pool which PHP is running under. If you don't know, just restart IIS
6. Check the [Site Admin > Server > PHP info] page for a "wincache" section
7. The "WinCache cache" store should now appear as an option in the Caching Configuration

If you previously installed the WinCache extension already, just be sure that user caching is
not disabled. In your php.ini file, make sure there is not a "wincache.ucenabled" option set
to 0. WinCache user caching is enabled by default so either that option should not exist or it
should be set to 1.


Configure MUC to Use WinCache
----------------------------------------
Once the WinCache extension is loaded in PHP, you can configure MUC to use WinCache. First you
must create an instance of the WinCache store, think of it as a connection from Moodle to the
WinCache extension.

1. Go to the [Site Admin > Plugins > Caching > Configuration] page
2. The "WinCache cache" plugin should be ready as a cache store, click the "Add instance" link
3. Enter an appropriate name for the instance, such as "Shared memory access via WinCache"
4. Click the "Save Changes" button

Now the WinCache plugin/instance is ready to be used. You have two options to enable its' use;
configure specific definitions or enable it as a default store (most common). To enable
WinCache as the default Application mapping:

1. Go to the [Site Admin > Plugins > Caching > Configuration] page, if not already there
2. Under the "Stores used when no mapping is present" section, click the "Edit mappings" link
3. Click the Application drop-down and choose the instance you just created above
4. Click the "Save Changes" button


Enable WinCache Session Handler (optional)
----------------------------------------
In addition to MUC caching, WinCache can also handle PHP sessions as well. This is an added
bonus as doing so will will essentially cause the "Default session store for session caches"
to use WinCache as well. And by using WinCache as a PHP session handler, the session cache
will be in a separate memory allocation, allowing more room for Application caches in the user
cache that this plugin utilizes. Since Moodle handles sessions directly, the WinCache session
handler must be enabled in the Moodle config.php file:

1. Edit your Moodle config.php file
2. Towards the end of the file, but before the require_once(), add the following:
   define('SESSION_CUSTOM_CLASS', 'wincache_session');
   define('SESSION_CUSTOM_FILE', '/cache/stores/wincache/sessionlib.php');
3. Save the config.php file changes

By configuring the Moodle config.php file above, Moodle basically forces the use of WinCache
session store. PHP also has a session store configuration option. Although it is not required
for Moodle, it might be a good idea to enable WinCache session handler there as well:

1. Edit your php.ini file
2. Change the "session.save_handler" to "wincache" (no quotes)
3. Ensure the "session.save_path" is set to the sessions folder within your moodle data folder.
   Ex: C:/inetpub/moodledata/sessions
4. Save the php.ini file changes
5. Restart the App Pool which PHP is running under. If you don't know, just restart IIS

For more details, visit the documentation:
http://www.php.net/manual/wincache.sessionhandler.php


Increase wincache.ucachesize Limit
----------------------------------------
The user caching size is a memory limit that WinCache may use when caching user items. If this
limit is reached, WinCache may delete older entries to free up space. The default limit is
relatively small compared to what Moodle could eventually use/need. It would be a good idea to
adjust the wincache.ucachesize option to something larger so that Moodle may cache more items.

1. Edit your php.ini file
2. Find the [wincache] section, or create one at the end of the file
   [wincache]
   ; This section contains WinCache extension settings
3. Change/Add the "wincache.ucachesize" to something reasonable, preferably the max of 85MB.
   wincache.ucachesize = 85
4. Save the php.ini file changes
5. Restart the App Pool which PHP is running under. If you don't know, just restart IIS


Monitor WinCache Memory Usage
----------------------------------------
Separate from the MUC WinCache store, it is a good idea to monitor the cache memory usage and
adjust settings as needed. The WinCache download includes a "phpinfo()" style page to display
cache usage statistics (http://www.php.net/manual/wincache.stats.php), named "wincache.php".
Copy the file from the extracted WinCache files to an accessible Website directory
(such as wwwroot) and edit the configuration settings. Another option is to use the Moodle
WinCache Info plugin: https://moodle.org/plugins/view.php?plugin=tool_wincache As each cache
type memory usage nears 100%, adjust the relative cache size limit as needed. See the WinCache
documentation for details: http://www.php.net/manual/wincache.configuration.php
