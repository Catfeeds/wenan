define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'template'], function ($, undefined, Backend, Table, Form, Template) {

    var Controller = {
        index: function () {

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/chargeinfo/index/ids/'+Config.memberInfo.id,
                    del_url: 'user/chargeinfo/del',
                    table: 'charge_info',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                showToggle: false,
                showColumns: false,
                searchFormVisible: true,
                showExport: false,
                search: false,
                commonSearch:false,
                // queryParams: queryParams,

                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        // {checkbox: true},
                        {field: 'serial_number', title: __('Serial_number')},
                        {field: 'fee_id', title: __('Consumption_type'),
                            formatter:function (value, row, index){
                                return value ? Config.fee_type[value] :  __('None');;
                            }
                        },
                        {field: 'createtime', title: __('Createtime'), formatter: Table.api.formatter.datetime},
                        {field: 'hos_name', title: __('Hos_name')},
                        {field: 'admin_input_name', title: __('Admin_name')},
                        {field: 'should_pay', title: __('Consumption_amount')},
                        {field: 'status', title: __('Status'),
                            formatter:function (value, row, index){
                                return value == 1 ?"已付" : '未付';
                            }
                        },
                        {field: 'name', title: __('Name')},
                        {field: 'hos_fee_name', title: __('Fee_name')},
                        // {field: 'operate', title: __('Operate'), table: table,
                        //     events: Controller.api.events.operate,
                        //     formatter: Controller.api.formatter.operate
                        // }
                        {field: 'operate', title: __('Operate'), table: table,
                            events: {
                                'click .btn-del-fee': function (e, value, row, index) {
                                    e.preventDefault();
                                    var that = this;
                                    var top = $(that).offset().top - $(window).scrollTop();
                                    var left = $(that).offset().left - $(window).scrollLeft() - 260;
                                    if (top + 154 > $(window).height()) {
                                        top = top - 154;
                                    }
                                    if ($(window).width() < 480) {
                                        top = left = undefined;
                                    }
                                    var index = Layer.confirm(
                                        __('Are you sure you want to delete this item?'),
                                        {icon: 3, title: __('Warning'), offset: [top, left], shadeClose: true},
                                        function () {
                                            var table = $(that).closest('table');
                                            var options = table.bootstrapTable('getOptions');
                                            Table.api.multi("del", row[options.pk], table, that);
                                            Layer.close(index);
                                        }
                                    );
                                },
                                'click .btn-pay': function (e, value, row, index) {
                                    e.preventDefault();
                                    if (Config.memberInfo.status != 1){
                                        Layer.alert('该会员已被禁用');
                                        return false;
                                    }
                                    var chargeInfo = row;
                                    if (chargeInfo.should_pay > 0) {
                                        Layer.open({
                                            content: Template("pay",chargeInfo),
                                            area: ['500px', '410px'],
                                            title: "支付",
                                            resize: false,
                                            yes: function () {
                                                var tipmsg = '';
                                                var already_paid = $(".already_paid").val();
                                                var pay_way = $("#mytab.nav-pills > li.active > a").attr('pay_way');
                                                var paywayobj ={
                                                    1:'cash',//现金
                                                    2:'zhifubao',//支付宝
                                                    3:'weixin',   //微信
                                                    4:'iscard'//卡内
                                                };
                                                tipmsg = '您需要'+__(paywayobj[pay_way])+'支付'+ chargeInfo.should_pay +'元';
                                                //组合卡内余额
                                                if($('#iscard').prop("checked")){
                                                    //卡内余额不足，弹出提示信息
                                                    if(chargeInfo.balance < chargeInfo.should_pay){
                                                        var extpay = chargeInfo.should_pay-chargeInfo.balance;
                                                        tipmsg = '卡内余额不足,需'+__(paywayobj[pay_way])+'另付款'+ extpay +'元';
                                                        if (pay_way != 1){
                                                            tipmsg +='<div style="text-align: center;"><img class=""  src="/assets/img/zhifu/'+paywayobj[pay_way]+'.png" /></div>'
                                                        }
                                                        pay_way = $('#iscard').val()+','+pay_way;
                                                    }else{
                                                        pay_way = $('#iscard').val();
                                                        tipmsg = '卡内扣款'+chargeInfo.should_pay+'元';
                                                    }

                                                }
                                                //防止确定键重复点击
                                                var forbidendbc = false;
                                                Layer.confirm(tipmsg, {
                                                    btn: ['确定','取消'] //按钮
                                                }, function(){
                                                    if (forbidendbc){
                                                        return;
                                                    }
                                                    forbidendbc = true;
                                                    $.ajax({
                                                        type: 'post',
                                                        dataType: 'json',
                                                        url: 'user/chargeinfo/pay',
                                                        data: {id:chargeInfo.id,pay_way:pay_way,already_paid:already_paid},
                                                        success:function (ret) {
                                                            Layer.closeAll();
                                                            $('#should_pay').val(0);
                                                            Layer.alert(ret.msg);
                                                            forbidendbc = false;
                                                        },
                                                        error:function (e) {
                                                            Backend.api.toastr.error(e.message);
                                                            forbidendbc = false;
                                                        }
                                                    });
                                                }, function(){
                                                    return;
                                                });
                                            }
                                        });
                                    }else{
                                        Layer.alert("没有未付款项");
                                    }
                                },
                            },
                            buttons: [
                                {
                                    name: 'del',
                                    text: '删除',
                                    icon: 'fa fa-trash',
                                    classname: 'btn btn-xs btn-danger btn-del-fee',
                                    url: 'user/chargeinfo/del'
                                },
                                {
                                    name: 'pay',
                                    text: '支付',
                                    icon: 'fa fa-rmb',
                                    classname: 'btn btn-info btn-xs btn-pay',
                                    url: 'user/chargeinfo/pay'
                                }
                            ],
                            formatter: function (value, row, index) {
                                var table = this.table;
                                // 操作配置
                                var options = table ? table.bootstrapTable('getOptions') : {};
                                // 默认按钮组
                                var buttons = this.buttons;
                                var html = [];
                                var url, classname, icon, text, title, extend;

                                $.each(buttons, function (i, j) {
                                    var attr = table.data("buttons-" + j.name);
                                    if (typeof attr === 'undefined' || attr) {
                                        url = j.url ? j.url : '';
                                        if (url.indexOf("{ids}") === -1) {
                                            url = url ? url + (url.match(/(\?|&)+/) ? "&ids=" : "/ids/") + row[options.pk] : '';
                                        }
                                        url = Table.api.replaceurl(url, value, row, table);
                                        url = url ? Fast.api.fixurl(url) : 'javascript:;';
                                        classname = j.classname ? j.classname : 'btn-primary btn-' + name + 'one';
                                        icon = j.icon ? j.icon : '';
                                        text = j.text ? j.text : '';
                                        title = j.title ? j.title : text;
                                        extend = j.extend ? j.extend : '';
                                        if(row.status == 1){//已付收费，收费按钮置灰
                                            classname+=' disabled hide';
                                        }else if(j.name == 'del'&& row.fee_id == 1){//费用类型为挂号费，删除按钮置灰,隐藏
                                            classname+=' disabled hidden';
                                        }
                                        html.push('<a href="' + url + '" class="' + classname + '" ' + extend + ' title="' + title + '"><i class="' + icon + '"></i>' + (text ? ' ' + text : '') + '</a>');
                                    }
                                });
                                return html.join(' ');
                            }
                            // events: Table.api.events.operate,
                            // formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

           // 添加返回会员详情按钮
            $('#toolbar').append(
                '<a href="/admin/user/member/detail/ids/'+Config.memberInfo.id+'" class="btn btn-primary btn-returnMemberDetail"><i class=""></i>返回会员详情 </a>'
            );

            //新增费用按钮
            // $('.btn-add-fee').on('click',function (event) {
            //     event.preventDefault();
            //     window.location.href = '/admin/user/chargeinfo/patientInMemberId/'+Config.patientInMemberId;
            // });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            // if (Config.patientId != -1) {
            //     Form.events.validator = function (form, success, error, submit) {
            //         //绑定表单事件
            //         form.validator($.extend({
            //             validClass: 'has-success',
            //             invalidClass: 'has-error',
            //             bindClassTo: '.form-group',
            //             formClass: 'n-default n-bootstrap',
            //             msgClass: 'n-right',
            //             stopOnError: true,
            //             display: function (elem) {
            //                 return $(elem).closest('.form-group').find(".control-label").text().replace(/\:/, '');
            //             },
            //             target: function (input) {
            //                 var $formitem = $(input).closest('.form-group'),
            //                     $msgbox = $formitem.find('span.msg-box');
            //                 if (!$msgbox.length) {
            //                     return [];
            //                 }
            //                 return $msgbox;
            //             },
            //             valid: function (ret) {
            //                 //验证通过提交表单
            //                 Form.api.submit($(ret), function (data, ret) {
            //                     if (typeof success === 'function') {
            //                         if (!success.call($(this), data, ret)) {
            //                             return false;
            //                         }
            //                     }
            //                     //提示及关闭当前窗口
            //                     var msg = ret.hasOwnProperty("msg") && ret.msg !== "" ? ret.msg : __('Operation completed');
            //                     parent.Toastr.success(msg);
            //                     //parent.$(".btn-refresh").trigger("click");
            //                     var index = parent.Layer.getFrameIndex(window.name);
            //                     //console.log(window.name);
            //                     window.location.href = ret.url;
            //                     //console.log(parent.$(".layui-layer-footer").size());
            //                     var layerDiv = parent.$("#layui-layer" + index);
            //                     layerDiv.find(".layui-layer-footer").remove();
            //                     var titHeight = layerDiv.find('.layui-layer-title').outerHeight() || 0;
            //                     //重设iframe高度
            //                     parent.$("#" + window.name).height(layerDiv.height() - titHeight);
            //                     //parent.Layer.close(index);
            //                 }, error, submit);
            //                 return false;
            //             }
            //         }, form.data("validator-options") || {}));
            //
            //         //移除提交按钮的disabled类
            //         $(".layer-footer .btn.disabled", form).removeClass("disabled");
            //     };
            // }
            // Form.api.bindevent($("form[role=form]"));

            $(function(){
                $('#backlist').click(function(){
                    window.location.href = '/admin/user/member/detail/ids/' + Config.patientInMemberInfo.member_id;
                    var index = parent.Layer.getFrameIndex(window.name);
                    //console.log(index);
                    var layerDiv = parent.$("#layui-layer" + index);
                    layerDiv.find(".layui-layer-footer").remove();
                    var titHeight = layerDiv.find('.layui-layer-title').outerHeight() || 0;
                    //重设iframe高度
                    parent.$("#" + window.name).height(layerDiv.height() - titHeight);
                })
                $('#fee_id').change(function(){
                    var fee_id = $(this).val();
                    $('.hos_fee_id').html('<select class="form-control selectpicker" id="hos_fee_id" data-rule="required" name="row[hos_fee_id]"><option value="">加载中...</option></select>');
                    $("#hos_fee_id").selectpicker("refresh");
                    $('#should_pay').val('');
                    $('#hos_fee_name').val('');
                    $.ajax({
                        url: "system/fee/getFeeList",
                        type: 'post',
                        dataType: 'json',
                        data: {fee_id: fee_id},
                        success: function (ret) {
                            if (ret.hasOwnProperty("code")) {
                                if (ret.code === 1) {
                                    $('.hos_fee_id').html(ret.data.hos_fee_html);
                                    $("#hos_fee_id").selectpicker("refresh");
                                    $('#should_pay').val(ret.data.price);
                                    $('#hos_fee_name').val(ret.data.hosFeeNmae);
                                } else {
                                    Backend.api.toastr.error(ret.msg);
                                }
                            }
                        }, error: function (e) {
                            Backend.api.toastr.error(e.message);
                        }
                    });
                })
                $("#add-form").on('change', '#hos_fee_id', function(){
                    var hos_fee_id = $(this).val();
                    $('#should_pay').val('');
                    $('#hos_fee_name').val('');
                    $.ajax({
                        url: "system/fee/getFeeInfo",
                        type: 'post',
                        dataType: 'json',
                        data: {id: hos_fee_id},
                        success: function (ret) {
                            if (ret.hasOwnProperty("code")) {
                                if (ret.code === 1) {
                                    $('#should_pay').val(ret.data.price);
                                    $('#hos_fee_name').val(ret.data.hosFeeNmae);
                                } else {
                                    Backend.api.toastr.error(ret.msg);
                                }
                            }
                        }, error: function (e) {
                            Backend.api.toastr.error(e.message);
                        }
                    });
                })
            });

            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"),function (ret,data) {
                    parent.Toastr.success(data.msg);
                    // window.location.href = data.url;//跳转详情页

                    //跳转到支付页面
                    //付费弹框
                    var chargeInfo = data.data;
                    if (chargeInfo.should_pay > 0) {
                        Layer.open({
                            content: Template("pay",chargeInfo),
                            area: ['500px', '410px'],
                            title: "支付",
                            resize: false,
                            yes: function () {
                                var tipmsg = '';
                                var already_paid = $(".already_paid").val();
                                var pay_way = $("#mytab.nav-pills > li.active > a").attr('pay_way');
                                var paywayobj ={
                                    1:'cash',//现金
                                    2:'zhifubao',//支付宝
                                    3:'weixin',   //微信
                                    4:'iscard'//卡内
                                };
                                tipmsg = '您需要'+__(paywayobj[pay_way])+'支付'+ chargeInfo.should_pay +'元';
                                //组合卡内余额
                                if($('#iscard').prop("checked")){
                                    //卡内余额不足，弹出提示信息
                                    if(chargeInfo.balance < chargeInfo.should_pay){
                                        var extpay = chargeInfo.should_pay-chargeInfo.balance;
                                        tipmsg = '卡内余额不足,需'+__(paywayobj[pay_way])+'另付款'+ extpay +'元';
                                        if (pay_way != 1){
                                            tipmsg += '<div style="text-align: center;"><img class=""  src="/assets/img/zhifu/'+paywayobj[pay_way]+'.png" /></div>';
                                        }
                                        pay_way = $('#iscard').val()+','+pay_way;
                                    }else{
                                        pay_way = $('#iscard').val();
                                        tipmsg = '卡内扣款'+chargeInfo.should_pay+'元';
                                    }
                                }
                                //防止确定键重复点击
                                var forbidendbc = false;
                                Layer.confirm(tipmsg, {
                                    btn: ['确定','取消'] //按钮
                                }, function(){
                                    if (forbidendbc){
                                        return;
                                    }
                                    forbidendbc = true;
                                    $.ajax({
                                        type: 'post',
                                        dataType: 'json',
                                        url: '/admin/user/chargeinfo/pay',
                                        data: {id:chargeInfo.id,pay_way:pay_way,already_paid:already_paid},
                                        success:function (ret) {
                                            Layer.closeAll();
                                            // Layer.alert(ret.msg);
                                            forbidendbc = false;
                                            // location.reload();//刷新
                                            if(Config.patientId != -1) {//患者详情或会员详情
                                                window.location.href = data.url;//跳转
                                            }else{//医生站预约
                                                parent.window.location.href = data.url;//跳转
                                            }

                                        },
                                        error:function (e) {
                                            Backend.api.toastr.error(e.message);
                                            forbidendbc = false;
                                        }
                                    });
                                }, function(){
                                    return;
                                });
                            }
                        });
                    }else{
                        Layer.alert("没有未付款项");
                    }
                });

            },
        }
    };
    return Controller;
});