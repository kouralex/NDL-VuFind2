<?php
/**
 * GetContentFeed AJAX handler
 *
 * PHP version 5
 *
 * Copyright (C) The National Library of Finland 2018.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  AJAX
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\AjaxHandler;

use Finna\Feed\Feed as FeedService;
use VuFind\Cache\Manager as CacheManager;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\Record\Loader;
use VuFind\Session\Settings as SessionSettings;
use VuFindHttp\HttpService;
use Zend\Config\Config;
use Zend\Mvc\Controller\Plugin\Params;
use Zend\Mvc\Controller\Plugin\Url;
use Zend\View\Renderer\RendererInterface;

/**
 * GetContentFeed AJAX handler
 *
 * @category VuFind
 * @package  AJAX
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetContentFeed extends \VuFind\AjaxHandler\AbstractBase
{
    use FeedTrait;

    /**
     * Organisation page RSS configuration
     *
     * @var Config
     */
    protected $config;

    /**
     * Feed service
     *
     * @var FeedService
     */
    protected $feedService;

    /**
     * View renderer
     *
     * @var RendererInterface
     */
    protected $renderer;

    /**
     * URL helper
     *
     * @var Url
     */
    protected $url;

    /**
     * Constructor
     *
     * @param SessionSettings   $ss       Session settings
     * @param Config            $config   Organisation page RSS configuration
     * @param FeedService       $fs       Feed service
     * @param RendererInterface $renderer View renderer
     * @param Url               $url      URL helper
     */
    public function __construct(SessionSettings $ss, Config $config,
        FeedService $fs, RendererInterface $renderer, Url $url
    ) {
        $this->sessionSettings = $ss;
        $this->config = $config;
        $this->feedService = $fs;
        $this->renderer = $renderer;
        $this->url = $url;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, internal status code, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $this->disableSessionWrites();  // avoid session write timing bug

        $id = $params->fromPost('id', $params->fromQuery('id'));
        if (!$id) {
            return $this->formatResponse('', self::STATUS_ERROR, 400);
        }

        $element = urldecode($params->fromQuery('element'));
        if (!$element) {
            $element = 0;
        }
        $feedUrl = $params->fromQuery('feedUrl');
        try {
            $serverHelper = $this->renderer->plugin('serverurl');
            $homeUrl = $serverHelper($this->url->fromRoute('home'));

            if ($feedUrl) {
                $config = $this->getOrganisationFeedConfig($id, $feedUrl);
                $feed = $this->feedService->readFeedFromUrl(
                    $id, $feedUrl, $config, $this->url, $homeUrl
                );
            } else {
                $feed = $this->feedService->readFeed($id, $this->url, $homeUrl);
            }
        } catch (\Exception $e) {
            return $this->formatResponse($e->getMessage(), self::STATUS_ERROR, 400);
        }

        if (!$feed) {
            return $this->formatResponse(
                'Error reading feed', self::STATUS_ERROR, 400
            );
        }

        $channel = $feed['channel'];
        $items = $feed['items'];
        $config = $feed['config'];
        $modal = $feed['modal'];
        $contentPage = $feed['contentPage'] && !$modal;

        $result = [
            'channel' => [
                'title' => $channel->getTitle(),
                'link' => $channel->getLink()
            ]
        ];
        $numeric = is_numeric($element);
        if ($numeric) {
            $element = (int)$element;
            if (isset($items[$element])) {
                $result['item'] = $items[$element];
            }
        } else {
            foreach ($items as $item) {
                if ($item['id'] === $element) {
                    $result['item'] = $item;
                    break;
                }
            }
        }

        if ($contentPage && !empty($items)) {
            $result['navigation'] = $this->renderer->partial(
                'feedcontent/navigation',
                [
                   'items' => $items, 'element' => $element, 'numeric' => $numeric,
                   'feedUrl' => $feedUrl
                ]
            );
        }

        return $this->formatResponse($result, self::STATUS_OK);
    }

    /**
     * Return configuration settings for organisation page
     * RSS-feed sections (news, events).
     *
     * @param string $id  Section
     * @param string $url Feed URL
     *
     * @return array settings
     */
    protected function getOrganisationFeedConfig($id, $url)
    {
        $config = $this->config;
        $feedConfig = ['url' => $url];

        if (isset($config[$id])) {
            $feedConfig['result'] = $config[$id]->toArray();
        } else {
            $feedConfig['result'] = ['items' => 5];
        }
        $feedConfig['result']['type'] = 'list';
        $feedConfig['result']['active'] = 1;
        return $feedConfig;
    }
}
