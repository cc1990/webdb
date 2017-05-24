<title>CodeMirror: SQL Mode for CodeMirror</title>
<meta charset="utf-8"/>
<link rel=stylesheet href="/codemirror/doc/docs.css">
<link rel="stylesheet" href="/codemirror/lib/codemirror.css" />
<script src="/codemirror/lib/codemirror.js"></script>
<!--<script src="/codemirror/mode/sql/sql.js"></script>-->
<!--<script src="/codemirror/mode/javascript/javascript.js"></script>-->
<script src="/codemirror/mode/css/css.js"></script>
<body>
<div style="width: 100%;text-align: center;">
    <textarea id="code">
        function hello(){
        var hello = '123';
    }
    </textarea>
</div>
<div style="width: 100%;" id="editor1">
</div>
</body>

<script>
    window.onload = function(){
//        var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
//            lineNumbers: true,
//            matchBrackets: true,
//            continueComments: "Enter",
//            extraKeys: {"Ctrl-Q": "toggleComment"}
//        });

//        var editor1 = CodeMirror(document.body);
//        var editor2 = CodeMirror(document.body,{
//            value : "function myScript(){\nreturn 1000;\n}\n",
//            mode : "javascript",
//            lineNumbers : true,
//        });
//        var code = document.getElementById('code');
//        var editor3 = CodeMirror(function(elt){code.parentNode.replaceChild(elt,code);},
//            {value : code.value,mode : "css"})
        var editor4 = CodeMirror.fromTextArea(document.getElementById("code"),{
            lineNumbers : true,
//            mode : "css",
        })

    };
</script>