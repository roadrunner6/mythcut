<?php

/* ===============================================================
 * MythCut
 * (c) 2011,2012 Mario Weilguni
 * roadrunner6@gmx.at
 * Licenced under GNU General Public Licence Version 3 or higher
 * See misc/LICENSE for details
 *===============================================================*/

// You may change everything here, but most defaults are quite good

define("CONFIG_XML", '/etc/mythtv/config.xml');
define("TN_WIDTH", 200);
define("TN_HEIGHT", 150);
define("CACHE_DIR", "/tmp/mythcut-cache");
define("MPLAYER", "mplayer"); // path to mplayer, normal UNIX $PATH is used
define("TN_FONT", dirname(__FILE__) .'/../misc/Tiresias Infofont.ttf');

// Hits/Page in the movie list
define("HPP", 10);
