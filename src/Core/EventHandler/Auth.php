<?php
namespace ABSCore\Core\EventHandler;

use Zend\Authentication\AuthenticationServiceInterface;
use Zend\Mvc\Router;

/**
 * Class to handle authentication events
 */
class Auth extends AbstractEventHandler
{
    const ALLOW_TYPE = 'allow_type';
    const DENY_TYPE = 'deny_type';

    /**
     * Authentication Service
     *
     * @var AuthenticationServiceInterface
     * @access protected
     */
    protected $auth;

    /**
     * Verification Type
     *
     * @var string
     * @access protected
     */
    protected $type;

    /**
     * Routes
     *
     * @var array
     * @access protected
     */
    protected $routes = [];

    /**
     * Routes to redirect
     *
     * @var array
     * @access protected
     */
    protected $redirectRoutes = [];

    /**
     * Options to redirect
     *
     * @var array
     * @access protected
     */
    protected $redirectOptions = [];

    /**
     * Params to create redirect URL
     *
     * @var array
     * @access protected
     */
    protected $redirectParams = [];

    /**
     * Class constructor
     *
     * @param AuthenticationServiceInterface $auth Authentication service
     * @param string $type Type of verification
     * @access public
     */
    public function __construct(AuthenticationServiceInterface $auth, $type = null)
    {
        $this->setAuth($auth)
             ->setType($type);
    }

    /**
     * Set type of verification
     *
     * @param string $type DENY_TYPE | ALLOW_TYPE
     * @access public
     * @return $this
     */
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

    /**
     * Get type of verification
     *
     * @access public
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Match a config route with the current route
     *
     * @param array $needle                 Route config
     * @param Router\RouteMatch $haystack   Current route
     * @access protected
     * @return boolean
     */
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

    /**
     * Format route configuration
     *
     * @param mixed $route route configuration
     * @access protected
     * @return $this
     */
    protected function formatRoute($route)
    {
        if (is_string($route)) {
            $route = ['route_name' => $route];
        }

        return $route;
    }

    /**
     * Get routes configuration
     *
     * @access public
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Set configuration routes
     *
     * @param array $routes
     * @access public
     * @return $this
     */
    public function setRoutes(array $routes)
    {
        $this->routes = [];
        foreach($routes as $key => $route) {
            $this->routes[] = $this->formatRoute($route);
        }

        return $this;
    }

    /**
     * Set autehtication service
     *
     * @param AuthenticationServiceInterface $auth
     * @access public
     * @return $this
     */
    public function setAuth(AuthenticationServiceInterface $auth)
    {
        $this->auth = $auth;
        return $this;
    }

    /**
     * Get authentication service
     *
     * @access public
     * @return AuthenticationServiceInterface
     */
    public function getAuth()
    {
        return $this->auth;
    }

    /**
     * isAllowedRoute
     *
     * @param Router\RouteMatch $needle
     * @param array $haystack
     * @access protected
     * @return boolean
     */
    protected function isAllowedRoute(Router\RouteMatch $needle, array $haystack)
    {
        $exists = false;
        $isAllowed = true;
        foreach($haystack as $route) {
            $match = $this->matchRoute($route, $needle);
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

    /**
     * Get redirect routes
     *
     * @access public
     * @return array
     */
    public function getRedirectRoutes()
    {
        return $this->redirectRoutes;
    }

    /**
     * Set redirect routes
     *
     * @param array $redirectRoutes
     * @access public
     * @return $this
     */
    public function setRedirectRoutes($redirectRoutes)
    {
        $this->redirectRoutes = $redirectRoutes;
        return $this;
    }

    /**
     * Set redirect route params
     *
     * @param array $params
     * @param array $options
     * @access public
     * @return $this
     */
    public function setRedirectRouteParams(array $params, array $options)
    {
        $this->redirectParams = $params;
        $this->redirectOptions = $options;
        return $this;
    }

    /**
     * Set Redirect Url
     *
     * @param string $url
     * @access public
     * @return $this
     */
    public function setRedirectUrl($url)
    {
        $this->redirectUrl = $url;
        return $this;
    }

    /**
     * Get Redirect Params
     *
     * @access public
     * @return array
     */
    public function getRedirectParams()
    {
        return $this->redirectParams;
    }

    /**
     * Get Redirect Options
     *
     * @access public
     * @return array
     */
    public function getRedirectOptions()
    {
        return $this->redirectOptions;
    }

    /**
     * Get Redirect Url
     *
     * @param mixed $router
     * @access public
     * @return string
     */
    public function getRedirectUrl($router)
    {
        if (empty($this->redirectUrl)) {
            $this->redirectUrl = $router->assemble($this->getRedirectParams(), $this->getRedirectOptions());
        }

        return $this->redirectUrl;
    }

    /**
     * Set params into response to make a redirect
     *
     * @param mixed $response
     * @param mixed $event
     * @access protected
     * @return $this
     */
    protected function setReponseRedirect($response, $event)
    {
        $router = $event->getRouter();
        $response->getHeaders()->addHeaderLine('Location', $this->getRedirectUrl($router));
        $response->setStatusCode(302);
        return $this;
    }

    /**
     * Configure reponse to respond as unaauthorized
     *
     * @param mixed $response
     * @access protected
     * @return $this
     */
    protected function setResponseUnauthorized($response)
    {
        $response->setStatusCode(401);
        return $this;
    }

    /**
     * Implementation of abstract method, it is called when event happens
     *
     * @param mixed $event
     * @access public
     * @return mixed
     */
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
