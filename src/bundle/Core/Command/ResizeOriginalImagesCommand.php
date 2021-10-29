<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Command;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Core\FieldType\Image\Value;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use Imagine\Image\ImagineInterface;
use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Exception\Imagine\Filter\NonExistingFilterException;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Model\Binary;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Mime\MimeTypesInterface;
use Exception;

/**
 * This command resizes original images stored in ezimage FieldType in given ContentType using the selected filter.
 */
class ResizeOriginalImagesCommand extends Command implements BackwardCompatibleCommand
{
    const DEFAULT_ITERATION_COUNT = 25;
    const DEFAULT_REPOSITORY_USER = 'admin';

    /** @var \Ibexa\Contracts\Core\Repository\PermissionResolver */
    private $permissionResolver;

    /** @var \Ibexa\Contracts\Core\Repository\UserService */
    private $userService;

    /** @var \Ibexa\Contracts\Core\Repository\ContentTypeService */
    private $contentTypeService;

    /** @var \Ibexa\Contracts\Core\Repository\ContentService */
    private $contentService;

    /** @var \Ibexa\Contracts\Core\Repository\SearchService */
    private $searchService;

    /** @var \Liip\ImagineBundle\Imagine\Filter\FilterManager */
    private $filterManager;

    /** @var \Ibexa\Core\IO\IOServiceInterface */
    private $ioService;

    /** @var \Symfony\Component\Mime\MimeTypesInterface */
    private $mimeTypes;

    /** @var \Imagine\Image\ImagineInterface */
    private $imagine;

    public function __construct(
        PermissionResolver $permissionResolver,
        UserService $userService,
        ContentTypeService $contentTypeService,
        ContentService $contentService,
        SearchService $searchService,
        FilterManager $filterManager,
        IOServiceInterface $ioService,
        MimeTypesInterface $mimeTypes,
        ImagineInterface $imagine
    ) {
        $this->permissionResolver = $permissionResolver;
        $this->userService = $userService;
        $this->contentTypeService = $contentTypeService;
        $this->contentService = $contentService;
        $this->searchService = $searchService;
        $this->filterManager = $filterManager;
        $this->ioService = $ioService;
        $this->mimeTypes = $mimeTypes;
        $this->imagine = $imagine;

        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->permissionResolver->setCurrentUserReference(
            $this->userService->loadUserByLogin($input->getOption('user'))
        );
    }

    protected function configure()
    {
        $this
            ->setName('ibexa:images:resize-original')
            ->setAliases($this->getDeprecatedAliases())
            ->addArgument(
                'imageFieldIdentifier',
                InputArgument::REQUIRED,
                'Identifier of a Field of ezimage type.'
            )
            ->addArgument(
                'contentTypeIdentifier',
                InputArgument::REQUIRED,
                'Identifier of a Content Type which has an ezimage Field Type.'
            )
            ->addOption(
                'filter',
                'f',
                InputOption::VALUE_REQUIRED,
                'Filter which will be used for original images.'
            )
            ->addOption(
                'iteration-count',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Iteration count. Number of images to be recreated in a single iteration set to avoid using too much memory.',
                self::DEFAULT_ITERATION_COUNT
            )
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_OPTIONAL,
                'eZ Platform username (with Role containing at least content Policies: read, versionread, edit, publish)',
                self::DEFAULT_REPOSITORY_USER
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $contentTypeIdentifier = $input->getArgument('contentTypeIdentifier');
        $imageFieldIdentifier = $input->getArgument('imageFieldIdentifier');
        $filter = $input->getOption('filter');
        $iterationCount = (int)$input->getOption('iteration-count');

        $contentType = $this->contentTypeService->loadContentTypeByIdentifier($contentTypeIdentifier);
        $fieldType = $contentType->getFieldDefinition($imageFieldIdentifier);
        if (!$fieldType || $fieldType->fieldTypeIdentifier !== 'ezimage') {
            $output->writeln(
                sprintf(
                    "<error>Field Type with identifier '%s' in Content Type '%s' must be 'ezimage', you provided '%s'.</error>",
                    $imageFieldIdentifier,
                    $contentType->identifier,
                    $fieldType ? $fieldType->fieldTypeIdentifier : ''
                )
            );

            return 0;
        }

        try {
            $this->filterManager->getFilterConfiguration()->get($filter);
        } catch (NonExistingFilterException $e) {
            $output->writeln(
                sprintf(
                    '<error>%s</error>',
                    $e->getMessage()
                )
            );

            return 0;
        }

        $query = new Query();
        $query->filter = new Query\Criterion\ContentTypeIdentifier($contentType->identifier);

        $totalCount = $this->searchService->findContent($query)->totalCount;
        $query->limit = $iterationCount;

        if ($totalCount > 0) {
            $output->writeln(
                sprintf(
                    '<info>Found %d images matching given criteria.</info>',
                    $totalCount
                )
            );
        } else {
            $output->writeln(
                sprintf(
                    '<info>No images matching given criteria (ContentType: %s, FieldType %s) found. Exiting.</info>',
                    $contentTypeIdentifier,
                    $imageFieldIdentifier
                )
            );

            return 0;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<question>The changes you are going to perform cannot be undone. Remember to do a proper backup before. Would you like to continue?</question> ', false);
        if (!$helper->ask($input, $output, $question)) {
            return 0;
        }

        $progressBar = new ProgressBar($output, $totalCount);
        $progressBar->start();

        while ($query->offset <= $totalCount) {
            $results = $this->searchService->findContent($query);

            /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit $hit */
            foreach ($results->searchHits as $hit) {
                $this->resize($output, $hit, $imageFieldIdentifier, $filter);
                $progressBar->advance();
            }

            $query->offset += $iterationCount;
        }

        $progressBar->finish();
        $output->writeln('');
        $output->writeln(
            sprintf(
                "<info>All images have been successfully resized using the '%s' filter.</info>",
                $filter
            )
        );

        return 0;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit $hit
     * @param string $imageFieldIdentifier
     * @param string $filter
     */
    private function resize(OutputInterface $output, SearchHit $hit, string $imageFieldIdentifier, string $filter): void
    {
        try {
            /** @var \Ibexa\Core\FieldType\Image\Value $field */
            foreach ($hit->valueObject->fields[$imageFieldIdentifier] as $language => $field) {
                if (null === $field->id) {
                    continue;
                }
                $binaryFile = $this->ioService->loadBinaryFile($field->id);
                $mimeType = $this->ioService->getMimeType($field->id);
                $binary = new Binary(
                    $this->ioService->getFileContents($binaryFile),
                    $mimeType,
                    $this->mimeTypes->getExtensions($mimeType)[0] ?? null
                );

                $resizedImageBinary = $this->filterManager->applyFilter($binary, $filter);
                $newBinaryFile = $this->store($resizedImageBinary, $field);
                $image = $this->imagine->load($this->ioService->getFileContents($newBinaryFile));
                $dimensions = $image->getSize();

                $contentDraft = $this->contentService->createContentDraft($hit->valueObject->getVersionInfo()->getContentInfo(), $hit->valueObject->getVersionInfo());
                $contentUpdateStruct = $this->contentService->newContentUpdateStruct();
                $contentUpdateStruct->setField($imageFieldIdentifier, [
                    'id' => $field->id,
                    'alternativeText' => $field->alternativeText,
                    'fileName' => $field->fileName,
                    'fileSize' => $newBinaryFile->size,
                    'imageId' => $field->imageId,
                    'width' => $dimensions->getWidth(),
                    'height' => $dimensions->getHeight(),
                ]);
                $contentDraft = $this->contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
                $this->contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
                $this->contentService->publishVersion($contentDraft->versionInfo);
            }
        } catch (Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>Cannot resize image ID: %s, error message: %s.</error>',
                    $field->imageId,
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Copy of {@see \Ibexa\Bundle\Core\Imagine\IORepositoryResolver::store}
     * Original one cannot be used since original method uses {@see \Ibexa\Bundle\Core\Imagine\IORepositoryResolver::getFilePath}
     * so ends-up with image stored in _aliases instead of overwritten original image.
     *
     * @param \Liip\ImagineBundle\Binary\BinaryInterface $binary
     * @param \Ibexa\Core\FieldType\Image\Value $image
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentValue
     *
     * @return \Ibexa\Core\IO\Values\BinaryFile
     */
    private function store(BinaryInterface $binary, Value $image): BinaryFile
    {
        $tmpFile = tmpfile();
        fwrite($tmpFile, $binary->getContent());
        $tmpMetadata = stream_get_meta_data($tmpFile);
        $binaryCreateStruct = $this->ioService->newBinaryCreateStructFromLocalFile($tmpMetadata['uri']);
        $binaryCreateStruct->id = $image->id;
        $newBinaryFile = $this->ioService->createBinaryFile($binaryCreateStruct);
        fclose($tmpFile);

        return $newBinaryFile;
    }

    public function getDeprecatedAliases(): array
    {
        return ['ezplatform:images:resize-original'];
    }
}

class_alias(ResizeOriginalImagesCommand::class, 'eZ\Bundle\EzPublishCoreBundle\Command\ResizeOriginalImagesCommand');
