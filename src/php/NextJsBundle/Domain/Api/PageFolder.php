<?php

namespace Frontastic\Catwalk\NextJsBundle\Domain\Api;

/**
 * @replaces Frontastic\Catwalk\FrontendBundle\Domain\Node
 * @type
 */
class PageFolder
{
    /**
     * @replaces $nodeId.
     * @required
     * @var string
     */
    public string $pageFolderId;

    /**
     * @replaces $isMaster
     * @required
     * @var bool
     */
    public bool $isDynamic = false;

    /**
     * @replaces $nodeType
     * @required
     * @var string
     */
    public string $pageFolderType = 'landingpage';

    /**
     * @removed Sequence is not meaningful to the customer and can be re-added later, if needed
     */
    // public string $sequence;

    /**
     * @required
     * @var array
     */
    public array $configuration = [];

    /**
     * @replaces $streams
     * @var DataSourceConfiguration[]
     * @required
     */
    public array $dataSourceConfigurations = [];

    /**
     * @var string
     */
    public string $name;

    /**
     * @replaces $path
     * @required
     * @var string
     */
    public string $ancestorIdsMaterializedPath;

    /**
     * @var integer
     */
    public $depth;

    /**
     * @var integer
     * @required
     */
    public $sort = 0;

    /**
     * @removed Removing this for now as it is ambiguously used and can lead to large payloads.
     */
    // public $children = [];

    /**
     * @removed MetaData is not relevant to API hub but only studio
     */
    // public $metaData;

    /**
     * @removed extensions will never be called for deleted data
     */
    // public $isDeleted = false;
}
