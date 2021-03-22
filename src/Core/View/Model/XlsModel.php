<?php

namespace ABSCore\Core\View\Model;

use Zend\View\Model\ViewModel;

use PHPExcel;

/**
 * XlsModel
 *
 * @uses ViewModel
 * @author Luiz Cunha <luiz.felipe@absoluta.net>
 */
class XlsModel extends ViewModel
{

    protected $filename;

    /**
     * Terminate is true to prevent layou rendering
     *
     * @var mixed
     * @access protected
     */
    protected $terminate = true;

    /**
     * Document
     *
     * @var PHPExcel
     * @access protected
     */
    protected $xlsDocument = null;

    /**
     * Default Style
     *
     * @var array
     * @access protected
     */
    protected $defaultStyle = [];

    /**
     * Header Style
     *
     * @var array
     * @access protected
     */
    protected $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => [
                'argb' => 'FFFFFFFF',
            ]
        ],
        'alignment' => [
            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        ],
        'borders' => [
            'right' => [
                'style' => \PHPExcel_Style_Border::BORDER_THIN,
            ],
            'top' => [
                'style' => \PHPExcel_Style_Border::BORDER_THIN,
            ],
            'left' => [
                'style' => \PHPExcel_Style_Border::BORDER_THIN,
            ],
            'bottom' => [
                'style' => \PHPExcel_Style_Border::BORDER_THIN,
            ],
        ],
        'fill' => [
            'type' => \PHPExcel_Style_Fill::FILL_SOLID,
            'startcolor' => [
                'argb' => 'CCCCCCCC',
            ],
        ],
    ];

    /**
     * Body Style
     *
     * @var array
     * @access protected
     */
    protected $bodyStyle = [
        'borders' => [
            'right' => [
                'style' => \PHPExcel_Style_Border::BORDER_THIN,
            ],
            'top' => [
                'style' => \PHPExcel_Style_Border::BORDER_THIN,
            ],
            'left' => [
                'style' => \PHPExcel_Style_Border::BORDER_THIN,
            ],
            'bottom' => [
                'style' => \PHPExcel_Style_Border::BORDER_THIN,
            ],
        ],
    ];

    /**
     * Set a custom Xls Document
     *
     * @param PHPExcel $document
     * @access public
     * @return $this
     */
    public function setXlsDocument(PHPExcel $document)
    {
        $this->xlsDocument = $document;
        return $this;
    }

    /**
     * Get Xls Document
     *
     * @access public
     * @return PHPExcel
     */
    public function getXlsDocument()
    {
        if (is_null($this->xlsDocument)) {
            $document = new PHPExcel();
            $this->xlsDocument = $document;
        }
        return $this->xlsDocument;
    }

    /**
     * Set Header Style
     *
     * @param array $style
     * @access public
     * @return $this
     */
    public function setHeaderStyle(array $style)
    {
        $currentStyle = $this->getHeaderStyle();
        $this->headerStyle = array_merge_recursive($currentStyle, $style);

        return $this;
    }

    /**
     * Get Header Style
     *
     * @access public
     * @return array
     */
    public function getHeaderStyle()
    {
        return $this->headerStyle;
    }

    /**
     * Set Body Style
     *
     * @param array $style
     * @access public
     * @return $this
     */
    public function setBodyStyle(array $style)
    {
        $currentStyle = $this->getBodyStyle();
        $this->bodyStyle = array_merge_recursive($currentStyle, $style);

        return $this;
    }

    /**
     * Get Body Style
     *
     * @access public
     * @return array
     */
    public function getBodyStyle()
    {
        return $this->bodyStyle;
    }

    /**
     * Set Headers values
     *
     * @param array $names Set of names to put in header
     * @param int $x       Initial position in X axis
     * @param int $y       Initial position in Y axis
     * @access public
     * @return $this
     */
    public function setHeaders(array $names, $x = 0, $y = 1)
    {
        $sheet = $this->getXlsDocument()->setActiveSheetIndex(0);
        $x = (int)$x;
        $y = (int)$y;

        $styleArray = $this->getHeaderStyle();
        foreach ($names as $name) {
            $sheet->setCellValueByColumnAndRow($x, $y, $name);
            $style = $sheet->getStyleByColumnAndRow($x, $y);
            $style->applyFromArray($styleArray);

            $x++;
        }

        return $this;
    }

    /**
     * Set Body content
     *
     * @param array $data A multidimension array with data
     * @param int $x      Initial position in X axis
     * @param int $y      Initial position in Y axis
     * @access public
     * @return $this
     */
    public function setBody(array $data, $x = 0, $y=2)
    {
        $sheet = $this->getXlsDocument()->setActiveSheetIndex(0);
        $originalX = $x = (int)$x;
        $y = (int)$y;

        foreach ($data as &$values) {
            $styleArray = $this->getBodyStyle();

            $this->setStyleInVariables($values, $styleArray);
            $this->destroyVariablesUnset($values);

            foreach ($values as $value) {
                $sheet->setCellValueByColumnAndRow($x, $y, $value);
                $style = $sheet->getStyleByColumnAndRow($x, $y);
                $style->applyFromArray($styleArray);
                $x++;
            }
            $x = $originalX;
            $y++;
        }
    }

    public function setStyleInVariables($variable, &$styleArray)
    {
        /** Check the content before coloring cell. */
        if (isset($variable['showallschedules']) && !empty($variable['showallschedules'])) {
            $styleArrayAllSchedules = array(
                'font'  => array(
                    'color' => array('rgb' => 'd3180f'),
                ));

            $styleArray = array_merge($styleArray, $styleArrayAllSchedules);
        }

        if (isset($variable['workdayIsNotUseful']) && !empty($variable['workdayIsNotUseful'])) {
            $styleArrayAllSchedules = array(
                'font'  => array(
                    'color' => array('rgb' => 'ffa500'),
                ));

            $styleArray = array_merge($styleArray, $styleArrayAllSchedules);
        }
    }

    /**
     * Remove variaveis que não estão sendo usadas.
     *
     * @param $values
     */
    public function destroyVariablesUnset(&$values)
    {
        if (isset($values['showallschedules']))  {
            unset($values['showallschedules']);
        }

        if (isset($values['workdayIsNotUseful']))  {
            unset($values['workdayIsNotUseful']);
        }


        if (isset($values['id'])) {
            unset($values['id']);
        }
    }

    /**
     * Set Default Style
     *
     * @param array $style
     * @access public
     * @return $this
     */
    public function setDefaultStyle(array $style)
    {
        $this->defaultStyle = $style;
        return $this;
    }

    /**
     * Get Default Style
     *
     * @access public
     * @return array
     */
    public function getDefaultStyle()
    {
        return $this->defaultStyle;
    }

    /**
     * Set a new Variable
     *
     * @param string $position    Position in String format like A1, B4, ...
     * @param string $value Value
     * @param array $style        Style of text, it override default styles
     * @access public
     * @return $this
     */
    public function setVariable($position, $value, array $style = [])
    {
        $style = array_merge_recursive($this->getDefaultStyle(), $style);
        $value = (string)$value;
        $position = (string)$position;
        $sheet = $this->getXlsDocument()->setActiveSheetIndex(0);
        $sheet->setCellValue($position, $value);
        $sheet->getStyle($position)->applyFromArray($style);

        return $this;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    public function getFilename()
    {
        return $this->filename ?: "download.xls";
    }
}
