<?php
namespace Breadlesscode\NodeTypes\Folder\Eel\Helper;

use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\Exception\UnknownPackageException;
use Neos\Flow\Package\PackageManager;

class PackageManagerHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @return string
     */
    public function getNeosUiMajorVersion()
    {
        return preg_replace('#^v?(\d+)\.(\d+)\.(\d+)$#', '$1', $this->getNeosUiVersion());
    }

    /**
     * @return string
     */
    public function getNeosUiMinorVersion()
    {
        return preg_replace('#^v?(\d+)\.(\d+)\.(\d+)$#', '$1.$2', $this->getNeosUiVersion());
    }

    /**
     * @return string
     */
    public function getNeosUiVersion()
    {
        try {
            $packageManager = $this->objectManager->get(PackageManager::class);
            $neosUiPackage = $packageManager->getPackage('Neos.Neos.Ui');
            return $neosUiPackage->getInstalledVersion();
        } catch (UnknownPackageException $e) {
            return '0.0.0';
        }
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
