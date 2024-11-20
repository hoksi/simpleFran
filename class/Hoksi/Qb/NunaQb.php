<?php
namespace Hoksi\Qb;

/**
 * Description of NunaQB
 *
 * @author hoksi
 */

class NunaQb extends CI_DB_query_builder
{
    public $stmt_id;
    public $curs_id;
    public $limit_used;
    public $dbversion;
    protected $_quoted_identifier;

    public function __construct($params)
    {
        parent::__construct($params);

        $this->driver($this->dbdriver);
    }

    public function driver($driver = 'mysqli')
    {
        $this->dbdriver = $driver;

        $this->_escape_char          = '`';
        $this->_reserved_identifiers = array('*');
        $this->_random_keyword       = array('RAND()', 'RAND(%d)');
        $this->_count_string         = 'SELECT COUNT(*) AS ';

        switch ($driver) {
            case 'cubrid':
                $this->_escape_char          = '"';
                $this->_random_keyword       = array('RANDOM()', 'RANDOM(%d)');
            case 'mysqli':
            case 'mysql':
                $this->_escape_char          = '`';
                break;
            case 'oci8':
                $this->_escape_char          = '"';
                $this->_reserved_identifiers = array('*', 'rownum');
                $this->_random_keyword       = array('ASC', 'ASC');
                $this->_count_string         = 'SELECT COUNT(1) AS ';
                break;
            case 'odbc':
                $this->_escape_char          = '';
                $this->_like_escape_str      = " {escape '%s'} ";
                $this->_random_keyword       = array('RND()', 'RND(%d)');
                break;
            case 'postgre':
            case 'sqlite3':
            case 'sqlite':
                $this->_escape_char          = '"';
                $this->_random_keyword       = array('RANDOM()', 'RANDOM()');
                break;
            case 'sqlsrv':
            case 'mssql':
                $this->_escape_char          = '"';
                $this->_random_keyword       = array('NEWID()', 'RAND(%d)');
                $this->_quoted_identifier    = TRUE;
                break;
            case 'ibase':
                $this->_escape_char          = '"';
                $this->_random_keyword       = array('RAND()', 'RAND()');
                break;
        }
    }

    public function getCountString()
    {
        return $this->_count_string;
    }

    protected function _limit($sql)
    {
        $db_limit_func = $this->dbdriver.'_limit';

        if (method_exists($this, $db_limit_func)) {
            return $this->{$db_limit_func}($sql);
        } else {
            return parent::_limit($sql);
        }
    }

    protected function oci8_limit($sql)
    {
        if (version_compare($this->version(), '12.1', '>=')) {
            // OFFSET-FETCH can be used only with the ORDER BY clause
            empty($this->qb_orderby) && $sql .= ' ORDER BY 1';

            return $sql.' OFFSET '.(int) $this->qb_offset.' ROWS FETCH NEXT '.$this->qb_limit.' ROWS ONLY';
        }

        $this->limit_used = TRUE;
        return 'SELECT * FROM (SELECT inner_query.*, rownum rnum FROM ('.$sql.') inner_query WHERE rownum < '.($this->qb_offset + $this->qb_limit + 1).')'
            .($this->qb_offset ? ' WHERE rnum >= '.($this->qb_offset + 1) : '');
    }

    protected function ibase_limit($sql)
    {
        // Limit clause depends on if Interbase or Firebird
        if (stripos($this->version(), 'firebird') !== FALSE) {
            $select = 'FIRST '.$this->qb_limit
                .($this->qb_offset ? ' SKIP '.$this->qb_offset : '');
        } else {
            $select = 'ROWS '
                .($this->qb_offset ? $this->qb_offset.' TO '.($this->qb_limit + $this->qb_offset) : $this->qb_limit);
        }

        return preg_replace('`SELECT`i', 'SELECT '.$select, $sql, 1);
    }

    protected function mssql_limit($sql)
    {
        $limit = $this->qb_offset + $this->qb_limit;

        // As of SQL Server 2005 (9.0.*) ROW_NUMBER() is supported,
        // however an ORDER BY clause is required for it to work
        if (version_compare($this->version(), '9', '>=') && $this->qb_offset && !empty($this->qb_orderby)) {
            $orderby = $this->_compile_order_by();

            // We have to strip the ORDER BY clause
            $sql = trim(substr($sql, 0, strrpos($sql, $orderby)));

            // Get the fields to select from our subquery, so that we can avoid CI_rownum appearing in the actual results
            if (count($this->qb_select) === 0 OR strpos(implode(',', $this->qb_select), '*') !== FALSE) {
                $select = '*'; // Inevitable
            } else {
                // Use only field names and their aliases, everything else is out of our scope.
                $select       = array();
                $field_regexp = ($this->_quoted_identifier) ? '("[^\"]+")' : '(\[[^\]]+\])';
                for ($i = 0, $c = count($this->qb_select); $i < $c; $i++) {
                    $select[] = preg_match('/(?:\s|\.)'.$field_regexp.'$/i', $this->qb_select[$i], $m) ? $m[1] : $this->qb_select[$i];
                }
                $select = implode(', ', $select);
            }

            return 'SELECT '.$select." FROM (\n\n"
                .preg_replace('/^(SELECT( DISTINCT)?)/i', '\\1 ROW_NUMBER() OVER('.trim($orderby).') AS '.$this->escape_identifiers('CI_rownum').', ',
                    $sql)
                ."\n\n) ".$this->escape_identifiers('CI_subquery')
                ."\nWHERE ".$this->escape_identifiers('CI_rownum').' BETWEEN '.($this->qb_offset + 1).' AND '.$limit;
        }

        return preg_replace('/(^\SELECT (DISTINCT)?)/i', '\\1 TOP '.$limit.' ', $sql);
    }

    protected function sqlsrv_limit($sql)
    {
        // As of SQL Server 2012 (11.0.*) OFFSET is supported
        if (version_compare($this->version(), '11', '>=')) {
            // SQL Server OFFSET-FETCH can be used only with the ORDER BY clause
            empty($this->qb_orderby) && $sql .= ' ORDER BY 1';

            return $sql.' OFFSET '.(int) $this->qb_offset.' ROWS FETCH NEXT '.$this->qb_limit.' ROWS ONLY';
        }

        $limit = $this->qb_offset + $this->qb_limit;

        // An ORDER BY clause is required for ROW_NUMBER() to work
        if ($this->qb_offset && !empty($this->qb_orderby)) {
            $orderby = $this->_compile_order_by();

            // We have to strip the ORDER BY clause
            $sql = trim(substr($sql, 0, strrpos($sql, $orderby)));

            // Get the fields to select from our subquery, so that we can avoid CI_rownum appearing in the actual results
            if (count($this->qb_select) === 0 OR strpos(implode(',', $this->qb_select), '*') !== FALSE) {
                $select = '*'; // Inevitable
            } else {
                // Use only field names and their aliases, everything else is out of our scope.
                $select       = array();
                $field_regexp = ($this->_quoted_identifier) ? '("[^\"]+")' : '(\[[^\]]+\])';
                for ($i = 0, $c = count($this->qb_select); $i < $c; $i++) {
                    $select[] = preg_match('/(?:\s|\.)'.$field_regexp.'$/i', $this->qb_select[$i], $m) ? $m[1] : $this->qb_select[$i];
                }
                $select = implode(', ', $select);
            }

            return 'SELECT '.$select." FROM (\n\n"
                .preg_replace('/^(SELECT( DISTINCT)?)/i', '\\1 ROW_NUMBER() OVER('.trim($orderby).') AS '.$this->escape_identifiers('CI_rownum').', ',
                    $sql)
                ."\n\n) ".$this->escape_identifiers('CI_subquery')
                ."\nWHERE ".$this->escape_identifiers('CI_rownum').' BETWEEN '.($this->qb_offset + 1).' AND '.$limit;
        }

        return preg_replace('/(^\SELECT (DISTINCT)?)/i', '\\1 TOP '.$limit.' ', $sql);
    }

    protected function postgre_limit($sql)
    {
        return $sql.' LIMIT '.$this->qb_limit.($this->qb_offset ? ' OFFSET '.$this->qb_offset : '');
    }

    public function version()
    {
        return $this->dbversion;
    }

    public function set_dbversion($version)
    {
        $this->dbversion = $version;

        return $this;
    }

    public function hasWhare()
    {
        return empty($this->qb_where) === false;
    }

    /**
     * Insert_Batch
     *
     * Compiles batch insert strings and runs the queries
     *
     * @param	string	$table	Table to insert into
     * @param	array	$set 	An associative array of insert values
     * @param	bool	$escape	Whether to escape values and identifiers
     * @return	int	Number of rows inserted or FALSE on failure
     */
    public function insert_batch($table, $set = NULL, $escape = NULL, $batch_size = 100)
    {
        if ($set === NULL)
        {
            if (empty($this->qb_set))
            {
                return ($this->db_debug) ? $this->display_error('db_must_use_set') : FALSE;
            }
        }
        else
        {
            if (empty($set))
            {
                return ($this->db_debug) ? $this->display_error('insert_batch() called with no data') : FALSE;
            }

            $this->set_insert_batch($set, '', $escape);
        }

        if (strlen($table) === 0)
        {
            if ( ! isset($this->qb_from[0]))
            {
                return ($this->db_debug) ? $this->display_error('db_must_set_table') : FALSE;
            }

            $table = $this->qb_from[0];
        }

        // Batch this baby
        $sql = [];
        for ($i = 0, $total = count($this->qb_set); $i < $total; $i += $batch_size)
        {
            $sql[] = $this->_insert_batch($this->protect_identifiers($table, TRUE, $escape, FALSE), $this->qb_keys, array_slice($this->qb_set, $i, $batch_size));
        }

        $this->_reset_write();

        return $sql;
    }

    /**
     * Update_Batch
     *
     * Compiles an update string and runs the query
     *
     * @param	string	the table to retrieve the results from
     * @param	array	an associative array of update values
     * @param	string	the where key
     * @return	int	number of rows affected or FALSE on failure
     */
    public function update_batch($table, $set = NULL, $index = NULL, $batch_size = 100)
    {
        // Combine any cached components with the current statements
        $this->_merge_cache();

        if ($index === NULL)
        {
            return ($this->db_debug) ? $this->display_error('db_must_use_index') : FALSE;
        }

        if ($set === NULL)
        {
            if (empty($this->qb_set_ub))
            {
                return ($this->db_debug) ? $this->display_error('db_must_use_set') : FALSE;
            }
        }
        else
        {
            if (empty($set))
            {
                return ($this->db_debug) ? $this->display_error('update_batch() called with no data') : FALSE;
            }

            $this->set_update_batch($set, $index);
        }

        if (strlen($table) === 0)
        {
            if ( ! isset($this->qb_from[0]))
            {
                return ($this->db_debug) ? $this->display_error('db_must_set_table') : FALSE;
            }

            $table = $this->qb_from[0];
        }

        // Batch this baby
        $sql = [];
        for ($i = 0, $total = count($this->qb_set_ub); $i < $total; $i += $batch_size)
        {
            $sql[] = $this->_update_batch($this->protect_identifiers($table, TRUE, NULL, FALSE), array_slice($this->qb_set_ub, $i, $batch_size), $index);

            $this->qb_where = array();
        }

        $this->_reset_write();
        return $sql;
    }
}