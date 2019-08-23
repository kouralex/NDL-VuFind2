<?php
/**
 * Record driver view helper
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) The National Library of Finland 2015-2017.
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
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Finna\View\Helper\Root;

/**
 * Record driver view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Record extends \VuFind\View\Helper\Root\Record
{
    /**
     * Record loader
     *
     * @var \VuFind\Record\Loader
     */
    protected $loader;

    /**
     * Rendered URLs
     *
     * @var array
     */
    protected $renderedUrls = [];

    /**
     * Record image helper
     *
     * @var \Finna\View\Helper\Root\RecordImage
     */
    protected $recordImageHelper;

    /**
     * Constructor
     *
     * @param \Zend\Config\Config                 $config      VuFind configuration
     * @param \VuFind\Record\Loader               $loader      Record loader
     * @param \Finna\View\Helper\Root\RecordImage $recordImage Record image helper
     */
    public function __construct(
        \Zend\Config\Config $config,
        \VuFind\Record\Loader $loader,
        \Finna\View\Helper\Root\RecordImage $recordImage
    ) {
        parent::__construct($config);
        $this->loader = $loader;
        $this->recordImageHelper = $recordImage;
    }

    /**
     * Store a record driver object and return this object so that the appropriate
     * template can be rendered.
     *
     * @param \VuFind\RecordDriver\AbstractBase|string $driver Record
     * driver object or record id.
     *
     * @return Record
     */
    public function __invoke($driver)
    {
        if (is_string($driver)) {
            $driver = $this->loader->load($driver);
        }
        return parent::__invoke($driver);
    }

    /**
     * Deprecated method. Return false for legacy template code.
     *
     * @return boolean
     */
    public function bxRecommendationsEnabled()
    {
        return false;
    }

    /**
     * Is commenting allowed.
     *
     * @param object $user Current user
     *
     * @return boolean
     */
    public function commentingAllowed($user)
    {
        if (!$this->ratingAllowed()) {
            return true;
        }
        $comments = $this->driver->getComments();
        foreach ($comments as $comment) {
            if ($comment->user_id === $user->id) {
                return false;
            }
        }
        return true;
    }

    /**
     * Is commenting enabled.
     *
     * @return boolean
     */
    public function commentingEnabled()
    {
        return !isset($this->config->Social->comments)
            || ($this->config->Social->comments
                && $this->config->Social->comments !== 'disabled');
    }

    /**
     * Return record driver
     *
     * @return \VuFind\RecordDriver\AbstractBase
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Render the record as text for email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->renderTemplate('result-email.phtml');
    }

    /**
     * Get record format in the requested export format.  For legal values, see
     * the export helper's getFormatsForRecord() method.
     *
     * @param string $format Export format to display
     *
     * @return string        Exported data
     */
    public function getExportFormat($format)
    {
        $format = strtolower($format);
        return $this->renderTemplate('export-' . $format . '-format.phtml');
    }

    /**
     * Render the link of the specified type.
     *
     * @param string $type    Link type
     * @param string $lookfor String to search for at link
     * @param array  $params  Optional array of parameters for the link template
     *
     * @return string
     */
    public function getLink($type, $lookfor, $params = [])
    {
        $searchAction = !empty($this->getView()->browse)
            ? 'browse-' . $this->getView()->browse : '';
        $params = $params ?? [];
        $params = array_merge(
            $params,
            [
                'driver' => $this->driver,
                'lookfor' => $lookfor,
                'searchAction' => $searchAction
            ]
        );
        $result = $this->renderTemplate(
            'link-' . $type . '.phtml', $params
        );

        $result .= $this->getView()->plugin('searchTabs')
            ->getCurrentHiddenFilterParams($this->driver->getSourceIdentifier());

        return $result;
    }

    /**
     * Render an HTML checkbox control for the current record.
     *
     * @param string $idPrefix Prefix for checkbox HTML ids
     * @param string $formAttr ID of form for [form] attribute
     * @param bool   $label    Whether to enclose the actual checkbox in a label
     *
     * @return string
     */
    public function getCheckbox($idPrefix = '', $formAttr = false, $label = false)
    {
        static $checkboxCount = 0;
        $id = $this->driver->getSourceIdentifier() . '|'
            . $this->driver->getUniqueId();
        $context = [
            'id' => $id,
            'count' => $checkboxCount++,
            'prefix' => $idPrefix,
            'label' => $label
        ];
        if ($formAttr) {
            $context['formAttr'] = $formAttr;
        }
        return $this->contextHelper->renderInContext(
            'record/checkbox.phtml', $context
        );
    }

    /**
     * Return all record image urls as array keys.
     *
     * @return array
     */
    public function getAllRecordImageUrls()
    {
        $images = $this->driver->tryMethod('getAllImages', ['']);
        if (empty($images)) {
            return [];
        }
        $urls = [];
        foreach ($images as $image) {
            $urls[] = $image['urls']['small'];
            $urls[] = $image['urls']['medium'];
            if (isset($image['urls']['large'])) {
                $urls[] = $image['urls']['large'];
            }
        }
        return array_flip($urls);
    }

    /**
     * Return if image popup zoom has been enabled in config
     *
     * @return boolean
     */
    public function getImagePopupZoom()
    {
        return isset($this->config->Content->enableImagePopupZoom)
            && $this->config->Content->enableImagePopupZoom === '1';
    }

    /**
     * Return record image URL.
     *
     * @param string $size Size of requested image
     *
     * @return mixed
     */
    public function getRecordImage($size)
    {
        $images = $this->getAllImages('');

        $params = $this->driver->tryMethod('getRecordImage', [$size]);
        if (empty($params)) {
            $params = [
                'url' => $this->getThumbnail($size),
                'description' => '',
                'rights' => []
            ];
        }
        return $params;
    }

    /**
     * Allow record image to be downloaded?
     * If record image is converted from PDF, downloading is allowed only
     * for configured record formats.
     *
     * @return boolean
     */
    public function allowRecordImageDownload()
    {
        $master = $this->recordImageHelper->getMasterImageWithInfo(0);
        if (!$master['pdf']) {
            return true;
        }
        $formats = $this->config->Content->pdfCoverImageDownload ?? '';
        $formats = explode(',', $formats);
        return array_intersect($formats, $this->driver->getFormats());
    }

    /**
     * Return an array of all record images in all sizes
     *
     * @param string $language   Language for description and rights
     * @param bool   $thumbnails Whether to include thumbnail links if no image links
     * are found
     * @param bool   $includePdf Whether to include first PDF file when no image
     * links are found
     *
     * @return array
     */
    public function getAllImages($language, $thumbnails = true, $includePdf = true)
    {
        $sizes = ['small', 'medium', 'large', 'master'];
        $recordId = $this->driver->getUniqueID();
        $images = $this->driver->tryMethod('getAllImages', [$language, $includePdf]);
        if (null === $images) {
            $images = [];
        }
        if (empty($images) && $thumbnails) {
            $urls = [];
            foreach ($sizes as $size) {
                if ($thumb = $this->driver->getThumbnail($size)) {
                    $params = is_array($thumb) ? $thumb : [
                        'id' => $recordId
                    ];
                    $params['index'] = 0;
                    $params['size'] = $size;
                    $urls[$size] = $params;
                }
            }
            if ($urls) {
                $images[] = [
                    'urls' => $urls,
                    'description' => '',
                    'rights' => []
                ];
            }
        } else {
            foreach ($images as $idx => &$image) {
                foreach ($sizes as $size) {
                    if (!isset($image['urls'][$size])) {
                        continue;
                    }
                    $params = [
                        'id' => $recordId,
                        'index' => $idx,
                        'size' => $size
                    ];
                    $image['urls'][$size] = $params;
                }
            }
        }
        return $images;
    }

    /**
     * Return number of record images.
     *
     * @param string $size       Size of requested image
     * @param bool   $includePdf Whether to include first PDF file when no image
     * links are found
     *
     * @return int
     */
    public function getNumOfRecordImages($size, $includePdf = true)
    {
        $images = $this->driver->trymethod('getAllImages', ['', $includePdf]);
        return count($images);
    }

    /**
     * Render online URLs
     *
     * @param string $context Record context ('results', 'record' or 'holdings')
     *
     * @return string
     */
    public function getOnlineUrls($context)
    {
        return $this->renderTemplate(
            'result-online-urls.phtml',
            [
                'driver' => $this->driver,
                'context' => $context
            ]
        );
    }

    /**
     * Render meta tags for use on the record view.
     *
     * @return string
     */
    public function getMetaTags()
    {
        return $this->renderTemplate('meta-tags.phtml');
    }

    /**
     * Render average rating
     *
     * @return string
     */
    public function getRating()
    {
        if ($this->ratingAllowed()
            && $average = $this->driver->trymethod('getAverageRating')
        ) {
            return $this->getView()->render(
                'Helpers/record-rating.phtml',
                ['average' => $average['average'], 'count' => $average['count']]
            );
        }
        return false;
    }

    /**
     * Check if the given array of URLs contain URLs that
     * are not record images.
     *
     * @param array $urls      Array of URLs in the format returned by
     *                         getURLs and getOnlineURLs.
     * @param array $imageURLs Array of record image URLs as keys.
     *
     * @return boolean
     */
    public function containsNonImageURL($urls, $imageURLs)
    {
        foreach ($urls as $url) {
            if (!isset($imageURLs[$url['url']])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Is rating allowed.
     *
     * @return boolean
     */
    public function ratingAllowed()
    {
        return $this->commentingEnabled()
            && $this->driver->tryMethod('ratingAllowed');
    }

    /**
     * Set rendered URLs
     *
     * @param array $urls Array of rendered URLs
     *
     * @return array
     */
    public function setRenderedUrls($urls)
    {
        $this->renderedUrls = $urls;
    }

    /**
     * Get rendered URLs
     *
     * @return array
     */
    public function getRenderedUrls()
    {
        return $this->renderedUrls;
    }
}