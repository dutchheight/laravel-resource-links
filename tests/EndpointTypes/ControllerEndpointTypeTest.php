<?php

namespace Spatie\LaravelEndpointResources\Tests\EndpointTypes;

use Spatie\LaravelEndpointResources\EndpointTypes\ControllerEndpointType;
use Spatie\LaravelEndpointResources\Tests\Fakes\TestController;
use Spatie\LaravelEndpointResources\Tests\Fakes\TestModel;
use Spatie\LaravelEndpointResources\Tests\Fakes\TestControllerWithSpecifiedEndpoints;
use Spatie\LaravelEndpointResources\Tests\TestCase;

class ControllerEndpointTypeTest extends TestCase
{
    /** @var \Spatie\LaravelEndpointResources\Tests\Fakes\TestModel */
    private $testModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testModel = TestModel::create([
            'id' => 1,
            'name' => 'TestModel',
        ]);
    }

    /** @test */
    public function it_will_only_give_local_endpoints()
    {
        $indexAction = [TestController::class, 'index'];
        $showAction = [TestController::class, 'show'];

        $this->fakeRouter->get('', $indexAction);
        $this->fakeRouter->get('{testModel}', $showAction);

        $endpointType = new ControllerEndpointType(TestController::class);

        $endpoints = $endpointType->getEndpoints($this->testModel);

        $this->assertEquals([
            'show' => [
                'method' => 'GET',
                'action' => action($showAction, $this->testModel),
            ],
        ], $endpoints);
    }

    /** @test */
    public function it_will_only_give_collection_endpoints()
    {
        $indexAction = [TestController::class, 'index'];
        $showAction = [TestController::class, 'show'];

        $this->fakeRouter->get('', $indexAction);
        $this->fakeRouter->get('{testModel}', $showAction);

        $endpointType = new ControllerEndpointType(TestController::class);

        $endpoints = $endpointType->getCollectionEndpoints();

        $this->assertEquals([
            'index' => [
                'method' => 'GET',
                'action' => action($indexAction),
            ],
        ], $endpoints);
    }

    /** @test */
    public function it_will_only_create_endpoints_that_can_be_constructed()
    {
        $action = [TestController::class, 'show'];

        $this->fakeRouter->get('{testModel}', $action);

        $endpointType = new ControllerEndpointType(TestController::class);

        $endpoints = $endpointType->getEndpoints();

        $this->assertEquals([], $endpoints);
    }

    /** @test */
    public function it_will_create_all_possible_routes_when_a_model_is_available()
    {
        $showAction = [TestController::class, 'show'];
        $updateAction = [TestController::class, 'update'];

        $this->fakeRouter->get('{testModel}', $showAction);
        $this->fakeRouter->route('PATCH', '{testModel}', $updateAction);

        $testModel = TestModel::create([
            'name' => 'TestModel',
        ]);

        $endpointType = new ControllerEndpointType(TestController::class);

        $endpoints = $endpointType->getEndpoints($testModel);

        $this->assertEquals([
            'show' => [
                'method' => 'GET',
                'action' => action($showAction, $testModel),
            ],
            'update' => [
                'method' => 'PATCH',
                'action' => action($updateAction, $testModel),
            ],
        ], $endpoints);
    }

    /** @test */
    public function it_will_only_create_routes_based_upon_the_end_point_methods_property()
    {
        $endPointAction = [TestControllerWithSpecifiedEndpoints::class, 'endpoint'];
        $nonEndpointAction = [TestControllerWithSpecifiedEndpoints::class, 'nonEndpoint'];

        $this->fakeRouter->get('/a/{testModel}', $endPointAction);
        $this->fakeRouter->get('/b/{testModel}', $nonEndpointAction);

        $testModel = TestModel::create([
            'name' => 'TestModel',
        ]);

        $endpointType = new ControllerEndpointType(TestControllerWithSpecifiedEndpoints::class);

        $endpoints = $endpointType->getEndpoints($testModel);

        $this->assertEquals([
            'endpoint' => [
                'method' => 'GET',
                'action' => action($endPointAction, $testModel),
            ],
        ], $endpoints);
    }

    /** @test */
    public function it_will_only_create_routes_based_upon_the_collection_end_point_methods_property()
    {
        $collectionEndpoint = [TestControllerWithSpecifiedEndpoints::class, 'collectionEndpoint'];
        $nonCollectionEndpoint = [TestControllerWithSpecifiedEndpoints::class, 'nonCollectionEndpoint'];

        $this->fakeRouter->get('/a/', $collectionEndpoint);
        $this->fakeRouter->get('/b/', $nonCollectionEndpoint);

        $endpointType = new ControllerEndpointType(TestControllerWithSpecifiedEndpoints::class);

        $endpoints = $endpointType->getCollectionEndpoints();

        $this->assertEquals([
            'collectionEndpoint' => [
                'method' => 'GET',
                'action' => action($collectionEndpoint),
            ],
        ], $endpoints);
    }
}
