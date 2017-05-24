<?php
    $this->title = '数据库备份';
    $title2 = "<a href='/backup/index/index' event-bind='redirect'>工作台</a>-备份服务器管理";
    $page = $search['page'];
    $pageSize = $search['pageSize'];
    $localHostList = [];
    foreach($serverList as $value){
        $localHostList[] = $value['serverIp'];
    }
?>
<div class="div-index">
<div class="servers-index div-index">
    <?php $this->params['breadcrumbs'][] = $this->title; ?>
    <h1><?=$title2?></h1>
</div>
<div class="blog-title content">
    <div class="content-right sui-layout select-menu">
        <a href="javascript:void(0);" class="sui-btn btn-large btn-primary" id="update_host"><i class="sui-icon icon-pc-settings"></i>设置备份服务器</a>
    </div>
</div>


<table class="sui-table table-bordered table-zebra table-content-center" id="dbList">
    <thead>
    <tr>
        <th>序号</th>
        <th>服务器地址</th>
        <th>服务器名称</th>
        <th>备份目录</th>
        <th>状态</th>
        <th>操作</th>
    </tr>
    </thead>
    <tbody>
        <tr>
            <td></td>
            <td><input class="form-control" name="HostSearch[serverIp]" type="text" value="<?=isset($search['serverIp']) ? $search['serverIp'] : ''?>"></td>
            <td><input class="form-control" name="HostSearch[serverName]" type="text" value="<?=isset($search['serverName']) ? $search['serverName'] : ''?>"></td>
            <td></td>
<!--            <td><input class="form-control" name="HostSearch[status]" placeholder="1-开启 0-关闭" type="text" value="--><?//=isset($search['status']) ? $search['status'] : ''?><!--"></td>-->
            <td><select name="HostSearch[status]">
                    <option value="">请选择主机状态</option>
                    <option value="1" <?php if(isset($search['status']) && $search['status'] == 1) echo 'selected="selected"'; ?>>开启</option>
                    <option value="0" <?php if(isset($search['status']) && $search['status'] == 0) echo 'selected="selected"'; ?>>关闭</option>
                </select></td>
            <td></td>
        </tr>
        <?php foreach($serverList as $key=>$value) : ?>
            <tr><td><?=($page-1)*$pageSize + ($key+1)?></td>
                <td><?=$value['serverIp']?></td>
                <td><?=$value['serverName']?></td>
                <td><?=$value['backupPath']?></td>
                <td><img src="/public/images/<?=$value['status']?"on":"off"?>.png" event-bind="status_change" data-status="<?=$value['status']?0:1?>" data-id="<?=$value['id']?>"></td>
                <td>
                    <a name="save" href="/backup/host/save" data-id="<?=$value['id']?>" data-serverIp="<?=$value['serverIp']?>" data-logPath="<?=$value['logPath']?>" data-serverName="<?=$value['serverName']?>" data-backupPath="<?=$value['backupPath']?>" data-scriptFile="<?=$value['scriptFile']?>" data-disk="<?=$value['disk']?>" data-archiveIp="<?=$value['archiveIp']?>" data-archivePath="<?=$value['archivePath']?>">编辑</a>&nbsp;
                    <a name="relationStrategy"  href="/backup/host/get-all?host_id=<?=$value['id']?>" data-id="<?=$value['id']?>">关联备份策略组</a></td>
            </tr>
        <?php endforeach; ?>
                <td colspan="5"><?=$pageHtml?></td>
    </tbody>
</table>
    <input type="hidden" id="localHostList" value='<?=json_encode($localHostList)?>' />
</div>
<?php $this->registerJsFile("/public/js/common.function.js"); ?>
<?php $this->registerJsFile("/public/js/local/backup_host_index.js"); ?>
