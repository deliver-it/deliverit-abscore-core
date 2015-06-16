<?php
namespace ABSCore\Core\EventHandler;

use Zend\Authentication\AuthenticationServiceInterface;
use Zend\Mvc\Router;

/**
 * Class to handle authentication events
 *
 * @uses AbstractEventHandler
 */
class Auth extends AbstractEventHandler
{
    const ALLOW_TYPE = 'allow_type';
    const DENY_TYPE = 'deny_type';

    /**
     * Authentication Service
     *
     * @var AuthenticationServiceInterface
     */
    protected $auth;

    /**
     * Routes
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Routes to redirect
     *
     * @var array
     */
    protected $redirectRoutes = [];

    /**
     * Options to redirect
     *
     * @var array
     */
    protected $redirectOptions = [];

    /**
     * redirectParams
     *
     * @var mixed
     */
    protected $redirectParams = [];

    public function __construct(AuthenticationServiceInterface $auth, $type = '')
    {
        $this->setAuth($auth)
             ->setType($type);
    }

    public function setType($type)
    {
        switch($type){
            case self::DENY_TYPE:
                $this->type = $type;
                break;
            case self::ALLOW_TYPE:
            default:
                $this->type = self::ALLOW_TYPE;
                break;
        }
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    protected function matchRoute(array $needle, Router\RouteMatch $haystack)
    {
        foreach($needle as $key => $value) {
            if ($key == 'route_name') {
                if ($haystack->getMatchedRouteName() != $value) {
                    return false;
                }
            } else {
                $param = $haystack->getParam($key);
                $value = "/^$value$/";
                if (preg_match($value, $param) !== 1) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function formatRoute($route)
    {
        if (is_string($route)) {
            $route = ['route_name' => $route];
        }

        return $route;
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public function setRoutes(array $routes)
    {
        $this->routes = [];
        foreach($routes as $key => $route) {
            $this->routes[] = $this->formatRoute($route);
        }

        return $this;
    }

    public function setAuth($auth)
    {
        $this->auth = $auth;
        return $this;
    }

    public function getAuth()
    {
        return $this->auth;
    }

    protected function isAllowedRoute($routeMatch, $haystack)
    {
        $exists = false;
        $isAllowed = true;
        foreach($haystack as $route) {
            $match = $this->matchRoute($route, $routeMatch);
            if ($this->getType() == self::DENY_TYPE) {
                if ($match) {
                    $isAllowed = false;
                    break;
                }
            } else if ($match) {
                $exists = true;
                break;
            }
        }

        if (!$exists && $this->getType() == self::ALLOW_TYPE) {
            $isAllowed = false;
        }

        return $isAllowed;
    }

    public function getRedirectRoutes()
    {
        return $this->redirectRoutes;
    }

    public function setRedirectRoutes($redirectRoutes)
    {
        $this->redirectRoutes = $redirectRoutes;
        return $this;
    }

    public function setRedirectRouteParams(array $params, array $options)
    {
        $this->redirectParams = $params;
        $this->redirectOptions = $options;
        return $this;
    }

    public function setRedirectUrl($url)
    {
        $this->redirectUrl = $url;
        return $this;
    }

    public function getRedirectParams()
    {
        return $this->redirectParams;
    }

    public function getRedirectOptions()
    {
        return $this->redirectOptions;
    }

    public function getRedirectUrl($router)
    {
        if (empty($this->redirectUrl)) {
            $this->redirectUrl = $router->assemble($this->getRedirectParams(), $this->getRedirectOptions());
        }

        return $this->redirectUrl;
    }

    protected function setReponseRedirect($response, $event)
    {
        $router = $event->getRouter();
        $response->getHeaders()->addHeaderLine('Location', $this->getRedirectUrl($router));
        $response->setStatusCode(302);
        return $this;
    }

    protected function setResponseUnauthorized($response)
    {
        $response->setStatusCode(401);
        return $this;
    }

    public function invoke($event)
    {
        $auth = $this->getAuth();
        if (!$auth->hasIdentity()) {
            $routeMatch = $event->getRouteMatch();
            $request = $event->getRequest();
            $isAllowed = $this->isAllowedRoute($routeMatch, $this->getRoutes());
            if (!$isAllowed) {
                $response = $event->getResponse();
                $isRedirect = false;
                var_dump($this->getRedirectRoutes());
                foreach($this->getRedirectRoutes() as $route) {
                    if ($this->matchRoute($route, $routeMatch)) {
                        $isRedirect = true;
                        $this->setReponseRedirect($response, $event);
                        break;
                    }
                }
                if (!$isRedirect) {
                    $this->setResponseUnauthorized($response);
                }

                return $response;
            }
        }
    }
}
