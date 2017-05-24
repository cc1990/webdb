<?php
    $this->title = '数据库备份控制台';
    $title2 = "<a href='/backup/index/index' event-bind='redirect'>工作台</a>";
    $page = isset($search['page']) ? $search['page'] : 1;
    $pageSize = isset($search['pageSize']) ? $search['pageSize'] : 20;
?>
<?php
    /**
     * Description 时间转换函数
    */
    function SecToTime($time){
        if($time >= 60){
            $str = $time%60 . "秒";
            $minute = floor($time/60);
            if($minute >= 60){
                $str = floor($minute/60) . "时" . $minute % 60 . "分" .$str;
            }else{
                $str = $minute . "分" . $str;
            }
        }else{
            $str = "{$time}秒";
        }
        return $str;
    }
?>
<div class="div-index">
<div class="servers-index div-index">
    <?php $this->params['breadcrumbs'][] = $this->title; ?>
    <h1><?=$title2?></h1>
</div>
<div class="blog-title content">
    <div class="content-right sui-layout select-menu">
        <a href="/backup/host/index" class="sui-btn btn-large btn-primary" id="update_host"><i class="sui-icon icon-pc-settings"></i>备份服务器管理</a>
        <a href="/backup/strategy/index" class="sui-btn btn-large btn-success" id="update_host"><i class="sui-icon icon-pc-settings"></i>备份策略管理</a>
        <a href="/backup/log/index" class="sui-btn btn-large btn-warning" id="update_host"><i class="sui-icon icon-list-alt"></i>备份日志查看</a>
    </div>
</div>


<table class="sui-table table-bordered table-zebra table-content-center" id="dbList">
    <thead>
    <tr>
        <th width="3%">序号</th>
        <th width="5%">备份服务器IP</th>
        <th width="5%">备份服务器名称</th>
        <th width="5%">归档服务器IP</th>
        <th width="8%">备份开始时间</th>
        <th width="8%">备份结束时间</th>
        <th width="8%">备份消耗时间</th>
        <th width="8%">备份大小</th>
        <th width="10%">备份剩余空间</th>
        <th width="10%">归档剩余空间</th>
        <th width="10%">归档总空间</th>
        <th width="8%">备份类型</th>
        <th width="5%">备份状态</th>
        <th width="13%">操作</th>
    </tr>
    </thead>
    <tbody>
        <tr>
            <td></td>
            <td><input class="input-mini" name="LogSearch[server_ip]" type="text" value="<?=isset($search['server_ip']) ? $search['server_ip'] : ''?>"></td>
            <td><input class="input-mini" name="LogSearch[serverName]" type="text" value="<?=isset($search['serverName']) ? $search['serverName'] : ''?>"></td>
            <td><input class="input-mini" name="LogSearch[archive_ip]" type="text" value="<?=isset($search['archive_ip']) ? $search['archive_ip'] : ''?>"></td>
            <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
            <td></td>
            <td></td>
        </tr>
        <?php foreach($logList as $key=>$value) : ?>
            <tr><td><?=($page-1)*$pageSize + ($key+1)?></td>
                <td><?=$value['server_ip']?></td>
                <td><?=$value['serverName']?></td>
                <td><?=$value['archive_ip']?></td>
                <td><?=date("Y-m-d H:i:s",$value['start_time'])?></td>
                <td><?=date("Y-m-d H:i:s",$value['end_time'])?></td>
                <td><?=SecToTime($value['end_time'] - $value['start_time'])?></td>
                <td><?=$value['filesize']?></td>
                <td><?=$value['backup_remain_space']?></td>
                <td><?=$value['archive_remain_space']?></td>
                <td><?=$value['archive_all_space']?></td>
                <td><?=Yii::$app->params['backup']['type'][$value['type']]?></td>
                <td><?php switch($value['status']){case 1 : echo '备份中';break;case 2 : echo '备份成功';break;case 1 : echo '备份失败';break;}?></td>
                <td><a name="relationStrategy"  href="/backup/log/index?server_ip=<?=$value['server_ip']?>">查看历史记录</a></td>
            </tr>
        <?php endforeach; ?>
                <td colspan="14"><?=$pageHtml?></td>
    </tbody>
</table>
</div>
<?php $this->registerJsFile("/public/js/common.function.js"); ?>
<?php $this->registerJsFile("/public/js/local/backup_index_index.js"); ?>
