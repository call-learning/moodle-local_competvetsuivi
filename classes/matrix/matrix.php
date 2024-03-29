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
 * @package local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_competvetsuivi\matrix;

defined('MOODLE_INTERNAL') || die();

/**
 * Class to represent a matrix
 *
 * @package local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class matrix {
    /**
     * Possible UC prefix
     */
    const UC_PREFIX = ['UC', 'UE'];
    /**
     * We convert the UC name to this prefix
     */
    const UC_REAL_PREFIX = 'UC';
    /**
     * Prefix of the matrice sheet (should be starting with...)
     */
    const MATRIX_SHEET_PREFIX = 'matrice';

    /**
     * Type knowledge
     */
    const MATRIX_COMP_TYPE_KNOWLEDGE = 1;
    /**
     * Type ability
     */
    const MATRIX_COMP_TYPE_ABILITY = 2;
    /**
     * Type objective
     */
    const MATRIX_COMP_TYPE_OBJECTIVES = 3;
    /**
     * Type evaluation
     */
    const MATRIX_COMP_TYPE_EVALUATION = 4;

    /**
     * Array of all the name associated with shortname
     */
    const MATRIX_COMP_TYPE_NAMES = array(
        self::MATRIX_COMP_TYPE_KNOWLEDGE => 'knowledge',
        self::MATRIX_COMP_TYPE_ABILITY => 'ability',
        self::MATRIX_COMP_TYPE_OBJECTIVES => 'objective',
        self::MATRIX_COMP_TYPE_EVALUATION => 'evaluation',
    );

    /**
     * Array of possible strands and associated values
     * Warning: max values are not the one we think: they are from max to min (so 1 is max, 2, is
     * middle and 3 is min, 0 is min too!!).
     */
    const MAX_VALUE_PER_STRAND = [
        self::MATRIX_COMP_TYPE_KNOWLEDGE => 3, // This is the maximum value possible.
        self::MATRIX_COMP_TYPE_ABILITY => 30,
        self::MATRIX_COMP_TYPE_OBJECTIVES => 300,
        self::MATRIX_COMP_TYPE_EVALUATION => 3000
    ];

    /**
     * Table name
     */
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

    /**
     * Is data loaded
     * @var bool
     */
    protected $dataloaded = false;

    /**
     * The cached child array to optimise the retrieval of direct children competencies.
     * @var array
     */
    protected $compdirectchildarray = [];

    /**
     * Constructor.
     *
     * @param int $matrixid
     * @throws \dml_exception
     */
    public function __construct($matrixid) {
        global $DB;
        $matrix = $DB->get_record(self::CLASS_TABLE, array('id' => $matrixid));
        $this->id = $matrix->id;
        $this->fullname = $matrix->fullname;
        $this->shortname = $matrix->shortname;
        $this->hash = $matrix->hash;
        $this->timemodified = $matrix->timemodified;
    }

    /**
     * Delete all entities associated with this matrix
     *
     * @param bool $withdependencies
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     */
    public function delete($withdependencies = false) {
        global $DB;
        // Start a delegated transation here so it is all or nothing.
        $delegatedtransaction = $DB->start_delegated_transaction();
        $DB->delete_records(self::CLASS_TABLE, array('id' => $this->id));
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
        // Start a delegated transation here so it is all or nothing.
        $delegatedtransaction = $DB->start_delegated_transaction();
        $this->delete_matrix_dependencies();
        $DB->commit_delegated_transaction($delegatedtransaction);
    }

    /**
     * Delete dependencies associated with this matrix
     * @throws \dml_exception
     */
    protected function delete_matrix_dependencies() {
        global $DB;
        $DB->delete_records_select('cvs_matrix_comp_ue',
            'compid IN (SELECT DISTINCT id FROM {cvs_matrix_comp} WHERE matrixid= :cmatrixid) OR
                    ueid IN (SELECT DISTINCT id FROM {cvs_matrix_ue} WHERE matrixid= :umatrixid)',
            array('cmatrixid' => $this->id, 'umatrixid' => $this->id));
        // Then fully delete the rest.
        $DB->delete_records('cvs_matrix_ue', array('matrixid' => $this->id));
        $DB->delete_records_select('cvs_matrix_comp_ue',
            'compid IN (SELECT c.id FROM {cvs_matrix_comp} c WHERE c.matrixid = :matrixid)',
            array('matrixid' => $this->id));
        $DB->delete_records('cvs_matrix_comp', array('matrixid' => $this->id));
        $DB->delete_records('cvs_matrix_cohorts', array('matrixid' => $this->id));
    }

    /**
     * Update DB record
     */
    public function save() {
        global $DB;
        $this->timemodified = time();
        $DB->update_record(self::CLASS_TABLE, $this);
    }

    /**
     * Load matrix data
     * The competencies are sorted by path
     * Data is also normalised so 0 is transformed to the max value
     *
     * @throws \dml_exception
     *
     */
    public function load_data() {
        global $DB;
        $this->ues = $DB->get_records('cvs_matrix_ue', array('matrixid' => $this->id), 'id ASC');
        $this->comp = $DB->get_records('cvs_matrix_comp', array('matrixid' => $this->id), 'path ASC');
        $compuesql = "SELECT compue.id AS id, compue.ueid AS ueid, compue.compid AS compid, compue.type AS type, compue.value
        AS value
        FROM {cvs_matrix_comp_ue} compue
        LEFT JOIN {cvs_matrix_ue} ue ON ue.id = compue.ueid
        LEFT JOIN {cvs_matrix_comp} comp ON comp.id = compue.compid
        WHERE ue.matrixid = :matrixid_1  AND comp.matrixid = :matrixid_2
        ORDER BY comp.path ASC
        ";
        $companduesvals = $DB->get_records_sql($compuesql, array('matrixid_1' => $this->id, 'matrixid_2' => $this->id));
        $this->compuevalues = array();
        raise_memory_limit(MEMORY_EXTRA);
        foreach ($companduesvals as $cuv) {
            if (empty($this->compuevalues[$cuv->ueid])) {
                $this->compuevalues[$cuv->ueid] = array();
            }
            $value = new \stdClass();
            $value->type = $cuv->type;
            $value->value = ($cuv->value == 0 || $cuv->value > self::MAX_VALUE_PER_STRAND[$cuv->type]) ?
                self::MAX_VALUE_PER_STRAND[$cuv->type] : intval($cuv->value); // Normalize value.

            if (empty($this->compuevalues[$cuv->ueid][$cuv->compid])) {
                $this->compuevalues[$cuv->ueid][$cuv->compid] = array();
            }
            $this->compuevalues[$cuv->ueid][$cuv->compid][] = $value;
        }
        $this->dataloaded = true;
    }

    /**
     * Get UEs for this matrix
     *
     * @return array
     * @throws matrix_exception
     */
    public function get_matrix_ues() {
        if (!$this->dataloaded) {
            throw new matrix_exception('matrixnotloaded', 'local_competvetsuivi');
        }
        return $this->ues;
    }

    /**
     * Make sure we always have the same name for the UC (UC as prefix for now)
     *
     * @param string $ucname
     * @return string
     */
    public static function normalize_uc_name($ucname) {
        foreach (self::UC_PREFIX as $prefix) {
            $ucname = str_replace($prefix, '', $ucname);
        }
        return self::UC_REAL_PREFIX . $ucname;
    }

    /**
     * Get matching UE per search criteria.
     * We take care of the special case for shortname where we can either have UC or UE
     *
     * @param string $propertyname
     * @param any $propertyvalue
     * @return mixed
     * @throws matrix_exception
     */
    public function get_matrix_ue_by_criteria($propertyname, $propertyvalue) {
        if (!$this->dataloaded) {
            throw new matrix_exception('matrixnotloaded', 'local_competvetsuivi');
        }
        if ($propertyname == 'shortname') {
            $propertyvalue = self::normalize_uc_name($propertyvalue);
            $matchingues = array_filter($this->ues, function($ue) use ($propertyname, $propertyvalue) {
                $currentvalue = $ue->$propertyname;
                $currentvalue = self::normalize_uc_name($currentvalue);
                return $currentvalue == $propertyvalue;
            });
        } else {
            $matchingues = array_filter($this->ues, function($ue) use ($propertyname, $propertyvalue) {
                return $ue->$propertyname == $propertyvalue;
            });
        }
        if (!$matchingues) {
            throw new matrix_exception('foundnomatchingue', 'local_competvetsuivi');
        }
        if (count($matchingues) > 1) {
            throw new matrix_exception('foundtoomanymatchingue', 'local_competvetsuivi');
        }
        return reset($matchingues);
    }

    /**
     * Get the list of attached competencies for this matrix
     *
     * @return array
     * @throws matrix_exception
     */
    public function get_matrix_competencies() {
        if (!$this->dataloaded) {
            throw new matrix_exception('matrixnotloaded', 'local_competvetsuivi');
        }
        return $this->comp;
    }

    /**
     * Get matching competency per search criteria.
     *
     * @param string $propertyname
     * @param any $propertyvalue
     * @return mixed
     * @throws matrix_exception
     */
    public function get_matrix_comp_by_criteria($propertyname, $propertyvalue) {
        if (!$this->dataloaded) {
            throw new matrix_exception('matrixnotloaded', 'local_competvetsuivi');
        }
        $matchingcomp = array_filter($this->comp, function($comp) use ($propertyname, $propertyvalue) {
            return $comp->$propertyname == $propertyvalue;
        });
        if (!$matchingcomp) {
            throw new matrix_exception('foundnomatchingcompetency', 'local_competvetsuivi');
        }
        if (count($matchingcomp) > 1) {
            throw new matrix_exception('foundtoomanymatchingcompetency', 'local_competvetsuivi');
        }
        return reset($matchingcomp);
    }

    /**
     * Get the associative array from UE
     * @param int $ueid
     * @param int $compid
     * @return array
     */
    protected function get_associative_array_value_for_ue($ueid, $compid) {
        $currentvalue = [];
        // Because isset && isnull faster than key_exists.
        $exists = isset($this->compuevalues[$ueid]) && !is_null($this->compuevalues[$ueid]);
        $exists = $exists && isset($this->compuevalues[$ueid][$compid]) && !is_null($this->compuevalues[$ueid][$compid]);
        if (!$exists) {
            foreach (array_keys(self::MATRIX_COMP_TYPE_NAMES) as $strandid) {
                $currentvalue[$strandid] = new \stdClass();
                $currentvalue[$strandid]->type = $strandid;
                $currentvalue[$strandid]->value = 3;
            }
        } else {
            $currentvalue = [];
            foreach ($this->compuevalues[$ueid][$compid] as $val) {
                $currentvalue[$val->type] = clone $val;
            }
        }
        return $currentvalue;
    }

    /**
     * Get recursively the possible (maximum) values for this competency
     * We take the maximum value as we are using thresholds that have 3 states
     *  - No contribution
     *  - Some contribution
     *  - Full contribution
     * Having a mean or average does not have sense in this. So either a competency has no, some
     * contribution, or full contribution
     *
     * @param int $ueid
     * @param int $compid
     * @param bool $recursive
     * @return mixed
     * @throws matrix_exception
     */
    public function get_values_for_ue_and_competency($ueid, $compid, $recursive = false) {
        if (!$this->dataloaded) {
            throw new matrix_exception('matrixnotloaded', 'local_competvetsuivi');
        }
        $currentvalue = $this->get_associative_array_value_for_ue($ueid, $compid);
        if ($recursive) {
            foreach ($this->get_child_competencies($compid, true) as $cmp) {
                $childvalues = $this->get_values_for_ue_and_competency($ueid, $cmp->id, true);
                foreach ($childvalues as $key => $cv) {
                    /*
                     *  Here this is a bit complicated due to the range chosen
                     *  For example with the Knowledge strand:
                     *  * 0 or 3 is None
                     *  * 1 is max value
                     *  * 2 is middle value
                     *  So when calculating the aggregated for a given value we take he min
                     *  except when it is equal to 0
                     */
                    $currentvalue[$key]->value = min($currentvalue[$key]->value, $cv->value);
                }
            }
        }
        return $currentvalue;
    }

    /**
     * Get recursively the total amount of contribution for  values for this competency
     * This is a bit different from the get_values_for_ue_and_competency as it does
     * a sum of the possible value with a weight
     *  - No contribution (value = 3) - 0
     *  - Some contribution (value = 2) - 0.5
     *  - Full contribution (value = 1) - 1
     *
     * @param int $ueid
     * @param int $compid
     * @param bool $recursive
     * @return mixed
     * @throws matrix_exception
     */
    public function get_total_values_for_ue_and_competency($ueid, $compid, $recursive = false) {
        static $totalvalues = [];
        if (!$this->dataloaded) {
            throw new matrix_exception('matrixnotloaded', 'local_competvetsuivi');
        }

        $currentvalue = $this->get_associative_array_value_for_ue($ueid, $compid);
        foreach (array_keys(self::MATRIX_COMP_TYPE_NAMES) as $strandid) {
            $currentvalue[$strandid]->totalvalue = self::get_real_value_from_strand($strandid, $currentvalue[$strandid]->value);
        }
        if ($recursive) {
            $childcomps = $this->get_child_competencies($compid, true);
            foreach ($childcomps as $cmp) {
                $childvalues = $this->get_total_values_for_ue_and_competency($ueid, $cmp->id, true);
                foreach ($childvalues as $key => $cv) {
                    /*
                     *  Here this is a bit complicated due to the range chosen
                     *  For example with the Knowledge strand:
                     *  * 0 or 3 is None
                     *  * 1 is max value
                     *  * 2 is middle value
                     *  So when calculating the aggregated for a given value we take he min
                     *  except when it is equal to 0
                     */
                    $currentvalue[$key]->value = min($currentvalue[$key]->value, $cv->value);
                    $currentvalue[$key]->totalvalue += $cv->totalvalue;
                }

            }
            if (!isset($totalvalues[$ueid])) {
                $totalvalues[$ueid] = [];
            }
            $totalvalues[$ueid][$compid] = $currentvalue;
        }
        return $currentvalue;
    }

    /**
     * Return a numeric value corresponding to the threshold and the strand
     *
     * @param int $comptypeid
     * @param int $currentval
     * @return float|int : 3 or 0 => 0, 2 => 0.5, 1 => 1
     */
    public static function get_real_value_from_strand($comptypeid, $currentval) {
        $value = 0;
        $strandfactor = $currentval / (self::MAX_VALUE_PER_STRAND[$comptypeid] / 3);
        switch ($strandfactor) {
            case 1 :
                $value = 1;
                break;
            case 2:
                $value = 0.5;
                break;
        }
        return $value;
    }

    /**
     * Build a cache of direct child
     *
     * @throws matrix_exception
     */
    protected function build_direct_child_array() {
        if (empty($this->compdirectchildarray)) {
            $complist = $this->get_matrix_competencies(); // Make sure competencies are loaded.
            foreach ($complist as $cid => $cmp) {
                $allparentsid = explode('/', $cmp->path);
                $pid = 0;
                $allparentsiddepth = count($allparentsid);
                if ($allparentsiddepth > 2) {
                    $pid = $allparentsid[$allparentsiddepth - 2];
                    if (empty($pid)) {
                        $pid = 0;
                    }
                }
                // Create entry for the child itself if it does not exist.
                if (!isset($this->compdirectchildarray[$cid])) {
                    $this->compdirectchildarray[$cid] = [];
                }
                if (!isset($this->compdirectchildarray[$pid])) {
                    $this->compdirectchildarray[$pid] = [];
                }
                $this->compdirectchildarray[$pid][$cid] = $cmp;
            }
        }
    }

    /**
     * Get all direct child competencies or direct child competencies
     *
     * @param int $compid
     * @param bool $directchildonly
     * @return array
     * @throws \dml_exception|matrix_exception
     */
    public function get_child_competencies($compid = 0, $directchildonly = false) {

        $this->build_direct_child_array();
        // To avoid going through the array for direct child (optimisation).
        if ($directchildonly && key_exists($compid, $this->compdirectchildarray)) {
            return $this->compdirectchildarray[$compid];
        }

        // Usual case
        $complist = $this->get_matrix_competencies(); // Make sure competencies are loaded.
        if ($compid && key_exists($compid, $complist)) {
            $rootcomp = $complist[$compid];
        } else {
            $rootcomp = null;
        }
        $comps = [];
        $currentpath = $rootcomp ? $rootcomp->path . '/' : '/';
        foreach ($complist as $cid => $cmp) {
            if (strpos($cmp->path, $currentpath) === 0) {
                // All children which are direct child will have <ROOTCOMPPATH>/XXXXX.
                $isdirectchild = substr_count($cmp->path, "/", strlen($currentpath)) == 0;
                if (!$directchildonly || $isdirectchild) {
                    $comps[$cid] = $cmp;
                }
            }
        }

        // We could use array filter but it seems slower.
        return $comps;
    }

    /**
     * Get all direct child competencies or direct child competencies
     *
     * @param int $comp
     * @return bool
     * @throws \dml_exception
     */
    public function has_children($comp) {
        $children = $this->get_child_competencies($comp->id, true);
        return !empty($children);
    }

    /**
     * Get root competency
     *
     * @return array
     * @throws \dml_exception
     */
    public function get_root_competency() {
        static $rootcompetency = null;

        if ($rootcompetency) {
            return $rootcompetency;
        }

        $complist = $this->get_matrix_competencies(); // Make sure competencies are loaded.
        $comps = array_filter($complist, function($comp) {
            return substr_count($comp->path, '/') == 1;
        });
        $rootcompetency = reset($comps);
        return $rootcompetency;
    }

    /**
     * Competency type to fullname string
     *
     * @param int $comptypeid
     * @return \lang_string|string
     * @throws \coding_exception
     */
    static public function comptype_to_string($comptypeid) {
        return get_string('matrixcomptype:' . self::MATRIX_COMP_TYPE_NAMES[$comptypeid], 'local_competvetsuivi');
    }

    /**
     * Maximum in the fullname (rest will go in description)
     */
    const MAX_FULLNAME_SIZE = 255;

    /**
     * Import a matrix from a file and fills the relevant tables
     *
     * @param string $filepath
     * @param string $hash
     * @param string $fullname
     * @param string $shortname
     * @param \stdClass $matrixobject existing matrix object as a generic stdClass
     * @return array  a tuple (matrix, errors)
     * @throws \PHPExcel_Reader_Exception
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws matrix_exception
     */
    public static function import_from_file($filepath, $hash, $fullname, $shortname, &$matrixobject = null) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/local/competvetsuivi/lib/phpexcel/PHPExcel/IOFactory.php");
        raise_memory_limit(MEMORY_HUGE);

        // Log for later.
        $logcontent = new \stdClass();
        $logcontent->compcount = 0;
        $logcontent->macrocompcount = 0;
        $logcontent->uecount = 0;
        // END Log for later.
        $reader = \PHPExcel_IOFactory::createReaderForFile($filepath);
        $reader->setReadDataOnly(true);
        $allsheetsnames = $reader->listWorksheetNames($filepath);
        $matrixsheet = null;
        foreach ($allsheetsnames as $sheetname) {
            if (strpos(strtolower($sheetname), self::MATRIX_SHEET_PREFIX) === 0) {
                $reader->setLoadSheetsOnly($sheetname);
                $worksheet = $reader->load($filepath);
                $matrixsheet = $worksheet->getSheetByName($sheetname);
                break;
            }
        }
        if (!$matrixsheet) {
            throw new matrix_exception('nomatrixerror', 'local_competvetsuivi', '', self::MATRIX_SHEET_PREFIX);
        }
        if (!$matrixobject) {
            $matrixobject = new \stdClass();
            $matrixobject->timemodified = time();
            $matrixobject->fullname = $fullname;
            $matrixobject->shortname = $shortname;
            $id = $DB->insert_record(self::CLASS_TABLE, $matrixobject);
            $matrixobject->id = $id;
        }
        $matrixobject->hash = $hash;
        $columnsvsue = [];
        $rowiterator = $matrixsheet->getRowIterator();

        // Start a delegated transation here so it is all or nothing.
        $delegatedtransaction = $DB->start_delegated_transaction();

        list($firstuecolumn, $lastuecolumn, $uecount) =
            self::get_matrix_layout_from_file($rowiterator->current(), $matrixobject, $columnsvsue);
        $logcontent->uecount = $uecount;

        // Then we iterate through the rest of the worksheet
        $rowiterator->seek(4); // We start at row 4
        while ($rowiterator->valid()) { // We don't use foreach as it will call rewind on the iterator.
            $row = $rowiterator->current();
            $celliterator = $row->getCellIterator();
            // Get the competency first column.
            $compref = rtrim(strtoupper($celliterator->current()->getValue()),
                '.'); // First column is the reference for the competency.
            if (!$compref) {
                break; // We finished.
            }
            $logcontent->compcount++;
            // We should have the reference in the first column
            // And the description in the second.
            $competencypath = explode('.', $compref);

            // We need to search for parent's shortname in the database so we obtain the real path.
            $seachparentshortname = join('.', array_slice($competencypath, 0, count($competencypath) - 1));

            $parentcomp = $DB->get_record('cvs_matrix_comp',
                array('shortname' => $seachparentshortname, 'matrixid' => $matrixobject->id));

            // Now get the next column value for description.
            $celliterator->seek('B');
            $description = $celliterator->current()->getValue();
            $competency = new \stdClass();
            $competency->description = $description;
            $competency->descriptionformat = FORMAT_PLAIN;
            $competency->shortname = join('.', $competencypath);
            $competency->fullname = $description;
            if (strlen($competency->fullname) > self::MAX_FULLNAME_SIZE) {
                $competency->fullname = trim(\core_text::substr($competency->fullname, 0, 252)) . '...';
            }
            $competency->id = $DB->insert_record('cvs_matrix_comp', $competency);
            $competency->matrixid = $matrixobject->id;
            $competency->path = '/' . $competency->id;
            if ($parentcomp) {
                $competency->path = $parentcomp->path . $competency->path;
            }
            $DB->update_record('cvs_matrix_comp', $competency); // Update path.

            if (!$parentcomp) {
                $logcontent->macrocompcount++; // Log only macrocomps.
            }

            if (!empty($competency->id)) {
                // We skip all other columns until the first column containing the UC/UE.
                $celliterator->seek($firstuecolumn);
                // This row has a valid competency, so we can now examine the rest of the cells.
                while ($celliterator->valid()) {
                    $cell = $celliterator->current();
                    if ($cell->getColumn() == $lastuecolumn) {
                        break; // We arrived to the last UE column.
                    }
                    $matchingue = $columnsvsue[$cell->getColumn()];

                    $compue = new \stdClass();
                    $compue->ueid = $matchingue['ue']->id;
                    $compue->compid = $competency->id;
                    $compue->value = intval($cell->getValue());
                    $compue->type = $matchingue['type'];
                    $DB->insert_record('cvs_matrix_comp_ue', $compue);
                    $celliterator->next(); // Next value.
                }
            }
            $rowiterator->next(); // Next Value.
        }
        $DB->commit_delegated_transaction($delegatedtransaction);

        $logmessage = get_string('matrixaddedlog', 'local_competvetsuivi', $logcontent);
        return array($matrixobject, $logmessage);

    }

    /**
     * Build up UE information and return the first column where an UE is found
     *
     * @param \stdClass $firstrow (PHPExcel_Worksheet_RowIterator)
     * @param \stdclass $matrixobject
     * @param array $columnsvsue
     * @return array
     * @throws \dml_exception
     */
    protected static function get_matrix_layout_from_file($firstrow, &$matrixobject, &$columnsvsue) {
        global $DB;
        // First extract the columns/UE names.
        $previousuename = "";

        // Match between column id and type.
        $comptypecolums = [self::MATRIX_COMP_TYPE_KNOWLEDGE,
            self::MATRIX_COMP_TYPE_ABILITY,
            self::MATRIX_COMP_TYPE_OBJECTIVES,
            self::MATRIX_COMP_TYPE_EVALUATION];
        $currentypecol = 0;

        $uecount = 0;
        $firstuecolumn = "";
        $lastuecolumn = "";
        // First we get the UE names.
        foreach ($firstrow->getCellIterator() as $cellheader) {
            // First we are in search mode for the first UE/UC.

            if (!$firstuecolumn) {
                $value = $cellheader->getValue();
                if (in_array(substr($value, 0, 2), self::UC_PREFIX)) {
                    // Then we found the first UC.
                    $firstuecolumn = $cellheader->getColumn();
                }
            }
            // Then we can continue.
            if ($firstuecolumn) {
                if ($cellheader->getValue()) {
                    $previousuename = $cellheader->getValue(); // We fill the array with the same value if null.
                    $currentypecol = 0;
                } else {
                    if ($currentypecol > 3) {
                        $lastuecolumn = $cellheader->getColumn();
                        break; // We come at the end of the columns.
                    }
                }
                $ue = new \stdClass();
                $ue->fullname = $previousuename; // We fill the array with the same value if null.
                $ue->shortname = self::normalize_uc_name($previousuename);
                // Make sure we normalize data here, especially shortname.
                $ue->matrixid = $matrixobject->id;
                $existingue = $DB->get_record('cvs_matrix_ue', array('matrixid' => $matrixobject->id, 'fullname' => $ue->fullname));
                if (!$existingue) {
                    $ue->id = $DB->insert_record('cvs_matrix_ue', $ue);
                    $uecount++;
                } else {
                    $ue = $existingue; // We don't insert the UE twice.
                }
                $columnsvsue[$cellheader->getColumn()] = array('ue' => $ue, 'type' => $comptypecolums[$currentypecol]);
                $currentypecol++;
            }
        }
        return array($firstuecolumn, $lastuecolumn, $uecount);
    }

    /**
     * Get all possible competency types names
     *
     * @return array
     * @throws \coding_exception
     */
    public static function get_all_competency_types_names() {
        $competenciestypesnames = [];
        foreach (self::MATRIX_COMP_TYPE_NAMES as $comptypname) {
            $competenciestypesnames[] = get_string('matrixcomptype:' . $comptypname, 'local_competvetsuivi');
        }
        return $competenciestypesnames;
    }

    /**
     * Get a competency type name
     *
     * @param int $competencytypeid
     * @return \lang_string|string
     * @throws \coding_exception
     */
    public static function get_competency_type_name($competencytypeid) {
        $comptypename = "";
        if (key_exists($competencytypeid, self::MATRIX_COMP_TYPE_NAMES)) {
            $comptypename =
                get_string('matrixcomptype:' . self::MATRIX_COMP_TYPE_NAMES[$competencytypeid], 'local_competvetsuivi');
        }
        return $comptypename;
    }
}



