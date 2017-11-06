<?php
namespace Breadlesscode\Domain\Model\ThumbnailGenerator;

use Breadlesscode\Office\Converter;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Adjustment\ResizeImageAdjustment;
use Neos\Media\Domain\Model\Thumbnail;
use Neos\Media\Domain\Model\ThumbnailGenerator\AbstractThumbnailGenerator;
use Neos\Media\Domain\Service\ImageService;
use Neos\Media\Exception\NoThumbnailAvailableException;

class OfficeThumbnailGenerator extends AbstractThumbnailGenerator
{
    /**
     * @var integer
     * @api
     */
    protected static $priority = 200;
    /**
     * @var ImageService
     * @Flow\Inject
     */
    protected $imageService;
    /**
     * @param Thumbnail $thumbnail
     * @return boolean
     */
    public function canRefresh(Thumbnail $thumbnail)
    {
        return Converter::canHandleExtension($thumbnail->getOriginalAsset()->getResource()->getFileExtension());
    }
    /**
     * @param Thumbnail $thumbnail
     * @return void
     * @throws NoThumbnailAvailableException
     */
    public function refresh(Thumbnail $thumbnail)
    {
        $resource = $thumbnail->getOriginalAsset()->getResource();

        try {
            $filenameWithoutExtension = pathinfo($resource->getFilename(), PATHINFO_FILENAME);
            $temporaryLocalCopyFilename = $thumbnail->getOriginalAsset()->getResource()->createTemporaryLocalCopy();
            $fileType = $thumbnail->getOriginalAsset()->getResource()->getFileExtension();

            $converter = Converter::file($temporaryLocalCopyFilename, $fileType)
                ->setLibreofficeBinaryPath($this->getOption('binPath'))
                ->setTimeout($this->getOption('timeout'));

            $resource = $this->resourceManager->importResourceFromContent(
                $converter->content('png'),
                $filenameWithoutExtension . '.png'
            );
            $processedImageInfo = $this->adjustImage($thumbnail, $resource);

            $thumbnail->setResource($processedImageInfo['resource']);
            $thumbnail->setWidth($processedImageInfo['width']);
            $thumbnail->setHeight($processedImageInfo['height']);
        } catch (\Exception $exception) {
            $filename = $resource->getFilename();
            $sha1 = $resource->getSha1();
            $message = sprintf('Unable to generate thumbnail for the given office document (filename: %s, SHA1: %s)', $filename, $sha1);
            throw new NoThumbnailAvailableException($message, 1433109652, $exception);
        }
    }
    /**
     * adjust the thumbnail resource
     * @param  Thumbnail $thumbnail
     * @param  PersistentResource $resource
     * @return array               processed image info
     */
    protected function adjustImage(Thumbnail $thumbnail, PersistentResource $resource)
    {
        $adjustments = [
            new ResizeImageAdjustment([
                'width' => $thumbnail->getConfigurationValue('width'),
                'maximumWidth' => $thumbnail->getConfigurationValue('maximumWidth'),
                'height' => $thumbnail->getConfigurationValue('height'),
                'maximumHeight' => $thumbnail->getConfigurationValue('maximumHeight'),
                'ratioMode' => $thumbnail->getConfigurationValue('ratioMode'),
                'allowUpScaling' => $thumbnail->getConfigurationValue('allowUpScaling'),
            ])
        ];
        return $this->imageService->processImage($resource, $adjustments);
    }
}
