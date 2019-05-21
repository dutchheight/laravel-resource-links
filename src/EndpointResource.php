<?php

namespace Spatie\LaravelEndpointResources;

use Illuminate\Support\Arr;
use Spatie\LaravelEndpointResources\EndpointTypes\ActionEndpointType;
use Spatie\LaravelEndpointResources\EndpointTypes\ControllerEndpointType;
use Spatie\LaravelEndpointResources\EndpointTypes\EndpointType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Spatie\LaravelEndpointResources\EndpointTypes\InvokableControllerEndpointType;

class EndpointResource extends JsonResource
{
    /** @var \Illuminate\Database\Eloquent\Model */
    protected $model;

    /** @var string */
    protected $endpointResourceType;

    /** @var \Illuminate\Support\Collection */
    protected $endPointTypes;

    public function __construct(Model $model = null, string $endpointResourceType = null)
    {
        parent::__construct($model);

        $this->model = $model;
        $this->endpointResourceType = $endpointResourceType ?? EndpointResourceType::ITEM;
        $this->endPointTypes = new Collection();
    }

    public function addController(string $controller, $parameters = null): JsonResource
    {
        if (method_exists($controller, '__invoke')) {
            return $this->addInvokableController($controller, $parameters);
        }

        $this->endPointTypes->push(new ControllerEndpointType(
            $controller,
            $this->resolveProvidedParameters($parameters)
        ));

        return $this;
    }

    public function addAction(array $action, $parameters = null, string $httpVerb = null): JsonResource
    {
        $this->endPointTypes->push(new ActionEndpointType(
            $action,
            $this->resolveProvidedParameters($parameters),
            $httpVerb
        ));

        return $this;
    }

    public function addInvokableController(string $controller, $parameters = null) : JsonResource
    {
        $this->endPointTypes->push(new InvokableControllerEndpointType(
            $controller,
            $this->resolveProvidedParameters($parameters)
        ));

        return $this;
    }

    public function mergeCollectionEndpoints(): JsonResource
    {
        $this->endpointResourceType = EndpointResourceType::MULTI;

        return $this;
    }

    public function toArray($request)
    {
        return $this->endPointTypes->mapWithKeys(function (EndPointType $endpointType) {
            if ($endpointType instanceof MultiEndpointType) {
                return $this->resolveEndpointsFromMultiEndpointType($endpointType);
            }

            return $endpointType->getEndpoints($this->model);
        });
    }

    protected function resolveProvidedParameters($parameters = null): array
    {
        $parameters = Arr::wrap($parameters);

        return count($parameters) === 0
            ? request()->route()->parameters()
            : $parameters;
    }

    protected function resolveEndpointsFromMultiEndpointType(MultiEndpointType $multiEndpointType): array
    {
        if ($this->endpointResourceType === EndpointResourceType::ITEM) {
            return $multiEndpointType->getEndpoints($this->model);
        }

        if ($this->endpointResourceType === EndpointResourceType::COLLECTION) {
            return $multiEndpointType->getCollectionEndpoints();
        }

        if ($this->endpointResourceType === EndpointResourceType::MULTI) {
            return array_merge(
                $multiEndpointType->getEndpoints($this->model),
                $multiEndpointType->getCollectionEndpoints()
            );
        }

        return [];
    }
}
