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

use core\exception\moodle_exception;
use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use moodle_url;
use tool_monitoring\local\managed_metric_tag;
use tool_monitoring\metric_tag;
use tool_monitoring\registered_metric;

/**
 * Provides information about all available metrics and links to their configuration pages.
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
final class overview implements renderable, templatable {
    /**
     * Constructor without additional logic.
     *
     * @param iterable<string, registered_metric> $metrics Metrics for which to render the overview, indexed by qualified name.
     * @param array<string, metric_tag> $tags Tags used to filter the metrics by, indexed by normalized tag name.
     *
     * @phpcs:disable Squiz.WhiteSpace.ScopeClosingBrace
     */
    public function __construct(
        /** @var iterable<string, registered_metric> Metrics for which to render the overview, indexed by qualified name. */
        private iterable $metrics,
        /** @var array<string, metric_tag> Tags used to filter the metrics by, indexed by normalized tag name. */
        private array $tags,
        /** @var string Properties to sort the list by. */
        private string $sort,
        /** @var moodle_url The URL of the currently displayed overview page. */
        private moodle_url $currenturl
    ) {}

    /**
     * {@inheritDoc}
     *
     * @param renderer_base $output
     * @return array<string, mixed> Template context.
     * @throws moodle_exception
     */
    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $tagsenabled = managed_metric_tag::is_enabled();
        $lines = [];
        $sortarray = sorting_helper::parse_sort_string($this->sort);

        if (!empty($sortarray)) {
            foreach ($sortarray as $sort) {
                $sortcategory = $sort[0];
                $sortdirection = $sort[1];
                switch ($sortcategory) {
                    case "component":
                        usort($this->metrics, "sort_by_component");
                        break;
                    case "name":
                        usort($this->metrics, "sort_by_name");
                        break;
                }
                if ($sortdirection == sorting_helper::DESC) {
                    $this->metrics = array_reverse($this->metrics);
                }
            }
        }

        foreach ($this->metrics as $qualifiedname => $metric) {
            $configurl = new moodle_url('/admin/tool/monitoring/configure.php', ['metric' => $qualifiedname]);
            $line = [
                'component' => $metric->component,
                'name' => $metric->name,
                'qualified_name' => $qualifiedname,
                'type' => $metric->type->value,
                'description' => $metric->description->out(),
                'enabled' => $metric->enabled,
                'config_url' => $configurl->out(escaped: false),
            ];
            if ($tagsenabled) {
                $line['tags'] = array_map(
                    fn (metric_tag $tag): array => [
                        'id' => $tag->id,
                        'name' => $tag->rawname,
                        'view_url' => $this->add_tag_url($tag)->out(escaped: false),
                    ],
                    array_values($metric->tags),
                );
            }
            $lines[] = $line;
        }
        $hastags = !empty($this->tags) && $tagsenabled;
        $data = [
            'metrics' => $lines,
            'is_tagging_enabled' => $tagsenabled,
            'manage_tags_url' => managed_metric_tag::get_manage_url(),
            'has_tags' => $hastags,
            'current_url' => $this->currenturl->out(escaped: false),
        ];
        if ($hastags) {
            $allmetricsurl = new moodle_url('/admin/tool/monitoring/');
            $data['all_metrics_url'] = $allmetricsurl->out(escaped: false);
            $data['tags'] = [];
            foreach ($this->tags as $tag) {
                $data['tags'][] = [
                    'name' => $tag->rawname,
                    'remove_url' => $this->remove_tag_url($tag)->out(escaped: false),
                    'edit_url' => $tag->editurl->out(escaped: false),
                ];
            }
        }
        $data['has_sort'] = !empty($sortarray);
        foreach ($sortarray as $sort) {
            $sortcategory = $sort[0];
            $sortdirection = $sort[1];
            $data['sort'][] = [
                    'category' => $sortcategory,
                    'direction' => $sortdirection,
            ];
            switch ($sortcategory) {
                case "component":
                    switch ($sortdirection) {
                        case sorting_helper::DESC:
                            $data['sort_component_desc'] = true;
                            break;
                        case sorting_helper::ASC:
                        default:
                            $data['sort_component_asc'] = true;
                            break;
                    }
                    break;
                case "name":
                    switch ($sortdirection) {
                        case sorting_helper::DESC:
                            $data['sort_name_desc'] = true;
                            break;
                        case sorting_helper::ASC:
                        default:
                            $data['sort_name_asc'] = true;
                            break;
                    }
                    break;
            }
        }

        $defaultsortingurl = $this->currenturl;
        $defaultsortingurl->remove_params('sort');
        $data['url_default_sorting'] = $defaultsortingurl->out(escaped: false);
        $sortcomponentarr = sorting_helper::add_sort($sortarray, 'component');
        $data['url_sort_component'] = $this->sort_url(sorting_helper::tostring($sortcomponentarr))->out(escaped: false);
        $sortnamearr = sorting_helper::add_sort($sortarray, 'name');
        $data['url_sort_name'] = $this->sort_url(sorting_helper::tostring($sortnamearr))->out(escaped: false);
        return $data;
    }

    /**
     * Generates a URL to the current overview with the current filter and default sorting.
     *
     * @return moodle_url URL with the `tag` query parameter listing the current {@see self::$tags}.
     * @throws moodle_exception
     */
    private function current_tag_url(): moodle_url {
        $tags = $this->tags;
        if (empty($tags)) {
            return new moodle_url('/admin/tool/monitoring/');
        } else {
            return new moodle_url('/admin/tool/monitoring/', ['tag' => implode(',', array_keys($tags))]);
        }
    }

    /**
     * Generates a URL to the current overview with an additional tag in the filter.
     *
     * @param metric_tag $tag New tag.
     * @return moodle_url URL with the `tag` query parameter listing the current {@see self::$tags} and the new `$tag`.
     * @throws moodle_exception
     */
    private function add_tag_url(metric_tag $tag): moodle_url {
        $tags = $this->tags;
        if (!array_key_exists($tag->name, $tags)) {
            $tags[$tag->name] = $tag;
        }
        return new moodle_url($this->currenturl, ['tag' => implode(',', array_keys($tags))]);
    }

    /**
     * Generates a URL to the current overview with one tag removed from the filter.
     *
     * @param metric_tag $tag Tag to remove.
     * @return moodle_url URL with the `tag` query parameter listing the current {@see self::$tags} minus the `$tag`.
     * @throws moodle_exception
     */
    private function remove_tag_url(metric_tag $tag): moodle_url {
        $tags = $this->tags;
        unset($tags[$tag->name]);
        $tagparam = "";
        if (!empty($tags)) {
            $tagparam = implode(',', array_keys($tags));
        }
        return new moodle_url($this->currenturl, ['tag' => $tagparam]);
    }

    /**
     * Generates a URL to the current overview with a new sorting by the given property.
     *
     * @param string $sortproperty Property to sort by.
     * @return moodle_url URL with current the `tag` query parameter and the modified `sort` parameter.
     */
    private function sort_url(string $sortproperty): moodle_url {
        $newurl = $this->currenturl;
        $newurl->param('sort', $sortproperty);
        return $newurl;
    }
}
