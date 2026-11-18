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

/**
 * Definition of the {@see sorting_helper_test} class.
 *
 * @package    tool_monitoring
 * @copyright  2025 MootDACH DevCamp
 *             Daniel Fainberg <d.fainberg@tu-berlin.de>
 *             Martin Gauk <martin.gauk@tu-berlin.de>
 *             Sebastian Rupp <sr@artcodix.com>
 *             Malte Schmitz <mal.schmitz@uni-luebeck.de>
 *             Melanie Treitinger <melanie.treitinger@ruhr-uni-bochum.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * {@noinspection PhpIllegalPsrClassPathInspection}
 */

namespace tool_monitoring\output;

use advanced_testcase;
use core\exception\moodle_exception;
use core\output\renderer_base;
use moodle_url;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for the {@see sorting_helper} class.
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
#[CoversClass(sorting_helper::class)]
final class sorting_helper_test extends advanced_testcase {
    /**
     * Tests the {@see sorting_helper::parse_sort_string} method.
     */
    public function test_parse_sort_string(): void {
        $sort = 'component_desc,name_asc';
        $arr = sorting_helper::parse_sort_string($sort);
        $arr_expected = array(
            0 => array("component", "desc"),
            1 => array("name", "asc"),
        );
        self::assertEquals($arr_expected, $arr);

        $sort_empty = '';
        $arr_empty = sorting_helper::parse_sort_string($sort_empty);
        self::assertEquals([], $arr_empty);
    }

    /**
     * Tests the {@see sorting_helper::tostring} method.
     */
    public function test_tostring(): void {
        $arr = array(
                0 => array("component", "desc"),
                1 => array("name", "asc"),
        );
        $sort = sorting_helper::tostring($arr);
        $sort_expected = 'component_desc,name_asc';
        self::assertEquals($sort_expected, $sort);

        $arr_empty = [];
        $sort_empty = sorting_helper::tostring($arr_empty);
        $sort_empty_expected = '';
        self::assertEquals($sort_empty_expected, $sort_empty);
    }

    /**
     * Tests a round-trip of deserialization and serialization.
     */
    public function test_roundtrip(): void {
        $sort = 'component_desc,name_asc';
        $arr = sorting_helper::parse_sort_string($sort);
        $sort_new = sorting_helper::tostring($arr);
        self::assertEquals($sort_new, $sort);
    }

    /**
     * Tests adding a sorting using the {@see sorting_helper::add_sort} method.
     */
    public function test_add_sort(): void {
        $sort = 'name_desc,component_asc';
        $to_add = 'name';
        $expected = 'name_asc,component_asc';
        $arr = sorting_helper::parse_sort_string($sort);
        $arr = sorting_helper::add_sort($arr, $to_add);
        $sort = sorting_helper::tostring($arr);
        self::assertEquals($expected, $sort);

        $sort = 'name_desc,component_asc';
        $to_add = 'component';
        $expected = 'component_asc,name_desc';
        $arr = sorting_helper::parse_sort_string($sort);
        $arr = sorting_helper::add_sort($arr, $to_add);
        $sort = sorting_helper::tostring($arr);
        self::assertEquals($expected, $sort);

        $sort = 'name_desc,component_asc';
        $to_add = 'name';
        $expected = 'name_asc,component_asc';
        $arr = sorting_helper::parse_sort_string($sort);
        $arr = sorting_helper::add_sort($arr, $to_add);
        $sort = sorting_helper::tostring($arr);
        self::assertEquals($expected, $sort);

        $sort = 'name_desc,component_desc';
        $to_add = 'component';
        $expected = 'component_asc,name_desc';
        $arr = sorting_helper::parse_sort_string($sort);
        $arr = sorting_helper::add_sort($arr, $to_add);
        $sort = sorting_helper::tostring($arr);
        self::assertEquals($expected, $sort);

        $sort = '';
        $to_add = 'component';
        $expected = 'component_asc';
        $arr = sorting_helper::parse_sort_string($sort);
        $arr = sorting_helper::add_sort($arr, $to_add);
        $sort = sorting_helper::tostring($arr);
        self::assertEquals($expected, $sort);
    }
}
