<?php
namespace Breadlesscode\NodeTypes\Folder\Package;

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\SiteService;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Neos\Routing\Exception;
use \Neos\Neos\Routing\FrontendNodeRoutePartHandler as NeosFrontendNodeRoutePartHandler;

/**
 * A route part handler for finding nodes specifically in the website's frontend.
 */
class FrontendNodeRoutePartHandler extends NeosFrontendNodeRoutePartHandler
{
    /**
     * folder mixin type for hiding uri segement
     */
    const MIXIN_PROPERTY_NAME = 'hideSegmentInUriPath';

    /**
     * @Flow\InjectConfiguration("routing.supportEmptySegmentForDimensions", package="Neos.Neos")
     * @var boolean
     */
    protected $supportEmptySegmentForDimensions;

    /**
     * @param string $requestPath
     * @return string
     */
    protected function getWorkspaceName($requestPath)
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
     * @return NodeInterface
     */
    protected function findNextNodeWithPathSegmentRecursively(
        NodeInterface $startingNode,
        $pathSegment,
        &$relativeNodePathSegments,
        $workspaceName
    ) {
        foreach ($startingNode->getChildNodes('Neos.Neos:Document') as $node) {
            if ($workspaceName == 'live' && $this->shouldHideNodeUriSegement($node)) {
                $foundNode = $this->findNextNodeWithPathSegmentRecursively(
                    $node,
                    $pathSegment,
                    $relativeNodePathSegments,
                    $workspaceName
                );
                if ($foundNode !== null) {
                    array_unshift($relativeNodePathSegments, $node->getName());
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
     */
    protected function getRequestPathByNode(NodeInterface $node)
    {
        if ($node->getParentPath() === SiteService::SITES_ROOT_PATH) {
            return '';
        }
        $startingNode = $node;
        $workspaceName = $node->getContext()->getWorkspaceName();

        if ($workspaceName !== 'live') {
            return parent::getRequestPathByNode($node);
        }

        $requestPathSegments = [];
        while ($node->getParentPath() !== SiteService::SITES_ROOT_PATH && $node instanceof NodeInterface) {
            if (!$node->hasProperty('uriPathSegment')) {
                throw new Exception\MissingNodePropertyException(
                    sprintf(
                        'Missing "uriPathSegment" property for node "%s". Nodes can be migrated with the "flow node:repair" command.',
                        $node->getPath()
                    ),
                    1415020326
                );
            }

            if ($startingNode === $node || !$this->shouldHideNodeUriSegement($node)) {
                $pathSegment = $node->getProperty('uriPathSegment');
                $requestPathSegments[] = $pathSegment;
            }
            $node = $node->getParent();
            if ($node === null || !$node->isVisible()) {
                return '';
            }
        }
        return implode('/', array_reverse($requestPathSegments));
    }
    /**
     * check for hiding uri segement of node
     *
     * @param NodeInterface $node
     * @return boolean
     */
    protected function shouldHideNodeUriSegement(NodeInterface $node)
    {
        return
            $node->hasProperty(self::MIXIN_PROPERTY_NAME) &&
            $node->getProperty(self::MIXIN_PROPERTY_NAME) === true;
    }
}
