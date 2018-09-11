define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showToggle: false,
                showRefresh: false,
                showColumns: false,
                showToolbar: false,
                commonSearch: false,
                //searchFormVisible: true,
                showExport: false,
                search: false,
                extend: {
                    index_url: 'system/hospital/index',
                    add_url: 'system/hospital/add',
                    edit_url: 'system/hospital/edit',
                    multi_url: 'system/hospital/multi',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: [
                    [
                        //{field: 'state', checkbox: true, },
                        {field: 'hos_name', title: '医馆名称'},
                        {field: 'departs', title: '科室'},
                        {field: 'account', title: '账号数量'},
                        {field: 'admin_phone', title: '管理员账号'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: function (value, row, index) {
                                return Table.api.formatter.operate.call(this, value, row, index);
                            }}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Form.api.bindevent($("form[role=form]"));
            var maxtime = 60, timer;
            function CountDown() {
                if (maxtime > 0) {
                    $('#send').val("还剩" + maxtime + "秒");
                    maxtime--;
                } else{
                    clearInterval(timer);
                    maxtime = 60;
                    $('#send').val("发送验证码");
                    $('#send').removeAttr('disabled');
                }
            }
            $(function(){
                $('#send').click(function(){
                    var phone = $.trim($('#phone').val()), _this = $(this);
                    if (!phone.match(/^1[3-9]\d{9}$/)) {
                        Layer.alert("请输入正确的手机号码！");
                        return false;
                    }
                    _this.attr('disabled', 'disabled');
                    $.ajax({
                        url: "system/hospital/sms",
                        type: 'post',
                        dataType: 'json',
                        data: {phone: phone, action: 'add'},
                        success: function (ret) {
                            if (ret.hasOwnProperty("code")) {
                                if (ret.code === 1) {
                                    CountDown();
                                    timer = setInterval(CountDown, 1000);
                                } else {
                                    _this.removeAttr('disabled');
                                    Backend.api.toastr.error(ret.msg);
                                }
                            }
                        }, error: function (e) {
                            _this.removeAttr('disabled');
                            Backend.api.toastr.error(e.message);
                        }
                    });
                })
            })
        },
        edit: function () {
            Form.api.bindevent($("form[role=form]"));
            var maxtime = 60, timer;
            function CountDown() {
                if (maxtime > 0) {
                    $('#send').val("还剩" + maxtime + "秒");
                    maxtime--;
                } else{
                    clearInterval(timer);
                    maxtime = 60;
                    $('#send').val("发送验证码");
                    $('#send').removeAttr('disabled');
                }
            }
            $(function(){
                $('#editAdmin').click(function(){
                    $('.new-admin').css('display', 'block');
                })

                $('#send').click(function(){
                    var phone = $.trim($('#phone').val()), hos_id = $('#hos_id').val(), _this = $(this);
                    if (!phone.match(/^1[3-9]\d{9}$/)) {
                        Layer.alert("请输入正确的手机号码！");
                        return false;
                    }
                    _this.attr('disabled', 'disabled');
                    $.ajax({
                        url: "system/hospital/sms",
                        type: 'post',
                        dataType: 'json',
                        data: {phone: phone, action: 'edit', hos_id: hos_id},
                        success: function (ret) {
                            if (ret.hasOwnProperty("code")) {
                                if (ret.code === 1) {
                                    CountDown();
                                    timer = setInterval(CountDown, 1000);
                                } else {
                                    _this.removeAttr('disabled');
                                    Backend.api.toastr.error(ret.msg);
                                }
                            }
                        }, error: function (e) {
                            _this.removeAttr('disabled');
                            Backend.api.toastr.error(e.message);
                        }
                    });
                })
            })
        }
    };
    return Controller;
});