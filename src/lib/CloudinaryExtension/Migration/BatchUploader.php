<?php

namespace CloudinaryExtension\Migration;

use Cloudinary\Api\AlreadyExists;
use CloudinaryExtension\Exception\MigrationError;
use CloudinaryExtension\Image;
use CloudinaryExtension\Image\Synchronizable;
use CloudinaryExtension\ImageProvider;
use \Cloudinary_Cloudinary_Helper_Util_ArrayUtils as ArrayUtils;

class BatchUploader
{
    const MESSAGE_STATUS = 'Cloudinary migration: %s images migrated, %s failed';

    const MESSAGE_UPLOADED = 'Cloudinary migration: uploaded %s';

    const MESSAGE_UPLOADED_EXISTS = 'Cloudinary migration: %s exists - tagged as synchronized';
    
    const MESSAGE_UPLOAD_ERROR = 'Cloudinary migration: %s trying to upload %s';

    private $imageProvider;

    private $baseMediaPath;

    private $logger;

    private $migrationTask;

    private $countMigrated = 0;
    private $countFailed = 0;

    private $errors = [];

    public function __construct(ImageProvider $imageProvider, Task $migrationTask, Logger $logger, $baseMediaPath)
    {
        $this->imageProvider = $imageProvider;
        $this->migrationTask = $migrationTask;
        $this->baseMediaPath = $baseMediaPath;
        $this->logger = $logger;
    }

    public function uploadImages(array $images)
    {
        $this->countMigrated = 0;
        foreach ($images as $image) {

            if ($this->migrationTask->hasBeenStopped()) {
                break;
            }
            $this->uploadImage($image);
        }
        $this->logger->notice(sprintf(self::MESSAGE_STATUS, $this->countMigrated, $this->countFailed));
    }

    private function getAbsolutePath(Synchronizable $image)
    {
        return sprintf('%s%s', $this->baseMediaPath, $image->getFilename());
    }

    private function uploadImage(Synchronizable $image)
    {
        $absolutePath = $this->getAbsolutePath($image);
        $relativePath = $image->getRelativePath();
        $apiImage = Image::fromPath($absolutePath, $relativePath);

        try {
            $this->imageProvider->upload($apiImage);
            $image->tagAsSynchronized();
            $this->countMigrated++;
            $this->logger->notice(sprintf(self::MESSAGE_UPLOADED, $absolutePath . ' - ' . $relativePath));
        } catch (AlreadyExists $e) {
            $image->tagAsSynchronized();
            $this->countMigrated++;
            $this->logger->notice(sprintf(self::MESSAGE_UPLOADED, $image->getFilename()));
        } catch (\Exception $e) {
            $this->errors[] = $e;
            $this->countFailed++;
            $this->logger->error(sprintf(self::MESSAGE_UPLOAD_ERROR, $e->getMessage(), $absolutePath . ' - ' . $relativePath));
        }
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    public function getMigrationErrors()
    {
        return array_filter($this->errors, function ($val) {
            return $val instanceof MigrationError;
        });
    }

}
