<?php 
namespace backend\server\fixedsql;

/**
 * delete修订语句语法分析
 */
class FixedDeleteParser extends FixedBase
{
	/**
	 * 表别名
	 */
	private $_alias_table_name = null;

	/**
	 * 表名称
	 */
	private $_table_name = null;

	/**
	 * where条件
	 */
	private $_where_condition = null;

	/**
	 * 解析要执行的订正sql
	 */
	public function parseFixedSql($sql)
	{
		/* 初始化解析对象 */
		$this->initPhpmyadminParser($sql);

		/* 设置表名称 */
		$this->_setTableName();

		/* 设置where条件 */
		$this->_setWhereCondition();

		return $this;
	}

	/**
	 * 设置表名称
	 */
	private function _setTableName()
	{
		$statement = current($this->phpmyadmin_parser->statements);
		if(!isset($statement->from)) {
			throw new \Exception('解析sql语句时并未发现表名称');
		}
		foreach($statement->from as $item) {
			$this->_table_name[] = $item->table;
			$alias_table_name = isset($item->alias) ? trim($item->alias) : '';
			$this->_alias_table_name[$item->table] = $alias_table_name;
		}
	}

	/**
	 * 获取表名称
	 */
	private function _getTableName()
	{
		return $this->_table_name;	
	}

	/**
	 * 获取表别名
	 */
	private function _getAliasTableName()
	{
		return $this->_alias_table_name;	
	}

	/**
	 * 设置where条件
	 */
	private function _setWhereCondition()
	{
		$statement = current($this->phpmyadmin_parser->statements);
		if(!isset($statement->where)) {
			throw new \Exception('解析sql语句时并未发现where条件');
		}
		$wherestr = '';
		foreach($statement->where as $item) {
			$wherestr .= ' ' . $item->expr;
		}
		$this->_where_condition = $wherestr;
	}

	/**
	 * 获取where条件
	 */
	private function _getWhereCondition()
	{
		return $this->_where_condition;	
	}

	/**
	 * 获取备份需要的必要条件
	 */
	public function getElementsForBackup()
	{
		return [
			'table_name' => $this->_getTableName(),
			'alias_table_name' => $this->_getAliasTableName(),
			'where_condition' => $this->_getWhereCondition()
		];	
	}
}
