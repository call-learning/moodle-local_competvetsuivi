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

    const MATRIX_COMP_TYPE_NAMES = array(
            1 => 'knowledge',
            2 => 'ability',
            3 => 'objective',
            4 => 'evaluation',
    );
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

    /** @var array of ue (see cvs_matrix_ue) */
    public $ues;

    /** @var array of comp (see cvs_matrix_comp) */
    public $comp;

    /**
     * @var array of comp per ue (see cvs_matrix_comp_ue)
     * So we have $comperue[<ueid>][<compid>] = <value>
     * TODO: see if we need the opposite also (compid, uidi => val)
     */
    public $compuevalues;

    protected $dataloaded = false;

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

    public function delete($withdependencies = false) {
        global $DB;
        // Start a delegated transation here so it is all or nothing
        $delegatedtransaction = $DB->start_delegated_transaction();
        $DB->delete_records(static::CLASS_TABLE, array('id' => $this->id));
        if ($withdependencies) {
            $this->delete_matrix_dependencies();
        }
        $DB->commit_delegated_transaction($delegatedtransaction);
    }

    /**
     * Reset a matrix (used before loading a new one in place)
     *
     * @throws \dml_transaction_exception
     */
    public function reset_matrix() {
        global $DB;
        // Start a delegated transation here so it is all or nothing
        $delegatedtransaction = $DB->start_delegated_transaction();
        $this->delete_matrix_dependencies();
        $DB->commit_delegated_transaction($delegatedtransaction);
    }

    protected function delete_matrix_dependencies() {
        global $DB;
        $DB->delete_records_select('cvs_matrix_comp_ue',
                'compid IN (SELECT DISTINCT id FROM {cvs_matrix_comp} WHERE matrixid= :cmatrixid) OR 
                    ueid IN (SELECT DISTINCT id FROM {cvs_matrix_ue} WHERE matrixid= :umatrixid)',
                array('cmatrixid' => $this->id, 'umatrixid' => $this->id));
        // Then fully delete the rest
        $DB->delete_records('cvs_matrix_ue', array('matrixid' => $this->id));
        $DB->delete_records('cvs_matrix_comp', array('matrixid' => $this->id));
    }

    public function save() {
        global $DB;
        $DB->update_record(static::CLASS_TABLE, $this);
    }

    /**
     * Load matrix data
     * The competencies are sorted by path
     *
     * @throws \dml_exception
     *
     */
    public function load_data() {
        global $DB;
        $this->ues = $DB->get_records('cvs_matrix_ue', array('matrixid' => $this->id));
        $this->comp = $DB->get_records('cvs_matrix_comp', array('matrixid' => $this->id));
        $compuesql = "SELECT compue.id AS id, compue.ueid AS ueid, compue.compid AS compid, compue.type AS type, compue.value AS value
        FROM {cvs_matrix_comp_ue} compue
        LEFT JOIN {cvs_matrix_ue} ue ON ue.id = compue.ueid
        LEFT JOIN {cvs_matrix_comp} comp ON comp.id = compue.compid
        WHERE ue.matrixid = :matrixid_1  AND comp.matrixid = :matrixid_2
        ORDER BY comp.path ASC
        ";
        $companduesvals = $DB->get_records_sql($compuesql, array('matrixid_1' => $this->id, 'matrixid_2' => $this->id));
        $this->compuevalues = array();
        foreach ($companduesvals as $cuv) {
            if (empty($this->compuevalues[$cuv->ueid])) {
                $this->compuevalues[$cuv->ueid] = array();
            }
            $value = new \stdClass();
            $value->type = $cuv->type;
            $value->value = $cuv->value;
            if (empty($this->compuevalues[$cuv->ueid][$cuv->compid])) {
                $this->compuevalues[$cuv->ueid][$cuv->compid] = array();
            }
            $this->compuevalues[$cuv->ueid][$cuv->compid][] = $value;
        }
        $this->dataloaded = true;
    }

    public function get_matrix_ues() {
        if (!$this->dataloaded) {
            throw new matrix_exception('matrixnotloaded', 'local_competvetsuivi');
        }
        return $this->ues;
    }

    public function get_matrix_competencies() {
        if (!$this->dataloaded) {
            throw new matrix_exception('matrixnotloaded', 'local_competvetsuivi');
        }
        return $this->comp;
    }

    public function get_values_for_ue_and_competency($ueid, $compid, $recursive=false) {
        if (!$this->dataloaded) {
            throw new matrix_exception('matrixnotloaded', 'local_competvetsuivi');
        }
        $currentvalue = $this->compuevalues[$ueid][$compid];
        if ($recursive) {
            foreach($this->get_child_competency($compid) as $cmp) {
                $childvalues = $this->get_values_for_ue_and_competency($ueid, $cmp->id, false);
                foreach($childvalues as $val){
                    foreach($currentvalue as $key => $cv) {
                        if ($cv->type == $val->type) {
                            $currentvalue[$key]->value = $cv->value + $val->value;
                        }
                    }
                }
            }
        }
        return $currentvalue;
    }

    public function get_child_competency($compid) {
        global $DB;
        $comps = [];
        $comppath = $DB->get_field('cvs_matrix_comp', 'path', array('id'=>$compid));
        if ($comppath) {
            $params = ['comppath'=> "%{$comppath}/%"];
            $comps = $DB->get_records_select('cvs_matrix_comp', $DB->sql_like('path',':comppath'), $params);
        }
        return $comps;
    }
    static public function comptype_to_string($comptypeid) {
        return get_string('matrixcomptype:' . static::MATRIX_COMP_TYPE_NAMES[$comptypeid], 'local_competvetsuivi');
    }

    /**
     * Import a matrix from a file and fills the relevant tables
     *
     * @param $filepath
     * @param $hash
     * @param $fullname
     * @param $shortname
     * @param $matrixobject existing matrix object as a generic stdClass
     * @return \stdClass
     * @throws \PHPExcel_Reader_Exception
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws matrix_exception
     */
    public static function import_from_file($filepath, $hash, $fullname, $shortname, &$matrixobject = null) {
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
        if (!$matrixobject) {
            $matrixobject = new \stdClass();
            $matrixobject->timemodified = time();
            $matrixobject->fullname = $fullname;
            $matrixobject->shortname = $shortname;
            $matrixobject->id = $DB->insert_record(static::CLASS_TABLE, $matrixobject);
        }
        $matrixobject->hash = $hash;
        $columnsvsue = [];
        $competencies = [];
        $rowiterator = $matrixsheet->getRowIterator();

        // Start a delegated transation here so it is all or nothing
        $delegatedtransaction = $DB->start_delegated_transaction();

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
            $existingue = $DB->get_record('cvs_matrix_ue', array('matrixid' => $matrixobject->id, 'fullname' => $ue->fullname));
            if (!$existingue) {
                $ue->id = $DB->insert_record('cvs_matrix_ue', $ue);
            } else {
                $ue = $existingue; // We don't insert the UE twice
            }
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
            if (preg_match('/^(\w+)\.([0-9.]*)\s+(.+)/', $comptext, $compmatch)) {
                $competencypath = explode('.', rtrim($compmatch[2], '.'));

                $competencyrootsn = strtoupper($compmatch[1]);
                // We need to search for parent's shortname in the database so we obtain the real path
                $seachparentshortname = join('.', array_slice($competencypath, 0, count($competencypath) - 1));
                $seachparentshortname = "{$competencyrootsn}"
                        .($seachparentshortname?'.': '')
                        ."$seachparentshortname";

                $parentcomp = $DB->get_record('cvs_matrix_comp',
                        array('shortname' => $seachparentshortname, 'matrixid' => $matrixobject->id));

                $competency = new \stdClass();
                $competency->description = $compmatch[3];
                $competency->descriptionformat = FORMAT_PLAIN;
                $competency->shortname = join('.', $competencypath);
                $competency->shortname = "{$competencyrootsn}"
                        .($competency->shortname?'.': '')
                        ."{$competency->shortname}";
                $competency->fullname = $competency->shortname . ' ' . $compmatch[3];
                if (strlen($competency->fullname)) {
                    $competency->fullname = trim(\core_text::substr($competency->fullname, 0, 252)) . '...';
                }
                $competency->id = $DB->insert_record('cvs_matrix_comp', $competency);
                $competency->matrixid = $matrixobject->id;
                $competency->path = '/' . $competency->id;
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



