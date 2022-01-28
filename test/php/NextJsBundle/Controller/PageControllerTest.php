<?php

namespace Frontastic\NextJsBundle\Controller;

use Frontastic\Catwalk\ApiCoreBundle\Domain\Context;
use Frontastic\Catwalk\FrontendBundle\Domain\Node;
use Frontastic\Catwalk\FrontendBundle\Domain\NodeService;
use Frontastic\Catwalk\FrontendBundle\Domain\Page;
use Frontastic\Catwalk\FrontendBundle\Domain\PageService;
use Frontastic\Catwalk\FrontendBundle\Domain\PreviewService;
use Frontastic\Catwalk\FrontendBundle\Domain\ViewData;
use Frontastic\Catwalk\FrontendBundle\Domain\ViewDataProvider;
use Frontastic\Catwalk\NextJsBundle\Controller\PageController;
use Frontastic\Catwalk\NextJsBundle\Domain\Api\DynamicPageSuccessResult;
use Frontastic\Catwalk\NextJsBundle\Domain\Api\Frontend\RedirectResponse;
use Frontastic\Catwalk\NextJsBundle\Domain\DynamicPageService;
use Frontastic\Catwalk\NextJsBundle\Domain\FromFrontasticReactMapper;
use Frontastic\Catwalk\NextJsBundle\Domain\RedirectService;
use Frontastic\Catwalk\NextJsBundle\Domain\PageDataCompletionService;
use Frontastic\Catwalk\NextJsBundle\Domain\SiteBuilderPageService;
use Frontastic\Common\ReplicatorBundle\Domain\Project;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class PageControllerTest extends TestCase
{
    private PageController $pageController;

    /**
     * @var SiteBuilderPageService|\Phake_IMock
     */
    private $siteBuilderPageServiceMock;
    /**
     * @var DynamicPageService|\Phake_IMock
     */
    private $dynamicPageService;
    /**
     * @var NodeService|\Phake_IMock
     */
    private $nodeServiceMock;
    /**
     * @var PageService|\Phake_IMock
     */
    private $pageServiceMock;
    /**
     * @var PreviewService|\Phake_IMock
     */
    private $previewServiceMock;
    /**
     * @var PageDataCompletionService|\Phake_IMock
     */
    private $completionServiceMock;
    /**
     * @var FromFrontasticReactMapper|\Phake_IMock
     */
    private $mapperMock;
    /**
     * @var ViewDataProvider|\Phake_IMock
     */
    private $viewDataProviderMock;
    /**
     * @var RedirectService|\Phake_IMock
     */
    private $redirectServiceMock;

    /**
     * @var Context
     */
    private $contextFixture;

    public function setUp()
    {
        $this->siteBuilderPageServiceMock = \Phake::mock(SiteBuilderPageService::class);
        $this->dynamicPageService = \Phake::mock(DynamicPageService::class);
        $this->nodeServiceMock = \Phake::mock(NodeService::class);
        $this->pageServiceMock = \Phake::mock(PageService::class);
        $this->previewServiceMock = \Phake::mock(PreviewService::class);
        $this->completionServiceMock = \Phake::mock(PageDataCompletionService::class);
        $this->mapperMock = \Phake::mock(FromFrontasticReactMapper::class);
        $this->viewDataProviderMock = \Phake::mock(ViewDataProvider::class);
        $this->redirectServiceMock = \Phake::mock(RedirectService::class);

        $this->contextFixture = new Context([
            'project' => new Project([
                'languages' => ['en_US'],
                'defaultLanguage' => 'en_US',
            ])
        ]);

        \Phake::when($this->viewDataProviderMock)->fetchDataFor->thenReturn(new ViewData([
            'tastic' => new \stdClass(),
        ]));
        \Phake::when($this->mapperMock)->map->thenReturnCallback(function ($input) {
            return $input;
        });
        \Phake::when($this->redirectServiceMock)->getRedirectResponseForPath->thenReturn(null);

        $this->pageController = new PageController(
            $this->siteBuilderPageServiceMock,
            $this->dynamicPageService,
            $this->mapperMock,
            $this->nodeServiceMock,
            $this->pageServiceMock,
            $this->previewServiceMock,
            $this->completionServiceMock,
            $this->viewDataProviderMock,
            $this->redirectServiceMock
        );
    }

    public function testDynamicPageHandlingTriggeredWhenNoNodeFound()
    {
        $request = new Request([
            'path' => '/no/node/found',
            'locale' => 'en_US',
        ]);

        \Phake::when($this->siteBuilderPageServiceMock)->matchSiteBuilderPage->thenReturn(null);
        \Phake::when($this->dynamicPageService)->handleDynamicPage->thenReturn(new DynamicPageSuccessResult());
        \Phake::when($this->dynamicPageService)->matchNodeFor->thenReturn(new Node());
        \Phake::when($this->pageServiceMock)->fetchForNode->thenReturn(new Page());

        $responseData = $this->pageController->indexAction(
            $request,
            $this->contextFixture
        );

        \Phake::verify($this->dynamicPageService)->handleDynamicPage;
        \Phake::verify($this->dynamicPageService)->matchNodeFor;

        $this->assertInstanceOf(Node::class, $responseData['pageFolder']);
    }

    public function testRedirectResponseSentWhenRedirectExistsForPath()
    {
        $path = '/redirect';

        \Phake::when($this->siteBuilderPageServiceMock)->matchSiteBuilderPage->thenReturn(null);
        \Phake::when($this->dynamicPageService)->handleDynamicPage->thenReturn(null);
        \Phake::when($this->redirectServiceMock)
            ->getRedirectResponseForPath($path, [], $this->contextFixture)
            ->thenReturn(new RedirectResponse([
                'statusCode' => 301,
                'reason' => RedirectResponse::REASON_REDIRECT_EXISTS_FOR_PATH,
                'targetType' => RedirectResponse::TARGET_TYPE_LINK,
                'target' => 'https://frontastic.cloud'
            ]));

        $request = new Request([
            'path' => $path,
            'locale' => 'en_US'
        ]);

        $response = $this->pageController->indexAction($request, $this->contextFixture);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testRedirectResponseIsNotSentWhenSiteBuilderPageExists()
    {
        $path = '/redirect';

        \Phake::when($this->siteBuilderPageServiceMock)->matchSiteBuilderPage->thenReturn('fakeNodeId1');
        \Phake::when($this->nodeServiceMock)->get('fakeNodeId1')->thenReturn(new Node());
        \Phake::when($this->dynamicPageService)->handleDynamicPage->thenReturn(null);
        \Phake::when($this->redirectServiceMock)
            ->getRedirectResponseForPath($path, [], $this->contextFixture)
            ->thenReturn(new RedirectResponse([
                'statusCode' => 301,
                'reason' => RedirectResponse::REASON_REDIRECT_EXISTS_FOR_PATH,
                'targetType' => RedirectResponse::TARGET_TYPE_LINK,
                'target' => 'https://frontastic.cloud'
            ]));

        $request = new Request([
            'path' => $path,
            'locale' => 'en_US'
        ]);

        $response = $this->pageController->indexAction($request, $this->contextFixture);

        $this->assertNotInstanceOf(RedirectResponse::class, $response);
    }

    public function testRedirectResponseIsNotSentWhenDynamicPageExists()
    {
        $path = '/redirect';

        \Phake::when($this->siteBuilderPageServiceMock)->matchSiteBuilderPage->thenReturn(null);
        \Phake::when($this->dynamicPageService)->handleDynamicPage->thenReturn(new DynamicPageSuccessResult());
        \Phake::when($this->dynamicPageService)->matchNodeFor->thenReturn(new Node());

        \Phake::when($this->redirectServiceMock)
            ->getRedirectResponseForPath($path, [], $this->contextFixture)
            ->thenReturn(new RedirectResponse([
                'statusCode' => 301,
                'reason' => RedirectResponse::REASON_REDIRECT_EXISTS_FOR_PATH,
                'targetType' => RedirectResponse::TARGET_TYPE_LINK,
                'target' => 'https://frontastic.cloud'
            ]));

        $request = new Request([
            'path' => $path,
            'locale' => 'en_US'
        ]);

        $response = $this->pageController->indexAction($request, $this->contextFixture);

        $this->assertNotInstanceOf(RedirectResponse::class, $response);
    }
}
