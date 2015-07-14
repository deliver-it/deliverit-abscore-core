<?php

namespace ABSCore\Core\View\Renderer;

use Zend\View\Renderer\RendererInterface;
use Zend\View\Resolver\ResolverInterface;

use Application\View\Model\XlsModel;

use PHPExcel_IOFactory;

/**
 * XlsRenderer
 *
 * @uses RendererInterface
 * @author Luiz Cunha <luiz.felipe@absoluta.net>
 */
class XlsRenderer implements RendererInterface
{
    /**
     * {@inheritDoc}
     */
    public function getEngine()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function setResolver(ResolverInterface $resolver)
    {
        return $this;
    }

    /**
     * Render Xls Model
     *
     * @param mixed $nameOrModel Expect a XlsModel, if it isn't then do nothing
     * @param mixed $values
     * @access public
     * @return string
     */
    public function render($nameOrModel, $values = null)
    {
        if (!is_object($nameOrModel) || !$nameOrModel instanceof XlsModel) {
            return;
        }
        $document = $nameOrModel->getXlsDocument();
        $writer = PHPExcel_IOFactory::createWriter($document, 'Excel2007');
        ob_start();
        $writer->save('php://output');
        $result = ob_get_clean();
        return $result;
    }
}
