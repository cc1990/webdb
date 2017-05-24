<?php 
namespace backend\server\fixedsql;
use SqlParser\Parser;

/**
 * 定义解析sql语句的基类
 */
abstract class FixedBase
{
	/**
	 * 解析结果状态
	 */
	public static $parse_status_list = [	'01' => 'failed',
											'02' => 'skip',
											'03' => 'success'	];

	/**
	 * phpmyadmin/sql-parser第三方工具包对象 
	 */
	public $phpmyadmin_parser = null;

	/**
	 * 初始化phpmyadmin/sql-parser第三方工具包对象
	 */
	public function initPhpmyadminParser($sql)
	{
		if(empty($sql)) {
			throw new Exception('要解析的sql语句为空');	
		} else {
			$phpmyadmin_parser = new Parser($sql);
			$this->phpmyadmin_parser = $phpmyadmin_parser;
		}
	}

	/**
	 * 解析要执行的订正sql
	 */
	abstract public function parseFixedSql($sql);

	/**
	 * 获取备份需要的元素，包括库名、表明、where条件
	 */
	abstract public function getElementsForBackup();
}
