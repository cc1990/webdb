<?php

/**
 * you cannot execute this script within Eclipse PHP
 * because of the limited output buffer. Try to run it
 * directly within a shell.
 */

require_once dirname(__FILE__) . '/../src/PHPSQLParser.php';

$sql = "select store_code sourceId FROM t_store ts left join (select * from oms.a ) tt on ts.id = tt.id where ts.store_id in( select id from occ.store as st where st.name='cc' )";
//$sql = "UPDATE user_info ui left join qccr.user on ui.id = user.id SET update_time = now(), update_person = '20170103-02293' WHERE create_time > '2017-01-03 00:00:00' id in ( select oc.id from oms.cc oc where oc.name in ( select m.name from member.user m where m.id=1 ))";
//$sql = "insert into oms.cc values (1, 'cc', 'aaaa')";
$parser = new PHPSQLParser($sql, true);
$parsed = $parser->parsed;


$table = array();
// if( isset( $parsed['SELECT'] ) ){
//     $from = $parsed['FROM'];
//     $where = $parsed['WHERE'];
//     if( !empty( $from ) && is_array( $from ) ){
//         foreach ($from as $fk => $fv) {
//             $table[] = !empty( $fv['table'] );
//         }
//     }
//     getWhereTable( $where );

    
// }
var_dump($parsed);exit;

function getFromTable( $from, $table ){
    if( !empty( $from ) && is_array( $from ) ){
        foreach ($from as $fk => $fv) {
            $table[] = !empty( $fv['table'] );
        }
    }
    return $table;
}

function getTable( $parsed ,$table){
  foreach ($parsed as $key => $value) {
      if(isset($parsed['FROM']) && is_array($parsed['FROM'])){
          $table1 = getTable($parsed['FROM'],$table);
      }
      if(isset($parsed['WHERE']) && is_array($parsed['WHERE'])){
          $table2 = getTable($parsed['where'],$table);
      }
  }
  return array_merge($table1, $table2);
   if( !empty( $where ) && is_array( $where ) ){
        foreach ($where as $wk => $wv) {
            $sub_tree = $wv['sub_tree'];
            if( $sub_tree && isset( $sub_tree['SELECT'] ) ){ 
                $sub_tree_from = $sub_tree['FROM'];
                $sub_tree_where = $sub_tree['WHERE'];
                $table2 = getWhereTable( $sub_tree_where, $table );
                $table = array_merge($table,$table1,$table2);
            }
            if( $sub_tree && isset( $sub_tree['from'] ) ){ 
                $sub_tree_from = $sub_tree['FROM'];
                $sub_tree_where = $sub_tree['WHERE'];
                $table2 = getWhereTable( $sub_tree_where, $table );
                $table = array_merge($table,$table1,$table2);
            }
        }
        foreach ($sub as $key => $value) {
          # code...
        }
    }
    return $table;

}

$sql = 'SELECT 1';
echo $sql . "\n";
$start = microtime(true);
$parser = new PHPSQLParser($sql, true);
$stop = microtime(true);
print_r($parser->parsed);
echo "parse time simplest query:" . ($stop - $start) . "\n";

/*You can use the constuctor for parsing.  The parsed statement is stored at the ->parsed property.*/
$sql = 'REPLACE INTO table (a,b,c) VALUES (1,2,3)';
echo $sql . "\n";
$start = microtime(true);
$parser = new PHPSQLParser($sql);
$stop = microtime(true);
print_r($parser->parsed);
echo "parse time very somewhat simple statement:" . ($stop - $start) . "\n";

/* You can use the ->parse() method too.  The parsed structure is returned, and 
   also available in the ->parsed property. */
$sql = 'SELECT a,b,c 
          from some_table an_alias
	where d > 5;';
echo $sql . "\n";
print_r($parser->parse($sql, true));

$sql = 'SELECT a,b,c 
          from some_table an_alias
	  join `another` as `another table` using(id)
	where d > 5;';
echo $sql . "\n";
$parser = new PHPSQLParser($sql, true);
print_r($parser->parsed);

$sql = 'SELECT a,b,c 
          from some_table an_alias
	  join (select d, max(f) max_f
                 from some_table 
                where id = 37
                group by d) `subqry` on subqry.d = an_alias.d
	where d > 5;';
echo $sql . "\n";
$parser = new PHPSQLParser($sql);
print_r($parser->parsed);

$sql = "(select `c2`, `c```, \"quoted \'string\' \\\" with `embedded`\\\"\\\" quotes\" as `an``alias` from table table)
UNION ALL (select `c2`, `c```, \"quoted \'string\' \\\" with `embedded`\\\"\\\" quotes\" as `an``alias` from table table)";
$parser = new PHPSQLParser($sql);
print_r($parser->parsed);

$sql = "(select `c2`, `c```, \"quoted \'string\' \\\" with `embedded`\\\"\\\" quotes\" as `an``alias` from table table)
UNION  (select `c2`, `c```, \"quoted \'string\' \\\" with `embedded`\\\"\\\" quotes\" as `an``alias` from table table)";
$parser = new PHPSQLParser($sql);
print_r($parser->parsed);

$sql = "select `c2`, `c```, \"quoted \'string\' \\\" with `embedded`\\\"\\\" quotes\" as `an``alias` from table table";
echo $sql . "\n";
$parser = new PHPSQLParser($sql);
print_r($parser->parsed);

$sql = "alter table xyz add key my_key(a,b,c), drop primay key";
echo $sql . "\n";
$parser = new PHPSQLParser($sql);
print_r($parser->parsed);

$sql = 'INSERT INTO table (a,b,c) VALUES (1,2,3)
  ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), c=3;';
echo $sql . "\n";
$parser = new PHPSQLParser($sql);
print_r($parser->parsed);

$sql = 'UPDATE t1 SET col1 = col1 + 1, col2 = col1;';
echo $sql . "\n";
$parser = new PHPSQLParser($sql);
print_r($parser->parsed);

$sql = 'DELETE FROM t1, t2 USING t1 INNER JOIN t2 INNER JOIN t3
WHERE t1.id=t2.id AND t2.id=t3.id;';
echo $sql . "\n";
$parser = new PHPSQLParser($sql);
print_r($parser->parsed);

$sql = 'delete low_priority partitioned_table.* from partitioned_table where partition_id = 1;';
echo $sql . "\n";
$parser = new PHPSQLParser($sql);
print_r($parser->parsed);

$sql = "UPDATE t1 SET col1 = col1 + 1, col2 = col1;";
echo $sql . "\n";
$parser = new PHPSQLParser($sql);
print_r($parser->parsed);

$sql = 'insert into partitioned_table (partition_id, some_col) values (1,2);';
echo $sql . "\n";
$parser = new PHPSQLParser($sql);
print_r($parser->parsed);

$sql = 'delete from partitioned_table where partition_id = 1;';
echo $sql . "\n";
$parser = new PHPSQLParser($sql);
print_r($parser->parsed);

$sql = 'SELECT 1';
echo $sql . "\n";
$parser = new PHPSQLParser($sql);
print_r($parser->parsed);

$sql = 'SHOW TABLE STATUS';
echo $sql . "\n";
$parser = new PHPSQLParser($sql);
print_r($parser->parsed);

$sql = 'SHOW TABLES';
echo $sql . "\n";
$parser = new PHPSQLParser($sql);
print_r($parser->parsed);

$sql = 'select DISTINCT 1+2   c1, 1+ 2 as 
`c2`, sum(c2),"Status" = CASE
        WHEN quantity > 0 THEN \'in stock\'
        ELSE \'out of stock\'
        END 
, t4.c1, (select c1+c2 from t1 inner_t1 limit 1) as subquery into @a1, @a2, @a3 from oms.t1 the_t1 left outer join t2 using(c1,c2) join t3 as tX on tX.c1 = the_t1.c1 natural join t4 t4_x using(cX)  where c1 = 1 and c2 in (1,2,3, "apple") and exists ( select 1 from some_other_table another_table where x > 1 and y in (select y from user where name="cc")) and ("zebra" = "orange" or 1 = 1) group by 1, 2 having sum(c2) > 1 ORDER BY 2, c1 DESC LIMIT 0, 10 into outfile "/xyz" FOR UPDATE LOCK IN SHARE MODE';

echo $sql . "\n";
$parser = new PHPSQLParser($sql);
print_r($parser->parsed);

$sql = "(select 1, 1, 1, 1 from dual dual1) union all (select 2, 2, 2, 2 from dual dual2) union all (select c1,c2,c3,sum(c4) from (select c1,c2,c3,c4 from a_table where c2 = 1) subquery group by 1,2,3) limit 10";
//echo $sql . "\n";
$parser = new PHPSQLParser($sql);
//print_r($parser->parsed);

$sql = 'select DISTINCT 1+2   c1, 1+ 2 as 
`c2`, sum(c2),"Status" = CASE
        WHEN quantity > 0 THEN "in stock"
        ELSE "out of stock"
        END 
, t4.c1, (select c1+c2 from t1 table limit 1) as subquery into @a1, @a2, @a3 from `table` the_t1 left outer join t2 using(c1,c2) join
(select a, b, length(concat(a,b,c)) from ( select 1 a,2 b,3 c from some_Table ) table ) subquery_in_from join t3 as tX on tX.c1 = the_t1.c1 natural join t4 t4_x using(cX)  where c1 = 1 and c2 in (1,2,3, "apple") and exists ( select 1 from some_other_table another_table where x > 1) and ("zebra" = "orange" or 1 = 1) group by 1, 2 having sum(c2) > 1 ORDER BY 2, c1 DESC LIMIT 0, 10 into outfile "/xyz" FOR UPDATE LOCK IN SHARE MODE
UNION ALL
SELECT NULL,NULL,NULL,NULL,NULL FROM DUAL LIMIT 1';

$start = microtime(true);
$parser = new PHPSQLParser($sql);
$stop = microtime(true);
echo "Parse time highly complex statement: " . ($stop - $start) . "\n";

?>
