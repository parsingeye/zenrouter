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
     * Handles 404
     */
    protected $notFoundHandler;

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

        if (!empty ($_SERVER['PATH_INFO']))
        {
            $path = $_SERVER['PATH_INFO'];
        }
        else if (!empty ($_SERVER['REQUEST_URI']))
        {
            $path = (strpos($_SERVER['REQUEST_URI'], '?') > 0) ? strstr(
                $_SERVER['REQUEST_URI'],
                '?',
                true
            ) : $_SERVER['REQUEST_URI'];
        }

        return $path;
    }

    /**
     * @return mixed
     */
    public function route()
    {
        $path = $this->getPath();

        $method = strtolower($_SERVER['REQUEST_METHOD']);

        if (isset($this->routes[$method][$path]))
        {
            $action = $this->routes[$method][$path];

            // check if action is function or closure
            if (is_callable($action))
            {
                return call_user_func($action);
            }
            else
            {
                $actionArray = explode('@', $this->routes[$method][$path]);

                if ($actionArray[0] and $actionArray[1])
                {
                    return $this->callControllerAction($actionArray[0], $actionArray[1]);
                }
            }
        }

        $this->handle404();
    }

    /**
     * @param $handler
     */
    public function notFound($handler)
    {
        $this->notFoundHandler = $handler;
    }

    /**
     * Function to handle cases when route is not found, call handler of 404 if defined else
     * sends a 404 header
     */
    public function handle404()
    {
        header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");

        if (isset ($this->notFoundHandler) and is_callable($this->notFoundHandler))
        {
            return call_user_func($this->notFoundHandler);
        }

        die('404 Not Found');
    }

    /**
     * @param $actionClass
     * @param $actionMethod
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function callControllerAction($actionClass, $actionMethod)
    {
        if (class_exists($actionClass))
        {
            if ($this->container)
            {
                $actionInstance = $this->container->get($actionClass);
            }
            else
            {
                $actionInstance = new $actionClass;
            }

            return call_user_func([$actionInstance, $actionMethod]);
        }

        throw new BadMethodCallException(sprintf('Class or function %s not found', $actionClass));
    }
}