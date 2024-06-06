<?php
namespace Internezzo\SitemapExport\Eel\Helper;

use Internezzo\SitemapExport\Service\SitemapService;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;


class SitemapHelper implements ProtectedContextAwareInterface
{

    /**
     * @Flow\Inject
     * @var SitemapService
     */
    protected $sitemapService;

    public function getFrontendUri(NodeInterface $node)
    {
        return $this->sitemapService->getFrontendUri($node);
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
