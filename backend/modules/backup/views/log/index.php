<?php
    $this->title = '数据库备份日志';
    $title2 = "<a href='/backup/index/index' event-bind='redirect'>工作台</a>-备份日志查看";
    $page = $search['page'];
    $pageSize = $search['pageSize'];
    $localHostList = [];
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

    </div>
</div>


<table class="sui-table table-bordered table-zebra table-content-center" id="dbList">
    <thead>
    <tr>
        <th width="3%">序号</th>
        <th width="5%">备份服务器IP</th>
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
    </tr>
    </thead>
    <tbody>
        <tr>
            <td></td>
            <td>
                <select class="form-control" name="LogSearch[server_ip]" type="text" value="">
                    <option value="0">请选择备份服务器</option>
                    <?php foreach($serverList as $server) : ?>
                        <option value="<?=$server['server_ip']?>" <?php if(isset($search['server_ip'])&&$search['server_ip']==$server['server_ip']) echo 'selected="selected"'; ?>><?=$server['server_ip']?>/<?=$server['serverName']?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>
                <select class="form-control" name="LogSearch[type]" type="text" value="">
                    <option value="0">请选择备份类型</option>
                    <?php foreach(Yii::$app->params['backup']['type'] as $key=>$value) : ?>
                        <option value="<?=$key?>" <?php if(isset($search['type'])&&$search['type']==$key) echo 'selected="selected"'; ?>><?=$value?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <select class="form-control" name="LogSearch[status]" type="text" value="">
                    <option value="0">请选择备份状态</option>
                    <option value="1" <?php if(isset($search['status'])&&$search['status']==1) echo 'selected="selected"'; ?>>备份中</option>
                    <option value="2" <?php if(isset($search['status'])&&$search['status']==2) echo 'selected="selected"'; ?>>备份成功</option>
                    <option value="3" <?php if(isset($search['status'])&&$search['status']==3) echo 'selected="selected"'; ?>>备份失败</option>
                </select>
            </td>
        </tr>
        <?php foreach($logList as $key=>$value) : ?>
            <tr><td><?=($page-1)*$pageSize + ($key+1)?></td>
                <td><?=$value['server_ip']?></td>
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
            </tr>
        <?php endforeach; ?>
                <td colspan="5"><?=$pageHtml?></td>
    </tbody>
</table>
</div>
<?php $this->registerJsFile("/public/js/common.function.js"); ?>
<?php $this->registerJsFile("/public/js/local/backup_log_index.js"); ?>
