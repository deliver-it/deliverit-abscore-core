<?php


namespace ABSCore\Core\View\Model;

use Zend\View\Model\ViewModel;

use PHPExcel;

/**
 * Class for modeling class professional allocations report.
 *
 * @package ABSCore\Core\View\Model
 *
 *
 * @author Marcelo Jean <marcelojeam1@gmail.com>
 */
class ProfessionalAllocations extends XlsModel
{

    /**
     * Add header for total items.
     *
     * @param array $names
     * @param $rows
     * @param int $x
     * @param int $y
     * @return ClosingHours
     */
    public function addTotalHeader(array $names, $rows, $x = 0, $y = 1)
    {
        $y = (int) $y + $rows + 1;

        return $this->setHeaders($names, $x, $y);
    }

    /**
     * Add data for total records.
     *
     * @param array $data
     * @param $rows
     * @param int $x
     * @param int $y
     */
    public function addTotalData(array $data, $rows, $x = 0, $y=2)
    {
        $sheet = $this->getXlsDocument()->setActiveSheetIndex(0);
        $y = (int)$y + $rows + 1;

        foreach ($data as $values) {
            $styleArray = $this->getBodyStyle();

            $sheet->setCellValueByColumnAndRow($x + 1, $y, $values);
            $style = $sheet->getStyleByColumnAndRow($x + 1, $y);
            $style->applyFromArray($styleArray);

            $x++;
        }
    }
}
