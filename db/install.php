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
 * Post-install code for the submission_onlinetext module.
 *
 * @package assignsubmission_onlinetext
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require($CFG->dirroot . '/mod/simplecertificate/locallib.php');


/**
 * Code run after the assignsubmission_onlinetext module database tables have been created.
 * Moves the plugin to the top of the list (of 3)
 * @return bool
 */
function xmldb_simplecertificate_install() {
    return update_textmark_plugins();
}

function update_textmark_plugins() {
    global $CFG;
    $plugins = core_component::get_plugin_list('simplecertificatetextmark');
    // $plugins = core_plugin_manager::instance()->get_installed_plugins('simplecertificatetextmark');
    if (!$plugins) {
        return array();
    }
    $installed = array();
    foreach ($plugins as $plugin => $path) {
        require($path.'/locallib.php');
        $pluginclass = 'simplecertificate_textmark_' . $plugin;
        // $a = new stdClass();
        //(simplecertificate)
        $p = new $pluginclass(new simplecertificate(new stdClass()));
        $pnames = implode(",", $p->get_names());
        set_config('names', $pnames, 'simplecertificatetextmark_' . $plugin);
    }

    return true;
}
