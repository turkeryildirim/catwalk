<?php

namespace Frontastic\Catwalk;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;
use Doctrine\Common\Annotations\AnnotationRegistry;

class Catwalk
{
    const CATWALK_LIBRARY_BASE = __DIR__ . '/../..';

    public static function runWeb(string $projectDirectory, $autoloader): void
    {
        // Resolve all relative path elements
        $projectDirectory = realpath($projectDirectory);

        AppKernel::$catwalkBaseDir = $projectDirectory;

        static::loadEnvironmentVariables($projectDirectory);
        static::startTidewaysIfConfigured();

        $env = getenv('env') ?? 'prod';
        $debug = (bool) ($_SERVER['APP_DEBUG'] ?? (AppKernel::isDebugEnvironment($env)));

        if ($debug) {
            umask(0000);
            Debug::enable();
        }

        static::setTrustedProxies();
        static::setTrustedHosts();

        try {
            AnnotationRegistry::registerLoader(array($autoloader, 'loadClass'));

            $request = Request::createFromGlobals();
            $kernel = new AppKernel($env, $debug);
            $response = $kernel->handle($request);
            $response->send();
            $kernel->terminate($request, $response);
        } catch (\Throwable $e) {
            syslog(LOG_CRIT, $e->getMessage() . PHP_EOL . $e->getTraceAsString());

            if (!headers_sent()) {
                header("HTTP/1.0 500 Internal Server Error");
            }

            echo "<html><body><h1>Frontastic Internal Server Error</h1>";
            if (AppKernel::getDebug()) {
                echo '<pre style="white-space: pre-wrap;">', $e, '</pre>';
            }
            echo "</body></html>";
        }
    }

    /**
     * @param string $projectDirectory
     */
    private static function loadEnvironmentVariables(string $projectDirectory): void
    {
        (new \Frontastic\Common\EnvironmentResolver())->loadEnvironmentVariables(
            [$projectDirectory . '/..', self::CATWALK_LIBRARY_BASE, $projectDirectory],
            AppKernel::getBaseConfiguration()
        );
    }

    private static function startTidewaysIfConfigured(): void
    {
        if (class_exists('Tideways\Profiler')) {
            \Tideways\Profiler::start(array('api_key' => getenv('tideways_key')));
        }
    }

    private static function setTrustedProxies(): void
    {
        $trustedProxies = getenv('trusted_proxies') ?? false;
        if ($trustedProxies) {
            if (trim($trustedProxies) === '*') {
                // Trust all proxies. In our cloud setup servers are only reachable through the LB but the LB IPs change.
                $trustedProxies = $_SERVER['REMOTE_ADDR'];
            }
        }
        Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL);
    }

    private static function setTrustedHosts(): void
    {
        if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
            Request::setTrustedHosts(explode(',', $trustedHosts));
        }
    }
}
