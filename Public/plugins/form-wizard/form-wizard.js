var FormWizard = function () {


    return {
        //main function to initiate the module
        init: function () {
            if (!jQuery().bootstrapWizard) {
                return;
            }


            var form = $('#submit_form');
            var error = $('.alert-danger', form);
            var success = $('.alert-success', form);

            form.validate({
                doNotHideMessage: true, //this option enables to show the error/success messages on tab switch.
                errorElement: 'span', //default input error message container
                errorClass: 'help-block help-block-error', // default input error message class
                focusInvalid: false, // do not focus the last invalid input
                rules: {
                    //account
                    stuNum: {
                        required: true,
                        minlength: 10,
                        maxlength: 10
                    },

                    username: {
                        required: true
                    },
                    gender: {
                        required: true
                    },
                    role:{
                        required: true
                    },
                    roleList: {
                        required: true
                    }

                },

                messages: {

                    stuNum: {
                        required: "学号不能为空",
                        minlength: "学号长度为10",
                        maxlength: "学号长度为10"
                    },
                    username: {
                        required: "名字不能为空!"

                    },

                    role: {
                        required:"角色不为空"
                    },
                    gender: {
                        required:"性别不为空"
                    },
                    roleList: {
                        required: "职务不能为空"
                    }
                },

                errorPlacement: function (error, element) { // render error placement for each input type
                    if (element.attr("name") == "gender") { // for uniform radio buttons, insert the after the given container
                        error.insertAfter("#form_gender_error");
                    } else if (element.attr("name") == "payment[]") { // for uniform checkboxes, insert the after the given container
                        error.insertAfter("#form_payment_error");
                    } else {
                        error.insertAfter(element); // for other inputs, just perform default behavior
                    }
                },

                invalidHandler: function (event, validator) { //display error alert on form submit
                    success.hide();
                    error.show();
                    App.scrollTo(error, -200);
                },

                highlight: function (element) { // hightlight error inputs
                    $(element)
                        .closest('.form-group').removeClass('has-success').addClass('has-error'); // set error class to the control group
                },

                unhighlight: function (element) { // revert the change done by hightlight
                    $(element)
                        .closest('.form-group').removeClass('has-error'); // set error class to the control group
                },

                success: function (label) {
                    if (label.attr("for") == "gender" || label.attr("for") == "payment[]") { // for checkboxes and radio buttons, no need to show OK icon
                        label
                            .closest('.form-group').removeClass('has-error').addClass('has-success');
                        label.remove(); // remove error label here
                    } else { // display success icon for other inputs
                        label
                            .addClass('valid') // mark the current input as valid and display OK icon
                        .closest('.form-group').removeClass('has-error').addClass('has-success'); // set success class to the control group
                    }
                },

                submitHandler: function (form) {
                    success.show();
                    error.hide();
                    //add here some ajax code to submit your form or just call form.submit() if you want to submit the form without ajax
                }

            });

            var displayConfirm = function() {
                $('#tab3 .form-control-static', form).each(function(){
                    var input = $('[name="'+$(this).attr("data-display")+'"]', form);
                    if (input.is(":radio")) {
                        input = $('[name="'+$(this).attr("data-display")+'"]:checked', form);
                    }
                    if (input.is(":text") || input.is("textarea")) {
                        $(this).html(input.val());
                    } else if (input.is("select")) {
                        $(this).html(input.find('option:selected').text());
                    } else if (input.is(":radio") && input.is(":checked")) {
                        $(this).html(input.attr("data-title"));
                    } else if ($(this).attr("data-display") == 'payment[]') {
                        var payment = [];
                        $('[name="payment[]"]:checked', form).each(function(){
                            payment.push($(this).attr('data-title'));
                        });
                        $(this).html(payment.join("<br>"));
                    }
                });
            }

            var handleTitle = function(tab, navigation, index) {
                var total = navigation.find('li').length;
                var current = index + 1;
                // set wizard title
                $('.step-title', $('#form_wizard_1')).text('Step ' + (index + 1) + ' of ' + total);
                // set done steps
                jQuery('li', $('#form_wizard_1')).removeClass("done");
                var li_list = navigation.find('li');
                for (var i = 0; i < index; i++) {
                    jQuery(li_list[i]).addClass("done");
                }

                if (current == 1) {
                    $('#form_wizard_1').find('.button-previous').hide();
                } else {
                    $('#form_wizard_1').find('.button-previous').show();
                }

                if (current >= total) {
                    $('#form_wizard_1').find('.button-next').hide();
                    $('#form_wizard_1').find('.button-submit').show();
                    displayConfirm();
                } else {
                    $('#form_wizard_1').find('.button-next').show();
                    $('#form_wizard_1').find('.button-submit').hide();
                }
                App.scrollTo($('.page-title'));
            }

            // default form wizard
            $('#form_wizard_1').bootstrapWizard({
                'nextSelector': '.button-next',
                'previousSelector': '.button-previous',
                onTabClick: function (tab, navigation, index, clickedIndex) {
                    return false;

                    success.hide();
                    error.hide();
                    if (form.valid() == false) {
                        return false;
                    }

                    handleTitle(tab, navigation, clickedIndex);
                },
                onNext: function (tab, navigation, index) {
                    success.hide();
                    error.hide();

                    if (form.valid() == false) {
                        return false;
                    }

                    if (index === 1) {
                        var stunum = $('#stuNum').val();
                        var url = getUrl();
                        $.ajax({
                            url : url+'/Admin/Data/role',
                            type: 'POST',
                            data: connection.encode({value:stunum}),
                            dataType: 'text',
                            success: function(data){
                                data = connection.decode(data);
                                if (data.status === 200) {
                                    $("#roleList").select2({
                                      allowClear: true,
                                      width: 'auto',
                                      placeholder: '选择角色',
                                      data: data.data,
                                    });
                                } else {
                                    return false;
                                }
                            },
                        });
                    } else if(index === 2) {
                        var before_role = $('#role').val();
                        var after_role = $('#roleList').find('option:selected').text();
                        if (before_role === after_role) {
                            return false;
                        }
                    }
                    handleTitle(tab, navigation, index);
                },
                onPrevious: function (tab, navigation, index) {
                    success.hide();
                    error.hide();

                    handleTitle(tab, navigation, index);
                },
                onTabShow: function (tab, navigation, index) {
                    var total = navigation.find('li').length;
                    var current = index + 1;
                    var $percent = (current / total) * 100;
                    $('#form_wizard_1').find('.progress-bar').css({
                        width: $percent + '%'
                    });
                }
            });

            $('#form_wizard_1').find('.button-previous').hide();
            $('#form_wizard_1 .button-submit').click(function () {
                var url = getUrl();
                var stunum = $("#stuNum").val();
                var before_role_id = $('#role', form).attr('data_role_id');
                var after_role_id = $('#roleList').val();
                var token = $('[name="__hash__"]', form).val();
                var after_role_name = $("#roleList").find("option:selected").text();
                var data = {
                    stuNum: stunum,
                    before_role_id: before_role_id,
                    after_role_id: after_role_id,
                    after_role_name: after_role_name,
                    __hash__: token,
                };
                $.ajax({
                    url: url+'/Admin/Admin/changeRole',
                    type: 'POST',
                    data: connection.encode(data),
                    dataType: 'text',
                    success: function(response) {
                        //成功重载页面
                        response = connection.decode(response);
                        if (response.status === 200) {
                            var current = $(".active.in[role='tabpanel']");
                            $.ajax({
                              url : getUrl()+'/Admin/Data/index?part='+current.attr('id'),
                              success: function (output) {
                                current.html(output);
                              },
                              error: function(XMLHttpRequest, textStatus) {
                               if(XMLHttpRequest.status === 403) {
                                location.reload();
                               }
                              },
                            })

                        } else {

                        }
                    }

                })
            }).hide();
            $('#stuNum').change(function(){
                $.ajax({
                    url : getUrl()+"/Admin/Data/user",
                    type: 'POST',
                    data: connection.encode({
                        field:'stunum',
                        value:this.value,
                    }),
                    dataType: 'text',
                    success: function(data){
                        data = connection.decode(data);
                        if (data.status === 200) {
                           var value = data.data;
                            $('#username', form).val(value.username);
                            $('#gender', form).val(value.gender);
                            $('#role', form).attr({
                                value: value.role,
                                data_role_id: value.role_id}
                            );
                        } else {
                              $('#username', form).val('');
                              $('#gender', form).val('');
                              $('#role', form).val('');
                        }
                    }

                });
            });

        }

    };

}();

