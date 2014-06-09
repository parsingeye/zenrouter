<?php namespace Gil\ZenRouter;

use BadMethodCallException;
use Gil\ZenRouter\Contracts\ContainerInterface;

class Router {

    /**
     * Route collection
     *
     * @var array
     */
    protected $routes = [];

    /**
     * DI Container
     *
     * @var null
     */
    protected $container;

    /**
     * Supported Verbs
     *
     * @var array
     */
    protected $verbs = ['get', 'post', 'put', 'patch', 'delete', 'options'];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param $method
     * @param $params
     * @throws \BadMethodCallException
     */
    public function __call($method, $params)
    {
        if (in_array($method, $this->verbs))
        {
            if (isset($params[0]) && isset($params[1]))
            {
                $this->routes[$method][$params[0]] = $params[1];
            }
        }
        else
        {
            throw new BadMethodCallException();
        }
    }

    /**
     * @return string
     */
    protected function getPath()
    {
        $path = '/';
        if (!empty($_SERVER['PATH_INFO']))
        {
            $path = $_SERVER['PATH_INFO'];
        }
        else if (!empty($_SERVER['REQUEST_URI']))
        {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }

        return $path;
    }

    /**
     * @return mixed
     */
    public function route()
    {
        $handler = null;

        $path = $this->getPath();

        $method = strtolower($_SERVER['REQUEST_METHOD']);

        if (isset($this->routes[$method][$path]))
        {
            list($handlerClass, $handlerMethod) = explode('@', $this->routes[$method][$path]);

            return $this->callAction($handlerClass, $handlerMethod);
        }

        $this->handle404();
    }

    /**
     * Function to handle cases when route is not found, call handler of 404 if defined else
     * sends a 404 header
     */
    public function handle404()
    {
        /* Call '404' route if it exists */
        if (isset($this->routes['get']['404']))
        {
            call_user_func($this->routes['get']['404']);
        }
        else
        {
            http_response_code(404);
        }

        die('404 Not Found');
    }

    /**
     * @param $handlerClass
     * @param $handlerMethod
     * @return mixed
     * @throws BadMethodCallException
     */
    public function callAction($handlerClass, $handlerMethod)
    {
        if (class_exists($handlerClass))
        {
            if ($this->container)
            {
                $handlerInstance = $this->container->get($handlerClass);
            }
            else
            {
                $handlerInstance = new $handlerClass;
            }

            return call_user_func([$handlerInstance, $handlerMethod]);
        }

        throw new BadMethodCallException(sprintf('Class or function %s not found', $handlerClass));
    }
}