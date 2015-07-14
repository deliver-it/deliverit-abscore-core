<?php

namespace ABSCore\Core\View\Strategy;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\View\Renderer\RendererInterface;
use Zend\View\ViewEvent;

/**
 * AcceptStrategy
 *
 * @uses ListenerAggregateInterface
 * @author Luiz Cunha <luiz.felipe@absoluta.net>
 */
class AcceptStrategy implements ListenerAggregateInterface
{
    /**
     * Set of Listeners
     *
     * @var array
     * @access protected
     */
    protected $listeners = [];

    /**
     * Set of rendereres
     *
     * @var mixed
     * @access protected
     */
    protected $renderers = [];

    /**
     * addRenderer
     *
     * @param mixed $mediaType
     * @param RendererInterface $renderer
     * @param array $additionalHeaders
     * @access public
     * @return $this
     */
    public function addRenderer($mediaType, RendererInterface $renderer, array $additionalHeaders = [])
    {
        $this->renderers[] = [
            'mediaType' => (string)$mediaType,
            'renderer' => $renderer,
            'additionalHeaders' => $additionalHeaders,
        ];

        return $this;
    }

    /**
     * Attach into event manager
     *
     * @param EventManagerInterface $events
     * @param int $priority
     * @access public
     * @return $this
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach('renderer', [$this, 'selectRenderer'], $priority);
        $this->listeners[] = $events->attach('response', [$this, 'injectResponse'], $priority);

        return $this;
    }

    /**
     * Detach from event manager
     *
     * @param EventManagerInterface $events
     * @access public
     * @return $this
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }

        return $this;
    }

    /**
     * Select which Renderer is requested
     *
     * @param ViewEvent $e
     * @access public
     * @return RendererInterface | null
     */
    public function selectRenderer(ViewEvent $e)
    {
        $request = $e->getRequest();
        $headers = $request->getHeaders();

        if (!$headers->has('accept')) {
            return;
        }

        $accept = $headers->get('accept');
        $availables = $this->getRenderers();
        foreach ($accept->getPrioritized() as $mediaType) {
            foreach ($availables as $type) {
                if (strpos($mediaType->getTypeString(), $type['mediaType']) === 0) {
                    return $type['renderer'];
                }
            }
        }

        return;
    }

    /**
     * Inject Response
     *
     * @param ViewEvent $e
     * @access public
     * @return null
     */
    public function injectResponse(ViewEvent $e)
    {
        $renderer = $e->getRenderer();
        $response = $e->getResponse();
        $result   = $e->getResult();

        $availableRenderers = $this->getRenderers();
        $exists = false;
        foreach ($availableRenderers as $availableRenderer) {
            if ($renderer === $availableRenderer['renderer']) {
                $headers = $response->getHeaders();
                if (!array_key_exists('Content-Type', $availableRenderer['additionalHeaders'])) {
                    $headers->addHeaderLine('Content-Type', $availableRenderer['mediaType']);
                }
                foreach ($availableRenderer['additionalHeaders'] as $key => $value) {
                    $headers->addHeaderLine($key, $value);
                }
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            return;
        }

        $response->setContent($result);
    }

    /**
     * Get Renderers
     *
     * @access public
     * @return array
     */
    public function getRenderers()
    {
        return $this->renderers;
    }
}
