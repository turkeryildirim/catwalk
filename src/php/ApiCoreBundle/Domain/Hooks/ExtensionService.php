<?php

namespace Frontastic\Catwalk\ApiCoreBundle\Domain\Hooks;

use Frontastic\Common\CoreBundle\Domain\Json\InvalidJsonDecodeException;
use Frontastic\Common\CoreBundle\Domain\Json\InvalidJsonEncodeException;
use Frontastic\Common\HttpClient;
use Frontastic\Catwalk\ApiCoreBundle\Domain\ContextService;
use Frontastic\Catwalk\FrontendBundle\EventListener\RequestIdListener;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Frontastic\Common\CoreBundle\Domain\Json\Json;
use Frontastic\Common\HttpClient\Response;

class ExtensionService
{
    const DEFAULT_HEADERS = ['Content-Type: application/json'];
    const BASE_PATH = 'http://localhost:8082/'; // TODO: move to a config file later on
    const DYNAMIC_PAGE_EXTENSION_NAME = 'dynamic-page-handler';

    const MAX_ACTION_TIMEOUT = 10;
    const MAX_DATASOURCE_TIMEOUT = 5;
    const MAX_PAGE_TIMEOUT = 5;

    const TIMEOUT_MESSAGE = <<< EOT
The provided timeout of '%s' is greater than the maximum allowed value of '%s' using maximum value instead
EOT;

    private LoggerInterface $logger;
    private ContextService $contextService;

    /** @var array[] */
    private ?array $extensions = null;

    private RequestStack $requestStack;

    private HttpClient $httpClient;


    public function __construct(
        LoggerInterface $logger,
        ContextService $contextService,
        RequestStack $requestStack,
        HttpClient $httpClient
    ) {
        $this->logger = $logger;
        $this->contextService = $contextService;
        $this->requestStack = $requestStack;
        $this->httpClient = $httpClient;
    }

    /**
     * Fetches the extension list from the extension runner
     *
     * @param string $project
     * @return array
     * @throws InvalidJsonDecodeException
     * @throws \Exception
     */
    public function fetchProjectExtensions(string $project): array
    {
        //TODO: should the path be changed to 'extensions' in the extension runner?
        $response = $this->httpClient->get($this->makePath('hooks', $project));

        if ($response->status != 200) {
            throw new \Exception(
                'Fetching available extensions failed. Error: ' . $response->body
            );
        }

        return Json::decode($response->body, true);
    }

    /**
     * Gets the list of extensions
     *
     * If the list does not exist yet, it will be fetched automatically from the extension runner
     *
     * @return array|array[]
     * @throws InvalidJsonDecodeException
     */
    public function getExtensions(): array
    {
        if ($this->extensions === null) {
            $this->extensions = $this->fetchProjectExtensions($this->getProjectIdentifier());
        }

        return $this->extensions;
    }

    /**
     * Check if extension exists
     *
     * @param string $extensionName
     * @return bool
     * @throws InvalidJsonDecodeException
     */
    public function hasExtension(string $extensionName): bool
    {
        $extensions = $this->getExtensions();

        return in_array($extensionName, array_keys($extensions), true);
    }

    /**
     * Checks if the dynamic page handler extension exists
     *
     * @return bool
     * @throws InvalidJsonDecodeException
     */
    public function hasDynamicPageHandler(): bool
    {
        return $this->hasExtension(self::DYNAMIC_PAGE_EXTENSION_NAME);
    }

    /**
     * Check if the specified action extension exists
     *
     * @param $namespace
     * @param $action
     * @return bool
     * @throws InvalidJsonDecodeException
     */
    public function hasAction($namespace, $action): bool
    {
        $hookName = $this->getActionHookName($namespace, $action);
        return $this->hasExtension($hookName);
    }

    /**
     * Calls a datasource extension
     *
     * @param string $extensionName
     * @param array $arguments
     * @param int|null $timeout
     * @return PromiseInterface
     * @throws InvalidJsonDecodeException
     * @throws InvalidJsonEncodeException
     */
    public function callDataSource(string $extensionName, array $arguments, ?int $timeout): PromiseInterface
    {
        if ($timeout && $timeout > self::MAX_DATASOURCE_TIMEOUT) {
            $this->logger->info(
                sprintf(
                    self::TIMEOUT_MESSAGE,
                    $timeout,
                    self::MAX_DATASOURCE_TIMEOUT
                )
            );
            $timeout = self::MAX_DATASOURCE_TIMEOUT;
        }

        return $this->callExtension($extensionName, $arguments, $timeout ?? self::MAX_DATASOURCE_TIMEOUT);
    }

    /**
     * Calls a dynamic page handler extension
     *
     * @param array $arguments
     * @param int|null $timeout
     * @return object|null
     * @throws InvalidJsonDecodeException
     * @throws InvalidJsonEncodeException
     */
    public function callDynamicPageHandler(array $arguments, ?int $timeout): ?object
    {
        if ($timeout && $timeout > self::MAX_PAGE_TIMEOUT) {
            $this->logger->warning(
                sprintf(
                    self::TIMEOUT_MESSAGE,
                    $timeout,
                    self::MAX_PAGE_TIMEOUT
                )
            );
            $timeout = self::MAX_PAGE_TIMEOUT;
        }

        return Json::decode(
            $this->callExtension(
                self::DYNAMIC_PAGE_EXTENSION_NAME,
                $arguments,
                $timeout ?? self::MAX_PAGE_TIMEOUT
            )->wait()
        );
    }

    /**
     * Calls an action
     *
     * @param string $namespace
     * @param string $action
     * @param array $arguments
     * @param int|null $timeout
     * @return mixed|object
     * @throws InvalidJsonDecodeException
     * @throws InvalidJsonEncodeException
     */
    public function callAction(string $namespace, string $action, array $arguments, ?int $timeout)
    {
        if ($timeout && $timeout > self::MAX_ACTION_TIMEOUT) {
            $this->logger->warning(
                sprintf(
                    self::TIMEOUT_MESSAGE,
                    $timeout,
                    self::MAX_ACTION_TIMEOUT
                )
            );
            $timeout = self::MAX_ACTION_TIMEOUT;
        }

        $hookName = $this->getActionHookName($namespace, $action);
        return Json::decode($this->callExtension($hookName, $arguments, $timeout ?? self::MAX_ACTION_TIMEOUT)->wait());
    }

    private function getActionHookName(string $namespace, string $action): string
    {
        return sprintf('action-%s-%s', $namespace, $action);
    }

    private function getProjectIdentifier(): string
    {
        $context = $this->contextService->createContextFromRequest();
        return $context->project->customer . '_' . $context->project->projectId;
    }


    /**
     * @throws InvalidJsonEncodeException
     * @throws InvalidJsonDecodeException
     */
    private function callExtension(string $extensionName, array $arguments, int $timeout): PromiseInterface
    {
        if (!$this->hasExtension($extensionName)) {
            return Create::promiseFor(Json::encode([
                'ok' => false,
                'message' => sprintf('The requested extension "%s" was not found.', $extensionName)
            ]));
        }

        $requestId = $this->requestStack->getCurrentRequest()->attributes->get(
            RequestIdListener::REQUEST_ID_ATTRIBUTE_KEY
        );

        $payload = Json::encode(['arguments' => $arguments]);

        $headers = ['Frontastic-Request-Id' => "Frontastic-Request-Id:$requestId"];

        try {
            return $this->doCallAsync($this->getProjectIdentifier(), $extensionName, $payload, $headers, $timeout);
        } catch (\Exception $exception) {
            return Create::promiseFor(Json::encode([
                'ok' => false,
                'message' => $exception->getMessage()
            ]));
        }
    }

    /**
     * @throws \Exception
     */
    private function doCallAsync(
        string $project,
        string $extensionName,
        string $payload,
        ?array $headers,
        int $timeout
    ): PromiseInterface {
        $path = $this->makePath('run', $project, $extensionName);
        $requestHeaders = $headers + self::DEFAULT_HEADERS;

        $requestOptions = new HttpClient\Options();
        $requestOptions->timeout = $timeout;

        return $this->httpClient->postAsync($path, $payload, $requestHeaders, $requestOptions)->then(
            function (Response $response) use ($extensionName) {
                if ($response->status != 200) {
                    throw new \Exception('Calling extension ' . $extensionName . ' failed. Error: ' . $response->body);
                }

                return $response->body;
            }
        );
    }


    private function makePath(string ...$uri): string
    {
        return self::BASE_PATH . implode("/", $uri);
    }
}
