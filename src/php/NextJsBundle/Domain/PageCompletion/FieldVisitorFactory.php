<?php

namespace Frontastic\Catwalk\NextJsBundle\Domain\PageCompletion;

use Frontastic\Catwalk\ApiCoreBundle\Domain\Context;
use Frontastic\Catwalk\FrontendBundle\Domain\NodeService;
use Frontastic\Catwalk\NextJsBundle\Domain\SiteBuilderPageService;
use Frontastic\Common\SpecificationBundle\Domain\Schema\FieldVisitor;
use Frontastic\Common\SpecificationBundle\Domain\Schema\FieldVisitor\SequentialFieldVisitor;

class FieldVisitorFactory
{
    private SiteBuilderPageService $pageService;
    private NodeService $nodeService;

    private ?FieldVisitor $nodeDataVisitor = null;

    public function __construct(SiteBuilderPageService $pageService, NodeService $nodeService)
    {
        $this->pageService = $pageService;
        $this->nodeService = $nodeService;
    }

    public function createTasticDataVisitor(Context $context, array $tasticFieldData): FieldVisitor
    {
        return new SequentialFieldVisitor([
            // IMPORTANT: TasticFieldHandler must be called before PageFolderUrl!
            new TasticFieldValueInlineVisitor($tasticFieldData),
            new PageFolderCompletionVisitor($this->pageService, $this->nodeService, $context, $this),
            new SelectTranslationVisitor($context),
            new DataSourceReferenceFormatUpdater(),
        ]);
    }

    public function createNodeDataVisitor(Context $context): FieldVisitor
    {
        if ($this->nodeDataVisitor === null) {
            $this->nodeDataVisitor = new SequentialFieldVisitor([
                new PageFolderCompletionVisitor($this->pageService, $this->nodeService, $context, $this),
                new SelectTranslationVisitor($context),
            ]);
        }
        return $this->nodeDataVisitor;
    }

    public function createProjectConfigurationDataVisitor(Context $context): FieldVisitor
    {
        return new SequentialFieldVisitor([
            new SelectTranslationVisitor($context),
        ]);
    }
}
