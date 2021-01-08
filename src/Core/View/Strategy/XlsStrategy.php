<?php

namespace ABSCore\Core\View\Strategy;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\View\ViewEvent;

use ABSCore\Core\View\Renderer\XlsRenderer;
use ABSCore\Core\View\Model\XlsModel;

/**
 * XlsStrategy
 *
 * @uses ListenerAggregateInterface
 * @author Luiz Cunha <luiz.felipe@absoluta.net>
 */
class XlsStrategy implements ListenerAggregateInterface
{
    /**
     * Set of Listeners
     *
     * @var array
     * @access protected
     */
    protected $listeners = [];

    /**
     * Xls renderer
     *
     * @var XlsRenderer
     * @access protected
     */
    protected $renderer;

    /**
     * Constructor
     *
     * @param XlsRenderer $renderer
     * @access public
     */
    public function __construct(XlsRenderer $renderer)
    {
        $this->renderer = $renderer;
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
        $renderer = $e->getModel();
        if ($e->getModel() instanceof XlsModel) {
            return $this->renderer;
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
        $result = $e->getResult();


        if ($this->renderer !== $renderer) {
            return;
        }
        $headers = $response->getHeaders();

        $filename = $e->getModel()->getFilename();

        $headers->addHeaders([
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' =>'attachment; filename='.$filename,
            'Content-Transfer-Encoding' => 'binary',
        ]);

        $response->setContent($result);
    }
}
