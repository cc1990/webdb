<?php
    $this->title = '数据库备份策略';
    $title2 = "<a href='/backup/index/index' event-bind='redirect'>工作台</a>-备份服务器策略管理";
?>
<div class="div-index">
<div class="servers-index div-index">
    <?php $this->params['breadcrumbs'][] = $this->title; ?>
    <h1><?=$title2?></h1>
</div>
<div class="blog-title content">
    <div class="content-right sui-layout select-menu">
        <a class="sui-btn btn-large btn-primary" href="/backup/strategy/add"><i class="sui-icon icon-plus-sign"></i>新增备份策略</a>
    </div>
</div>


<table class="sui-table table-bordered table-zebra table-content-center" id="dbList">
    <thead>
    <tr>
        <th>ID</th>
        <th>策略名称</th>
        <th>状态</th>
        <th>操作</th>
    </tr>
    </thead>
    <tbody>
        <tr>
            <td></td>
            <td><input class="form-control" name="StrategySearch[name]" type="text" value="<?=isset($search['name']) ? $search['name'] : ''?>"></td>
<!--            <td><input class="form-control" name="StrategySearch[status]" placeholder="1-开启 0-关闭" type="text" value="--><?//=isset($search['status']) ? $search['status'] : ''?><!--"></td>-->
            <td><select name="StrategySearch[status]">
                    <option value="">请选择状态</option>
                    <option value="1" <?php if(isset($search['status']) && $search['status'] == 1) echo 'selected="selected"'; ?>>开启</option>
                    <option value="0" <?php if(isset($search['status']) && $search['status'] == 0) echo 'selected="selected"'; ?>>关闭</option>
                </select>
            </td>
            <td></td>
        </tr>
        <?php foreach($strategyList as $key=>$value) : ?>
            <tr><td><?=$value['id']?></td>
                <td><?=$value['name']?></td>
                <td><img src="/public/images/<?=$value['status']?"on":"off"?>.png" event-bind="status_change" data-status="<?=$value['status']?0:1?>" data-id="<?=$value['id']?>"></td>
                <td>
                    <a name="relationHost"  href="/backup/strategy/get-all?strategy_id=<?=$value['id']?>" data-id="<?=$value['id']?>">关联主机</a>
                    <a  href="/backup/strategy/update?id=<?=$value['id']?>">修改</a>
                    <a name="delete" href="/backup/strategy/delete?id=<?=$value['id']?>">删除</a>
                </td>
            </tr>
        <?php endforeach; ?>
                <td colspan="5"><?=$pageHtml?></td>
    </tbody>
</table>
</div>
<?php $this->registerJsFile("/public/js/common.function.js"); ?>
<?php $this->registerJsFile("/public/js/local/backup_strategy_index.js"); ?>
