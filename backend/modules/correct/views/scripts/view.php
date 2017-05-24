<?php use yii\helpers\Html; ?>
<link rel="stylesheet" type="text/css" href="http://g.alicdn.com/sj/dpl/1.5.1/css/sui.min.css">
<?=Html::cssFile('@web/public/iconfont/iconfont.css') ?>
<div class="div-index">
    <div>
        <table class="sui-table table-bordered table-zebra">
            <thead>
                <tr>
                    <th width="20px">#</th>
                    <th width="100px">服务器IP</th>
                    <th width="100px">数据库名</th>
                    <th width="100px">表</th>
                    <th>SQL语句</th>
                    <th width="200px">备份结果</th>
                    <th width="200px">执行结果</th>
                    <th width="60px">操作</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($list as $key => $value) { ?>
                <tr>
                    <td><?= $key+1?></td>
                    <td><?= $value['server_ip']?></td>
                    <td><?= $value['db_name']?></td>
                    <td><?= $value['tb_name']?></td>
                    <td><?= $value['sql']?></td>
                    <td><?= $value['backup_note']?></td>
                    <td><?= $value['execute_note']?></td>
                    <td><?php if( $value['backup_status'] == '3' ){ echo "<a href='download?id=".$value['id']."' target='_blank' ><i class='iconfont'>&#xe610;</i></a>"; } ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>