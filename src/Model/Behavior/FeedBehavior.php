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
use FeedIo\Feed\Item;
use FeedIo\FeedInterface;

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
                        $feed = Factory::create()->getFeedIo()
                            ->read($row[$options['field']])
                            ->getFeed();
                        $items = $this->getItems($feed, (int)$options['count']);

                        if (!empty($items)) {
                            $res = new \stdClass;
                            $res->title = $feed->getTitle();
                            $res->description = $feed->getDescription();
                            $res->link = $row[$options['field']];
                            $res->items = $items;

                            $row[$options['property']] = $res;
                        }
                    }

                    return $row;
                });
            }, Query::APPEND);
    }

    /**
     * Gets the items of the given feed.
     * @param FeedInterface $feed The feed to scan.
     * @param int $count The items to retrieve.
     * @return array The items of the given feed.
     */
    protected function getItems(FeedInterface $feed, int $count): array
    {
        $items = [];

        foreach ($feed as $entry) {
            if ($count-- > 0) {
                $items[] = [
                    'title' => $entry->getTitle(),
                    'description' => $entry->getDescription(),
                    'link' => $entry->getLink(),
                    'medias' => $this->getMediaUrls($entry),
                    'date' => $entry->getLastModified()
                ];
            } else {
                break;
            }
        }

        return $items;
    }

    /**
     * Gets the URLs of the medias from given item.
     * @param Item $item Feed node
     * @return array An array containing the URLs of the medias from given item.
     */
    protected function getMediaUrls(Item $item): array
    {
        $medias = [];

        foreach ($item->getMedias() as $media) {
            $medias[] = $media->getUrl();
        }

        return $medias;
    }
}
