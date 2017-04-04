function initTable(tableId, url, opreatUrl, prop) {    var table = $('#'+tableId);    var state = null;    var args = {};        //各列情况        var columns = [{            "data": "",            "render"        : function(data, type, rows) {                var template = "<label class='mt-checkbox mt-checkbox-single mt-checkbox-outline'><input type='checkbox' class='checkboxes' name='"+ tableId +"'";                $.each(prop, function(i, val){                  if (val in rows) {                    template += " data-"+val+"='"+rows[val]+"' ";                  }                 });            template += "/><span></span></label>"            return template;            }        }];        table.find("thead > tr >th[data-data]").each(function(key, value){        var column = {};        $.each($(value).data(), function(method, val){            column[method] = val;        });        if($(value).data("data") === 'state') {            column['render'] = function(data, type, rows) {                var text, type;                data = parseInt(data);                switch(data) {                case 1:                    text = "正 常";                    type = "success";                    break;                case 2:                    text = "锁 定";                    type = "warning";                    break;                case 0:                    text = "禁 用";                    type = "danger";                    break;                }                return '<span class="label label-sm label-'+ type +'" data-value="'+ data +'" > '+ text +' </span>';            }        }                columns.push(column);        });        table.dataTable({                "dom": "<'row'<'col-md-6 col-sm-12'l><'col-md-6 col-sm-12'f>r>t<'row'<'col-md-5 col-sm-12'i><'col-md-7 col-sm-12'p>>",                "autoWidth"             : false,                "deferRender"   : true,                "processing"    : false,                "jQueryUI"              : true,                "serverSide"    : true,                "lengthMenu"    : [                        [10, 20, 50, -1],                        [10, 20, 50, "所有"]                ],                "language"              : {                        "sProcessing": "处理中...",                        "sLengthMenu": "显示 _MENU_ 项结果",                        "sZeroRecords": "没有匹配结果",                        "sInfo": "显示第 _START_ 至 _END_ 项结果，共 _TOTAL_ 项",                        "sInfoEmpty": "显示第 0 至 0 项结果，共 0 项",                        "sInfoFiltered": "(由 _MAX_ 项结果过滤)",                        "sInfoPostFix": "", "sSearch": "搜索:",                        "sUrl": "", "sEmptyTable": "表中数据为空",                        "sLoadingRecords": "载入中...",                        "sInfoThousands": ",",                        "oPaginate": {                                "sFirst": "首页",                                "sPrevious": "上页",                                "sNext": "下页",                                "sLast": "末页"                        },                        "oAria": {                                "sSortAscending": ": 以升序排列此列",                                "sSortDescending": ": 以降序排列此列"                        }                },                "bStateSave": true,        "columnDefs": [{ // define columns sorting options(by default all columns are sortable extept the first checkbox column)            "targets": 0,            "orderable": false,            "searchable": false        }],        "AjaxSource": url,        ajax : function(Data, Callback, settings) {            $.each(args, function (key, value) {                if(value === '' || value === null) {                    delete args[key];                }            });            Data['args'] = args;            $.ajax({                "dataType": "text",                "type" : "post",                "url"  : url,                "data" : connection.encode(Data),                "success" : function(data) {                    data = connection.decode(data);                    Callback(data);                }            });        },                "columns" : columns,    "searchDelay": 1000,                "pageLength"    : 10,                "pagingType"    : "bootstrap_full_number",                "columnDefs": [{  // set default column settings                'orderable': false,                'targets': [0]            }, {                "searchable": false,                "targets": [0]            }],        // set first column as a default sort by asc                "order": [            [1, "asc"]        ],        });        var tableWrapper = $('#'+tableId+'_wrapper');        //多选        table.find('.group-checkable').change(function () {        var set = jQuery(this).attr("data-set");        var checked = jQuery(this).is(":checked");        jQuery(set).each(function () {            if (checked) {                $(this).prop("checked", true);                $(this).parents('tr').addClass("active");            } else {                $(this).prop("checked", false);                $(this).parents('tr').removeClass("active");            }        });    });    table.on('change', 'tbody tr .checkboxes', function () {        $(this).parents('tr').toggleClass("active");    });    var actions = $("[target-table='"+tableId+"']");    actions.find("[operate]").on('click', function() {        var action = $(this).attr('operate');        if (action === 'reload') {            table.fnDraw();            return false;        }        var set = table.find('.group-checkable').attr("data-set");        var checked = [];        $(set+":checked").each(function(key, value){          checked.push($(this).data());        });        if (checked.length !== 0) {            $.ajax({               url : opreatUrl,               data: connection.encode({                    operate: action,                    data: checked,                }),               type: 'post',               dataType: 'text',               success: function(data) {                    data = connection.decode(data);                    if (data.status === 200) {                        table.fnDraw(false);                    } else {                        alert("你的操作发生错误,错误如下\n" + data.status + ':' + data.info);                    }               }          });        } else {            alert("请先选择目标");        }    });    var  deTransfer =  function (column, value) {        switch (column) {        case 'state':            switch(value) {                case 'all':                    value = null;                    break;                case 'black':                    value = 0;                    break;                case 'normal':                    value = 1;                    break;                case 'lock':                    value = 2;                    break;            }            break;        case 'type':            if (value.indexOf(",") !== -1)                value = value.split(",");            break;        }    return value;    };  $('[data-state]').on('click', function(){    var method = $(this).data('state');    state = deTransfer('state', method);    args['state'] = state;    table.fnDraw(false);  });  $('[data-column]').on('change', function () {      var column = $(this).data('column');      args[column] = deTransfer(column, $(this).val());      table.fnDraw(false);  });}