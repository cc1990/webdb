<?php
    $this->title = '备份策略组修改';
    $title2 = "<a href='/backup/strategy/index' event-bind='redirect'>备份策略</a>-策略组修改";
?>
    <div class="div-index">
        <div class="servers-index div-index">
            <?php $this->params['breadcrumbs'][] = $this->title; ?>
            <h2><?=$title2?></h2>
        </div>
    <div/>
        <form class="sui-form form-horizontal" style="margin-top: 20px">
            <div class="control-group">
                <label class="control-label v-top"><b style="color: #f00;">*</b>
                    备份策略组名称：
                </label>
                <div class="controls">
                    <input type="text" id="name" value="<?=$strategyInfo['name']?>" class="input-xxlarge">
                </div>
            </div>
            <div class="part-content">
            <div class="part-title">&nbsp;策略添加:</div>
            <div class="control-group">
                <label class="control-label v-top"><b style="color: #f00;">*</b>
                    备份策略类型：
                </label>
                <div class="controls">
                    <label data-toggle="radio" class="radio-pretty inline checked">
                        <input type="radio" checked="checked" name="type" value="物理全量备份"><span>物理全量备份</span>
                    </label>
                    <label data-toggle="radio" class="radio-pretty inline">
                        <input type="radio"  name="type" value="物理增量备份"><span>物理增量备份</span>
                    </label>
                    <label data-toggle="radio" class="radio-pretty inline">
                        <input type="radio"  name="type" value="逻辑备份"><span>逻辑备份</span>
                    </label>
                    <label data-toggle="radio" class="radio-pretty inline">
                        <input type="radio"  name="type" value="binlog备份"><span>binlog备份</span>
                    </label>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label v-top"><b style="color: #f00;">*</b>
                    备份周期：
                </label>
                <div class="controls">
                    <span class="sui-dropdown dropdown-bordered"><span class="dropdown-inner"><a role="button" data-toggle="dropdown" href="#" class="dropdown-toggle" id="month"><i class="caret"></i><span>*</span></a>
                      <ul role="menu" aria-labelledby="drop1" class="sui-dropdown-menu">
                          <li role="presentation" class="active"><a role="menuitem" tabindex="-1" href="#">*</a></li>
                          <?php for($i = 1 ; $i < 12 ;$i++) : ?>
                              <li role="presentation"><a role="menuitem" tabindex="-1" href="#"><?=$i?></a></li>
                          <?php endfor; ?>
                      </ul></span></span><label class="date-unit">月</label>
                    <span class="sui-dropdown dropdown-bordered"><span class="dropdown-inner"><a role="button" data-toggle="dropdown" href="#" class="dropdown-toggle" id="week"><i class="caret"></i><span>*</span></a>
                      <ul role="menu" aria-labelledby="drop1" class="sui-dropdown-menu">
                          <li role="presentation" class="active"><a role="menuitem" tabindex="-1" href="#">*</a></li>
                          <?php for($i = 1 ; $i < 7 ;$i++) : ?>
                              <li role="presentation"><a role="menuitem" tabindex="-1" href="#"><?=$i?></a></li>
                          <?php endfor; ?>
                      </ul></span></span><label class="date-unit">周</label>
                    <span class="sui-dropdown dropdown-bordered"><span class="dropdown-inner"><a role="button" data-toggle="dropdown" href="#" class="dropdown-toggle" id="day"><i class="caret"></i><span>*</span></a>
                      <ul role="menu" aria-labelledby="drop1" class="sui-dropdown-menu">
                          <li role="presentation" class="active"><a role="menuitem" tabindex="-1" href="#">*</a></li>
                          <?php for($i = 1 ; $i < 31 ;$i++) : ?>
                              <li role="presentation"><a role="menuitem" tabindex="-1" href="#"><?=$i?></a></li>
                          <?php endfor; ?>
                      </ul></span></span><label class="date-unit">天</label>
                    <span class="sui-dropdown dropdown-bordered"><span class="dropdown-inner"><a role="button" data-toggle="dropdown" href="#" class="dropdown-toggle" id="hour"><i class="caret"></i><span>*</span></a>
                      <ul role="menu" aria-labelledby="drop1" class="sui-dropdown-menu">
                          <li role="presentation" class="active"><a role="menuitem" tabindex="-1" href="#">*</a></li>
                          <?php for($i = 1 ; $i < 24 ;$i++) : ?>
                              <li role="presentation"><a role="menuitem" tabindex="-1" href="#"><?=$i?></a></li>
                          <?php endfor; ?>
                      </ul></span></span><label class="date-unit">时</label>
                    <span class="sui-dropdown dropdown-bordered"><span class="dropdown-inner"><a role="button" data-toggle="dropdown" href="#" class="dropdown-toggle" id="minute"><i class="caret"></i><span>*</span></a>
                      <ul role="menu" aria-labelledby="drop1" class="sui-dropdown-menu">
                          <li role="presentation" class="active"><a role="menuitem" tabindex="-1" href="#">*</a></li>
                          <?php for($i = 1 ; $i < 60 ;$i++) : ?>
                              <li role="presentation"><a role="menuitem" tabindex="-1" href="#"><?=$i?></a></li>
                          <?php endfor; ?>
                      </ul></span></span><label class="date-unit">分</label>
                    <div class="sui-msg msg-tips msg-default" style="vertical-align: top;">
                        <div class="msg-con warning-msg">
                            <p style="margin-bottom: 8px; font-weight: bold;">备份周期如何选择：</p>
                            <p>全部都为*表示关闭备份策略</p>
                            <p>精确到时,1时表示1时0分0秒开始备份</p>
                            <p>各日期配合使用，例:若只选择了月和周,则表示每年几月的周几24时00分00秒开始备份</p>
                        </div>
                        <s class="msg-icon"></s>
                    </div>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label v-top"><b style="color: #f00;">*</b>
                    保留期限：
                </label>
                <div class="controls">
                    <input type="text" value="" class="input-medium" id="retention_num">
                    <span class="sui-dropdown dropdown-bordered"><span class="dropdown-inner"><a role="button" data-toggle="dropdown" href="#" class="dropdown-toggle" id="retention_unit"><i class="caret"></i><span>天</span></a>
                      <ul role="menu" aria-labelledby="drop1" class="sui-dropdown-menu">
                          <li role="presentation" class="active"><a role="menuitem" tabindex="-1" href="#">天</a></li>
                          <li role="presentation"><a role="menuitem" tabindex="-1" href="#">周</a></li>
                          <li role="presentation"><a role="menuitem" tabindex="-1" href="#">月</a></li>
                          <li role="presentation"><a role="menuitem" tabindex="-1" href="#">年</a></li>
                      </ul></span></span>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label"></label>
                <div class="controls">
                    <button type="button" class="sui-btn btn-primary" id="content-add">添加策略</button>
                </div>
            </div>
        </div>

        <div class="part-content">
            <div class="part-title">&nbsp;策略列表:</div>
            <table class="sui-table table-bordered" id="backup_content">
                <thead>
                <tr>
                    <th>备份类型</th>
                    <th>备份周期</th>
                    <th>保留期限</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                    <?php foreach($strategyContentList as $value) : ?>
                        <tr>
                            <td><?=$value['type']?></td>
                            <td><?=$value['cycle']?></td>
                            <td><?=$value['retention_time']?></td>
                            <td><a href="#" style="cursor:pointer" title="删除"><i class="sui-icon icon-touch-garbage"></i></a>&nbsp;&nbsp;&nbsp;
                                <a href="#" style="cursor:pointer" title="编辑"><i class="sui-icon icon-touch-todo"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
            <input type="hidden" id="id" value="<?=$strategyInfo['id']?>">
            <a href="javascript:void(0);" class="sui-btn btn-block btn-xlarge btn-primary" id="save">更新备份策略组</a>
        </form>
<?php $this->registerCssFile("/public/css/backup.css"); ?>
<?php $this->registerJsFile("/public/js/common.function.js"); ?>
<?php $this->registerJsFile("/public/js/local/backup_strategy_modify.js"); ?>
