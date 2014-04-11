<?php
include_once './db.php';
$db = new MyPDO();
$statement = $db->query("SELECT table_name, column_name, referenced_table_name, referenced_column_name
FROM INFORMATION_SCHEMA.key_column_usage 
WHERE referenced_table_schema = 'biztram' 
  AND referenced_table_name IS NOT NULL 
ORDER BY table_name, column_name");
$references = array();
while ($reference = $statement->fetch(PDO::FETCH_OBJ)) {
    $references[$reference->table_name][] = $reference;
}
//echo "<div style='border: 2px solid green;'>" . "DEBUG" . " <pre>";
//print_r($references);
//echo "</pre></div>";

$statement = $db->query("show tables");
?>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>
<canvas id="links"></canvas>
<div id="tables">
    <?php while ($table = $statement->fetch(PDO::FETCH_COLUMN)) : ?>
        <div class="table" name="<?php echo $table; ?>"><?php echo $table; ?>
            <?php if (isset($references[$table])) foreach ($references[$table] as $parentRef): ?>
                    <input type="hidden" class="parent" column="<?php echo $parentRef->column_name ?>"
                           table_ref="<?php echo $parentRef->referenced_table_name ?>"
                           column_ref="<?php echo $parentRef->referenced_column_name ?>"/>
                       <?php endforeach; ?>
        </div>
    <?php endwhile; ?>
</div>
<style>
    *{
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        box-shadow: 0px 0px 1px black inset;
        background-color: rgba(255,255,255,0.5);
    }
    body, html{
        box-shadow: none;
        background-color: none;
    }
    /*    html{
            background-image: url('http://lorempixel.com/400/200/sports/');
            background-size: 100% 100%;
            background-attachment: fixed;
        }*/
    #tables, #tables *{
        -webkit-transition: all 100ms; /* For Safari 3.1 to 6.0 */
        -moz-transition: all 100ms;
        -o-transition: all 100ms;
        -ms-transition: all 100ms;
        transition: all 100ms;
    }
    body{
        margin: 0px;
    }
    #tables{
        width: 0px;
        height: 20px;
        padding-left: 20px;
        overflow: hidden;
        display: inline-block;
        position: fixed;
        top: 0px;
        left: 0px;
        background-color: orange;
        opacity: 0.8;
    }
    #tables:hover{
        width: auto;
        height: 100%;
        overflow: auto;
        padding-right: 50px;
    }
    .table{
        cursor: pointer;
    }
    .search{
        display: inline-block;
        min-height: 30px;
        min-width: 30px;
        position: absolute!important;
    }
    .search:hover{
        background-color: rgba(255,255,255,0.9);
    }
    .search input{
        width: 100px;
    }
    .table_name{
        cursor: move;
    }
    .remove{
        visibility: hidden;
        margin: 2px;
        cursor: pointer;
        float: left;
    }
    .search:hover .remove{
        visibility: visible;
    }
    .remove:hover{
        background-color: red;
    }
    .column_continer{
        width: 300px;
    }
    .column_continer select, .column_continer input{
        width: 140px;
    }
    .do_search{
        cursor: pointer;
    }
    .do_search:hover{
        background-color: greenyellow;
    }
    .children_tables, .parent_tables{
        position: absolute;
        margin-left: 15px;
        padding: 20px;
        border-radius: 0px 100px 115px 100px;
        margin-top: -20px;
    }
    .search tbody tr:hover{
        background-color: greenyellow;
    }
    .children_table, .parent_table{
        cursor: pointer;
    }
    .show_parent_tables, .show_children_tables{
        width: 20px;
    }
    canvas#links{
        position: absolute;
        z-index: -1;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        $('.table').click(function() {
            var $this = $(this);
            var table = $this.attr('name');
            var $search = $('<div>').addClass('search ').attr('name', table).css('left', '50px');
            $('body').prepend($search);
            $search.draggable({
                handle: '.table_name',
                snap: true,
                stop: function(event, ui) {
                    if (ui.position.left < 0) {
                        ui.helper.css('left', 0);
                    }
                    if (ui.position.top < 0) {
                        ui.helper.css('top', 0)
                    }
                    drawRelations();
                }
            });
            var $tableName = $('<div>').addClass('table_name').text(table);
            $search.append($tableName);
            /////////////////    remove the search
            var $remove = $('<span>').text('X').addClass('remove');
            $remove.click(function() {
                $(this).parents('.search').remove();
                drawRelations();
            });
            $tableName.append($remove);
            $search.append($this.children('input').clone());
            $search.effect("highlight");
            getColumns($search)
        });
    });
    function getColumns($search) {
        var table = $search.attr('name');
        $.ajax({data: {action: 'getColumns', table: table}
            , url: 'ajax.php'
            , async: false
            , type: 'POST'
            , success: function(response) {
                var $results = $('<table>');
                var $resultsHeader = $('<thead>');
                $results.append($resultsHeader);
                var $columns = $('<tr>');
                $resultsHeader.append($columns);
                //////// crear results
                var $columnClear = $('<th>').html('X').addClass('remove');
                $columnClear.click(function() {
                    var $tbody = $search.find('tbody');
                    $search.find('th input').val('');
                    $tbody.children().remove();
                    drawRelations();
                });
                $columns.append($columnClear);
                for (var ky in response) {
                    var column = response[ky];
                    var $column = $('<th>').text(column.Field).attr('column', column.Field);
                    var $columnFilter = $('<input>').attr('name', column.Field);
                    $column.append($columnFilter);
                    $columns.append($column);
                    $column.resizable({handles: "e"});
                }
                var $columnDo = $('<th>').html('&check;').addClass('do_search');
                $columnDo.click(doSearch);
                $columns.append($columnDo);
                var $resultsBody = $('<tbody>');
                $results.append($resultsBody);
                $search.append($results);
            }
        });
    }
    function doSearch() {
        var $this = $(this);
        var $thisSearch = $this.parents('.search');
        var $tbody = $thisSearch.find('tbody');
        $tbody.children().remove();
        var table = $thisSearch.attr('name');
        var params = $thisSearch.find('th input').serializeArray();
        $.post('ajax.php', {action: 'doSearch', table: table, params: params}, function(response) {
            for (var ky in response) {
                var row = response[ky];
                var $tr = $('<tr>');
                $tbody.append($tr);
                $thisSearch.find('th').each(function(ky, th) {
                    var $th = $(th);
                    var $td = $('<td>');
                    var column = $th.attr('column');
                    var value = row[column];
                    $td.text(value).attr('column', column);
                    $tr.append($td);
                });
                //show childrens List
                $tr.children().last().html('&disin;').addClass('show_children_tables').hover(showChildrenTables, function() {
                    $(this).children().remove();
                });
                //show parents List
                $tr.children().first().html('&leftarrow;').addClass('show_parent_tables').hover(showParentTables, function() {
                    $(this).children().remove();
                });
                drawRelations();
            }
        });
    }
    function showChildrenTables() {
        var $this = $(this);
        var $thisSearch = $this.parents('.search');
        var tableName = $thisSearch.attr('name');
        var $inputChildren = $('#tables input[table_ref=' + tableName + ']');
        $('.children_tables').remove();
        var $childrenTables = $('<div>').addClass('children_tables');
        $inputChildren.each(function() {
            var $childrenInfo = $(this);
            var childrenTableName = $childrenInfo.parent().attr('name');
            var $childrenTable = $('<div>').addClass('children_table')
                    .text(childrenTableName)
                    .attr('column', $childrenInfo.attr('column'))
                    .attr('table_ref', $childrenInfo.attr('table_ref'))
                    .attr('column_ref', $childrenInfo.attr('column_ref'));
            $childrenTables.append($childrenTable);
            $childrenTable.click(showRelationShip);
        });
        $this.append($childrenTables);
    }
    function showRelationShip() {
        var $this = $(this)
        var $thisRow = $this.parents('tr');
        var relationTable = $this.text();
        $this.parent().remove();
        var $relation = $('#tables div[name=' + relationTable + ']');
        $relation.click();
        var searchBy = $this.attr('column').trim();
        var searchByRef = $this.attr('column_ref').trim();
        var searchFor = $thisRow.children('td[column=' + searchBy + ']').text();
        var $searchIn = $('.search').first();
        var $searchInInput = $searchIn.find('input[name=' + searchByRef + ']');//esperar a que cargue
        $searchInInput.val(searchFor);
        $searchIn.find('.do_search').click();
    }
    function showParentTables() {
        var $this = $(this);
        var $thisSearch = $this.parents('.search');
        var tableName = $thisSearch.attr('name');
        var $inputParent = $thisSearch.find('input.parent');
        $('.parent_tables').remove();
        var $parentTables = $('<div>').addClass('parent_tables');
        $inputParent.each(function() {
            var $parentInfo = $(this);
            var parentTableName = $parentInfo.attr('table_ref');
            var $parentTable = $('<div>').addClass('parent_table')
                    .text(parentTableName)
                    .attr('column', $parentInfo.attr('column'))
                    .attr('table_ref', $parentInfo.attr('table_ref'))
                    .attr('column_ref', $parentInfo.attr('column_ref'));
            $parentTables.append($parentTable);
            $parentTable.click(showRelationShip);
        });
        $this.append($parentTables);
    }
    function drawRelations() {
        var $canvas = $('#links').width(0).height(0);
        var ctx = $canvas.get(0).getContext("2d");

        var newWidth = $(document).width()
        var newHeight = $(document).height()
        $canvas.width(newWidth).height(newHeight);

        ctx.canvas.width = newWidth;
        ctx.canvas.height = newHeight;
        ctx.clearRect(0, 0, newWidth, newHeight);

        $('.search').each(function() {
            var $thisSearch = $(this);
            var $parentsInfo = $thisSearch.find('input.parent');
            $thisSearch.find('tbody tr').each(function() {
                var $thisRow = $(this);
                $parentsInfo.each(function() {
                    var $thisParentInfo = $(this);
                    var parentTable = $thisParentInfo.attr('table_ref');
                    var parentColumn = $thisParentInfo.attr('column_ref');
                    var column = $thisParentInfo.attr('column');
                    var searchFor = $thisRow.children('[column=' + column + ']').text().trim();
                    var parentFilter = '.search[name=' + parentTable + '] tbody tr td[column=' + parentColumn + ']';
                    //console.log(parentFilter);
                    var $parentRows = $(parentFilter).filter(function() {
                        return $(this).text() === searchFor;
                    }).parent();
                    if ($parentRows.size() > 0) {
                        $parentRows.each(function() {
                            var $thisParent = $(this);
                            drawLine($thisParent, $thisRow, ctx);
                        });
                    }

                });
            });
        });
    }
    function drawLine($parent, $child, ctx) {
        var startX = $parent.offset().left + $parent.width();
        var startY = $parent.offset().top + $parent.height() / 2;

        var endX = $child.offset().left;
        var endY = $child.offset().top + $child.height() / 2;


        var widthLine = Math.abs(startX - endX) / 2;
        var heightLine = Math.abs(startY - endY);

        var start2X = startX + widthLine;
        var start2Y = startY;

        var start3X = start2X;
        var start3Y = start2Y + heightLine;

        if (endX < startX) {
            startX -= widthLine;
            start2X -= 2 * widthLine;
            start3X -= 3 * widthLine;
        }

        if (endY < start2Y) {
            start2Y = endY;
            start3Y -= 2 * heightLine;
        }

        //line container
//        var $lineContainer = $('<div>').addClass('line_container');
//        $('body').prepend($lineContainer);
//        var $line1 = $('<div>').addClass('line horizontal');
//        $line1.css('left', startX).css('top', startY).width(widthLine);
//        $lineContainer.append($line1);
//        var $line2 = $('<div>').addClass('line vertical');
//        $line2.css('left', start2X).css('top', start2Y).height(heightLine);
//        $lineContainer.append($line2);
//        var $line3 = $('<div>').addClass('line horizontal');
//        $line3.css('left', start3X).css('top', start3Y).width(widthLine);
//        $lineContainer.append($line3);

        //linea 1
        ctx.moveTo(startX, startY);
        ctx.lineTo(startX + widthLine, startY);
        //line 2
        ctx.moveTo(start2X, start2Y);
        ctx.lineTo(start2X, start2Y + heightLine);
        //line3
        ctx.moveTo(start3X, start3Y);
        ctx.lineTo(start3X + widthLine, start3Y);

        ctx.stroke();
    }
</script>