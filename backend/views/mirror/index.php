<?php
    use yii\helpers\Html;
?>
<!doctype html>

<title>CodeMirror: SQL Mode for CodeMirror</title>
<meta charset="utf-8"/>
<link rel=stylesheet href="/codemirror/doc/docs.css">
<link rel="stylesheet" href="/codemirror/lib/codemirror.css" />
<script src="/codemirror/lib/codemirror.js"></script>
<script src="/codemirror/mode/sql/sql.js"></script>
<link rel="stylesheet" href="/codemirror/addon/hint/show-hint.css" />
<script src="/codemirror/addon/hint/show-hint.js"></script>
<script src="/codemirror/addon/hint/sql-hint.js"></script>

<article>
<h2>SQL Mode for CodeMirror</h2>
<form>
            <textarea id="code">-- SQL Mode for CodeMirror
SELECT SQL_NO_CACHE DISTINCT
		@var1 AS `val1`, @'val2', @global.'sql_mode',
		1.1 AS `float_val`, .14 AS `another_float`, 0.09e3 AS `int_with_esp`,
		0xFA5 AS `hex`, x'fa5' AS `hex2`, 0b101 AS `bin`, b'101' AS `bin2`,
		DATE '1994-01-01' AS `sql_date`, { T "1994-01-01" } AS `odbc_date`,
		'my string', _utf8'your string', N'her string',
        TRUE, FALSE, UNKNOWN
	FROM DUAL
	-- space needed after '--'
	# 1 line comment
	/* multiline
	comment! */
	LIMIT 1 OFFSET 0;
</textarea>
            </form>
            <p><strong>MIME types defined:</strong> 
            <code><a href="?mime=text/x-sql">text/x-sql</a></code>,
            <code><a href="?mime=text/x-mysql">text/x-mysql</a></code>,
            <code><a href="?mime=text/x-mariadb">text/x-mariadb</a></code>,
            <code><a href="?mime=text/x-cassandra">text/x-cassandra</a></code>,
            <code><a href="?mime=text/x-plsql">text/x-plsql</a></code>,
            <code><a href="?mime=text/x-mssql">text/x-mssql</a></code>,
            <code><a href="?mime=text/x-hive">text/x-hive</a></code>,
            <code><a href="?mime=text/x-pgsql">text/x-pgsql</a></code>,
            <code><a href="?mime=text/x-gql">text/x-gql</a></code>.
        </p>
<script>
window.onload = function() {
      var mime = 'text/x-sql';
      // get mime type
      if (window.location.href.indexOf('mime=') > -1) {
        mime = window.location.href.substr(window.location.href.indexOf('mime=') + 5);
      }

      window.editor = CodeMirror.fromTextArea(document.getElementById('code'), {
        mode: mime,
        indentWithTabs: true,
        smartIndent: true,
        lineNumbers: true,
        matchBrackets : true,
        autofocus: true,
        extraKeys: {"Tab": "autocomplete"},
          hint : "javascript",
    //    hintOptions: {tables: {
    //      users: {name: null, score: null, birthDate: null},
    //      countries: {name: null, population: null, size: null}
    //    }}
      });

};
</script>

</article>
