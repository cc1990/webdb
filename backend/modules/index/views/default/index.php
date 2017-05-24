<?php
use yii\helpers\Url;
use yii\helpers\Html;
/* @var $this yii\web\View */

$this->title = 'sql操作平台';
?>
<style type="text/css">
</style>
    <div id="main">
        <div class="tree">
            <div class="check-menu">
                <ul>
                    <li><a href="javascript:;" class="active">通用库</a></li>
                    <li><a href="/index/sharding">分库分表</a></li>
                </ul>
            </div>
            <div class="select-menu">
                <select name="server_host" id="DBHost" >
                    <?php foreach ($server_list as $v):?>
                        <?php //if (in_array($v['server_id'],$server_ids)): ?>
                            <?php $is_have = 1; ?>
                            <option server_id= "<?=$v['server_id']?>"  value="<?=$v['ip']?>" data-environment="<?=$v['environment'] ?>" data-name="<?=$v['name']?>"><?=$v['ip']?> - <?=$v['name']?></option>';
                        <?php //endif; ?>
                    <?php endforeach;?>
                    <?php if (empty($is_have)): ?>
                        <option value='0' >未指定</option>
                    <?php endif; ?>
                </select>
                <span class="status" title="点击检查该服务器状态"><i class="iconfont">&#xe671;</i></span>
            </div>
            <div class="tree-menu">
                <ul class="well" id="DBName">
                <?php foreach ($allPrivilege['privilege'] as $server_ip => $databases) {
                    if( empty( $server_list[$server_ip] ) ){  continue; }
                    foreach ($databases as $database=>$tables) {
                ?>
                    <li class="dblist server_<?= $server_list[$server_ip]['server_id'] ?>">
                        <div class="db-name" data-dbname="<?=$database ?>"><span class="db-name-title"><i class="iconfont">&#xe606;</i><?=$database ?></span></div>
                    </li>
                <?php }} ?>
                    <!-- <li class="dblist">
                        <div class="db-name"><span class="db-name-title"><i class="iconfont">&#xe606;</i>membercenter</span></div>
                        <ul class="tblist">
                            <li class="tb-name" data-tbname="qccr" data-dbname="membercenter"><span class="tb-name-title"><i class="iconfont">&#xe602;</i>qccrt</span></li>
                            <li class="tb-name" data-tbname="qccr" data-dbname="membercenter"><span class="tb-name-title"><i class="iconfont">&#xe602;</i>qccrt</span></li>
                            <li class="tb-name" data-tbname="qccr" data-dbname="membercenter"><span class="tb-name-title"><i class="iconfont">&#xe602;</i>qccrt</span></li>
                            <li class="tb-name" data-tbname="qccr" data-dbname="membercenter"><span class="tb-name-title"><i class="iconfont">&#xe602;</i>qccrt</span></li>
                            <li class="tb-name" data-tbname="qccr" data-dbname="membercenter"><span class="tb-name-title"><i class="iconfont">&#xe602;</i>qccrt</span></li>
                        </ul>
                    </li> -->
                    
                </ul>
            </div>
            <div class="contextMenu" id="dbMenu" style="display: none;">
                <ul>
                    <li id="refresh-db"><i class="iconfont">&#xe620;</i>加载表</li>
                    <li id="create-tb"><i class="iconfont">&#xe640;</i>创建表</li>
                </ul>
            </div>
            <div class="contextMenu" id="tbMenu" style="display: none;">
                <ul>
                    <li id="open-table"><i class="iconfont">&#xe638;</i>SQL操作</li>
                    <li id="select-table" class="nav-border-bottom"><i class="iconfont">&#xe637;</i>打开表</li>
                    <li id="create-table"><i class="iconfont">&#xe640;</i>新增表</li>
                    <!-- <li id="alter-table" class="nav-border-bottom"><i class="iconfont">&#xe623;</i>编辑表结构</li> -->
                    <li id="refresh-table"><i class="iconfont">&#xe620;</i>刷新</li>
                    <li id="info-table"><i class="iconfont">&#xe619;</i>表信息</li>
                </ul>
            </div>
            <script type="text/javascript">
                
            </script>
        </div>
        <div class="spread" title="隐藏"><div class="img"></div></div>
        <div class="right" >
            <div class="container">
                <div class="tabbox" id="tabbox">
                    <!-- <ul class="sui-nav nav-tabs nav-large"> -->
                    <ul>
                        <li class="active"><span class="nav-title">首页&nbsp;&nbsp;&nbsp;</span></li>
                    </ul>
                </div>
                <div id="pagebox">
                    <div class="pagebox-index">
                        <!-- <div class="index-title"><span class="webdb-title">WEBDB运行状态</span><span class="nowdatetime"><?= date('Y-m-d H:i:s') ?></span></div> -->
                        <div class="index-box">
                            <div class="left">
                                <div class="action-sm">
                                    <h4>相关说明：</h4>
                                    <ul>
                                        <li>1、所选择的DB服务器，注意查看状态（正常的通信状态时为绿色标识<i class='iconfont' style='color:#34c360'>&#xe663;</i>，不能ping通为红色标<i class='iconfont' style='color:#e8351f'>&#xe655;</i> </li>
                                        <li>2、双击数据库名可展开显示其下的所有表；（<font color="red">如果双击库名没任何反应时表示该服务器无法连通或无表。</font>）
                                        </li>
                                        <li>3、数据库和表均有右键快捷菜单可操作；</li>
                                        <li>4、点击多个标签页可切换查看执行的SQL信息和结果集，标签页上限5个；</li>
                                        <li>5、对于无权限的环境或库表目标，请走工单申请权限或联系DBA沟通。。</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="version-box">
                                <!-- <div class="version-title"><?=$version['version_title']?></div> -->
                                <div class="version-log">
                                    <?=$version['version_log']?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="pagebox-list" style="display: none;">
                        <form class="sui-form">
                            <div class="navbox">
                                <div>
                                    <div style="display: none;">
                                        <input type="text" name="DBHost">
                                        <input type="text" name="DBName">
                                        <input type="text" name="tbname">
                                    </div>
                                        <select id="project" name="project" class="hidden">
                                            <option value='0' >请选择项目</option>
                                        <?php foreach ($project_list as $v):?>
                                            <option value='<?=$v['pro_id']?>' ><?=$v['name']?></option>
                                        <?php endforeach;?>
                                        </select>
                                    <div class="control-group select-group" name="select_project">
                                        项目：
                                        <div class="sui-btn-group select-absolute">
                                            <button data-toggle="dropdown" class="sui-btn dropdown-toggle button-dropdown" value="杭州"><i class="caret"></i>请选择项目</button>
                                            <ul role="menu" aria-labelledby="drop1" class="sui-dropdown-menu dropdown-scroll">
                                                <li name="search_project"><input type="text" placeholder="项目检索" class="input-default" value=""></li>
                                                <?php foreach ($project_list as $v):?>
                                                    <li role="presentation"><a role="menuitem" tabindex="-1" href="#" data-pro_id="<?=$v['pro_id']?>"><?=$v['name']?></a></li>
                                                <?php endforeach;?>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="control-group">
                                        <label><input type="checkbox" name="is_formal" value="1">&nbsp;正式脚本</label>&nbsp;
                                        <label><input type="checkbox" name="batch" value="1">&nbsp;批量注释</label>&nbsp;
                                        <input type="text" name="batch_notes" class="input-xlarge"><i class="sui-icon icon-tb-questionfill" title="请在需要相同类型批量操作的时候使用本功能"></i>
                                    </div>
                                    <div class="control-group">
                                    <a href="javascript:void(0);" class="sui-btn btn-large btn-info white" id="sql_verify" >SQL检测</a>
                                    <a href="javascript:void(0);" class="sui-btn btn-large btn-success white" id="submit" >执行SQL</a></div>
                                
                                </div>
                            </div>
                            <div class="sqlbox">
                                <textarea name="sqlinfo" class="sqlinfo" id="0" spellcheck="false"></textarea>
                            </div>
                        </form>
                        <div class="resultnav">
                            <ul>
                                <li class="active"><span class="resultnav-title">消息</span></li>
                            </ul>
                        </div>
                        <div class="resultbox">
                            <div class="result-info-box">
                            </div>
                            
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <?=Html::jsFile('@web/public/js/jquery.contextmenu.r2.js') ?>
    <?=Html::jsFile('@web/public/plug/layer/layer.js') ?>
    <?=Html::cssFile('@web/public/plug/easyui/easyui.css')?>
    <?=Html::jsFile('@web/public/plug/easyui/jquery.easyui.min.js') ?>
    <?=Html::jsFile('@web/public/js/layout.js') ?>

    <link rel=stylesheet href="/codemirror/doc/docs.css">
    <link rel="stylesheet" href="/codemirror/lib/codemirror.css" />
    <link rel="stylesheet" href="/codemirror/theme/mysql.css" />
    <script src="/codemirror/lib/codemirror.js"></script>
    <script src="/codemirror/mode/sql/sql.js"></script>
    <link rel="stylesheet" href="/codemirror/addon/hint/show-hint.css" />
    <script src="/codemirror/addon/hint/show-hint.js"></script>
    <script src="/codemirror/addon/hint/sql-hint.js"></script>
    <script src="/codemirror/addon/hint/get-dynamic-keyworks.js"></script>
<!--    <script src="/codemirror/addon/hint/grammarParseClass.js"></script>-->
<!--    <script src="/codemirror/addon/hint/parseClass.js"></script>-->
    <script type="text/javascript">
    $(document).ready(function(){
        $(".tree-menu .dblist").hide();
        show_dblist( ping( $("#DBHost").val() ));
        window.editor = new Array;
    });
        //服務器默認執行條數
    $("#DBHost").on("change", function () {
        //$(this).next().html("<i class=\"iconfont\">&#xe671;</i>");
        $(".tree-menu .dblist").hide();
        show_dblist( ping( $("#DBHost").val() ));
    });

    $(".select-menu .status").click(function(){
        ping( $("#DBHost").val() );
    })


    function show_dblist(data){
        var server_id = $("#DBHost option:selected").attr("server_id");
//        $(".tree-menu .server_"+server_id).show();
        $(".tree-menu .server_"+server_id).each(function(){
            var db_name = $(this).find('div').first().attr('data-dbname');
            if(data.db_list == 'all' || data.db_list[db_name] == 1){
                $(this).show();
            }
        });
    }

    //关闭项目检索的click事件
    $("#pagebox").on('click',"[name='select_project'] ul [name='search_project']",function(){
        return false;
    });

    //项目检索事件
    $("#pagebox").on('keyup',"[name='select_project'] [name='search_project']",function(){
        var search = $(this).find("input").val();
        $(this).parent().parent().find('li').each(function(i){
            var name = $(this).find("a").first().text();
            if(name != '') {
                if (name.indexOf(search) === -1) {
                    $(this).removeClass("show");
                    $(this).addClass("hide");
                } else {
                    $(this).removeClass("hide");
                    $(this).addClass("show");
                }
            }
        });
    });

    //选中事件
    $('#pagebox').on('click',"[name='select_project'] ul li a",function(){

        $(this).parent().parent().parent().parent().prev().val($(this).attr('data-pro_id'));
        $(this).parent().parent().prev().val($(this).attr('data-pro_id'));
        $(this).parent().parent().prev().html('<i class="caret"></i>' + "<span>" + $(this).html() + "</span>");
        $(this).parent().parent().prev().attr("title",$(this).html());
    });

    </script>