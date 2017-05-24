
<link rel="stylesheet" type="text/css" href="http://g.alicdn.com/sj/dpl/1.5.1/css/sui.min.css">
<div class="div-index">
    <div>
        <table class="sui-table table-bordered table-zebra">
            <thead>
                <tr>
                    <th width="20px">#</th>
                    <th width="100px">服务器IP</th>
                    <th width="100px">数据库名</th>
                    <th>项目名称</th>
                    <th>SQL语句</th>
                    <th width="200px">执行结果</th>
                    <th width="200px">执行时间</th>
                    <th width="60px">执行人</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($list as $key => $value) { ?>
                <tr>
                    <td><?= $key+1?></td>
                    <td><?= $value['host']?></td>
                    <td><?= $value['database']?></td>
                    <td><?= $value['name'] ? $value['name'] : $value['project_name']?></td>
                    <td><?= $value['script']?></td>
                    <td><?= $value['result']?></td>
                    <td><?= $value['created_date']?></td>
                    <td><?= $value['chinesename']?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>