<?php

namespace Tests\ShopBundle\Performance\Page;

use Shopsys\HttpSmokeTesting\RequestDataSet;
use Shopsys\HttpSmokeTesting\RequestDataSetGenerator;
use Shopsys\HttpSmokeTesting\RouteConfig;
use Shopsys\HttpSmokeTesting\RouteConfigCustomizer;
use Shopsys\HttpSmokeTesting\RouteInfo;
use Shopsys\HttpSmokeTesting\RouterAdapter\SymfonyRouterAdapter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Request;
use Tests\ShopBundle\Performance\JmeterCsvReporter;
use Tests\ShopBundle\Smoke\Http\RouteConfigCustomization;

class AllPagesTest extends KernelTestCase
{
    const PASSES = 5;

    protected function setUp()
    {
        parent::setUp();

        static::bootKernel([
            'environment' => 'test',
            'debug' => false,
        ]);

        self::$kernel->getContainer()->get('shopsys.shop.component.domain')
            ->switchDomainById(1);
    }

    /**
     * @group warmup
     */
    public function testAdminPagesWarmup()
    {
        $this->doWarmupPagesWithProgress(
            $this->getRequestDataSets('~^admin_~')
        );
    }

    /**
     * @group warmup
     */
    public function testFrontPagesWarmup()
    {
        $this->doWarmupPagesWithProgress(
            $this->getRequestDataSets('~^front~')
        );
    }

    public function testAdminPages()
    {
        $this->doTestPagesWithProgress(
            $this->getRequestDataSets('~^admin_~'),
            self::$kernel->getContainer()->getParameter('shopsys.root_dir') . '/build/stats/performance-tests-admin.csv'
        );
    }

    public function testFrontPages()
    {
        $this->doTestPagesWithProgress(
            $this->getRequestDataSets('~^front~'),
            self::$kernel->getContainer()->getParameter('shopsys.root_dir') . '/build/stats/performance-tests-front.csv'
        );
    }

    /**
     * @param string $routeNamePattern
     * @return \Shopsys\HttpSmokeTesting\RequestDataSet[]
     */
    private function getRequestDataSets($routeNamePattern)
    {
        $requestDataSetGenerators = [];
        $allRouteInfo = $this->getRouterAdapter()->getAllRouteInfo();
        foreach ($allRouteInfo as $routeInfo) {
            $requestDataSetGenerators[] = new RequestDataSetGenerator($routeInfo);
        }

        $routeConfigCustomizer = new RouteConfigCustomizer($requestDataSetGenerators);
        $routeConfigCustomization = new RouteConfigCustomization(self::$kernel->getContainer());
        $routeConfigCustomization->customizeRouteConfigs($routeConfigCustomizer);

        $routeConfigCustomizer->customize(function (RouteConfig $config, RouteInfo $info) use ($routeNamePattern) {
            if (!preg_match($routeNamePattern, $info->getRouteName())) {
                $config->skipRoute('Route name does not match pattern "' . $routeNamePattern . '".');
            }
        });

        $allRequestDataSets = [];
        foreach ($requestDataSetGenerators as $requestDataSetGenerator) {
            $requestDataSets = $requestDataSetGenerator->generateRequestDataSets();

            $nonSkippedRequestDataSets = array_filter($requestDataSets, function (RequestDataSet $requestDataSet) {
                return !$requestDataSet->isSkipped();
            });

            $allRequestDataSets = array_merge($allRequestDataSets, $nonSkippedRequestDataSets);
        }

        return $allRequestDataSets;
    }

    /**
     * @param \Shopsys\HttpSmokeTesting\RequestDataSet[] $requestDataSets
     */
    private function doWarmupPagesWithProgress(array $requestDataSets)
    {
        $consoleOutput = new ConsoleOutput();
        $consoleOutput->writeln('');

        $requestDataSetCount = count($requestDataSets);
        $requestDataSetIndex = 0;
        foreach ($requestDataSets as $requestDataSet) {
            $requestDataSetIndex++;

            $progressLine = sprintf(
                'Warmup: %3d%% (%s)',
                round($requestDataSetIndex / $requestDataSetCount * 100),
                $requestDataSet->getRouteName()
            );
            $consoleOutput->write(str_pad($progressLine, 80) . "\r");

            $this->doTestRequestDataSet($requestDataSet);
        }
    }

    /**
     * @param \Shopsys\HttpSmokeTesting\RequestDataSet[] $requestDataSets
     * @param string $jmeterOutputFilename
     */
    private function doTestPagesWithProgress(array $requestDataSets, $jmeterOutputFilename)
    {
        $consoleOutput = new ConsoleOutput();
        $consoleOutput->writeln('');

        $performanceTestSamples = [];

        $requestDataSetCount = count($requestDataSets);
        for ($pass = 1; $pass <= self::PASSES; $pass++) {
            $requestDataSetIndex = 0;
            foreach ($requestDataSets as $requestDataSet) {
                $requestDataSetIndex++;

                $progressLine = sprintf(
                    '%s: %3d%% (%s)',
                    'Pass ' . $pass . '/' . self::PASSES,
                    round($requestDataSetIndex / $requestDataSetCount * 100),
                    $requestDataSet->getRouteName()
                );
                $consoleOutput->write(str_pad($progressLine, 80) . "\r");

                $performanceTestSamples[] = $this->doTestRequestDataSet($requestDataSet);
            }
        }

        $performanceTestSamplesAggregatedByUrl = $this->aggregatePerformanceTestSamplesByUrl($performanceTestSamples);
        $this->exportJmeterCsvReport($performanceTestSamples, $jmeterOutputFilename);
        $this->printPerformanceTestsSummary($performanceTestSamplesAggregatedByUrl, $consoleOutput);

        $this->doAssert($performanceTestSamplesAggregatedByUrl);
    }

    /**
     * @param \Shopsys\HttpSmokeTesting\RequestDataSet $requestDataSet
     * @return \Tests\ShopBundle\Performance\Page\PerformanceTestSample
     */
    private function doTestRequestDataSet(RequestDataSet $requestDataSet)
    {
        $this->setUp();

        $requestDataSet->executeCallsDuringTestExecution(static::$kernel->getContainer());

        $uri = $this->getRouterAdapter()->generateUri($requestDataSet);

        $request = Request::create($uri);
        $requestDataSet->getAuth()->authenticateRequest($request);

        $entityManager = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        /* @var $entityManager \Doctrine\ORM\EntityManager */

        $startTime = microtime(true);
        $entityManager->beginTransaction();
        $response = static::$kernel->handle($request);
        $entityManager->rollback();
        $endTime = microtime(true);

        $statusCode = $response->getStatusCode();

        return new PerformanceTestSample(
            $requestDataSet->getRouteName(),
            $uri,
            ($endTime - $startTime) * 1000,
            0, // Currently, we are not able to measure query count
            $statusCode,
            $statusCode === $requestDataSet->getExpectedStatusCode()
        );
    }

    /**
     * @param \Tests\ShopBundle\Performance\Page\PerformanceTestSample[] $performanceTestSamples
     */
    private function doAssert(
        array $performanceTestSamples
    ) {
        $performanceTestSampleQualifier = new PerformanceTestSampleQualifier();

        $overallStatus = $performanceTestSampleQualifier->getOverallStatus($performanceTestSamples);

        switch ($overallStatus) {
            case PerformanceTestSampleQualifier::STATUS_OK:
            case PerformanceTestSampleQualifier::STATUS_WARNING:
                $this->assertTrue(true);
                return;
            case PerformanceTestSampleQualifier::STATUS_CRITICAL:
            default:
                $this->fail('Values are above critical threshold');
                return;
        }
    }

    /**
     * @param \Tests\ShopBundle\Performance\Page\PerformanceTestSample[] $performanceTestSamples
     * @param string $jmeterOutputFilename
     */
    private function exportJmeterCsvReport(array $performanceTestSamples, $jmeterOutputFilename)
    {
        $jmeterCsvReporter = new JmeterCsvReporter();
        $performanceResultsCsvExporter = new PerformanceResultsCsvExporter($jmeterCsvReporter);

        $performanceResultsCsvExporter->exportJmeterCsvReport($performanceTestSamples, $jmeterOutputFilename);
    }

    /**
     * @param \Tests\ShopBundle\Performance\Page\PerformanceTestSample[] $performanceTestSamples
     * @return \Tests\ShopBundle\Performance\Page\PerformanceTestSample[]
     */
    private function aggregatePerformanceTestSamplesByUrl(array $performanceTestSamples)
    {
        $performanceTestSamplesAggregator = new PerformanceTestSamplesAggregator();

        return $performanceTestSamplesAggregator->getPerformanceTestSamplesAggregatedByUrl($performanceTestSamples);
    }

    /**
     * @param \Tests\ShopBundle\Performance\Page\PerformanceTestSample[] $performanceTestSamples
     * @param \Symfony\Component\Console\Output\ConsoleOutput $consoleOutput
     */
    private function printPerformanceTestsSummary(array $performanceTestSamples, ConsoleOutput $consoleOutput)
    {
        $performanceTestSampleQualifier = new PerformanceTestSampleQualifier();
        $performanceTestSummaryPrinter = new PerformanceTestSummaryPrinter($performanceTestSampleQualifier);

        $performanceTestSummaryPrinter->printSummary($performanceTestSamples, $consoleOutput);
    }

    /**
     * @return \Shopsys\HttpSmokeTesting\RouterAdapter\SymfonyRouterAdapter
     */
    private function getRouterAdapter()
    {
        $router = static::$kernel->getContainer()->get('router');
        $routerAdapter = new SymfonyRouterAdapter($router);

        return $routerAdapter;
    }
}
