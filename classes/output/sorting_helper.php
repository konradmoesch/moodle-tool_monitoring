<?php
// This file is part of the tool_monitoring plugin for Moodle - https://moodle.org/
//
// tool_monitoring is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// tool_monitoring is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with tool_monitoring.  If not, see <https://www.gnu.org/licenses/>.

namespace tool_monitoring\output;
/**
 * Helper class to support sorting of metrics. Allows parsing of a sort string and various operations
 *
 * @package    tool_monitoring
 * @copyright  2025 MootDACH DevCamp
 *             Daniel Fainberg <d.fainberg@tu-berlin.de>
 *             Martin Gauk <martin.gauk@tu-berlin.de>
 *             Sebastian Rupp <sr@artcodix.com>
 *             Malte Schmitz <mal.schmitz@uni-luebeck.de>
 *             Melanie Treitinger <melanie.treitinger@ruhr-uni-bochum.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sorting_helper {

    const ASC = 'asc';
    const DESC = 'desc';

    /*
    * parses a sort string (e.g. 'component_desc,name_asc') to a multidimensional array (first element: the detail to sort by, second element: sorting direction)
    * @param string the sort string to parse
    * @return array deserialized sorting details
    */
    public static function parse_sort_string($str): array {
        if (empty($str)) {
            return [];
        }
        return array_map(fn($s) => explode('_', $s), explode(',', $str));
    }

    /*
    * serializes a sort array to a sort string (e.g. 'component_desc,name_asc')
    * @param array the sort array to serialize
    * @return string serialized sort string
    */
    public static function tostring($arr): string {
        return implode(',', array_map(fn($a) => implode('_', $a), $arr));
    }

    private static function find_index($arr, $key): false|int {
        foreach($arr as $i => $v) {
            if ($v[0] === $key) {
                return $i;
            }
        }
        return false;
    }

    /*
    * adds a sorting (e.g. 'name') to a sort array (first element: the detail to sort by, second element: sorting direction)
    * if the sorting to add already exists, the direction gets inverted. if it does not exist yet, it gets added to the array.
    * The sorting to add is always added to the beginning of the array (primary sorting)
    * @param array $arr the sort array to add the sorting to
    * @param string $key the detail to sort by
    * @return array modified sort array
    */
    public static function add_sort($arr, $key) {
        $i = self::find_index($arr, $key);
        if ($i === 0) {
            if ($arr[0][1] === self::ASC) {
                $arr[0][1] = self::DESC;
            } else {
                $arr[0][1] = self::ASC;
            }
            return $arr;
        } else {
            if ($i !== false) {
                array_splice($arr, $i, 1);
            }
            return [[$key, self::ASC], ...$arr];
        }
    }
}
