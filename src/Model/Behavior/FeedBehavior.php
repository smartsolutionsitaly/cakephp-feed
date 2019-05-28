<?php
/**
 * cakephp-feed (https://github.com/smartsolutionsitaly/cakephp-feed)
 * Copyright (c) 2019 Smart Solutions S.r.l. (https://smartsolutions.it)
 *
 * Atom/RSS tools for CakePHP
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @category  cakephp-plugin
 * @package   cakephp-feed
 * @author    Lucio Benini <dev@smartsolutions.it>
 * @copyright 2019 Smart Solutions S.r.l. (https://smartsolutions.it)
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      https://smartsolutions.it Smart Solutions
 * @since     1.0.0
 */

namespace SmartSolutionsItaly\CakePHP\Feed\Model\Behavior;

use Cake\Collection\CollectionInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use FeedIo\Factory;

/**
 * Feed behavior.
 * @package SmartSolutionsItaly\CakePHP\Feed\Model\Behavior
 * @author Lucio Benini
 * @since 1.0.0
 */
class FeedBehavior extends Behavior
{
    /**
     * Default configuration.
     * @var array
     */
    protected $_defaultConfig = [
        'count' => 5,
        'field' => 'rss'
    ];

    /**
     * Finder for feed.
     * Adds a formatter to the query.
     * @param Query $query The query object.
     * @param array $options Query options. May contains "count", "field" and "property" elements.
     * @return Query The query object.
     */
    public function findFeed(Query $query, array $options): Query
    {
        $options = $options + [
                'count' => (int)$this->getConfig('count'),
                'field' => (string)$this->getConfig('field'),
                'property' => 'feed'
            ];

        return $query
            ->formatResults(function (CollectionInterface $results) use ($options) {
                return $results->map(function ($row) use ($options) {
                    $row[$options['property']] = [];

                    if (!empty($row[$options['field']])) {
                        $count = (int)$options['count'];
                        $items = [];
                        $feed = Factory::create()->getFeedIo()
                            ->read($this->rss)
                            ->getFeed();

                        foreach ($feed as $entry) {
                            if ($count-- > 0) {
                                $medias = [];

                                foreach ($entry->getMedias() as $media) {
                                    $medias[] = $media->getUrl();
                                }

                                $items[] = [
                                    'title' => $entry->getTitle(),
                                    'description' => $entry->getDescription(),
                                    'link' => $entry->getLink(),
                                    'medias' => $medias
                                ];
                            }
                        }

                        if (!empty($items)) {
                            $res = new \stdClass;
                            $res->title = $feed->getTitle();
                            $res->description = $feed->getDescription();
                            $res->link = $this->rss;
                            $res->items = $items;

                            $row[$options['property']] = $res;
                        }
                    }

                    return $row;
                });
            }, Query::APPEND);
    }
}
