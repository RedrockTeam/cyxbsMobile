var FormEditable = function() {

    var initEditables = function(table) {

        //set editable mode based on URL parameter
        if (App.getURLParameter('mode') == 'inline') {
            $.fn.editable.defaults.mode = 'inline';
            $('#inline').attr("checked", true);
        } else {
            $('#inline').attr("checked", false);
        }

        //global settings 
        $.fn.editable.defaults.inputclass = 'form-control';
       // $.fn.editable.defaults.url = '/post';

        //editables element samples 
        $('#username', table).editable({ 
            //url: '/post',
            type: 'text',
            pk: 1,
            name: 'username',
            title: 'Enter username'
        });

        $('#gender', table).editable({
            prepend: "not selected",
            inputclass: 'form-control',
            source: [{
                value: '男',
                text: 'Male'
            }, {
                value: '女',
                text: 'Female'
            }],
            display: function(value, sourceData) {
                var colors = {
                        "": "gray",
                        '男': "green",
                        '女': "blue"
                    },
                    elem = $.grep(sourceData, function(o) {
                        return o.value == value;
                    });

                if (elem.length) {
                    $(this).text(elem[0].text).css("color", colors[value]);
                } else {
                    $(this).empty();
                }
            }
        });

        $('#status', table).editable({
            source: {
                0: '停职',
                1: '正常',
                2: '锁定',
            },
            display: function(value, sourceData) {
               
                var type, text, data = parseInt(value);
                switch(data) {
                case 1:
                    text = "正 常";
                    type = "success";
                    break;
                
                case 2:
                    text = "锁 定";
                    type = "warning";
                    break;

                case 0:
                    text = "停 职";
                    type = "danger";
                    break;
                }

                $(this).html('<span class="label label-sm label-'+ type +'" data-value="'+ data +'" > '+ text +' </span>');
            },
        });

        $('#role', table).editable({
            showbuttons: false
        });

        $('#vacation', table).editable({
            rtl: App.isRTL()
        });

        $('#dob', table).editable({
            inputclass: 'form-control'
        });

        $('#event', table).editable({
            placement: (App.isRTL() ? 'left' : 'right'),
            combodate: {
                firstItem: 'name'
            }
        });

        $('#last_login_time', table).editable({
            format: 'yyyy-mm-dd hh:ii',
            viewformat: 'yyyy-mm-dd hh:ii',
            validate: function(v) {
                if (v && v.getDate() == 10) return 'Day cant be 10!';
            },
            datetimepicker: {
                rtl: App.isRTL(),
                todayBtn: 'linked',
                weekStart: 1
            }
        });
        $('#last_login_ip', table).editable({
            prepend: '未登录',
        });

        $('#comments', table).editable({
            showbuttons: 'bottom'
        });

        $('#note', table).editable({
            showbuttons: (App.isRTL() ? 'left' : 'right')
        });

        $('#pencil', table).click(function(e) {
            e.stopPropagation();
            e.preventDefault();
            $('#note').editable('toggle');
        });

        $('#tags', table).editable({
            inputclass: 'form-control input-medium',
            select2: {
                tags: ['html', 'javascript', 'css', 'ajax'],
                tokenSeparators: [",", " "]
            }
        });


        $('#address', table).editable({
            url: '/post',
            value: {
                city: "San Francisco",
                street: "Valencia",
                building: "#24"
            },
            validate: function(value) {
                if (value.city == '') return 'city is required!';
            },
            display: function(value) {
                if (!value) {
                    $(this).empty();
                    return;
                }
                var html = '<b>' + $('<div>').text(value.city).html() + '</b>, ' + $('<div>').text(value.street).html() + ' st., bld. ' + $('<div>').text(value.building).html();
                $(this).html(html);
            }
        });
    }

    return {
        //main function to initiate the module
        init: function(target) {

            var table = $(target);
            // init editable elements
            initEditables(table);
            
            var editable = $(".editable", table);

            editable.editable('disable');

            // init editable toggler
            $('#enable').click(function() {
                editable.editable('toggleDisabled');
            });
    
            // handle editable elements on hidden event fired
            editable.on('hidden', function(e, reason) {
                if (reason === 'save' || reason === 'nochange') {
                    var $next = $(this).closest('tr').next().find('.editable');
                    if ($('#autoopen').is(':checked')) {
                        setTimeout(function() {
                            $next.editable('show');
                        }, 300);
                    } else {
                        $next.focus();
                    }
                }
            });


        }

    };

}();
