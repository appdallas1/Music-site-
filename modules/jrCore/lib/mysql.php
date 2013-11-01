<?php
/**
 * Jamroom 5 jrCore module
 *
 * copyright 2003 - 2013 by The Jamroom Network - All Rights Reserved
 * http://www.jamroom.net
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0.  Please see the included "license.html" file.
 *
 * This module may include works that are not developed by The Jamroom
 * Network and are used under license - any licenses are included and
 * can be found in the "contrib" directory within this module.
 *
 * Jamroom may use modules and skins that are licensed by third party
 * developers, and licensed under a different license than the Jamroom
 * Core - please reference the individual module or skin license that
 * is included with your download.
 *
 * This software is provided "as is" and any express or implied
 * warranties, including, but not limited to, the implied warranties
 * of merchantability and fitness for a particular purpose are
 * disclaimed.  In no event shall the Jamroom Network be liable for
 * any direct, indirect, incidental, special, exemplary or
 * consequential damages (including but not limited to, procurement
 * of substitute goods or services; loss of use, data or profits;
 * or business interruption) however caused and on any theory of
 * liability, whether in contract, strict liability, or tort
 * (including negligence or otherwise) arising from the use of this
 * software, even if advised of the possibility of such damage.
 * Some jurisdictions may not allow disclaimers of implied warranties
 * and certain statements in the above disclaimer may not apply to
 * you as regards implied warranties; the other terms and conditions
 * remain enforceable notwithstanding. In some jurisdictions it is
 * not permitted to limit liability and therefore such limitations
 * may not apply to you.
 *
 * @package MySQL
 * @copyright 2012 Talldude Networks, LLC.
 * @author Brian Johnson <brian [at] jamroom [dot] net>
 */

// make sure we are not being called directly
defined('APP_DIR') or exit();

/**
 * Get total number of Rows in a Table
 *
 * @param string $module Module to return count of
 * @param string $table Table to return count of
 * @return int Returns the number of rows in the table
 */
function jrCore_db_number_rows($module,$table)
{
    $tbl = jrCore_db_table_name($module,$table);
    $req = "SHOW TABLE STATUS LIKE '{$tbl}'";
    $_rt = jrCore_db_query($req,'SINGLE');
    if (isset($_rt['Rows']) && is_numeric($_rt['Rows'])) {
        return intval($_rt['Rows']);
    }
    return 0;
}

/**
 * Check if a MySQL table exists
 *
 * @param string $module Module to check table for
 * @param string $table Table to check
 * @return bool
 */
function jrCore_db_table_exists($module,$table)
{
    $table = jrCore_db_table_name($module,$table);
    $req = "DESCRIBE {$table}";
    $_rt = jrCore_db_query($req,'NUMERIC',false,null,false);
    if (isset($_rt) && is_array($_rt)) {
        return true;
    }
    return false;
}

/**
 * Return array of column definitions in a table
 *
 * @param string $module Module to return column names for
 * @param string $table Table to return column names for
 * @return array Returns an array of column names/data
 */
function jrCore_db_table_columns($module,$table)
{
    $table = jrCore_db_table_name($module,$table);
    // See if we have already done this table
    $_uniq = jrCore_get_flag("jrcore_db_table_columns_{$table}");
    if ($_uniq) {
        return $_uniq;
    }
    $req = "DESCRIBE {$table}";
    $_rt = jrCore_db_query($req,'NUMERIC');
    if (isset($_rt) && is_array($_rt)) {
        $_tmp = array();
        foreach ($_rt as $_col) {
            $_tmp["{$_col['Field']}"] = $_col;
            if (isset($_col['Extra']) && strpos(' '. $_col['Extra'],'auto_increment')) {
                $_tmp['JRCORE_DB_TABLE_AUTO_INCREMENT_FIELD'] = $_col['Field'];
            }
        }
        jrCore_set_flag("jrcore_db_table_columns_{$table}",$_tmp);
        return $_tmp;
    }
    return false;
}

/**
 * Returns constructed table name for module/table
 *
 * @param string $module Module Name
 * @param string $table Table Name
 * @return string Returns constructed table name
 */
function jrCore_db_table_name($module,$table)
{
    global $_conf;
    if (!isset($_conf['jrCore_db_prefix'])) {
        $_conf['jrCore_db_prefix'] = 'jr_';
    }
    return strtolower("{$_conf['jrCore_db_prefix']}{$module}_{$table}");
}

/**
 * Escape a string for DB insertion
 *
 * @param mixed $data String or Array to have data escaped for MySQL insertion
 * @return mixed Returns String or Array ready for db insertion
 */
function jrCore_db_escape($data)
{
    if (isset($data) && is_array($data)) {
        foreach ($data as $key => $val) {
            $data[$key] = jrCore_db_escape($val);
        }
    }
    else {
        $temp = jrCore_db_connect();
        $data = mysqli_real_escape_string($temp,$data);
    }
    return $data;
}

/**
 * Connect to the MySQL database
 *
 * <b>NOTE:</b> This function does not need to be called directly in Jamroom.
 * The jrCore_db_query() function will call it as needed if it sees that the
 * Jamroom Database has not been connected to.
 *
 * @return mysqli Returns database resource on success, exits on failure
 */
function jrCore_db_connect()
{
    global $_conf;
    $tmp = jrCore_get_flag('jrcore_dbconnect_mysqli_object');
    if ($tmp) {
        return $tmp;
    }
    $myi = mysqli_init();
    if (!$myi || !is_object($myi)) {
        jrCore_notice('CRI',"unable to initialize MySQL DB connection using mysqli_init() - check PHP Error Log");
    }
    $tmp = @mysqli_real_connect($myi,$_conf['jrCore_db_host'],$_conf['jrCore_db_user'],$_conf['jrCore_db_pass'],$_conf['jrCore_db_name'],$_conf['jrCore_db_port'],null,MYSQLI_CLIENT_FOUND_ROWS);
    if (!isset($tmp) || !$tmp) {
        // sleep for a second and try again
        sleep(1);
        $tmp = @mysqli_real_connect($myi,$_conf['jrCore_db_host'],$_conf['jrCore_db_user'],$_conf['jrCore_db_pass'],$_conf['jrCore_db_name'],$_conf['jrCore_db_port'],null,MYSQLI_CLIENT_FOUND_ROWS);
        if (!isset($tmp) || !$tmp) {
            jrCore_notice('CRI',"Error connecting to MySQL Server: ". mysqli_connect_error() .' (#'. mysqli_connect_errno() .') - the site owner should contact their hosting provider for assistance.');
        }
    }
    mysqli_set_charset($myi,'utf8');
    jrCore_set_flag('jrcore_dbconnect_mysqli_object',$myi);
    return $myi;
}

/**
 * Close the connection to the MySQL database
 *
 * <b>Note:</b> There is no need to call this function unless your script enters
 * a long processing segment where the database connection is no longer
 * needed, then it is a good idea to free the database resource.
 *
 * @return bool Returns true on success
 */
function jrCore_db_close()
{
    $tmp = jrCore_get_flag('jrcore_dbconnect_mysqli_object');
    if ($tmp) {
        if (mysqli_close($tmp)) {
            jrCore_delete_flag('jrcore_dbconnect_mysqli_object');
            return true;
        }
        return false;
    }
    return true;
}

/**
 * Send a Query to the MySQL database
 *
 * The jrCore_db_query function is the main function used to send MySQL queries to the database.
 *
 * Valid <b>$return</b> values are:
 *<br>
 * * (null)     - if empty or null, the Database connection resource is returned for raw processing<br>
 * * <b>NUMERIC</b>    - returns a multi-dimensional array, numerically indexed beginning at 0<br>
 * * <b>SINGLE</b>     - a single-dimension array is returned<br>
 * * <b>COUNT</b>      - the number of rows affected by an INSERT, DELETE or UPDATE query<br>
 * * <b>INSERT_ID</b>  - the id from the auto_increment column of the table from the last INSERT query<br>
 * * <b>NUM_ROWS</b>   - the number of rows returned from a SELECT query<br>
 * * <b>$column</b>    - if name of valid column given, will be used as key in associative array. NOTE: the $column used <b>must</b> be a column that is returned as part of the SELECT statement.<br>
 *<br>
 * @param string $query The MySQL query to send to the database
 * @param string $return format of the results you want returned from the query.
 * @param bool $multi Set to true if Query is a Transaction
 * @param string $only_val Set to a valid database table COLUMN name, then ONLY the value for that column will be returned (instead of an array of values)
 * @param bool $exit_on_error By default we exit when a database error is encountered
 * @return mixed Returns multiple formats - see list of $return values above for details
 */
function jrCore_db_query($query,$return = null,$multi = false,$only_val = null,$exit_on_error = true)
{
    // make sure we get a query
    if (!isset($query) || strlen($query) === 0) {
        return false;
    }

    $tmp = jrCore_db_connect();

    // If our $multi flag is true we use our multi_query function so multiple SQL
    // queries can be run in one shot
    if ($multi) {
        $res = mysqli_multi_query($tmp,$query) or $err = 'Query Error: '. mysqli_error($tmp);
    }
    else {
        $res = mysqli_query($tmp,$query) or $err = 'Query Error: '. mysqli_error($tmp);
    }
    if (isset($err) && strlen($err) > 0) {
        if (strpos($query,'DESCRIBE') !== 0) {
            fdebug($err,$query); // OK
            jrCore_logger('CRI',"{$err} - check Debug Log");
        }
        if ($exit_on_error) {
            jrCore_notice('CRI',$err);
        }
        return false;
    }

    if (is_null($return) || $return === false) {
        if ($multi) { jrCore_db_close(); }
        return $res;
    }
    switch ($return) {

        case 'SINGLE':
            $_tmp = mysqli_fetch_assoc($res);
            if ($multi) { jrCore_db_close(); }
            return $_tmp;
            break;

        case 'COUNT':
            $num = (int) mysqli_affected_rows($tmp);
            if ($multi) { jrCore_db_close(); }
            return $num;
            break;

        case 'NUM_ROWS':
            $num = (int) mysqli_num_rows($res);
            if ($multi) { jrCore_db_close(); }
            return $num;
            break;

        case 'INSERT_ID':
            $num = (int) mysqli_insert_id($tmp);
            if ($multi) { jrCore_db_close(); }
            return $num;
            break;

        default:
            if ($res) {
                $num = mysqli_num_rows($res);
            }
            else {
                return false;
            }
            // more than 1 row - return multidimensional array that is
            // either numeric based (base 0) or associative based if $akey given
            if ($num >= 1) {
                $_rt = array();
                $i = 0;
                while ($row = mysqli_fetch_assoc($res)) {
                    if ($return == 'NUMERIC') {
                        $_rt[$i] = $row;
                        $i++;
                    }
                    elseif (isset($only_val{0})) {
                        $_rt["{$row[$return]}"] = $row[$only_val];
                    }
                    else {
                        $_rt["{$row[$return]}"] = $row;
                    }
                }
                mysqli_free_result($res);
                if ($multi) { jrCore_db_close(); }
                return (isset($_rt) && is_array($_rt) && count($_rt) > 0) ? $_rt : false;
            }
            // 0 rows
            if ($multi) { jrCore_db_close(); }
    }
    return false;
}

/**
 * Paginate result sets from the MySQL database
 *
 * The dbPagedQuery function is a "paginator" for db results.  It does
 * NOT create any of the output, but will return an array consisting
 * of the actual data, and the "prev" and "next" pages, if they exist.
 * Use in place of jrCore_db_query if you need paginated results.
 *
 * @param string $query SQL query to run
 * @param int $page_num Numerical offset (start) of first row to return (default 0)
 * @param int $rows_per_page Number of rows to return
 * @param string $_ret Return type for data.  This can be any of the valid return
 *        types as used by jrCore_db_query().
 * @param string $c_query optional Counting Query that will be used in place of primary
 *        query to retrieve row count.  If the main query is very complex, this
 *        can seriously speed up the results.  Note that an int can be provided
 *        as well, and that will be used for the total.
 * @param string $only_val Only return this value from result set
 * @return array returns array of data
 */
function jrCore_db_paged_query($query,$page_num,$rows_per_page,$_ret = 'NUMERIC',$c_query = null,$only_val = null)
{
    // LIMIT should not be included in query
    if (isset($query) && strstr($query,'LIMIT')) {
        jrCore_notice('CRI',"jrCore_db_paged_query() do not include a LIMIT clause in your SQL query - it is added automatically");
    }
    if (is_null($c_query) || $c_query === false | strlen($c_query) === 0) {
        $c_query = $query;
    }
    // Get total number of rows
    if (isset($c_query) && jrCore_checktype($c_query,'number_nn')) {
        $total = intval($c_query);
    }
    else {
        $total = jrCore_db_query($c_query,'NUM_ROWS');
    }
    // For our query, we can't use the page number - we have to figure out
    // the offset based on the number of rows per page * page number - 1
    $start  = intval(($page_num - 1) * $rows_per_page);
    $query .= " LIMIT {$start},{$rows_per_page}";

    // get our results
    $_out = array(
        'info' => array()
    );
    // now figure out if we have "prev" or "next" links.
    if ($start === 0 && $total <= $rows_per_page) {
        $_out['info']['prev_page'] = false;
        $_out['info']['next_page'] = false;
        $_out['info']['this_page'] = 1;
    }
    elseif ($start === 0) {
        $_out['info']['prev_page'] = false;
        $_out['info']['next_page'] = 2;
        $_out['info']['this_page'] = 1;
    }
    elseif (($start + $rows_per_page) >= $total) {
        $_out['info']['prev_page'] = intval($page_num - 1);
        $_out['info']['next_page'] = false;
        $_out['info']['this_page'] = (int) ceil($total / $rows_per_page);
    }
    else {
        $_out['info']['prev_page'] = intval($page_num - 1);
        $_out['info']['next_page'] = intval($page_num + 1);
        $_out['info']['this_page'] = intval($page_num);
    }
    $_out['info']['total_items']  = intval($total);
    $_out['info']['total_pages'] = (int) ceil($total / $rows_per_page);
    // and now get our data
    $_out['_items'] = jrCore_db_query($query,$_ret,false,$only_val);
    return $_out;
}

/**
 * Verify a table schema in the MySQL database
 *
 * The dbVerifyTable function is used to create a MySQL database
 * table (if it does not exist), or validate the columns if it does exist.
 *
 * @param string $module Module table belongs to
 * @param string $table Table to validate
 * @param array $_schema MySQL Database creation SQL query - this will be executed if the table does not exist.
 * @param string $engine MySQL engine to use for table - "InnoDB" or "MyISAM"
 * @return bool Returns true/false on success/fail
 */
function jrCore_db_verify_table($module,$table,$_schema,$engine = 'MyISAM')
{
    if (!isset($table) || strlen($table) === 0) {
        jrCore_notice('CRI',"jrCore_db_verify_table() empty or missing table name");
    }
    if (!isset($_schema) || !is_array($_schema)) {
        jrCore_notice('CRI',"jrCore_db_verify_table() invalid schema array - ensure table columns and indexes are defined in an array");
    }
    // Cleanup
    foreach ($_schema as $k => $line) {
        $_schema[$k] = trim(trim($line),',');
    }
    // get our info about this table (if it exists)
    $tbl = jrCore_db_table_name($module,$table);
    $req = "DESCRIBE {$tbl}";
    $_rt = jrCore_db_query($req,'Field',false,null,false);
    if (!isset($_rt) || !is_array($_rt)) {
        // Create
        $crt = jrCore_db_query("CREATE TABLE {$tbl} (". implode(', ',$_schema) .') ENGINE='. $engine .' DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci',null,false,null,false);
        if ($table != 'log') {
            jrCore_logger('INF',"jrCore_db_verify_table() created missing table: {$tbl}");
        }
        if ($crt) {
            return true;
        }
        return false;
    }

    // It appears the table already exists.  Now we want to scan the incoming creation
    // schema and verify that each of our columns exist.  First load our indexes
    $req = "SHOW INDEX FROM {$tbl}";
    $_in = jrCore_db_query($req,'Key_name');
    foreach ($_schema as $line) {

        // TABLE start/end and PRIMARY KEY entries - skip
        if (strstr($line,'PRIMARY KEY') || strlen($line) === 0) {
            continue;
        }

        // INDEX
        // INDEX band_id (band_id)
        // INDEX stat_index (stat_index) )
        // INDEX band_name (band_name(15))
        if (stripos($line,'INDEX') === 0) {
            $idx_ikey = jrCore_string_field($line,2);
            $idx_name = trim(str_replace('`','',$idx_ikey));
            // Make sure it is built
            if (!isset($_in[$idx_name])) {
                $idx_args = trim(str_ireplace('INDEX '. $idx_ikey,'',$line));
                $req = "ALTER TABLE {$tbl} ADD INDEX `{$idx_name}` {$idx_args}";
                jrCore_db_query($req,null,false,null,false);
                // now let's see if we were successful in adding our INDEX or NOT
                $req = "SHOW INDEX FROM {$tbl}";
                $_tp = jrCore_db_query($req,'Key_name',false,null,false);
                if (is_array($_tp[$idx_name])) {
                    jrCore_logger('INF',"jrCore_db_verify_table() created missing table index: {$idx_name} in table: {$tbl}");
                }
                else {
                    jrCore_logger('CRI',"jrCore_db_verify_table() unable to create missing table index: {$idx_name} in table: {$tbl}");
                }
            }
        }

        // UNIQUE
        // UNIQUE band_id (band_id)
        // UNIQUE stat_index (stat_index) )
        // UNIQUE band_name (band_name(15))
        elseif (stripos($line,'UNIQUE') === 0) {
            $idx_ikey = jrCore_string_field($line,2);
            $idx_name = trim(str_replace('`','',$idx_ikey));
            // Make sure it is built
            if (!isset($_in[$idx_name])) {
                $idx_args = trim(str_ireplace('UNIQUE '. $idx_ikey,'',$line));
                $req = "ALTER TABLE {$tbl} ADD UNIQUE INDEX `{$idx_name}` {$idx_args}";
                jrCore_db_query($req,null,false,null,false);
                // now let's see if we were successful in adding our INDEX or NOT
                $req = "SHOW INDEX FROM {$tbl}";
                $_tp = jrCore_db_query($req,'Key_name',false,null,false);
                if (is_array($_tp[$idx_name])) {
                    jrCore_logger('INF',"jrCore_db_verify_table() created missing table unique index: {$idx_name} in table: {$tbl}");
                }
                else {
                    jrCore_logger('CRI',"jrCore_db_verify_table() unable to create missing table unique index: {$idx_name} in table: {$tbl}");
                }
            }
        }

        // COLUMN
        else {
            $col_ikey = jrCore_string_field($line,1);
            $col_name = trim(str_replace('`','',$col_ikey));
            // Make sure it is built
            if (!isset($_rt[$col_name])) {
                $col_args = trim(str_replace($col_name .' ','',str_replace('`','',$line)));
                $req = "ALTER TABLE {$tbl} ADD `{$col_name}` {$col_args}";
                jrCore_db_query($req,null,false,null,false);
                // now let's see if we were successful in adding our column or NOT
                $req = "DESCRIBE {$tbl}";
                $_tp = jrCore_db_query($req,'Field',false,null,false);
                if (is_array($_tp[$col_name])) {
                    jrCore_logger('INF',"jrCore_db_verify_table() created missing table column: {$col_name} in table: {$tbl}");
                }
                else {
                    jrCore_logger('CRI',"jrCore_db_verify_table() unable to create missing table column: {$col_name} in table: {$tbl}");
                }
            }
        }
    }
    return true;
}
