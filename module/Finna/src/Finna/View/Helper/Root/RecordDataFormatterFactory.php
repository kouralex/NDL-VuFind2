<?php
/**
 * Factory for record driver data formatting view helper
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2016.
 * Copyright (C) The National Library of Finland 2017-2020.
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
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
namespace Finna\View\Helper\Root;

use VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder;

/**
 * Factory for record driver data formatting view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
class RecordDataFormatterFactory
{
    /**
     * Create the helper.
     *
     * @return RecordDataFormatter
     */
    public function __invoke()
    {
        $helper = new RecordDataFormatter();
        $helper->setDefaults('core', $this->getDefaultCoreSpecs());
        $helper->setDefaults('description', $this->getDefaultDescriptionSpecs());
        $helper->setDefaults('authority', $this->getDefaultAuthoritySpecs());
        $helper->setDefaults(
            'authorityRecommend',
            $this->getDefaultAuthorityRecommendSpecs()
        );
        return $helper;
    }

    /**
     * Get default specifications for displaying data in core metadata.
     *
     * @return array
     */
    public function getDefaultCoreSpecs()
    {
        $spec = new SpecBuilder();

        foreach ($this->getDefaultCoreFields() as $key => $data) {
            if ($data[0] === true) {
                [$multiLine, $dataMethod, $callback, $options] = $data;
            } else {
                [$multiLine, $dataMethod, $template, $options] = $data;
            }
            if ($multiLine) {
                $spec->setMultiLine($key, $dataMethod, $callback, $options);
            } else {
                $spec->setTemplateLine($key, $dataMethod, $template, $options);
            }
        }
        return $spec->getArray();
    }

    /**
     * Utility function for getting fields in core metadata
     *
     * @return array
     */
    protected function getDefaultCoreFields()
    {
        $pos = 10;
        $lines = [];

        $setTemplateLine
            = function ($key, $dataMethod, $template, $options = []) use (
                &$lines,
                &$pos
            ) {
                $pos += 100;
                $options['pos'] = $pos;
                $lines[$key] = [false, $dataMethod, $template, $options];
            };

        $setMultiTemplateLine
            = function (
                $key,
                $dataMethod,
                $callback,
                $options = []
            ) use (&$lines, &$pos) {
                $pos += 100;
                $options['pos'] = $pos;
                $lines[$key] = [true, $dataMethod, $callback, $options];
            };
        $setTemplateLine(
            'Genre',
            'getGenres',
            'data-genres.phtml',
            [
                'context' => ['class' => 'recordGenres']
            ]
        );
        $setTemplateLine(
            'Age Limit',
            'getAgeLimit',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordAgeLimit']
            ]
        );
        $setTemplateLine(
            'Original Work',
            'getOriginalWork',
            'data-forwardFields.phtml',
            [
                'context' => ['class' => 'recordOriginalWork']
            ]
        );
        $setTemplateLine(
            'Published in',
            'getContainerTitle',
            'data-containerTitle.phtml',
            [
                'context' => ['class' => 'record-container-link']
            ]
        );
        $setTemplateLine(
            'New Title',
            'getNewerTitles',
            'data-titles.phtml',
            [
                'context' => ['class' => 'recordNextTitles']
            ]
        );
        $setTemplateLine(
            'Previous Title',
            'getPreviousTitles',
            'data-titles.phtml',
            [
                'context' => ['class' => 'recordPrevTitles']
            ]
        );

        $setTemplateLine(
            'Secondary Authors',
            'getNonPresenterSecondaryAuthors',
            'data-contributors.phtml',
            [
                'context' => ['class' => 'recordAuthors'],
                'labelFunction' => function () {
                    return 'Contributors';
                }
            ]
        );
        $setTemplateLine(
            'Actors',
            'getPresenters',
            'data-actors.phtml',
            [
                'context' => ['class' => 'recordPresenters']
            ]
        );
        $setTemplateLine(
            'Item Description FWD',
            'getGeneralNotes',
            'data-forwardFields.phtml',
            [
                'context' => ['class' => 'recordDescription']
            ]
        );
        $setTemplateLine(
            'Description FWD',
            'getDescription',
            'data-forwardFields.phtml',
            [
                'context' => ['class' => 'recordDescription']
            ]
        );
        $setTemplateLine(
            'Identifiers',
            'getOtherIdentifiers',
            'data-lines-with-detail.phtml',
            [
                'context' => ['class' => 'recordIdentifiers']
            ]
        );
        $setTemplateLine(
            'Press Reviews',
            'getPressReview',
            'data-forwardFields.phtml',
            [
                'context' => ['class' => 'record-press-review']
            ]
        );
        $setTemplateLine(
            'Music',
            'getMusicInfo',
            'data-forwardFields.phtml',
            [
                'context' => ['class' => 'record-music']
            ]
        );
        $setTemplateLine(
            'Projected Publication Date',
            'getProjectedPublicationDate',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'coreProjectedPublicationDate']
            ]
        );
        $setTemplateLine(
            'Dissertation Note',
            'getDissertationNote',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'coreDissertationNote']
            ]
        );
        $setTemplateLine(
            'Other Links',
            'getOtherLinks',
            'data-getOtherLinks.phtml',
            [
                'labelFunction'  => function ($data) {
                    $label = isset($data[0]) ? $data[0]['heading'] : '';
                    return $label;
                },
                'context' => ['class' => 'recordOtherLink']
            ]
        );
        $setTemplateLine(
            'Presenters',
            'getPresenters',
            'data-presenters.phtml',
            [
                'context' => ['class' => 'recordPresenters']
            ]
        );
        $setTemplateLine(
            'Other Titles',
            'getAlternativeTitles',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordAltTitles']
            ]
        );

        $setTemplateLine(
            'Format',
            'getFormats',
            'format-list.phtml',
            [
                'context' => ['class' => 'recordFormat']
            ]
        );
        $setTemplateLine(
            'Archive Origination',
            'getOrigination',
            'data-origination.phtml',
            [
                'context' => ['class' => 'record-origination']
            ]
        );
        $setTemplateLine(
            'Archive',
            true,
            'data-archive.phtml',
            [
                'context' => ['class' => 'recordHierarchyLinks']
            ]
        );
        $setTemplateLine(
            'Archive Series',
            'isPartOfArchiveSeries',
            'data-archiveSeries.phtml',
            [
                'context' => [
                    'class' => 'recordSeries',
                    'levels' => \Finna\RecordDriver\SolrEad::SERIES_LEVELS
                ]
            ]
        );
        $setTemplateLine(
            'Archive File',
            'isPartOfArchiveSeries',
            'data-archiveSeries.phtml',
            [
                'context' => [
                    'class' => 'recordFile',
                    'levels' => \Finna\RecordDriver\SolrEad::FILE_LEVELS
                ]
            ]
        );
        $setTemplateLine(
            'Physical Medium',
            'getPhysicalMediums',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'physical-medium']
            ]
        );
        $setTemplateLine(
            'Physical Description',
            'getPhysicalDescriptions',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'physicalDescriptions']
            ]
        );
        $setTemplateLine(
            'Extent',
            'getPhysicalDescriptions',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'record-extent']
            ]
        );
        $setTemplateLine(
            'Language',
            'getLanguages',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'recordLanguage']
            ]
        );
        $setTemplateLine(
            'original_work_language',
            'getOriginalLanguages',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'originalLanguage']
            ]
        );
        $setTemplateLine(
            'Item Description',
            'getGeneralNotes',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordDescription']
            ]
        );
        $setTemplateLine(
            'Subject Detail',
            'getSubjectDetails',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordSubjects']
            ]
        );
        $setTemplateLine(
            'Subject Place',
            'getSubjectPlaces',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordSubjects']
            ]
        );
        $setTemplateLine(
            'Subject Date',
            'getSubjectDates',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordSubjects']
            ]
        );
        $setTemplateLine(
            'Subject Actor',
            'getSubjectActors',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordSubjects']
            ]
        );

        $setTemplateLine(
            'Organisation',
            'getInstitutions',
            'data-organisation.phtml',
            [
                'context' => ['class' => 'recordInstitution']
            ]
        );
        $setTemplateLine(
            'Collection',
            'getCollections',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordCollection']
            ]
        );
        $setTemplateLine(
            'Content Description',
            'getContentDescription',
            'data-escapeHtml.phtml',
            ['context' => ['class' => 'recordContentDescription']]
        );

        $setTemplateLine(
            'Item History',
            'getItemHistory',
            'data-escapeHtml.phtml',
            ['context' => ['class' => 'recordHistory']]
        );

        $setTemplateLine(
            'Inventory ID',
            'getIdentifier',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordIdentifier']
            ]
        );

        $setTemplateLine(
            'Measurements',
            'getMeasurements',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordMeasurements']
            ]
        );
        $setTemplateLine(
            'Inscriptions',
            'getInscriptions',
            'data-inscriptions.phtml',
            [
                'context' => ['class' => 'recordInscriptions']
            ]
        );
        $setTemplateLine(
            'Other Classification',
            'getFormatClassifications',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordClassifications']
            ]
        );
        $setTemplateLine(
            'Other ID',
            'getLocalIdentifiers',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordIdentifiers']
            ]
        );

        $getEvents = function ($data, $options) {
            $final = [];
            $pos = $options['pos'];
            foreach ($data as $eventType => $events) {
                $final[] = [
                    'values' => $events,
                    'options' => [
                        'pos' => $pos++,
                        'renderType' => 'RecordDriverTemplate',
                        'template' => 'data-mainFormat.phtml',
                        'context' => ['class' => 'recordEvents'],
                        'labelFunction'
                            => function ($data, $driver) use ($eventType) {
                                $mainFormat = $driver->getMainFormat();
                                return "lido_event_type_{$mainFormat}_$eventType";
                            },
                    ],
                ];
            }
            return $final;
        };
        $setMultiTemplateLine(
            'Events',
            'getEvents',
            $getEvents
        );

        $setTemplateLine(
            'Unit ID',
            'getUnitID',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordReferenceCode']
            ]
        );
        $setTemplateLine(
            'Unit IDs',
            'getUnitIds',
            'data-lines-with-detail.phtml'
        );
        $setTemplateLine(
            'Authors',
            'getNonPresenterAuthors',
            'data-authors.phtml',
            [
                'context' => ['class' => 'recordAuthors']
            ]
        );
        $setTemplateLine(
            'Publisher',
            'getPublicationDetails',
            'data-publicationDetails.phtml',
            [
                'context' => ['class' => 'recordPublications']
            ]
        );
        $setTemplateLine(
            'Published',
            'getPublicationDetails',
            'data-publicationDetails.phtml',
            [
                'context' => ['class' => 'recordPublications']
            ]
        );
        $setTemplateLine(
            'Projected Publication Date',
            'getProjectedPublicationDate',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'coreProjectedPublicationDate']
            ]
        );
        $setTemplateLine(
            'Dissertation Note',
            'getDissertationNote',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'coreDissertationNote']
            ]
        );
        $setTemplateLine(
            'Edition',
            'getEdition',
            'data-edition.phtml',
            [
                'context' => ['class' => 'recordEdition']
            ]
        );
        $setTemplateLine(
            'Series',
            'getSeries',
            'data-series.phtml',
            [
                'context' => ['class' => 'recordSeries']
            ]
        );
        $setTemplateLine(
            'Classification',
            'getClassifications',
            'data-classification.phtml',
            [
                'context' => ['class' => 'recordClassifications']
            ]
        );
        $setTemplateLine(
            'Subjects',
            'getAllSubjectHeadings',
            'data-allSubjectHeadings.phtml',
            [
                'context' => ['class' => 'recordSubjects']
            ]
        );
        $setTemplateLine(
            'subjects_extended',
            'getAllSubjectHeadingsExtended',
            'data-allSubjectHeadingsExtended.phtml',
            [
                'context' => ['class' => 'recordSubjects']
            ]
        );
        $setTemplateLine(
            'Methodology',
            'getMethodology',
            'data-methodology-links.phtml',
            [
                'context' => ['class' => 'recordMethodology']
            ]
        );
        $setTemplateLine(
            'Publications',
            'getRelatedPublications',
            'data-relatedPublications.phtml',
            [
                'context' => ['class' => 'record-related-publications']
            ]
        );
        $setTemplateLine(
            'Other Classifications',
            'getOtherClassifications',
            'data-keywords.phtml',
            [
                'context' => [
                    'class' => 'recordClassifications', 'title' => 'Classification'
                ]
            ]
        );
        $setTemplateLine(
            'Introduction',
            'getIntroduction',
            'data-markdown.phtml',
            [
                'context' => ['class' => 'record-introduction']
            ]
        );
        $setTemplateLine(
            'Manufacturer',
            'getManufacturer',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'recordManufacturer']
            ]
        );
        $setTemplateLine(
            'Production',
            'getProducers',
            'data-producers.phtml',
            [
                'context' => ['class' => 'recordManufacturer']
            ]
        );
        $setTemplateLine(
            'Production Costs',
            'getProductionCost',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'record-production-cost']
            ]
        );
        $setTemplateLine(
            'Funding',
            'getFunders',
            'data-funding.phtml',
            [
                'context' => ['class' => 'record-funders']
            ]
        );
        $setTemplateLine(
            'Distribution',
            'getDistributors',
            'data-distribution.phtml',
            [
                'context' => ['class' => 'record-distributors']
            ]
        );
        $setTemplateLine(
            'Premiere Night',
            'getPremiereTime',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'record-premiere-night']
            ]
        );
        $setTemplateLine(
            'Premiere Theaters',
            'getPremiereTheaters',
            'data-escapeHtml.phtml',
            [
               'context' =>
                  ['class' => 'record-premiere-theaters'],
            ]
        );
        $setTemplateLine(
            'Broadcasting Dates',
            'getBroadcastingInfo',
            'data-broadcasting-dates.phtml',
            [
                'context' => ['class' => 'record-broadcasting-info']
            ]
        );
        $setTemplateLine(
            'Film Festivals',
            'getFestivalInfo',
            'data-festival-info.phtml',
            [
                'context' => ['class' => 'record-festival-info']
            ]
        );
        $setTemplateLine(
            'Foreign Distribution',
            'getForeignDistribution',
            'data-foreign-distribution.phtml',
            [
               'context' => ['class' => 'record-foreign-distribution']
            ]
        );
        $setTemplateLine(
            'Film Copies',
            'getNumberOfCopies',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'record-film-copies']
            ]
        );
        $setTemplateLine(
            'Other Screenings',
            'getOtherScreenings',
            'data-other-screenings.phtml',
            [
               'context' => ['class' => 'record-other-screenings']
            ]
        );
        $setTemplateLine(
            'Exterior Images',
            'getExteriors',
            'data-forwardFields.phtml',
            [
                'context' => ['class' => 'record-exteriors']
            ]
        );
        $setTemplateLine(
            'Interior Images',
            'getInteriors',
            'data-forwardFields.phtml',
            [
                'context' => ['class' => 'record-interiors']
            ]
        );
        $setTemplateLine(
            'Studios',
            'getStudios',
            'data-forwardFields.phtml',
            [
                'context' => ['class' => 'record-studios']
            ]
        );
        $setTemplateLine(
            'Filming Location Notes',
            'getLocationNotes',
            'data-forwardFields.phtml',
            [
                'context' => ['class' => 'record-location-notes']
            ]
        );
        $setTemplateLine(
            'Filming Date',
            'getFilmingDate',
            'data-forwardFields.phtml',
            [
                'context' => ['class' => 'record-filming-date']
            ]
        );
        $setTemplateLine(
            'Archive Films',
            'getArchiveFilms',
            'data-forwardFields.phtml',
            [
                'context' => ['class' => 'record-archive-films']
            ]
        );
        $setTemplateLine(
            'Additional Information',
            'getTitleStatement',
            'data-addInfo.phtml',
            [
                'context' => ['class' => 'recordTitleStatement']
            ]
        );

        $setTemplateLine(
            'Additional Information Extended',
            'getTitleStatementsExtended',
            'data-addInfoExtended.phtml',
            [
                'context' => [
                    'class' => 'recordTitleStatement',
                    'title' => 'Additional Information'
                ]
            ]
        );

        $setTemplateLine(
            'child_records',
            'getChildRecordCount',
            'data-childRecords.phtml',
            [
                'allowZero' => false,
                'context' => ['class' => 'recordComponentParts']
            ]
        );
        $setTemplateLine(
            'Record Links',
            'getAllRecordLinks',
            'data-allRecordLinks.phtml',
            [
                'context' => ['class' => 'recordLinks', 'title' => ""]
            ]
        );
        $setTemplateLine(
            'Related Materials',
            'getAllRecordLinks',
            'data-allRecordLinks.phtml',
            [
                'context' => ['class' => 'relatedMaterials']
            ]
        );
        $setTemplateLine(
            'Online Access',
            true,
            'data-onlineAccess.phtml',
            [
                'context' => ['class' => 'webResource']
            ]
        );
        $setTemplateLine(
            'Source Collection',
            'getSource',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordSource']
            ]
        );
        $setTemplateLine(
            'Publish date',
            'getDateSpan',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'extendedDateSpan']
            ]
        );
        $setTemplateLine(
            'Keywords',
            'getKeywords',
            'data-keywords.phtml',
            [
                'context' => ['class' => 'record-keywords']
            ]
        );
        $setTemplateLine(
            'Education Programs',
            'getEducationPrograms',
            'data-education.phtml',
            [
                'context' => ['class' => 'record-education-programs']
            ]
        );
        $setTemplateLine(
            'Educational Role',
            'getEducationalAudiences',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'record-educational-audience']
            ]
        );
        $setTemplateLine(
            'Educational Use',
            'getEducationalUse',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'record-educational-uses']
            ]
        );
        $setTemplateLine(
            'Educational Level',
            'getEducationalLevels',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'record-educational-levels']
            ]
        );
        $setTemplateLine(
            'Educational Subject',
            'getEducationalSubjects',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'record-educational-subjects']
            ]
        );
        $setTemplateLine(
            'Learning Resource Type',
            'getEducationalMaterialType',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'record-educational-material-type']
            ]
        );
        $setTemplateLine(
            'Objective and Content',
            'getEducationalAim',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'record-educational-aim']
            ]
        );
        $setTemplateLine(
            'Accessibility Feature',
            'getAccessibilityFeatures',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'record-accessibility-features']
            ]
        );
        $setTemplateLine(
            'Accessibility Hazard',
            'getAccessibilityHazards',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'record-accessibility-hazard']
            ]
        );
        $setTemplateLine(
            'Publication Frequency',
            'getPublicationFrequency',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'extendedFrequency']
            ]
        );
        $setTemplateLine(
            'Playing Time',
            'getPlayingTimes',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'extendedPlayTime']
            ]
        );
        $setTemplateLine(
            'Color',
            'getColor',
            'data-color.phtml',
            [
                'context' => ['class' => 'record-color']
            ]
        );
        $setTemplateLine(
            'Sound',
            'getSound',
            'data-sound.phtml',
            [
                'context' => ['class' => 'record-sound']
            ]
        );
        $setTemplateLine(
            'Aspect Ratio',
            'getAspectRatio',
            'data-escapeHtml',
            [
                'context' => ['class' => 'record-aspect-ratio']
            ]
        );
        $setTemplateLine(
            'System Format',
            'getSystemDetails',
            'data-escapeHtml',
            [
                'context' => ['class' => 'extendedSystem']
            ]
        );
        $setTemplateLine(
            'Audience',
            'getTargetAudienceNotes',
            'data-escapeHtml',
            [
                'context' => ['class' => 'extendedAudience']
            ]
        );
        $setTemplateLine(
            'Awards',
            'getAwards',
            'data-forwardFields.phtml',
            [
                'context' => ['class' => 'extendedAwards']
            ]
        );
        $setTemplateLine(
            'Production Credits',
            'getProductionCredits',
            'data-escapeHtml',
            [
                'context' => ['class' => 'extendedCredits']
            ]
        );
        $setTemplateLine(
            'Bibliography',
            'getBibliographyNotes',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'extendedBibliography']
            ]
        );
        $setTemplateLine(
            'ISBN',
            'getISBNs',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'extendedISBNs']
            ]
        );
        $setTemplateLine(
            'ISSN',
            'getISSNs',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'extendedISSNs']
            ]
        );
        $setTemplateLine(
            'DOI',
            'getCleanDOI',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'extended-doi']
            ]
        );
        $setTemplateLine(
            'Related Items',
            'getRelationshipNotes',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'extendedRelatedItems']
            ]
        );
        $setTemplateLine(
            'Access Restrictions',
            'getAccessRestrictions',
            'data-accrest.phtml',
            [
                'context' => ['class' => 'extendedAccess']
            ]
        );
        $setTemplateLine(
            'Access',
            'getAccessRestrictions',
            'data-accrest.phtml',
            [
                'context' => ['class' => 'extendedAccess']
            ]
        );

        $setTemplateLine(
            'Terms of Use',
            'getTermsOfUse',
            'data-termsOfUse.phtml',
            [
                'context' => ['class' => 'extendedTermsOfUse']
            ]
        );
        $setTemplateLine(
            'Finding Aid',
            'getFindingAids',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'extendedFindingAids']
            ]
        );
        $setTemplateLine(
            'Publication_Place',
            'getHierarchicalPlaceNames',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'publicationPlace']
            ]
        );
        $setTemplateLine(
            'Author Notes',
            true,
            'data-authorNotes.phtml',
            [
                'context' => ['class' => 'extendedAuthorNotes']
            ]
        );
        $setTemplateLine(
            'Location',
            'getPhysicalLocations',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordPhysicalLocation']
            ]
        );
        $setTemplateLine(
            'Date',
            'getUnitDate',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordDaterange']
            ]
        );
        $setTemplateLine(
            'Dates',
            'getUnitDates',
            'data-lines-with-detail.phtml',
            [
                'context' => ['title' => 'Date']
            ]
        );
        $setTemplateLine(
            'Material Condition',
            'getMaterialCondition',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'materialCondition']
            ]
        );

        $getAccessRestrictions = function ($data, $options) {
            $final = [];
            $pos = $options['pos'];
            // Check whether the first restriction element is an array. If so,
            // restrictions are grouped under subheadings.
            $useSubHeadings = is_array(array_values($data)[0]);
            foreach ($data as $type => $values) {
                $values = $useSubHeadings && $values
                    ? array_values($values) : $values;
                $label = $useSubHeadings ? "access_restrictions_$type" : null;
                if ($useSubHeadings
                    && isset($options['hideSubheadings'])
                    && in_array($label, $options['hideSubheadings'])
                ) {
                    $label = null;
                }
                $final[] = [
                    'label' => $label,
                    'values' => $values,
                    'options' => [
                        'pos' => $pos++,
                        'renderType' => 'RecordDriverTemplate',
                        'template' => 'data-escapeHtml.phtml',
                        'context' => [
                            'class' => 'extendedAccess',
                            'type' => "access_restrictions_$type",
                            'schemaLabel' => null,
                        ],
                    ],
                ];
            }
            return $final;
        };
        $setMultiTemplateLine(
            'Access Restrictions Extended',
            'getExtendedAccessRestrictions',
            $getAccessRestrictions
        );

        $setTemplateLine(
            'Source of Acquisition',
            'getAcquisitionSource',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordAcquisition']
            ]
        );
        $setTemplateLine(
            'Medium of Performance',
            'getMusicCompositions',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'record-composition']
            ]
        );

        $getExtendedMusicCompositions = function ($data, $options) {
            $final = [];
            $pos = $options['pos'];
            foreach ($data as $field) {
                $label = $field['partial']
                    ? 'Partial Medium of Performance'
                    : 'Medium of Performance';
                $final[] = [
                    'label' => $label,
                    'values' => $field['items'],
                    'options' => [
                        'pos' => $pos++,
                        'renderType' => 'RecordDriverTemplate',
                        'template' => 'data-music-composition.phtml',
                        'context' => [
                            'class' => 'record-composition',
                        ],
                    ],
                ];
            }
            return $final;
        };
        $setMultiTemplateLine(
            'Music Compositions Extended',
            'getExtendedMusicCompositions',
            $getExtendedMusicCompositions
        );

        $setTemplateLine(
            'Notated Music Format',
            'getNotatedMusicFormat',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordNoteFormat']
            ]
        );
        $setTemplateLine(
            'Event Notice',
            'getEventNotice',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordEventNotice']
            ]
        );
        $setTemplateLine(
            'First Lyrics',
            'getFirstLyrics',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordFirstLyrics']
            ]
        );
        $setTemplateLine(
            'Trade Availability Note',
            'getTradeAvailabilityNote',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordTradeNote']
            ]
        );
        $setTemplateLine(
            'Inspection Details',
            'getInspectionDetails',
            'data-inspection.phtml',
            [
                'context' => ['class' => 'recordInspection']
            ]
        );
        $setTemplateLine(
            'Scale',
            'getMapScale',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'record-map-scale']
            ]
        );
        $setTemplateLine(
            'Available Online',
            'getWebResources',
            'data-detailed-urls.phtml',
            [
                'context' => [
                    'class' => 'record-available-online',
                    'truncateUrl' => true
                ]
            ]
        );
        $setTemplateLine(
            'Notes',
            'getNotes',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'record-notes']
            ]
        );
        $setTemplateLine(
            'Place of Origin',
            'getAssociatedPlace',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'record-associated-place']
            ]
        );
        $setTemplateLine(
            'Related Places',
            'getRelatedPlacesExtended',
            'data-lines-with-detail.phtml',
            [
                'context' => ['class' => 'record-related-place']
            ]
        );
        $setTemplateLine(
            'Time Period of Creation',
            'getTimePeriodOfCreation',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'record-time-period-creation']
            ]
        );

        $setTemplateLine(
            'Uniform Title',
            'getCollectiveUniformTitle',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'record-collective-uniform-title']
            ]
        );
        $setTemplateLine(
            'Standard Codes',
            'getStandardCodes',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'record-standard-codes']
            ]
        );
        $setTemplateLine(
            'Publisher or Distributor Number',
            'getPubDistNumber',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'record-pubdist-number']
            ]
        );
        $setTemplateLine(
            'Time Period',
            'getTimePeriod',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'record-time-period']
            ]
        );
        $setTemplateLine(
            'Copyright Notes',
            'getCopyrightNotes',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'record-copyright-notes']
            ]
        );
        $setTemplateLine(
            'Language Notes',
            'getLanguageNotes',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'record-language-notes']
            ]
        );
        $setTemplateLine(
            'Uncontrolled Title',
            'getUncontrolledTitle',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'record-uncontrolled-title']
            ]
        );

        // Add arcrole-relations as multiple fields with role as field header
        $getRelations = function ($data, $options) {
            // Group relations by role
            $relationsByRole = [];
            foreach ($data as $relation) {
                // Combine all relations without role under the key '0'
                $role = ($relation['role'] ?? '0') ?: '0';
                if (!isset($relationsByRole[$role])) {
                    $relationsByRole[$role] = [];
                }
                // Unset so that role is not appended to relation name
                // since it's used as the record field label (see below).
                unset($relation['role']);
                $relationsByRole[$role][] = $relation;
            }
            $final = [];
            $pos = $options['pos'];
            // Add one record field for each role (might include several relations).
            foreach ($relationsByRole as $role => $relations) {
                $final[] = [
                    'label' => $role !== '0' ? "CreatorRoles::$role" : null,
                    'values' => $relations,
                    'options' => [
                        'pos' => $pos++,
                        'renderType' => 'RecordDriverTemplate',
                        'template' => 'data-authors.phtml',
                        'context' => [
                            'class' => 'recordRelations',
                            'schemaLabel' => null,
                        ],
                    ],
                ];
            }
            return $final;
        };

        $setMultiTemplateLine(
            'Archive Relations',
            'getRelations',
            $getRelations
        );

        $setTemplateLine(
            'Appraisal',
            'getAppraisal',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordAppraisal']
            ]
        );

        $setTemplateLine(
            'Container Information',
            'getContainerInformation',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordContainerInformation']
            ]
        );

        $setTemplateLine(
            'Material Arrangement',
            'getMaterialArrangement',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordMaterialArrangement']
            ]
        );
        $setTemplateLine(
            'Other Related Material',
            'getOtherRelatedMaterial',
            'data-otherRelatedMaterial.phtml',
            [
                'context' => ['class' => 'other-related-material']
            ]
        );
        $setTemplateLine(
            'Audience Characteristics',
            'getAudienceCharacteristics',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'audience-characteristics']
            ]
        );
        $setTemplateLine(
            'Creator Characteristics',
            'getCreatorCharacteristics',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'creator-characteristics']
            ]
        );

        return $lines;
    }

    /**
     * Get default specifications for displaying data in the description tab.
     *
     * @return array
     */
    public function getDefaultDescriptionSpecs()
    {
        $spec = new SpecBuilder();
        $spec->setLine('Summary', 'getSummary');
        $spec->setLine('Published', 'getDateSpan');
        $spec->setLine('Item Description', 'getGeneralNotes');
        $spec->setLine('Physical Description', 'getPhysicalDescriptions');
        $spec->setLine('Publication Frequency', 'getPublicationFrequency');
        $spec->setLine('Playing Time', 'getPlayingTimes');
        $spec->setLine('Format', 'getSystemDetails');
        $spec->setLine('Audience', 'getTargetAudienceNotes');
        $spec->setLine('Awards', 'getAwards');
        $spec->setLine('Production Credits', 'getProductionCredits');
        $spec->setLine('Bibliography', 'getBibliographyNotes');
        $spec->setLine('ISBN', 'getISBNs');
        $spec->setLine('ISSN', 'getISSNs');
        $spec->setLine('DOI', 'getCleanDOI');
        $spec->setLine('Related Items', 'getRelationshipNotes');
        $spec->setLine('Access', 'getAccessRestrictions');
        $spec->setLine('Finding Aid', 'getFindingAids');
        $spec->setLine('Publication_Place', 'getHierarchicalPlaceNames');
        $spec->setTemplateLine('Author Notes', true, 'data-authorNotes.phtml');
        return $spec->getArray();
    }

    /**
     * Get default specifications for displaying data in the description tab.
     *
     * @return array
     */
    public function getDefaultAuthoritySpecs()
    {
        $spec = new SpecBuilder();
        $spec->setLine('Date of birth', 'getBirthDate');
        $spec->setLine('Place of birth', 'getBirthPlace');
        $spec->setLine('Date of death', 'getDeathDate');
        $spec->setLine('Place of death', 'getDeathPlace');

        $spec->setLine('Established', 'getEstablishedDate');
        $spec->setLine('Terminated', 'getTerminatedDate');
        $spec->setLine('Awards', 'getAwards');

        $spec->setLine('Occupation', 'getOccupations');
        $spec->setLine('Field of Activity', 'getFieldsOfActivity');
        $spec->setTemplateLine(
            'Other Forms of Name',
            'getAlternativeTitles',
            'data-lines-with-detail.phtml'
        );
        $spec->setLine('Associated Place', 'getAssociatedPlace');
        $spec->setTemplateLine(
            'Related Places',
            'getRelatedPlaces',
            'data-lines-with-detail.phtml'
        );
        $spec->setTemplateLine(
            'Identifiers',
            'getOtherIdentifiers',
            'data-lines-with-detail.phtml'
        );
        $spec->setLine('Historical Information', 'getHistory');

        $spec->setTemplateLine('Sources', 'getSources', 'data-sources.phtml');
        $spec->setTemplateLine(
            'Related Authorities',
            'getRelations',
            'data-relations-author.phtml'
        );
        $spec->setTemplateLine(
            'Associated Groups',
            'getAssociatedGroups',
            'data-lines-with-detail.phtml'
        );
        $spec->setLine('Additional Information', 'getAdditionalInformation');

        return $spec->getArray();
    }

    /**
     * Get default specifications for displaying data in the
     * authority recommend module.
     *
     * @return array
     */
    public function getDefaultAuthorityRecommendSpecs()
    {
        $specs = $this->getDefaultAuthoritySpecs();
        if (isset($specs['Relations'])) {
            $specs['Relations']['template']
                = 'data-relations-author-recommend.phtml';
        }
        return $specs;
    }
}
