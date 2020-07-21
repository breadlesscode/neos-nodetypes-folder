<?php
declare(strict_types=1);

namespace Breadlesscode\NodeTypes\Folder\Package;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Neos\Domain\Service\SiteService;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Neos\Routing\Exception;
use Neos\Neos\Routing\FrontendNodeRoutePartHandler as NeosFrontendNodeRoutePartHandler;

/**
 * A route part handler for finding nodes specifically in the website's frontend.
 */
class FrontendNodeRoutePartHandler extends NeosFrontendNodeRoutePartHandler
{
    /**
     * Folder mixin property for hiding uri segment
     */
    public const MIXIN_PROPERTY_NAME = 'hideSegmentInUriPath';

    /**
     * @param string $requestPath
     * @return string
     */
    protected function getWorkspaceName($requestPath): string
    {
        $contextPathParts = [];
        if ($requestPath !== '' && strpos($requestPath, '@') !== false) {
            preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $requestPath, $contextPathParts);
        }

        if (!isset($contextPathParts['WorkspaceName']) || empty($contextPathParts['WorkspaceName'])) {
            $contextPathParts['WorkspaceName'] = 'live';
        }

        return $contextPathParts['WorkspaceName'];
    }

    /**
     * @inheritdoc
     * @throws NodeException
     */
    protected function getRelativeNodePathByUriPathSegmentProperties(NodeInterface $siteNode, $relativeRequestPath)
    {
        $node = $siteNode;
        $relativeNodePathSegments = [];
        $workspaceName = $this->getWorkspaceName($relativeRequestPath);

        foreach (explode('/', $relativeRequestPath) as $pathSegment) {
            $node = $this->findNextNodeWithPathSegmentRecursively(
                $node,
                $pathSegment,
                $relativeNodePathSegments,
                $workspaceName
            );
            if ($node === null) {
                return false;
            }
        }
        return implode('/', $relativeNodePathSegments);
    }

    /**
     * @param NodeInterface $startingNode
     * @param string $pathSegment
     * @param array $relativeNodePathSegments
     * @param string $workspaceName
     * @return NodeInterface|null
     * @throws NodeException
     */
    protected function findNextNodeWithPathSegmentRecursively(
        NodeInterface $startingNode,
        $pathSegment,
        &$relativeNodePathSegments,
        $workspaceName
    ): ?NodeInterface {
        foreach ($startingNode->getChildNodes('Neos.Neos:Document') as $node) {
            if ($workspaceName === 'live' && $this->shouldHideNodeUriSegment($node)) {
                $currentIndex = count($relativeNodePathSegments);
                $foundNode = $this->findNextNodeWithPathSegmentRecursively(
                    $node,
                    $pathSegment,
                    $relativeNodePathSegments,
                    $workspaceName
                );
                if ($foundNode !== null) {
                    array_splice($relativeNodePathSegments, $currentIndex, 0, $node->getName());
                    return $foundNode;
                }
            }
            if ($node->getProperty('uriPathSegment') === $pathSegment) {
                $relativeNodePathSegments[] = $node->getName();
                return $node;
            }
        }
        return null;
    }

    /**
     * @inheritdoc
     * @throws NodeException
     */
    protected function getRequestPathByNode(NodeInterface $node): string
    {
        if ($node->getParentPath() === SiteService::SITES_ROOT_PATH) {
            return '';
        }
        $startingNode = $node;
        $workspaceName = $node->getContext()->getWorkspaceName();

        if ($workspaceName !== 'live') {
            return parent::getRequestPathByNode($node);
        }

        // To allow building of paths to non-hidden nodes beneath hidden nodes, we assume
        // the input node is allowed to be seen and we must generate the full path here.
        // To disallow showing a node actually hidden itself has to be ensured in matching
        // a request path, not in building one.
        $contextProperties = $node->getContext()->getProperties();
        $contextAllowingHiddenNodes = $this->contextFactory->create(
            array_merge(
                $contextProperties,
                ['invisibleContentShown' => true]
            )
        );
        $currentNode = $contextAllowingHiddenNodes->getNodeByIdentifier($node->getIdentifier());

        $requestPathSegments = [];
        while ($currentNode->getParentPath() !== SiteService::SITES_ROOT_PATH && $currentNode instanceof NodeInterface) {
            if (!$currentNode->hasProperty('uriPathSegment')) {
                throw new Exception\MissingNodePropertyException(
                    sprintf(
                        'Missing "uriPathSegment" property for node "%s". Nodes can be migrated with the "flow node:repair" command.',
                        $currentNode->getPath()
                    ),
                    1415020326
                );
            }

            if ($startingNode === $currentNode || !$this->shouldHideNodeUriSegment($currentNode)) {
                $pathSegment = $currentNode->getProperty('uriPathSegment');
                $requestPathSegments[] = $pathSegment;
            }
            $currentNode = $currentNode->getParent();
            if ($currentNode === null || !$currentNode->isVisible()) {
                return '';
            }
        }
        return implode('/', array_reverse($requestPathSegments));
    }

    /**
     * Check for hiding uri segment of node
     *
     * @param NodeInterface $node
     * @return boolean
     * @throws NodeException
     */
    protected function shouldHideNodeUriSegment(NodeInterface $node): bool
    {
        return
            $node->hasProperty(self::MIXIN_PROPERTY_NAME) &&
            $node->getProperty(self::MIXIN_PROPERTY_NAME) === true;
    }
}
