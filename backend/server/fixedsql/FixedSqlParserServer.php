<?php 
namespace backend\server\fixedsql;

/**
* FixedSQL语法分析
*/
class FixedSqlParserServer
{
	/**
	 * 允许的操作类型
	 */
	public static $allow_operate_type_list = ['delete', 'update', 'insert', 'select'];

	/**
	 * 忽略的操作类型
	 */
	public static $skip_operate_type_list = ['insert', 'select'];

	/**
	 * 解析结果状态
	 */
	public static $parse_status_list = [	'01' => 'failed',
											'02' => 'skip',
											'03' => 'success'	];

	/**
	 * 确定操作类型
	 */
	private $_operate_type = null;

	/**
	 * 针对不同操作类型具体负责解析sql的对象
	 */
	private $_parser = null;

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
	 * 建造工厂，将解析的任务转移给对应处理对象
	 */
	private function _factory($operate_type)
	{
		switch($operate_type) {
			case 'delete':
				$class_name = __NAMESPACE__ .'\FixedDeleteParser';
				return new $class_name();
				break;
			case 'update':
				$class_name = __NAMESPACE__ .'\FixedUpdateParser';
				return new $class_name();
				break;
			default:
				throw new \Exception("该操作类型暂不支持");
				break;
		}			
	}

	/**
	 * 校验操作类型
	 */
	private function _confirmOperateType($sql)
	{
		/* 返回结果 */
		$return_data = ['status' => static::$parse_status_list['01'], 'reason' => 'sql解析失败'];

		/* 校验操作类型 */
		$this->_operate_type = current(explode(' ', $sql));
		if( !in_array(strtolower($this->_operate_type), static::$allow_operate_type_list) ) {
			$suport_type = implode(',', static::$allow_operate_type_list);
			$return_data['reason'] = "仅支持{$suport_type}类型的操作";
			return $return_data;
		} elseif( in_array($this->_operate_type, static::$skip_operate_type_list) ) {
			$skip_operate_type = implode(',', static::$skip_operate_type_list);
			$return_data['status'] = static::$parse_status_list['02'];
			$return_data['reason'] = "{$skip_operate_type}类型的操作在备份时将被忽略,本次被忽略语句如下:\r\n{$sql}";
			return $return_data;
		}

		/* 返回最终结果 */
		$return_data['status'] = static::$parse_status_list['03'];
		$return_data['reason'] = '';
		return $return_data;
	}

	/**
	 * 解析sql的分析结果
	 */
	public function parseSql($sql = null)
	{
		/* 返回结果 */
		$return_data = ['status' => static::$parse_status_list['01'], 'reason' => 'sql解析失败', 'data' => ''];

		/* 确认操作类型 */
		$sql = str_replace(["\r\n", "\r", "\n"], ' ', trim(strtolower($sql)) );
		$confirm_result = $this->_confirmOperateType($sql);
		if($confirm_result['status'] != static::$parse_status_list['03']) {
			$return_data['status'] = $confirm_result['status'];
			$return_data['reason'] = $confirm_result['reason'];
			return $return_data;
		}

		/* 基于操作类型，将操作任务交给对应的处理对象 */
		try{
			$this->_parser = $this->_factory($this->_operate_type);
			$element_list = $this->_parser->parseFixedSql($sql)->getElementsForBackup();
			if(empty($element_list)) {
				throw new Exception('未能得到备份数据需要的相关元素');
			}
			$return_data['status'] = static::$parse_status_list['03'];
			$return_data['reason'] = '';
			$return_data['data'] = $element_list;
		} catch(\Exception $e) {
			$reason = $e->getMessage();
			$return_data['status'] = static::$parse_status_list['01'];
			$return_data['reason'] = $reason;
			$return_data['data'] = '';
		}

		/* 返回解析结果 */
		return $return_data;
	}
}
