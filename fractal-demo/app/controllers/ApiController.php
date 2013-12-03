<?php

use League\Fractal\ItemResource;
use League\Fractal\CollectionResource;
use League\Fractal\PaginatorResource;
use League\Fractal\ResourceManager;

class ApiController extends Controller
{
    protected $statusCode = 200;

    public function __construct(ResourceManager $fractal)
    {
        $this->fractal = $fractal;

        // Are we going to try and include embedded data?
        $this->fractal->setRequestedScopes(explode(',', Input::get('include')));
    }

    /**
     * Getter for statusCode
     *
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
    
    /**
     * Setter for statusCode
     *
     * @param int $statusCode Value to set
     *
     * @return self
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }
    
    protected function respondWithItem(array $item, $callback)
    {
        $resource = new ItemResource($item, $callback);

        $rootScope = $this->fractal->createData($resource);

        $output = [
            'data' => $rootScope->toArray(),
        ];

        return $this->respondWithArray($output);
    }

    protected function respondWithCollection(array $collection, $callback)
    {
        $resource = new CollectionResource($collection, $callback);

        $rootScope = $this->fractal->createData($resource);

        $output = [
            'data' => $rootScope->toArray(),
        ];

        return $this->respondWithArray($output);
    }

    protected function respondWithPaginator(array $collection, $callback)
    {
        // 
        $resource = new PaginatorResource($collection, $callback);

        // Pull the actual paginator from the resource
        $paginator = $collection->getPaginator();

        $pagination = [
            'total' => (int) $paginator->getTotal(),
            'count' => (int) $paginator->count(),
            'per_page' => (int) $paginator->getPerPage(),
            'current_page' => (int) $paginator->getCurrentPage(),
            'total_pages' => (int) $paginator->getLastPage(),
        ];

        $pagination['links'] = [];

        $paginator->appends(array_except(Request::query(), ['page']));

        if ($paginator->getCurrentPage() > 1) {
            $pagination['links']['previous'] = $paginator->getUrl($paginator->getCurrentPage() - 1);
        }

        if ($paginator->getCurrentPage() < $paginator->getLastPage()) {
            $pagination['links']['next'] = $paginator->getUrl($paginator->getCurrentPage() + 1);
        }

        $rootScope = $this->fractal->createData($collection);

        $output = [
            'pagination' => $pagination,
            'data' => $rootScope->toArray(),
        ];

        return $this->respondWithArray($output);
    }

    protected function respondWithArray(array $array, array $headers = [])
    {
        $response = Response::json($array, $this->statusCode, $headers);

        // $response->header('Content-Type', 'application/json');

        return $response;
    }

}