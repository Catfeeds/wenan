define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'upload'], function ($, undefined, Backend, Table, Form, Upload ) {

    var Controller = {
        index: function () {

            // 初始化表格参数配置
            // Table.api.init({
            //     search: true,
            //     advancedSearch: true,
            //     pagination: true,
            //     extend: {
            //         "index_url": "general/profile/index",
            //         "add_url": "",
            //         "edit_url": "",
            //         "del_url": "",
            //         "multi_url": "",
            //     }
            // });

            // var table = $("#table");

            // 初始化表格
            // table.bootstrapTable({
            //     url: $.fn.bootstrapTable.defaults.extend.index_url,
            //     columns: [
            //         [
            //             {field: 'id', title: 'ID'},
            //             {field: 'title', title: __('Title')},
            //             {field: 'url', title: __('Url'), align: 'left', formatter: Table.api.formatter.url},
            //             {field: 'ip', title: __('ip')},
            //             {field: 'createtime', title: __('Createtime'), formatter: Table.api.formatter.datetime},
            //         ]
            //     ],
            //     commonSearch: false
            // });

            // 为表格绑定事件
            // Table.api.bindevent(table);//当内容渲染完成后

            // 给上传按钮添加上传成功事件
            // $("#plupload-avatar").data("upload-success", function (data) {
            //     var url = Backend.api.cdnurl(data.url);
            //     $(".profile-user-img").prop("src", url);
            //     Toastr.success("上传成功！");
            // });
            
            // 给表单绑定事件
            // Form.api.bindevent($("#update-form"), function () {
            //     $("input[name='row[password]']").val('');
            //     var url = Backend.api.cdnurl($("#c-avatar").val());
            //     top.window.$(".user-panel .image img,.user-menu > a > img,.user-header > img").prop("src", url);
            //     return true;
            // });


            //修改手机号按钮
            $(document).on('click', '.modify-telphone', function(){
                // var chargeInfo = JSON.parse($(this).attr("chargeInfo"));
                // if (true) {
                //     Layer.open({
                //         content: Template("general/profile/modifyPhone",{}),
                //         area: ['500px', '410px'],
                //         title: "支付",
                //         resize: false,
                //         yes: function () {
                //             var pay_way = $("#mytab.nav-pills > li.active > a").attr('pay_way');
                //             var already_paid = $(".already_paid").val();
                //             Fast.api.ajax({
                //                 url: 'user/patientvisitrecord/pay',
                //                 data: {id:chargeInfo.id,pay_way:pay_way,already_paid:already_paid},
                //             }, function (data, ret) {
                //
                //                 Layer.closeAll();
                //                 Layer.alert(ret.msg);
                //             }, function (data, ret) {
                //
                //                 Layer.alert(ret.msg);
                //             });
                //         }
                //     });
                // }

                Fast.api.open('general/profile/modifyPhone', '修改手机号', {});
            });

            //修改密码按钮
            $(document).on('click', '.modify-password', function(){
                Fast.api.open('general/profile/modifyPassword', '修改密码', {});
            });
        },
        modifyphone: function () {
            Controller.api.sendcode(1);
        },
        modifypassword: function () {
            Controller.api.sendcode(0);
        },
        api:{
            bindevent: function () {
                //为表单绑定事件
                var form = $("form[role=form]");
                //判断无父窗口
                if (top.location===self.location){
                    // console.log(1);
                    //移除提交按钮的disabled类
                    // $(".layer-footer .btn.disabled", form).removeClass("disabled");
                    Form.api.bindevent(form, function (data) {
                        location.href = Backend.api.fixurl(data.url);
                    });
                }else{
                    console.log(2);
                    Form.api.bindevent(form);
                }
            },
            sendcode:function (checkPhone) {
                console.log(checkPhone)
                $(document).on('click',  ".btn-sm", function(e){
                    e.preventDefault();
                    e.stopPropagation();
                    var  _this = $(this);
                    var phone = $("#phone").val();
                    if (!phone.match(/^1[3-9]\d{9}$/)) {
                        Toastr.error("请输入正确的手机号码！");
                        return false;
                    }
                    _this.attr('disabled', 'disabled');//一分钟内禁止发送
                    $.ajax({
                        url: "/admin/general/profile/sms",
                        type: 'post',
                        dataType: 'json',
                        data: {phone: phone, checkPhone: checkPhone},
                        success: function (ret) {
                            console.log(ret);
                            //倒计时
                            var maxtime = 60, timer;
                            if (ret.hasOwnProperty("code")) {
                                if (ret.code === 1) {
                                    //一分钟倒计时
                                    timer = setInterval(function () {
                                        if (maxtime > 0) {
                                            _this.val("还剩" + maxtime + "秒");
                                            maxtime--;
                                        } else{
                                            clearInterval(timer);
                                            maxtime = 60;
                                            _this.val("发送验证码");
                                            _this.removeAttr('disabled');
                                        }
                                    }, 1000);
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
                });
                Controller.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});