<?php 

require_once dirname(__FILE__) . '/../src/PHPSQLParser.php';

$sql = "select store_code sourceId FROM t_store ts left join (select * from oms.a ) tt on ts.id = tt.id where ts.store_id in( select id from occ.store as st where st.name='cc' )";
$sql = "select sleep;";
//$sql = "UPDATE user_info ui left join qccr.user on ui.id = user.id left join (select * from oms.user) ouser SET update_time = now(), update_person = '20170103-02293' WHERE create_time > '2017-01-03 00:00:00' id in ( select oc.id from oms.cc oc where oc.name in ( select m.name from member.user m where m.id=1 ))";
//$sql = "INSERT into member.user_info values (select * from user ui left join oms.user_info on ui.id = user.id left join (select * from oms.user ou where ou.id in (select id from membercenter.user where id = 1) ) ouser on ui.id = ouser.id where ui.name in (select name from order.user_info) ), (select * from ordercenter.user)";
//$sql = "delete oms.qccr.*, ass.mm.* from user ui left join oms.user_info on ui.id = user.id where ui.name in (select name from order.user_info) ";

$parser = new PHPSQLParser($sql, true);
$parsed = $parser->parsed;

$table = array();
$table = getTable( $parsed, $table );
echo $sql;
var_dump($table);exit;


function getTable( $parsed, $table )
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
                    @$table1 = getTable( $sub_tree, $table );
                }
            }
        }

        if( !empty( $where ) && is_array( $where ) ){ //判断where条件内容是否含有子查询
            foreach ($where as $wk => $wv) {
                @$sub_tree = $wv['sub_tree'];
                if( $sub_tree ){ //递归获取子查询中的表名
                    @$table2 = getTable( $sub_tree, $table );
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
                    $table1 = getTable( $sub_tree, $table );
                }
            }
        }

        if( !empty( $where ) && is_array( $where ) ){ //判断where条件内容是否含有子查询
            foreach ($where as $wk => $wv) {
                @$sub_tree = $wv['sub_tree'];
                if( $sub_tree ){ //递归获取子查询中的表名
                    @$table2 = getTable( $sub_tree, $table );
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
                    @$table1 = getTable( $sub_tree, $table );
                }
            }
        }

        if( !empty( $where ) && is_array( $where ) ){ //判断where条件内容是否含有子查询
            foreach ($where as $wk => $wv) {
                @$sub_tree = $wv['sub_tree'];
                if( $sub_tree ){ //递归获取子查询中的表名
                    @$table2 = getTable( $sub_tree, $table );
                }
            }
        }
    }

    //合并数组，并去重，去空值，最后重置数组键
    $table = array_values(array_filter(array_unique(array_merge($table, $table1, $table2))));
    return $table;
}