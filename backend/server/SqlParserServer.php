<?php 
namespace backend\server;

use Yii;
use SqlParser\Parser;
use SqlParser\Lexer;
use SqlParser\UtfString;
use SqlParser\Utils\Error as ParserError;
use SqlParser\Components\Limit;

use backend\modules\operat\models\Select;
use backend\modules\operat\models\SelectWhite;
use backend\modules\operat\models\Authorize;

require_once '../../vendor/phpsqlparser/src/PHPSQLParser.php';
/**
* SQL语法分析
*/
class SqlParserServer
{

    /**
     * 分隔SQL语句
     * 将所有的SQL切分成列表形式
     * @return [type] [description]
     */
    public function separateSql( $sqlinfo ){
        $result = array();
        $sqlinfo = rtrim( $sqlinfo );
        $end_char = substr($sqlinfo, -1);
        //SQL语句以英文分号结束
        if ($end_char !== ';') {
            $result['error'] = '请输入完整的sql，并以英文分号;结尾！';
            return $result;
        }

        $lexer = new Lexer($sqlinfo);
        $parser = new Parser($lexer->list);
        $errors = ParserError::get(array($lexer, $parser));

        if( !empty( $errors ) ){
            //$result['error'] = 'SQL语法错误：有未识别出SQL类型的SQL语句，请参照MySQL数据库开发设计规范编写SQL语句！';
            $result['error'] = 'SQL语句异常：' . substr($errors[0][0], 0, -1) . " " . $errors[0][2] . "，请检查！";
            return $result;
        }

        $sqlinfo = ';' . $sqlinfo . '#';
        $rule_key_words = Yii::$app->params['regexp']['rule_key_words'];
        //第一次分割规则，连同注释和SQL一起
        $rule = "(?:#|$rule_key_words)";

        preg_match_all("/;((?:\s|\r\n)*" . $rule . ")[\s\S]*?(?=;(?:\s|\r\n)*" . $rule . ")/i", $sqlinfo, $info);
        $info = $info[0];
        $nextadd = '';
        if( empty( $info ) ){
           $result['error'] = '注释格式不正确，请以#开始！';
            return $result;
        }
        
        return $result['list'] = $info;
    }


    /**
     * 获取select查询语句limit值
     * @param  [type] $sql [description]
     * @param  [type] $nums [默认可查询的条数]
     * @return [type]      [description]
     */
    public function getSelectLimit( $sql, $nums = 500 )
    {
        $sql_action = explode(" ", strtolower( rtrim($sql) ))[0];
        if( $sql_action != 'select' ){
            return null;
        }

        $parser = new Parser($sql);
        $stmt = $parser->statements[0];
        $limit  = $stmt->limit;
        if( empty( $limit ) ){
            $stmt->limit = new Limit($nums, 0);
            $new_sql = $stmt->build();
            return $new_sql;
        }

        $limit_offset = $limit->offset;
        $limit_row_count = $limit->rowCount;

        if( $limit_row_count > $nums ){
            $stmt->limit = new Limit($nums, $limit_offset);
            $new_sql = $stmt->build();
            return $new_sql;
        }
        return null;
    }

    /**
     * 获取select语句可查询的限制条数
     * @param  string $environment [description]
     * @return [type]              [description]
     */
    public function getSelectWhite( $db_name, $environment = 'dev' ){
        //查询条数限制
        @$environment_rule = Select::find()->asArray()->one();

        $environment = !empty( $environment ) ? $environment : 'dev';
        $nums = $environment_rule[$environment] ? $environment_rule[$environment] : '500';

        //根据用户查询白名单列表
        //部分用户针对于某些表会放开权限（线上环境）
        if( $environment == 'pro' ){
            $select_white_ = SelectWhite::find()->select(['number', 'db_name', 'stop_date'])->where( ['username' => Yii::$app->users->identity->username] )->andWhere([ '>=', 'stop_date', date('Y-m-d') ])->orderBy("number desc")->asArray()->all();

            if( !empty( $select_white_ ) ){
                foreach ($select_white_ as $key => $value) {
                    $db_name_list = $value['db_name'];
                    $db_name_list_array = explode(",", $db_name_list);
                    if( in_array( $db_name, $db_name_list_array ) ){
                        $nums = $value['number'];
                        break;
                    }
                }
            }
        }
        return $nums;
    }

    /**
     * 检测SQL规则
     * @param  [type] $sql    [description]
     * @param  string $dbname [description]
     * @return [type]         [description]
     */
    public function checkSqlRule( $sql, $dbname )
    {
        $parser = new Parser($sql);
        $stmt = $parser->statements[0];
        $sql_action = explode(" ", strtolower( rtrim($sql) ))[0];
        if( $sql_action == 'select' ){
            //默认返回的错误信息
            $result['error'] = "输入内容不合法请检查！";

            $from = $stmt->from;
            @$table = $from[0]->table;//获取SQL语句中的表
            $table_rule = array('user', 'user_info');
            $expr_arr = $stmt->expr;//字段列表

            foreach ($expr_arr as $key => $value) {
                @$function = $value->function;
                @$expr = $value->expr;
                @$alias = $value->alias;
                if( strstr( $expr, "'" ) || strstr( $expr, "\"" ) ){
                    if( empty( $alias ) || strstr( $alias, "'" ) || strstr( $alias, "\"" ) ){
                        $result['error'] = "查询含有引号的字段时请使用别名！";
                        return $result;
                    }
                }
                if( $function == 'sleep' ){ //不能使用sleep函数
                    return $result;
                }
                if( in_array( $table, $table_rule ) && $function == 'count' ){
                    return $result;
                }
            }

            //数据脱敏
            $result['error'] = '敏感金额数据的统计已做限制！';
            if( $dbname == 'oms' ){
                $preg = Yii::$app->params['regexp']['oms_limit_money'];
                if( preg_match( $preg, strtolower($sql) ) ){
                    return $result;
                }
            }

            if( $dbname == 'ordercenter' ){
                $preg = Yii::$app->params['regexp']['ordercenter_limit_money'];
                if( preg_match( $preg, strtolower($sql) ) ){
                    return $result;
                }
            }

            //不能含有#
            $result['error'] = 'SQL语句中#不能作为关键词！';
            $tokens_list = $parser->list->tokens;
            foreach ($tokens_list as $key => $value) {
                $token = substr($value->token, 0, 1);
                if( $token == '#' ){
                    return $result;
                }
            }
        }

        //防呆限制
        $result['error'] = 'update和delete语句必须带有where条件！';
        if( $sql_action == 'update' or $sql_action == 'delete' ){
            $where = $stmt->where;
            if( empty( $where ) || empty( $where[0] ) ){
                return $result;
            }
            @$where_expr = $where[0]->expr;
            if( empty( $where_expr ) ){
                return $result;
            }
        }

        //操作限制
        $result['error'] = '不能删除字段和修改字段名！';
        if( $sql_action == 'alter' ){
            $altered = $stmt->altered;
            if( !empty( $altered ) ){
                foreach ($altered as $key => $value) {
                    $options = $value->options->options[1];
                    if( $options == 'drop' || $options == 'change' ){
                        return $result;
                    }
                }
            }
        }
        return true;
    }

    /**
     * 获取SQL类型和首个关键词
     * @param  [type] $sql         [description]
     * @param  string $environment [description]
     * @return [type]              [description]
     */
    public function getSqlType( $sql, $environment = 'common' )
    {
        $sql = strtolower( rtrim( $sql ) );
        if( $environment == 'common' ){
            if (preg_match(Yii::$app->params['regexp']['dml_sql'], $sql)) {
                $sql_type = 'DML';
            }else if (preg_match(Yii::$app->params['regexp']['ddl_sql'], $sql)) {
                $sql_type = 'DDL';
            }else if (preg_match(Yii::$app->params['regexp']['dql_sql'], $sql)) {
                $sql_type = 'DQL';
            }else{
                $sql_type = 'other';
            }
        }elseif( $environment == 'sharding' ){
            if (preg_match(Yii::$app->params['regexp']['dml_sql'], $sql)) {
                $sql_type = 'DML';
            }else if (preg_match('/^(drop|create|alter){1}?\s/i', $sql)) { //分库分表中DDL类型只支持drop|create|alter
                $sql_type = 'DDL';
            }else if (preg_match('/^(select|show){1}?\s/i', $sql)) {//分库分表中DQL类型只支持select|show
                $sql_type = 'DQL';
            }else{
                $sql_type = 'other';
            }
        }
        $sql_action = explode(" ", $sql)[0];

        $sql_info['sql_type'] = $sql_type;
        $sql_info['sql_action'] = $sql_action;

        return $sql_info;
    }

    /**
     * 检测SQL是否正确
     * @param  [type] $sql [description]
     * @return [type]      [description]
     */
    public function checkSqlTrue( $sql )
    {
        $executeConnection = $this->connectDb("127.0.0.1", "webdb");
        $command = $executeConnection->createCommand($sql);
        try {
            if(preg_match('/^select\s+/i', rtrim($sql))){
                $excute_result = $command->queryAll();
            }else{
                $excute_result = $command->execute();
            }
            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 检测批量注释是否正确
     * @param  [type] $sql_list     [description]
     * @param  [type] $batch_note [description]
     * @return [type]              [description]
     */
    public function checkBatchNote( $sql_list, $batch_note )
    {
        $batch_notes = strtolower( rtrim( $batch_note ) );
        if(empty($batch_notes)){
            $result['error'] = '请输入批量注释内容或取消批量注释！';
            return $result;
        }
        $batch_first_char = substr( trim($batch_notes),0,1);
        if ($batch_first_char !== '#') {
            $result['error'] = '批量注释内容请以#号开始！';
            return $result;
        }
        
        //检测是否为同一类型关键字操作
        $keyword = '';
        if ( !is_array( $sql_list ) ) {
            $result['error'] = 'SQL语句不能为空';
            return $result;
        }


        $rule_key_words = Yii::$app->params['regexp']['rule_key_words'];
        //把SQL分割出来
        $rule = "(?:$rule_key_words)";
        foreach ($sql_list as $key => $sql) {
            $sql .= "\r\n";
            $notes = '';
            if (preg_match("/#.*?(?:\r\n)/i", $sql) == 1) {
                preg_match_all("/#[\s\S]*?(?=\r\n(?:\s|\r\n)*" . $rule . ")/i", $sql, $notes);
                //print_r($notes);exit;
                $notes = @$notes[0][0];
                $notes = str_replace("\r\n", '', $notes);
            }
            //有注释的则跳过
            if (!empty($notes)) {
                continue;
            }else{
                //如果为insert，update，delete，alter中一种则进行判断是否穿插使用
                if (preg_match_all("/insert|update|delete|alter?\s/i", strtolower($sql), $keywordA)) {
                    if($keyword == ''){
                        $keyword = $keywordA[0][0];
                    }elseif(!empty($keyword) && $keyword != $keywordA[0][0]){
                        $result['error'] = '请对同一类型的操作进行批量注释！只能为insert,update,delete,alter其中一种';
                        return $result;
                    }
                //如果没有，则报错，提示需要单独使用批量功能
                }else{
                    $result['error'] = '当前只支持insert,update,delete,alter操作进行批量注释！';
                    return $result;
                }
            }
        }
        return true;
    }

    /**
     * 获取SQL语句中的库名和表名
     * @param  [type] $sql     [description]
     * @param  string $db_name [description]
     * @return [type]          [description]
     */
    public function getSqlTable( $sql)
    {
        $sql = trim($sql);
        $table = array();
        if( preg_match('/^(?:show|select|explain|delete|insert|update){1}?\s/i', $sql) ){
            $parser = new \PHPSQLParser($sql, true);
            $parsed = $parser->parsed;
            $table = $this->getSqlLoTable( $parsed );
            //$table = $this->getTable( $parsed, $table );
            
        }elseif( preg_match('/^(?:create|alter|drop){1}?\s/i', $sql)  ){
            preg_match_all( "/^(create|alter|drop)\s+(table){1}\s+(if\s+not\s+exists|if\s+exists)?\s?([0-9a-zA-Z_.`]+)/", strtolower($sql), $tb_name_array );
            @$table[] = str_replace("`", "", end( $tb_name_array )[0]);
        }
        return $table;
    }

    /**
     * 获取服务器下所有的数据库名
     * @param  [type] $server_ip [description]
     * @param  [type] $db_name   [description]
     * @return [type]            [description]
     */
    public function getServerDbList( $server_ip, $db_name )
    {
        //数据库连接对象
        $executeConnection = $this->connectDb( $server_ip, $db_name );

        //获取全部数据库名
        try {
            $command = $executeConnection->createCommand('show databases');
            $list = $command->queryAll();
            $result['list'] = $list;
        } catch (\Exception $e) {
            $result['error'] = "数据库连接失败：".$e->getMessage();
        }
        return $result;
    }

    /**
     * 连接数据库
     * @param  [type] $server_ip [description]
     * @param  [type] $db_name   [description]
     * @return [type]            [description]
     */
    public function connectDb( $server_ip, $db_name ){
        //组合数据库配置
        $connect_config['dsn'] = "mysql:host=$server_ip;dbname=$db_name";
        $connect_config['username'] = Yii::$app->params['MARKET_USER'];
        $connect_config['password'] = Yii::$app->params['MARKET_PASSWD'];
        $connect_config['charset'] = Yii::$app->params['MARKET_CHARSET'];

        //数据库连接对象
        $executeConnection = new \yii\db\Connection((Object)$connect_config);
        return $executeConnection;
    }

    /**
     * [getSqlLoTable 获取DML类型SQL第一级表名]
     * @param  [type] $sql [description]
     * @return [type]         [description]
     */
    public function getSqlLoTable( $parsed )
    {
        $table = [];
        if( isset( $parsed['SELECT'] ) ){
            @$from = $parsed['FROM']; //获取from内容
            if( !empty( $from ) && is_array( $from ) ){
                foreach ($from as $fk => $fv) { //循环from
                    if( !empty( $fv['table'] ) ){ //如果form中的表名存在且不为空，则写入到$table数组中
                        @$table[] = str_replace("`", "", $fv['table']);
                    }
                }
            }
        }else if( isset( $parsed['UPDATE'] ) ){ //如果解析中有update
            $update = $parsed['UPDATE'];
            if( !empty( $update ) && is_array( $update ) ){
                foreach ($update as $uk => $uv) {
                    if( !empty( $uv['table'] ) ){
                        $table[] = str_replace("`", "", $uv['table']);
                    }
                }
            }
        }else if( isset( $parsed['INSERT'] ) ){
            $insert = $parsed['INSERT'];
            $values = $parsed['VALUES'];
            if( !empty( $insert ) && is_array( $insert ) ){
                foreach ($insert as $ik => $iv) {
                    if( !empty( $iv['table'] ) ){
                        $table[] = str_replace("`", "", $iv['table']); //获取insert后面的表名
                    }
                }
            }
        }else if( isset( $parsed['DELETE'] ) ){
            @$delete = $parsed['DELETE'];
            if( !empty( $delete['TABLES'] ) && is_array( $delete['TABLES'] ) ){
                foreach ($delete['TABLES'] as $dk => $dv) {
                    if( !empty( $dv ) ){
                        @$table[] = str_replace("`", "", $dv); //delete
                    }
                }
            }
        }else if( isset( $parsed['SHOW'] ) ){
            @$show = $parsed['SHOW'];
            if( !empty( $show ) && is_array( $show ) ){
                foreach ($show as $sk => $sv) {
                    if( !empty( $sv['table'] ) ){
                        @$table[] = str_replace("`", "", $sv['table']); //delete
                    }
                }
            }
        }
        return $table;
    }

    public function getTable( $parsed, $table )
    {
        $table1 = $table2 = array();

        if( isset( $parsed['SELECT'] ) ){ //如果解析结果是select语句
            @$from = $parsed['FROM']; //获取from内容
            @$where = $parsed['WHERE']; //获取where条件内容
            if( !empty( $from ) && is_array( $from ) ){
                foreach ($from as $fk => $fv) { //循环from
                    if( !empty( $fv['table'] ) ){ //如果form中的表名存在且不为空，则写入到$table数组中
                        @$table[] = $fv['table'];
                    }

                    @$sub_tree = $fv['sub_tree']; //如果form含有子查询，则递归获取表名，如果不含子查询  sub_tree的值为false
                    if( $sub_tree ){ //递归获取子查询中的表名
                        @$table1 = $this->getTable( $sub_tree, $table );
                    }
                }
            }

            if( !empty( $where ) && is_array( $where ) ){ //判断where条件内容是否含有子查询
                foreach ($where as $wk => $wv) {
                    @$sub_tree = $wv['sub_tree'];
                    if( $sub_tree ){ //递归获取子查询中的表名
                        @$table2 = $this->getTable( $sub_tree, $table );
                    }
                }
            }
        }else if( isset( $parsed['UPDATE'] ) ){ //如果解析中有update
            $update = $parsed['UPDATE'];
            $where = $parsed['WHERE'];
            if( !empty( $update ) && is_array( $update ) ){
                foreach ($update as $uk => $uv) {
                    if( !empty( $uv['table'] ) ){
                        $table[] = $uv['table'];
                    }
                    @$sub_tree = $uv['sub_tree']; //如果含有子查询，则递归获取表名，如果不含子查询  sub_tree的值为false
                    if( $sub_tree ){
                        $table1 = $this->getTable( $sub_tree, $table );
                    }
                }
            }

            if( !empty( $where ) && is_array( $where ) ){ //判断where条件内容是否含有子查询
                foreach ($where as $wk => $wv) {
                    @$sub_tree = $wv['sub_tree'];
                    if( $sub_tree ){ //递归获取子查询中的表名
                        @$table2 = $this->getTable( $sub_tree, $table );
                    }
                }
            }
        }else if( isset( $parsed['INSERT'] ) ){
            $insert = $parsed['INSERT'];
            $values = $parsed['VALUES'];
            if( !empty( $insert ) && is_array( $insert ) ){
                foreach ($insert as $ik => $iv) {
                    if( !empty( $iv['table'] ) ){
                        $table[] = $iv['table']; //获取insert后面的表名
                    }
                }
            }
        }else if( isset( $parsed['DELETE'] ) ){
            @$delete = $parsed['DELETE'];
            @$from = $parsed['FROM'];
            @$where = $parsed['WHERE'];
            if( !empty( $delete['TABLES'] ) && is_array( $delete['TABLES'] ) ){
                foreach ($delete['TABLES'] as $dk => $dv) {
                    if( !empty( $dv ) ){
                        @$table[] = $dv; //delete
                    }
                }
            }

            if( !empty( $from ) && is_array( $from ) ){
                foreach ($from as $fk => $fv) { //循环from
                    if( !empty( $fv['table'] ) ){ //如果form中的表名存在且不为空，则写入到$table数组中
                        @$table[] = $fv['table'];
                    }

                    @$sub_tree = $fv['sub_tree']; //如果form含有子查询，则递归获取表名，如果不含子查询  sub_tree的值为false
                    if( $sub_tree ){ //递归获取子查询中的表名
                        @$table1 = $this->getTable( $sub_tree, $table );
                    }
                }
            }

            if( !empty( $where ) && is_array( $where ) ){ //判断where条件内容是否含有子查询
                foreach ($where as $wk => $wv) {
                    @$sub_tree = $wv['sub_tree'];
                    if( $sub_tree ){ //递归获取子查询中的表名
                        @$table2 = $this->getTable( $sub_tree, $table );
                    }
                }
            }
        }else if( isset( $parsed['SHOW'] ) ){
            @$show = $parsed['SHOW'];
            if( !empty( $show ) && is_array( $show ) ){
                foreach ($show as $sk => $sv) {
                    if( !empty( $sv['table'] ) ){
                        @$table[] = $sv['table']; //delete
                    }
                }
            }
        }

        //合并数组，并去重，去空值，最后重置数组键
        $table = array_values(array_filter(array_unique(array_merge($table, $table1, $table2))));
        return $table;
    }

    /**
     * SQL语法解析树
     * @param  [type] $sql [description]
     * @return [type]      [description]
     */
    public function sqlParserTree( $sql )
    {
        $parser = new \PHPSQLParser($sql, true);
        $parsed = $parser->parsed;

        $sql_info = [
            'sql' => $sql,
            'table_number' => 1,
            'table_name' => '',
            'set_column' => '',
            'where_column' => '',
            'where' => '',
        ];

        if ( isset( $parsed['DELETE'] ) ){ //deleteSQL语句
            $sql_info['oper'] = 'delete';
            $tb_count = count( $parsed['DELETE']['TABLES'] );
            if( $tb_count != 1 ){ //表个数不等于1
                $sql_info['table_number'] = 2;
                return $sql_info;
            }else{ //判断where条件中是否涉及多张表

                if( !isset( $parsed['WHERE'] ) ){ //如果没有where条件，则返回结果
                    $sql_info['table_name'] = str_replace("`", "", $parsed['DELETE']['TABLES'][0]); //表名
                    return $sql_info;
                }

                $where = $parsed['WHERE'];
                $where_str = $where_column = [];
                foreach ($where as $wk => $wv) {
                    if( $wv['sub_tree'] && isset($wv['sub_tree']['SELECT'])  ){
                        $tb_count = 2; //多表
                        break;
                    }

                    $expr_type = $wv['expr_type'];//类型
                    $base_expr = $wv['base_expr']; //值

                    if( $expr_type == 'colref' ){ //字段名
                        $base_expr_array = explode(".", str_replace("`", "", $base_expr));
                        $base_expr = count( $base_expr_array ) == 1 ? $base_expr : end($base_expr_array); //去掉别名

                        $where_column[] = $base_expr;
                    }elseif( $expr_type == 'function' ){
                        $sub_tree = $wv['sub_tree'];
                        $sub_trees = $this->sub_tree( $sub_tree );
                        $base_expr .= "( {$sub_trees} )";
                    }

                    $where_str[] = $base_expr;
                }

                if( $tb_count == 2 ){ //如果表个数大于1，则返回表个数信息
                    $sql_info['table_number'] = 2;
                    return $sql_info;
                }else{
                    $sql_info['table_name'] = str_replace("`", "", $parsed['DELETE']['TABLES'][0]); //表名

                    $sql_info['where_column'] = implode(",", array_unique( $where_column )); //where条件中的字段列表
                    $sql_info['where'] = implode(" ", $where_str);
                }
            }
        }elseif( isset( $parsed['UPDATE'] ) ){
            $sql_info['oper'] = 'update';
            $tb_count = count( $parsed['UPDATE'] );
            if( $tb_count != 1 ){ //表个数不等于1
                $sql_info['table_number'] = 2;
                return $sql_info;
            }else{
                if( isset( $parsed['SET'] ) ){ 
                    $set_column = [];
                    foreach ($parsed['SET'] as $sk => $sv) {
                        $sub_tree = $sv['sub_tree'];
                        if( $sub_tree ){ //如果set的子树不为空
                            foreach ($sub_tree as $ssk => $ssv) {
                                if( $ssv['sub_tree'] ){
                                    $tb_count = 2;
                                }

                                if( $ssv['expr_type'] == 'colref' ){
                                    $base_expr_s = str_replace("`", "", $ssv['base_expr']);
                                    $base_expr_array = explode(".", $base_expr_s);
                                    $set_column[] = count( $base_expr_array ) == 1 ? $base_expr_s : end($base_expr_array); //去掉别名
                                }
                            }
                        }
                    }
                    $sql_info['set_column'] = implode(",", array_unique( $set_column ));
                }

                if( !isset( $parsed['WHERE'] ) ){ //如果没有where条件，则返回结果
                    $sql_info['table_name'] = str_replace("`", "", $parsed['UPDATE'][0]['table']); //表名
                    return $sql_info;
                }

                $where = $parsed['WHERE'];
                $where_str = $set_column = $where_column = [];
                foreach ($where as $wk => $wv) {
                    if( $wv['sub_tree'] && isset($wv['sub_tree']['SELECT'])  ){
                        $tb_count = 2; //多表
                        break;
                    }

                    $expr_type = $wv['expr_type'];//类型
                    $base_expr = $wv['base_expr']; //值

                    if( $expr_type == 'colref' ){ //字段名
                        $base_expr_array = explode(".", str_replace("`", "", $base_expr));
                        $base_expr = count( $base_expr_array ) == 1 ? $base_expr : end($base_expr_array)[0]; //去掉别名

                        $where_column[] = $base_expr;
                    }elseif( $expr_type == 'function' ){
                        $sub_tree = $wv['sub_tree'];
                        $sub_trees = $this->sub_tree( $sub_tree );
                        $base_expr .= "( {$sub_trees} )";
                    }

                    $where_str[] = $base_expr;
                }
                

                if( $tb_count == 2 ){
                    $sql_info['table_number'] = 2;
                    return $sql_info;
                }else{
                    $sql_info['table_name'] = str_replace("`", "", $parsed['UPDATE'][0]['table']); //表名

                    $sql_info['where_column'] = implode(",", array_unique( $where_column )); //where条件中的字段列表
                    $sql_info['where'] = implode(" ", $where_str);
                }
            }
        }elseif( isset( $parsed['INSERT'] ) ){
            $sql_info['oper'] = 'insert';
            $tb_count = count( $parsed['INSERT'] );
            if( $tb_count != 1 ){
                $sql_info['table_number'] = 2;
                return $sql_info;
            }else{
                $sql_info['table_name'] = str_replace("`", "", $parsed['INSERT'][0]['table']); //表名
                    
                $columns = $parsed['INSERT'][0]['columns'];
                if( $columns != false ){
                    $set_column = [];
                    foreach ($columns as $wk => $cv) {
                        $expr_type = $cv['expr_type'];//类型
                        $base_expr = $cv['base_expr']; //值

                        if( $expr_type == 'colref' ){ //字段名
                            $base_expr_array = explode(".", str_replace("`", "", $base_expr));
                            $base_expr = count( $base_expr_array ) == 1 ? $base_expr : end($base_expr_array)[0]; //去掉别名

                            $set_column[] = $base_expr;
                        }
                    }
                    $sql_info['set_column'] = implode(",", $set_column);
                }
            }
        }elseif( isset( $parsed['SELECT'] ) ){
            $sql_info['oper'] = 'select';
            $tb_count = count( $parsed['FROM'] );
            if( $tb_count != 1 ){
                $sql_info['table_number'] = 2;
                return $sql_info;
            }else{
                if( !isset( $parsed['WHERE'] ) ){ //如果没有where条件，则返回结果
                    $sql_info['table_name'] = str_replace("`", "", $parsed['FROM'][0]['table']); //表名
                    return $sql_info;
                }

                $where = $parsed['WHERE'];
                $where_str = $set_column = $where_column = [];
                foreach ($where as $wk => $wv) {
                    if( $wv['sub_tree'] && isset($wv['sub_tree']['SELECT']) ){
                        $tb_count = 2; //多表
                        break;
                    }

                    $expr_type = $wv['expr_type'];//类型
                    $base_expr = $wv['base_expr']; //值

                    if( $expr_type == 'colref' ){ //字段名
                        $base_expr_array = explode(".", str_replace("`", "", $base_expr));
                        $base_expr = count( $base_expr_array ) == 1 ? $base_expr : end($base_expr_array)[0]; //去掉别名

                        $where_column[] = $base_expr;
                    }elseif( $expr_type == 'function' ){
                        $sub_tree = $wv['sub_tree'];
                        $sub_trees = $this->sub_tree( $sub_tree );
                        $base_expr .= "( {$sub_trees} )";
                    }

                    $where_str[] = $base_expr;
                }
                

                if( $tb_count == 2 ){
                    $sql_info['table_number'] = 2;
                    return $sql_info;
                }else{
                    $sql_info['table_name'] = str_replace("`", "", $parsed['FROM'][0]['table']); //表名

                    $sql_info['where_column'] = implode(",", array_unique( $where_column )); //where条件中的字段列表
                    $sql_info['where'] = implode(" ", $where_str);
                }
            }
        }else{
            $sql_info['oper'] = 'other';
        }

        return $sql_info;
    }

    private function sub_tree( $sub_tree )
    {
        $data = '';
        if ( $sub_tree ) {
            $f_array = [];
            foreach ($sub_tree as $sv) {
                if ( $sv['expr_type'] == 'function' ) {
                    $base_expr_f = $sv['base_expr']."(";
                    if ( $sv['sub_tree'] ) {
                        $s_array = $base_expr_s = [];
                        foreach ($sv['sub_tree'] as $ssk => $ssv) {
                            if ( $ssv['expr_type'] == 'function' ) {
                                $base_expr_s[] = $ssv['base_expr']."(" . $ssv['sub_tree'][0]['base_expr'] . ")";
                            }else{
                                $base_expr_s[] = $ssv['base_expr'];
                            }
                        }
                        $base_expr_f .= implode(", ", $base_expr_s);
                    }
                    $base_expr_f .= ")";
                    $f_array[] = $base_expr_f;
                } else {

                    $f_array[] = $sv['base_expr'];
                }
            }
            $data = implode(", ", $f_array);
        }
        
        return $data;
    }
}