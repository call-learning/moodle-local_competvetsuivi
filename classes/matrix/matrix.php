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

    const MATRIX_COMP_TYPE_CAPABILITY = 1;
    const MATRIX_COMP_TYPE_LEARNING = 2;
    const MATRIX_COMP_TYPE_OBJECTIVES = 3;
    const MATRIX_COMP_TYPE_EVALUATION = 4;

    const CLASS_TABLE = 'cvs_matrix';

    /** @var integer */
    public $id;

    /** @var char (255) */
    public $fullname;

    /** @var char (255) */
    public $shortname;

    /** @var char (255) */
    public $hash;

    /** @var integer */
    public $timemodified;

    /**
     * Constructor.
     *
     */
    public function __construct($matrixid) {
        global $DB;
        $matrix = $DB->get_record(static::CLASS_TABLE, array('id' => $matrixid));
        $this->id = $matrix->id;
        $this->fullname = $matrix->fullname;
        $this->shortname = $matrix->shortname;
        $this->hash = $matrix->hash;
        $this->timemodified = $matrix->timemodified;
    }

    function delete($withdependencies = false) {
        global $DB;
        // Start a delegated transation here so it is all or nothing
        $delegatedtransaction = $DB->start_delegated_transaction();
        $DB->delete_records(static::CLASS_TABLE, array('id' => $this->id));
        if ($withdependencies) {

            // Delete all related competencies values
            $DB->delete_records_select('cvs_matrix_comp_ue',
                    'compid IN (SELECT DISTINCT id FROM {cvs_matrix_comp} WHERE matrixid= :cmatrixid) OR 
                    ueid IN (SELECT DISTINCT id FROM {cvs_matrix_ue} WHERE matrixid= :umatrixid)',
                    array('cmatrixid' => $this->id, 'umatrixid' => $this->id));
            // Then fully delete the rest
            $DB->delete_records('cvs_matrix_ue', array('matrixid' => $this->id));
            $DB->delete_records('cvs_matrix_comp', array('matrixid' => $this->id));
        }
        $DB->commit_delegated_transaction($delegatedtransaction);
    }

    function save() {
        global $DB;
        $DB->update_record(static::CLASS_TABLE, $this);
    }

    public static function import_from_file($filename, $filepath, $hash, $fullname, $shortname) {
        global $CFG, $DB;
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
        $matrixobject->timemodified = time();
        $matrixobject->fullname = $fullname;
        $matrixobject->shortname = $shortname;
        $columnsvsue = [];
        $competencies = [];
        $rowiterator = $matrixsheet->getRowIterator();

        // Start a delegated transation here so it is all or nothing
        $delegatedtransaction = $DB->start_delegated_transaction();

        // Add the matrix to the table
        $matrix = new \stdClass();
        $matrixobject->id = $DB->insert_record(static::CLASS_TABLE, $matrixobject);

        // First extract the columns/UE names
        $previousuename = "";

        // Match between column id and type
        $COMP_TYPE_COLUMNS = [matrix::MATRIX_COMP_TYPE_CAPABILITY,
                matrix::MATRIX_COMP_TYPE_LEARNING,
                matrix::MATRIX_COMP_TYPE_OBJECTIVES,
                matrix::MATRIX_COMP_TYPE_EVALUATION];
        $currentypecol = 0;

        foreach ($rowiterator->current()->getCellIterator() as $cellheader) {
            if ($cellheader->getColumn() == "A") {
                continue;  // We ignore the first column
            }
            if ($cellheader->getValue()) {
                $previousuename = $cellheader->getValue(); // We fill the array with the same value if null
                $currentypecol = 0;
            }
            $ue = new \stdClass();
            $ue->fullname = $previousuename; // We fill the array with the same value if null
            $ue->shortname = $previousuename;
            $ue->matrixid = $matrixobject->id;
            $ue->id = $DB->insert_record('cvs_matrix_ue', $ue);
            $columnsvsue[$cellheader->getColumn()] = array('ue' => $ue, 'type' => $COMP_TYPE_COLUMNS[$currentypecol]);
            $currentypecol++;
        }
        $rowiterator->next();
        while ($rowiterator->valid()) { // We don't use foreach as it will call rewind on the iterator
            $row = $rowiterator->current();
            $celliterator = $row->getCellIterator();
            // Get the competency first column
            $competency = new \stdClass();
            $comptext = $celliterator->current()->getValue();
            $compmatch = [];
            if (preg_match('/^(\w+)\.([0-9.]+)\s+(.+)/', $comptext, $compmatch)) {
                $competencypath = explode('.', rtrim($compmatch[2], '.'));

                $competencyrootsn = strtoupper($compmatch[1]);
                $compsnformat = '%s-%s';
                // We need to search for parent's shortname in the database so we obtain the real path
                $seachparentshortname = sprintf($compsnformat,
                        $competencyrootsn,
                        join('.', array_slice($competencypath, 0, count($competencypath) - 1)));

                $parentcomp = $DB->get_record('cvs_matrix_comp',
                        array('shortname' => $seachparentshortname, 'matrixid' => $matrixobject->id));

                $competency = new \stdClass();
                $competency->description = $compmatch[3];
                $competency->descriptionformat = FORMAT_PLAIN;
                $competency->fullname = sprintf($compsnformat, $competencyrootsn, join('.', $competencypath));
                $competency->shortname = sprintf($compsnformat, $competencyrootsn, join('.', $competencypath));
                $competency->id = $DB->insert_record('cvs_matrix_comp', $competency);
                $competency->matrixid = $matrixobject->id;
                $competency->path = '.' . $competency->id;
                if ($parentcomp) {
                    $competency->path = $parentcomp->path . $competency->path;
                }
                $DB->update_record('cvs_matrix_comp', $competency); // Update path
            }
            if (!empty($competency->id)) {
                // This row has a valid competency, so we can now examine the rest of the cells
                $celliterator->next();
                while ($celliterator->valid()) {
                    $cell = $celliterator->current();
                    $matchingue = $columnsvsue[$cell->getColumn()];
                    $compue = new \stdClass();
                    $compue->ueid = $matchingue['ue']->id;
                    $compue->compid = $competency->id;
                    $compue->value = intval($cell->getValue());
                    $compue->type = $matchingue['type'];
                    $DB->insert_record('cvs_matrix_comp_ue', $compue);
                    $celliterator->next(); // Next value
                }
            }
            $rowiterator->next(); // Next Value
        }
        $DB->commit_delegated_transaction($delegatedtransaction);
        return $matrixobject;

    }
}



