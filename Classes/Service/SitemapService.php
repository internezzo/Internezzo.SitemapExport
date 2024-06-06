<?php
namespace Internezzo\SitemapExport\Service;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\ActionRequestFactory;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Http\Factories\ServerRequestFactory;
use Neos\Http\Factories\UriFactory;

/**
 * @Flow\Scope("singleton")
 */
class SitemapService
{

    /**
     * @var array
     */
    protected $items;

    /**
     * @param NodeInterface $siteNode
     * @return array
     */
    public function getSitemapUrls(NodeInterface $siteNode)
    {
        if ($this->items === null) {
            $items = [];

            try {
                $this->appendItems($items, $siteNode);
            } catch (NodeException $e) {
            }
            $this->items = $items;
        }

        return $this->items;
    }

    /**
     * Returns frontend uri of node
     *
     * @param NodeInterface $node
     * @return string
     * @throws \Neos\Flow\Http\Exception
     * @throws \Neos\Flow\Mvc\Exception\InvalidActionNameException
     * @throws \Neos\Flow\Mvc\Exception\InvalidArgumentNameException
     * @throws \Neos\Flow\Mvc\Exception\InvalidArgumentTypeException
     * @throws \Neos\Flow\Mvc\Exception\InvalidControllerNameException
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     */
    public function getFrontendUri(NodeInterface $node)
    {
        $uriBuilder = new UriBuilder();
        $actionRequestFactory = new ActionRequestFactory();
        $serverRequestFactory = new ServerRequestFactory(new UriFactory());
        $httpRequest = $serverRequestFactory->createServerRequest('GET', 'http://neos.io');
        $httpRequest = $httpRequest->withAttribute(ServerRequestAttributes::ROUTING_PARAMETERS, RouteParameters::createEmpty()->withParameter('requestUriHost', 'http://neos.io'));
        $uriBuilder->setRequest($actionRequestFactory->createActionRequest($httpRequest));
        return $uriBuilder
            ->reset()
            ->setCreateAbsoluteUri(true)
            ->uriFor('show', ['node' => $node], 'Frontend\Node', 'Neos.Neos');
    }

    /**
     * @param array $items
     * @param NodeInterface $node
     * @return void
     * @throws NodeException
     */
    protected function appendItems(array &$items, NodeInterface $node)
    {
        if ($this->isDocumentNodeToBeIndexed($node)) {
            $item = [
                'node' => $node,
                'lastModificationDateTime' => $node->getNodeData()->getLastModificationDateTime(),
                'priority' => $node->getProperty('xmlSitemapPriority') ?: '',
                'images' => [],
            ];
            if ($node->getProperty('xmlSitemapChangeFrequency')) {
                $item['changeFrequency'] = $node->getProperty('xmlSitemapChangeFrequency');
            }
            $items[] = $item;
        }
        foreach ($node->getChildNodes('Neos.Neos:Document') as $childDocumentNode) {
            $this->appendItems($items, $childDocumentNode);
        }
    }

    /**
     * Return TRUE/FALSE if the node is currently hidden
     * of the Menu Fusion object into account.
     *
     * @param NodeInterface $node
     * @return bool
     * @throws NodeException
     */
    protected function isDocumentNodeToBeIndexed(NodeInterface $node): bool
    {
        return !$node->getNodeType()->isOfType('Neos.Seo:NoindexMixin') && $node->isVisible()
            && $node->isAccessible() && $node->getProperty('metaRobotsNoindex') !== true
            && ((string)$node->getProperty('canonicalLink') === '' || substr($node->getProperty('canonicalLink'),7)=== $node->getIdentifier());
    }
}
