<?php
namespace Hoksi\Qb;

/**
 * Description of ForbizResult
 *
 * @author hoksi
 */
class NunaResult
{
    protected $nrResult = false;

    public function __construct($result)
    {
        $this->nrResult = $result;
    }

    /**
     * Retrieve the results of the query. Typically an array of
     * individual data rows, which can be either an 'array', an
     * 'object', or a custom class name.
     * @param string $type
     * @return mixed
     */
    public function getResult($type = 'object')
    {
        return (is_object($this->nrResult) ? $this->nrResult->result($type) : false);
    }

    public function num_rows()
    {
        return (is_object($this->nrResult) ? $this->nrResult->num_rows() : false);
    }

    /**
     * Returns the results as an array of arrays.
     *
     * If no results, an empty array is returned.
     *
     * @return array
     */
    public function getResultArray()
    {
        return (is_object($this->nrResult) ? $this->nrResult->result_array() : false);
    }

    /**
     * Wrapper object to return a row as either an array, an object, or
     * a custom class.
     *
     * If row doesn't exist, returns null.
     *
     * @param int $n The index of the results to return
     * @param string $type The type of result object. 'array', 'object' or class name.
     *
     * @return mixed
     */
    public function getRow($n = 0, $type = 'object')
    {
        return (is_object($this->nrResult) ? $this->nrResult->row($n, $type) : false);
    }

    /**
     * Returns a single row from the results as an array.
     *
     * If row doesn't exist, returns null.
     *
     * @param int $n
     *
     * @return mixed
     */
    public function getRowArray($n = 0)
    {
        return (is_object($this->nrResult) ? $this->nrResult->row_array($n) : false);
    }

    /**
     * Returns an unbuffered row and move the pointer to the next row.
     *
     * @param string $type
     *
     * @return mixed
     */
    public function getUnbufferedRow($type = 'object')
    {
        if (is_object($this->nrResult)) {
            $row = $this->nrResult->unbuffered_row($type);

            if ($row === null) {
                $this->nrResult->free_result();
            }

            return $row;
        }

        return false;
    }

    /**
     * Frees the current result.
     *
     * @return mixed
     */
    public function freeResult()
    {
        return (is_object($this->nrResult) ? $this->nrResult->free_result() : false);
    }

    /**
     * Next the current result.
     *
     * @return mixed
     */
    public function nextResult()
    {
        if (get_class($this->nrResult) == 'CI_DB_mysqli_result') {
            if (is_object($this->nrResult) && is_object($this->nrResult->conn_id)) {
                if (mysqli_more_results($this->nrResult->conn_id)) {
                    return mysqli_next_result($this->nrResult->conn_id);
                }
            }
        }
    }

    /**
     * Fetch Field Names
     *
     * Generates an array of column names.
     *
     * Overridden by driver result classes.
     *
     * @return    array
     */
    public function listFields()
    {
        return (is_object($this->nrResult) ? $this->nrResult->list_fields() : false);
    }

    /**
     * Generate CSV from a query result object
     *
     * @param array $title An optional row of column names to include in the CSV
     * @param string $delim Delimiter (default: ,)
     * @param string $newline Newline character (default: \n)
     * @param string $enclosure Enclosure (default: ")
     * @return    string
     */
    public function toCsv($title = [], $delim = ',', $newline = "\n", $enclosure = '"')
    {
        $query = $this->nrResult;
        if (!is_object($query) or !method_exists($query, 'list_fields')) {
            show_error('You must submit a valid result object');
        }

        $out = '';
        // First generate the headings from the table column names
        foreach ($this->listFields() as $name) {
            $out .= $enclosure . str_replace($enclosure, $enclosure . $enclosure, $name) . $enclosure . $delim;
        }

        $out = substr($out, 0, -strlen($delim)) . $newline;

        // Next blast through the result array and build out the rows
        while ($row = $query->unbuffered_row('array')) {
            $line = array();
            foreach ($row as $item) {
                $line[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $item) . $enclosure;
            }
            $out .= implode($delim, $line) . $newline;
        }

        return $out;
    }

    /**
     * Generate HTML file from a query result object
     *
     * @param array $file_name HTML file name
     * @return    string
     */
    public function saveHtml($file_name)
    {
        $query = $this->nrResult;
        if (!is_object($query) or !method_exists($query, 'list_fields')) {
            show_error('You must submit a valid result object');
        }

        if (!$fp = @fopen($file_name, 'w')) {
            return null;
        }

        // file lock
        flock($fp, LOCK_EX);

        fwrite($fp, '<meta http-equiv="Content-Type" content="application/vnd.ms-excel; charset=utf-8"><table border="1" cellpadding="0" cellspacing="0">');

        $pRow = ['<tr>'];
        // First generate the headings from the table column names
        foreach ($this->listFields() as $name) {
            $pRow[] = sprintf('<th align="center" style=mso-number-format:"\@">%s</th>', $name);
        }
        $pRow[] = '</tr>';
        fwrite($fp, implode("\n", $pRow));


        // Next blast through the result array and build out the rows
        while ($row = $query->unbuffered_row('array')) {
            $pRow = ['<tr>'];
            foreach ($row as $item) {
                $pRow[] = sprintf('<td style=mso-number-format:"\@">%s</td>', $column);
            }
            $pRow[] = '</tr>';
            fwrite($fp, implode("\n", $pRow));
        }

        fwrite($fp, '</table>');

        // file unlock
        flock($fp, LOCK_UN);
        fclose($fp);

        return realpath($file_name);
    }

    /**
     * Generate HTML file from a query result object
     *
     * @param array $file_name HTML file name
     * @return    string
     */
    public function saveCsv($file_name)
    {
        $query = $this->nrResult;
        if (!is_object($query) or !method_exists($query, 'list_fields')) {
            show_error('You must submit a valid result object');
        }

        if (!$fp = @fopen($file_name, 'w')) {
            return null;
        }

        // file lock
        flock($fp, LOCK_EX);

        // utf8 header
        fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));
        // First generate the headings from the table column names
        fputcsv($fp, $this->listFields());

        // Next blast through the result array and build out the rows
        while ($row = $query->unbuffered_row('array')) {
            fputcsv($fp, $row);
        }
        // file unlock
        flock($fp, LOCK_UN);
        fclose($fp);

        return realpath($file_name);
    }
}