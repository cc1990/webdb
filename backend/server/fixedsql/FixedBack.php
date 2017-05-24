<?php 
namespace backend\server\fixedsql;
use SqlParser\Parser;

/**
 * 执行修正数据的备份
 */
class FixedBack
{
	/**
	 * sql解析对象
	 */	
	public static $instance = null;

	/**
	 * 获取当前实例
	 */
	public static function getInstance()
	{
		if(!static::$instance instanceof static) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * 开始备份
	 */
	public function backupData($mysql_server_ip = null, $db_name, $table_name, $where)
	{
        $filename = date("YmdHis",time())."_".$mysql_server_ip."_".$db_name."_".$table_name.".sql";
		if(empty($mysql_server_ip)) {
			throw new \Exception('mysql实例的ip为空');
		}
		if(empty($db_name)) {
			throw new \Exception('数据库名称为空');
		}
		if(empty($table_name)) {
			throw new \Exception('表名称为空');
		}
		if(empty($where)) {
			throw new \Exception('备份条件为空');
		}
		$back_sql = " --databases {$db_name} --tables {$table_name} --where='{$where}' ";
        exec("nohup sh /data/scripts/php_webdb_mysqldump.sh {$mysql_server_ip} \"{$back_sql}\" $filename&",$outcome,$status);
		var_dump($outcome);die();
        return $status;
	}
}
