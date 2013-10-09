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
 * Materials secure download.
 *
 * @package    local_materials
 * @copyright  2013 IOC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');

require_login();

$path = required_param('path', PARAM_PATH);

$path = trim($path, '/');
$parts = explode('/', $path);
while (count($parts) > 0) {
    if ($records = $DB->get_records('local_materials', array('path' => implode('/', $parts)))) {
        foreach ($records as $record) {
            $context = context_course::instance($record->courseid);
            if (has_capability('moodle/course:viewparticipants', $context)) {
                $time = sprintf("%08x", time());
                $token = md5($CFG->local_materials_secret_token.'/'.$path.$time);
                $url = $CFG->local_materials_secret_url.'/'.$token.'/'.$time.'/'.$path;
                @header($_SERVER['SERVER_PROTOCOL'] . ' 302 Found');
                @header('Location: ' . $url);
                exit;
            }
        }
    }
    array_pop($parts);
}
print_error('materialnotaccesible', 'local_materials');

