<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\modules\servers\models\ServersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = "数据备份";
$type = isset($search['type']) ? $search['type'] : 'backup';
?>
<style>
    .select-host{text-align: left;padding-left: 45px;font-size:17px;font-weight:600;padding-bottom:8px}
    .select-host select{font-weight:600;margin-right: 10px}
</style>
<div class="div-index">
<div class="servers-index div-index">
    <?php $this->params['breadcrumbs'][] = $this->title; ?>
    <h1><?=$this->title ?></h1>
</div>
    <div class="blog-title content">
        <div class="select-menu sui-layout select-host">
            请选择服务器： <select name="server_host" id="DBHost">
                <?php foreach($servers_list as $key=>$val) { ?>
                    <option server_id="<?=$val['server_id']?>" <?php if(isset($search['host'])&&$search['host'] == $val['ip']) echo 'selected="selected"';?> value="<?=$val['ip']?>" data-environment="<?=$val['environment']?>" data-name="<?=$val['name']?>"><?=$val['ip']?> - <?=$val['name']?></option>';
                <?php } ?>
            </select>
            请选择备份方式：
            <select id="type">
                <option value="righter" <?php if($type == "righter") echo "selected = 'selected'"?>>outfile</option>
                <option value="backup" <?php if($type == "backup") echo "selected = 'selected'"?>>mysqldump</option>
            </select>
        </div>
        <div>
            <div <?php if($type == "righter") echo "hidden"?> id="backup_add">
                <div class="select-menu sui-layout content-left">
                        请选择模版： <select id="template_list">
                            <option value="0">请选择模版</option>
                            <?php foreach($template_list as $key=>$val) { ?>
                                <option value="<?=$val['id']?>" data-databases="<?=$val['databases']?>" data-host="<?=$val['host']?>" data-tables="<?=$val['tables']?>" data-where="<?=$val['where']?>" data-job_number="<?=$val['job_number']?>"><?=$val['template_name']?></option>';
                            <?php } ?>
                        </select>
                        <a href="/servers/rule/index" class="sui-btn btn-large  btn-success" id="sel_database"><i class="iconfont"></i>备份数据库选择</a> ->
                        <a href="/servers/rule/index" class="sui-btn btn-large  btn-success" id="sel_table"><i class="iconfont"></i>备份表格选择</a> ->
                        <input type="hidden" id="data_database" value="*">
                        <input type="hidden" id="data_table" value="*">
                        备份条件: <input type="text" class="input-xlarge disabled" id="where" value="" disabled="disabled"/>
                        工单号: <input type="text" class="input-xlarge disabled" id="job_number" value=""/>
                </div>
                <div class="content-right">
                    <a href="javascript:void(0);" class="sui-btn btn-xlarge btn-primary" id="save">保存</a>
                    <a href="javascript:void(0);" class="sui-btn btn-xlarge btn-primary" id="backup">备份</a>
                </div>
            </div>
            <div <?php if($type == "backup") echo "hidden"?> id="righter_add">
                <div class="select-menu sui-layout content-left">
                        订正语句: <textarea id="outfile_sql" value="" cols="120"><?=isset($search['sql'])?$search['sql'] : ''?></textarea>
                </div>
                <div class="content-right">
                    <a href="javascript:void(0);" class="sui-btn btn-xlarge btn-primary" id="righter_backup">备份</a>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="tab_table">
    <ul class="sui-nav nav-tabs nav-large">
        <li <?php if($type == "righter") echo "class = 'active'"?> id="tab_righter"><a href="#righter_tab">outfile</a></li>
        <li <?php if($type == "backup") echo "class = 'active'"?> id="tab_backup"><a href="#backup_tab">mysqldump</a></li>
    </ul>
    <table class="sui-table table-bordered table-zebra" <?php if($type == "backup") echo 'style="display : none"';?> id="righter_tab">
        <thead>
        <tr>
            <th width="5%">ID</th>
            <th width="10%">服务器IP</th>
            <th width="65%">订正语句</th>
            <th width="10%">订正时间</th>
<!--            <th width="10%">操作</th>-->
        </tr>
        </thead>
        <tbody>
        <tr><td></td><td><input class="form-control" name="RighterSearch[host]" type="text" value="<?=isset($search['righterSearchHost'])?$search['righterSearchHost']:''?>"></td><td></td><td></td></tr>
        <?php foreach($righterLogList as $key=>$value) : ?>
            <tr>
                <td><?=$value['id']?></td>
                <td><?=$value['host']?></td>
                <td><?=stripslashes($value['sql'])?></td>
                <td><?=date("Y-m-d H:i:s",$value['create_time'])?></td>
<!--                <td><a href="javascript:void(0)" event-bind="backup" data-id="--><?//=$value['id']?><!--">备份</a></td>-->
            </tr>
        <?php endforeach; ?>
        <tr><td colspan="8"><?=$righterPageHtml?></td></tr>
        </tbody>
    </table>

    <table class="sui-table table-bordered table-zebra" <?php if($type == "righter") echo 'style="display : none"';?> id="backup_tab">
        <thead>
        <tr>
            <th width="4%">ID</th>
            <th width="10%">主机</th>
            <th width="20%">数据库</th>
            <th width="20%">表</th>
            <th width="20%">导出条件</th>
            <th width="6%">工单号</th>
            <th width="10%">创建时间</th>
            <th width="10%">操作</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td></td>
            <td><input class="form-control" name="BackupSearch[host]" type="text" value="<?=isset($search['backupSearchHost'])?$search['backupSearchHost']:''?>"></td>
            <td><input class="form-control" name="BackupSearch[databases]" type="text" value="<?=isset($search['databases'])?$search['databases']:''?>"></td>
            <td><input class="form-control" name="BackupSearch[table]" type="text" value="<?=isset($search['table'])?$search['table']:''?>"></td>
            <td><input class="form-control" name="BackupSearch[where]" type="text" value="<?=isset($search['where'])?$search['where']:''?>"></td>
            <td><input class="form-control" name="BackupSearch[job_number]" type="text" value="<?=isset($search['job_number'])?$search['job_number']:''?>"></td>
            <td></td>
            <td></td>
        </tr>
        <?php foreach($logs_list as $key=>$value) : ?>
            <tr>
                <td><?=$value['id']?></td>
                <td><?=$value['host']?></td>
                <td><?=$value['databases']?></td>
                <td><?=$value['tables']?></td>
                <td><?=$value['where']?></td>
                <td><?=$value['job_number']?></td>
                <td><?=date("Y-m-d H:i:s",$value['create_time'])?></td>
                <td><a href="javascript:void(0)" event-bind="backup" data-id="<?=$value['id']?>">备份</a> <?php if($value['template'] == 1){echo "已设为模板";}else{echo '<a href="javascript:void(0)" data-bind="template" data-id="'.$value['id'].'">设为模版</a>';} ?></td>
            </tr>
        <?php endforeach; ?>
        <tr><td colspan="8"><?=$backupPageHtml?></td></tr>
        </tbody>
    </table>
    </div>
<?php $this->registerJsFile("/public/js/local/function.js"); ?>
<?php $this->registerJsFile("/public/js/local/index_back_index.js"); ?>
