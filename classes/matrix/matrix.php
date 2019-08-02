<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Matrix Class
 *
 * @package     local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_competvetsuivi\matrix;

use file_storage;
use PHPExcel_IOFactory;

defined('MOODLE_INTERNAL') || die();
global $CFG;

/**
 * Class to represent a matrix
 *
 */
class matrix {
    const MATRIX_SHEET_PREFIX = 'matrice';

    /**
     * Constructor.
     *
     */
    public function __construct($matrixid) {

    }

    public static function import_from_file($filename, $filepath, $hash) {
        global $CFG;
        require_once("$CFG->libdir/phpexcel/PHPExcel/IOFactory.php");
        raise_memory_limit(MEMORY_HUGE);
        $reader = PHPExcel_IOFactory::createReaderForFile($filepath);
        $reader->setReadDataOnly(true);
        $allsheetsnames = $reader->listWorksheetNames($filepath);
        $matrixsheet = null;
        foreach ($allsheetsnames as $sheetname) {
            if (strpos(strtolower($sheetname), matrix::MATRIX_SHEET_PREFIX) === 0) {
                $reader->setLoadSheetsOnly($sheetname);
                $worksheet = $reader->load($filepath);
                $matrixsheet = $worksheet->getSheetByName($sheetname);
                break;
            }
        }
        if (!$matrixsheet) {
            throw new matrix_exception('nomatrixerror', 'local_competvetsuivi', '', matrix::MATRIX_SHEET_PREFIX);
        }
        $matrixobject = new \stdClass();
        $matrixobject->hash = $hash;
        $matrixobject->timestamp = time();
        $matrixobject->fullname = '';
        $matrixobject->shortname = '';
        $columnsvsue = [];
        $competencies=[];
        foreach($matrixsheet->getRowIterator() as  $row) {
            if ($row->getRowIndex() == 1) { // First Row we have the UE
                $previousvalue = "";
                foreach($row->getCellIterator() as $cellheader) {
                    if ($cellheader->getColumn() == "A")  {
                        continue;  // We ignore the first column
                    }
                    if ($cellheader->getValue()) {
                        $previousvalue = $cellheader->getValue();
                    }
                    $columnsvsue[$cellheader->getColumn()] = $previousvalue; // We fill the array with the same value if null
                }
            } else {
                foreach($row->getCellIterator() as $cell) {
                    if ($cell->getColumn() == "A") {
                        $competencies[$cell->getRow()] = $cell->getValue();
                    }
                }
            }

        }

        return $matrixobject;

    }

}

