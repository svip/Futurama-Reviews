<?php
//The class db_driver
class db_driver {
	private $sql = array(
			'host' => 'localhost',
			'name' => '',
			'user' => 'root',
			'pass' => '',
	);
	
	private $con_id 	= '';
	private $query_array = array();
	private $id_array = array();
	private $query_amount = 0;
	
	function db_driver($dbhost, $dbname, $dbuser, $dbpass) {
		$this->sql['host'] = $dbhost;
		$this->sql['name'] = $dbname;
		$this->sql['user'] = $dbuser;
		$this->sql['pass'] = $dbpass;
	}

	/**
	 * This function connects to the host and then the database.
	 * 
	 * @return TRUE upon succes, FALSE upon failure
	 */
	function connect() {
		$this->con_id = @mysql_connect($this->sql['host'], $this->sql['user'], $this->sql['pass']);
		if ( !mysql_select_db($this->sql['name'], $this->con_id) )
		{
			return false;
		}
		return true;
	}
	
	/**
	 * This function gets a function and returns it.  If $store is given, it inserts the
	 * resource in the query_array at the position of $store.  If $store isn't an int, it
	 * will then be stored in the standard query_id.  $store cannot be 0 or negative.
	 * 
	 * @param $sql string The query
	 * @param $store int[optional] Where to store the resource
	 * @return The query resource on succes, FALSE upon failure
	 */
	function query($sql, $store = 0) {
		//echo $sql."<br /><br /><br />\n";
		if((is_numeric($store)) && ($store>0)) {
				$this->query_array[$store] = "\0";
				if($this->query_array[$store] = mysql_query($sql, $this->con_id)) {
					$this->id_array[$store] = mysql_insert_id($this->con_id);
					$this->query_amount++;
					return $this->query_array[$store];
				}
				$this->id_array[$store] = false;
				return false;
		}
		$this->query_array[0] = "\0";
		if($this->query_array[0] = mysql_query($sql, $this->con_id)) {
			$this->id_array[0] = mysql_insert_id($this->con_id);
			$this->query_amount++;
			return $this->query_array[0];
		}
		$this->id_array[0] = false;
		return false;
	}
	
	/**
	 * This function returns the current query_id's row's information.  If $store is
	 * set, the resource at that point's row information will be returned.  If $store
	 * is not an int or is below 1, it will return the usual query_id.
	 * 
	 * @param $store int[optional] Where to get the result from
	 * @return The current row, FALSE upon failure
	 */
	function get_result($store = 0) {
		if((is_numeric($store)) && ($store>0)) {
				if($this->query_array[$store]) {
					return mysql_fetch_assoc($this->query_array[$store]);
				}
				return false;
		}
		if($this->query_array[0]) {
			return mysql_fetch_assoc($this->query_array[0]);
		}
		return false;
	}
	
	/**
	 * This function gets the amount of rows in the current resource.
	 * 
	 * @param $store int[optional] What resource to get the information from
	 * @return int, 0 upon no resource or 0 rows
	 */
	function get_num_rows($store = 0) {
		if((is_numeric($store)) && ($store>0)) {
			return mysql_num_rows($this->query_array[$store]);
		}
		return @mysql_num_rows($this->query_array[0]);
	}
	
	/**
	 * This function returns the id of the last INSERT INTO query.
	 * 
	 * @param $store int[optional] Resource to optain id
	 * @return int the id of the last insert query, FALSE upon failure
	 */
	function get_insert_id($store = 0) {
		return $this->id_array[$store];
	}
	
	function get_query_amount() {
		return $this->query_amount;
	}
}
?>
