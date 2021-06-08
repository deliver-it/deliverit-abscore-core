<?php


namespace ABSCore\Core\View\Model;

use Zend\View\Model\ViewModel;

use PHPExcel;

/**
 * Class for modeling class closing hours report.
 *
 * @package ABSCore\Core\View\Model
 *
 *
 * @author Marcelo Jean <marcelojeam1@gmail.com>
 */
class ClosingHours extends XlsModel
{

    /**
     * Add header for occurrences.
     *
     * @param array $names
     * @param $rows
     * @param int $x
     * @param int $y
     * @return ClosingHours
     */
    public function addOcurrenceHeader(array $names, &$rows, $x = 0, $y = 1)
    {
        $y = (int) $y + $rows + 3;

        return $this->setHeaders($names, $x, $y);
    }

    /**
     * Add data for occurrences.
     *
     * @param array $data
     * @param $rows
     * @param int $x
     * @param int $y
     */
    public function addOccurrencesData(array $data, $rows, $x = 0, $y=2)
    {
        $sheet = $this->getXlsDocument()->setActiveSheetIndex(0);
        $originalX = $x = (int)$x;
        $y = (int)$y + $rows + 4;

        foreach ($data as $name => &$values) {
            $styleArray = $this->getBodyStyle();

            $this->setStyleInVariables($values, $styleArray);
            $this->destroyVariablesUnset($values);

            $sheet->setCellValueByColumnAndRow($x, $y, $name);
            $style = $sheet->getStyleByColumnAndRow($x, $y);
            $style->applyFromArray($styleArray);

            if (isset($values['observations']) && !empty($values['observations'])) {
                foreach ($values['observations'] as $value) {
                    $y++;

                    $sheet->setCellValueByColumnAndRow($x + 1, $y, $value['date'])
                        ->getStyle($sheet->getActiveCell())
                        ->getNumberFormat()
                        ->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);


                    $style = $sheet->getStyleByColumnAndRow($x + 1, $y);
                    $style->applyFromArray($styleArray)
                        ->getAlignment()
                        ->setHorizontal('left');


                    $sheet->setCellValueByColumnAndRow($x + 2, $y, $value['observation']);

                    $style = $sheet->getStyleByColumnAndRow($x + 2, $y);
                    $style->applyFromArray($styleArray)
                        ->getAlignment()
                        ->setHorizontal('left');
                }
            }

            if (isset($values['workdayUseful'])) {
                foreach ($values['workdayUseful'] as $value) {
                    $hoursWorked = $value['hours_worked'] !== '00:00:00' ? $value['hours_worked'] : 'Dia Inteiro';
                    $message = current($value['motives_non_working_days'])['name'] . ' - (' . $hoursWorked . ')';

                    $y++;
                    $sheet->setCellValueByColumnAndRow($x + 1, $y, $value['accounted_day'])
                        ->getStyle($sheet->getActiveCell())
                        ->getNumberFormat()
                        ->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);

                    $style = $sheet->getStyleByColumnAndRow($x + 1, $y);
                    $style->applyFromArray($styleArray)
                        ->getAlignment()
                        ->setHorizontal('left');

                    $sheet->setCellValueByColumnAndRow($x + 2, $y,  $message);
                    $style = $sheet->getStyleByColumnAndRow($x + 2, $y);
                    $style->applyFromArray($styleArray)
                        ->getAlignment()
                        ->setHorizontal('left');
                }
            }

            $x = $originalX;
            $y++;
        }
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

            $projects = $values['projects'] ?: [];
            unset($values['projects']);

            foreach ($values as $value) {
                $sheet->setCellValueByColumnAndRow($x, $y, $value);
                $style = $sheet->getStyleByColumnAndRow($x, $y);
                $style->applyFromArray($styleArray)
                    ->getAlignment()
                    ->setHorizontal('left');
                $x++;
            }

            if (!empty($projects)) {
                $y += 2;

                $styleArray2 = array_merge($styleArray, array(
                    'font'  => array(
                        'bold' => true,
                        'color' => array('rgb' => 'ffffff')
                    ),
                    'fill' => array(
                        'type' => 'solid',
                        'color' => array('rgb' => 'cccccc')
                    ),
                ));

                $sheet->setCellValueByColumnAndRow($x - 2, $y, "Projeto");
                $style = $sheet->getStyleByColumnAndRow($x - 2, $y);
                $style->applyFromArray($styleArray2)
                    ->getAlignment()
                    ->setHorizontal('center');

                $sheet->setCellValueByColumnAndRow($x - 1, $y, "Horas Realizadas");
                $style = $sheet->getStyleByColumnAndRow($x - 1, $y);
                $style->applyFromArray($styleArray2)
                    ->getAlignment()
                    ->setHorizontal('center');

                foreach ($projects as $value) {
                    $y++;
                    $sheet->setCellValueByColumnAndRow($x - 2, $y, $value['project']['name']);
                    $style = $sheet->getStyleByColumnAndRow($x - 2, $y);
                    $style->applyFromArray($styleArray)
                        ->getAlignment()
                        ->setHorizontal('left');

                    $sheet->setCellValueByColumnAndRow($x - 1, $y, $value['time_worked']);
                    $style = $sheet->getStyleByColumnAndRow($x - 1, $y);
                    $style->applyFromArray($styleArray)
                        ->getAlignment()
                        ->setHorizontal('left');
                }
                $y++;
            }

            $x = $originalX;
            $y++;
        }
    }

    /**
     * Add footer to calculate total hours performed and planned.
     *
     * @param array $names
     * @param $data
     * @param $rows
     * @param int $x
     * @param int $y
     */
    public function setFooter(array $names, $data, $rows, $x = 0, $y = 1)
    {
        $sheet = $this->getXlsDocument()->setActiveSheetIndex(0);
        $x = (int)$x;
        $y = (int)$y + $rows + 1;

        $styleArray = $this->getHeaderStyle();

        $sheet->setCellValueByColumnAndRow($x, $y, current($names));
        $style = $sheet->getStyleByColumnAndRow($x, $y);
        $style->applyFromArray($styleArray);

        $sheet->setCellValueByColumnAndRow($x + 1, $y, $data['totalPerformed']);
        $style = $sheet->getStyleByColumnAndRow($x + 1, $y);
        $style->applyFromArray($styleArray)
            ->getAlignment()
            ->setHorizontal('left');

        $sheet->setCellValueByColumnAndRow($x + 2, $y, $data['totalPlanned']);
        $style = $sheet->getStyleByColumnAndRow($x + 2, $y);
        $style->applyFromArray($styleArray)
            ->getAlignment()
            ->setHorizontal('left');
    }
}