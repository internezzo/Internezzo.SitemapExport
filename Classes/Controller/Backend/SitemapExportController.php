<?php

namespace Internezzo\SitemapExport\Controller\Backend;

use Internezzo\SitemapExport\Service\SitemapService;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\ContentDimensionCombinator;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Translator;
use Neos\Fusion\View\FusionView;
use Neos\Neos\Controller\Module\AbstractModuleController;

class SitemapExportController extends AbstractModuleController
{

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\InjectConfiguration(path="filename")
     * @var string
     */
    protected $filename;

    /**
     * @Flow\Inject
     * @var SitemapService
     */
    protected $sitemapService;

    /**
     * @var NodeInterface
     */
    protected $siteNode;

    /**
     * @Flow\Inject
     * @var Translator
     */
    protected $translator;

    /**
     * @Flow\InjectConfiguration(package="Neos.ContentRepository", path="contentDimensions.country.presets")
     * @var array
     */
    protected $countryDimensionPresets;

    /**
     * @Flow\Inject
     * @var ContentDimensionCombinator
     */
    protected $dimensionCombinator;

    /**
     * @var FusionView
     */
    protected $defaultViewObjectName = FusionView::class;

    /**
     * @param string $language
     * @return void
     */
    public function indexAction(string $language = '', string $country = ''): void
    {
        $context = $this->getContext($language, $country);
        /** @var NodeInterface $siteNode */
        $siteNode = $context->getCurrentSiteNode();
        $dimensions = $siteNode->getDimensions();
        $language = reset($dimensions['language']);
        if (array_key_exists('country', $dimensions)) {
            $country = reset($dimensions['country']);
        }
        $this->view->assign('siteNode', $siteNode);
        $this->view->assign('language', $language);
        $this->view->assign('country', $country);
        $this->view->assign('languageDimensions', $this->dimensionCombinator->getAllAllowedCombinations());
    }

    /**
     * @param string $language
     * @return void
     * @throws \Neos\Flow\I18n\Exception\IndexOutOfBoundsException
     * @throws \Neos\Flow\I18n\Exception\InvalidFormatPlaceholderException
     * @throws \Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException
     */
    public function downloadAction(string $language = 'de', string $country = ''): void
    {
        $context = $this->getContext($language, $country);
        $siteNode = $context->getCurrentSiteNode();
        $items = $this->sitemapService->getSitemapUrls($siteNode);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename='.$this->filename.'_'.$language);

        $locale = new Locale($language);
        $cols[] = $this->translator->translateById('title.label', [], null, $locale, 'Backend', 'Internezzo.SitemapExport');;
        $cols[] = $this->translator->translateById('uri.label', [], null, $locale, 'Backend', 'Internezzo.SitemapExport');;
        // create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');
        // output the column headings
        fputcsv($output, $cols);
        foreach ($items as $item) {
            $data = [$item['node']->getProperty('title'), $this->sitemapService->getFrontendUri($item['node'])];
            fputcsv($output, $data);
        }
        exit;
    }

    protected function getContext(string $language = '', string $country = '')
    {
        $context = $this->contextFactory->create();
        if ($language != '') {
            $context = $this->contextFactory->create(['dimensions' => ['language' => [$language]]]);
        }
        if ($country != '') {
            $countryValues = (array_key_exists($country, $this->countryDimensionPresets) ? $this->countryDimensionPresets[$country]['values'] : [$country]);
            $context = $this->contextFactory->create(['dimensions' => ['country' => $countryValues, 'language' => [$language]]]);
        }
        return $context;
    }

}
