<?php declare(strict_types=1);

namespace TcgManager\Service;

use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaUploadService
{
    private EntityRepository $mediaRepository;
    private EntityRepository $mediaFolderRepository;
    private EntityRepository $productMediaRepository;
    private MediaService $mediaService;
    private FileSaver $fileSaver;
    private LoggerInterface $logger;
    private string $projectRoot;

    public function __construct(
        EntityRepository $mediaRepository,
        EntityRepository $mediaFolderRepository,
        EntityRepository $productMediaRepository,
        MediaService $mediaService,
        FileSaver $fileSaver,
        LoggerInterface $logger,
        string $projectRoot
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->mediaFolderRepository = $mediaFolderRepository;
        $this->productMediaRepository = $productMediaRepository;
        $this->mediaService = $mediaService;
        $this->fileSaver = $fileSaver;
        $this->logger = $logger;
        $this->projectRoot = $projectRoot;
    }

    /**
     * Upload card images and associate with product
     */
    public function uploadCardImages(string $productId, array $imageMapping, Context $context): array
    {
        $uploadedImages = [];
        $errors = [];

        try {
            // Get or create TCG media folder
            $mediaFolderId = $this->getOrCreateTcgMediaFolder($context);

            foreach ($imageMapping as $finishCode => $imageData) {
                if (!$imageData['exists']) {
                    continue;
                }

                try {
                    $mediaId = $this->uploadSingleImage(
                        $imageData['path'],
                        $finishCode,
                        $imageData['finish'],
                        $mediaFolderId,
                        $context
                    );

                    if ($mediaId) {
                        $uploadedImages[$finishCode] = [
                            'mediaId' => $mediaId,
                            'finish' => $imageData['finish'],
                            'path' => $imageData['path']
                        ];
                    }

                } catch (\Exception $e) {
                    $errors[] = "Failed to upload {$finishCode}: " . $e->getMessage();
                    $this->logger->error('Failed to upload card image', [
                        'finishCode' => $finishCode,
                        'path' => $imageData['path'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Associate uploaded images with product
            if (!empty($uploadedImages)) {
                $this->associateImagesWithProduct($productId, $uploadedImages, $context);
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to upload card images', [
                'productId' => $productId,
                'error' => $e->getMessage()
            ]);
            $errors[] = "General upload error: " . $e->getMessage();
        }

        return [
            'success' => !empty($uploadedImages),
            'uploadedImages' => $uploadedImages,
            'errors' => $errors,
            'totalUploaded' => count($uploadedImages),
            'totalErrors' => count($errors)
        ];
    }

    /**
     * Upload a single image file
     */
    private function uploadSingleImage(
        string $imagePath,
        string $finishCode,
        string $finishName,
        string $mediaFolderId,
        Context $context
    ): ?string {
        $fullPath = $this->projectRoot . '/' . $imagePath;

        if (!file_exists($fullPath)) {
            throw new \RuntimeException("Image file not found: {$fullPath}");
        }

        // Generate media ID and filename
        $mediaId = Uuid::randomHex();
        $fileName = pathinfo($imagePath, PATHINFO_FILENAME);
        $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
        
        // Create unique filename with finish code
        $uniqueFileName = $fileName . '_' . $finishCode . '.' . $extension;

        // Check if media already exists
        $existingMedia = $this->findExistingMedia($uniqueFileName, $context);
        if ($existingMedia) {
            $this->logger->info('Media already exists, reusing', [
                'fileName' => $uniqueFileName,
                'mediaId' => $existingMedia['id']
            ]);
            return $existingMedia['id'];
        }

        // Create media entity
        $mediaData = [
            'id' => $mediaId,
            'mediaFolderId' => $mediaFolderId,
            'fileName' => $uniqueFileName,
            'fileExtension' => $extension,
            'mimeType' => $this->getMimeType($extension),
            'fileSize' => filesize($fullPath),
            'alt' => $finishName,
            'title' => $fileName . ' (' . $finishName . ')',
            'customFields' => [
                'tcg_finish_code' => $finishCode,
                'tcg_finish_name' => $finishName,
                'tcg_original_path' => $imagePath
            ]
        ];

        $this->mediaRepository->create([$mediaData], $context);

        // Create MediaFile object and save
        $mediaFile = new MediaFile(
            $fullPath,
            $this->getMimeType($extension),
            $extension,
            filesize($fullPath)
        );

        $this->fileSaver->persistFileToMedia($mediaFile, $uniqueFileName, $mediaId, $context);

        $this->logger->info('Successfully uploaded card image', [
            'mediaId' => $mediaId,
            'fileName' => $uniqueFileName,
            'finishCode' => $finishCode
        ]);

        return $mediaId;
    }

    /**
     * Associate uploaded images with product
     */
    private function associateImagesWithProduct(string $productId, array $uploadedImages, Context $context): void
    {
        $productMediaData = [];
        $position = 1;

        foreach ($uploadedImages as $finishCode => $imageData) {
            $productMediaData[] = [
                'id' => Uuid::randomHex(),
                'productId' => $productId,
                'mediaId' => $imageData['mediaId'],
                'position' => $position++,
                'customFields' => [
                    'tcg_finish_code' => $finishCode,
                    'tcg_finish_name' => $imageData['finish']
                ]
            ];
        }

        if (!empty($productMediaData)) {
            $this->productMediaRepository->create($productMediaData, $context);
            
            $this->logger->info('Associated images with product', [
                'productId' => $productId,
                'imageCount' => count($productMediaData)
            ]);
        }
    }

    /**
     * Get or create TCG media folder
     */
    private function getOrCreateTcgMediaFolder(Context $context): string
    {
        // Try to find existing TCG folder
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'TCG Cards'));
        
        $result = $this->mediaFolderRepository->search($criteria, $context);
        $folder = $result->first();

        if ($folder) {
            return $folder->getId();
        }

        // Create new TCG folder
        $folderId = Uuid::randomHex();
        $folderData = [
            'id' => $folderId,
            'name' => 'TCG Cards',
            'useParentConfiguration' => false,
            'configuration' => [
                'createThumbnails' => true,
                'keepAspectRatio' => true,
                'thumbnailQuality' => 80
            ]
        ];

        $this->mediaFolderRepository->create([$folderData], $context);

        $this->logger->info('Created TCG media folder', ['folderId' => $folderId]);

        return $folderId;
    }

    /**
     * Find existing media by filename
     */
    private function findExistingMedia(string $fileName, Context $context): ?array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('fileName', $fileName));
        
        $result = $this->mediaRepository->search($criteria, $context);
        $media = $result->first();

        return $media ? $media->jsonSerialize() : null;
    }

    /**
     * Get MIME type for file extension
     */
    private function getMimeType(string $extension): string
    {
        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml'
        ];

        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }

    /**
     * Batch upload images for multiple products
     */
    public function batchUploadImages(array $productImageMappings, Context $context): array
    {
        $results = [
            'totalProducts' => count($productImageMappings),
            'successfulUploads' => 0,
            'failedUploads' => 0,
            'totalImages' => 0,
            'errors' => []
        ];

        foreach ($productImageMappings as $productId => $imageMapping) {
            try {
                $uploadResult = $this->uploadCardImages($productId, $imageMapping, $context);
                
                if ($uploadResult['success']) {
                    $results['successfulUploads']++;
                    $results['totalImages'] += $uploadResult['totalUploaded'];
                } else {
                    $results['failedUploads']++;
                    $results['errors'] = array_merge($results['errors'], $uploadResult['errors']);
                }

            } catch (\Exception $e) {
                $results['failedUploads']++;
                $results['errors'][] = "Product {$productId}: " . $e->getMessage();
            }
        }

        return $results;
    }
}
