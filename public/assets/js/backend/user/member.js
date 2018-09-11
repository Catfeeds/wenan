define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'template', 'bootstrap-datetimepicker'], function ($, undefined, Backend, Table, Form, Template) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/member/index',
                    add_url: 'user/member/add',
                    edit_url: 'user/member/edit',
                    del_url: 'user/member/del',
                    multi_url: 'user/member/multi',
                    detail_url: 'user/member/detail',
                    patientlist_url: 'user/patientinmember/index',
                    table: 'member',
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
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'updatetime',
                columns: [
                    [
                        {field: 'name', title: __('Name')},
                        {field: 'gender', title: __('Gender'), searchable:false,
                            formatter:function (value, row, index){
                                var gender = {1:'男', 2:'女'}
                                return gender[value];
                            }
                        },
                        {field: 'telphone', title: __('Telphone'),
                            formatter:function (value, row, index){
                                return Config.privacy?value:value.substr(0, 3) + '****' + value.substr(7);
                            }
                        },
                        // {field: 'card_type', title: __('Card_type'), searchable:false,
                        //     formatter:function (value, row, index){
                        //         return value ? Config.card_type[value] : __('None');
                        //     }
                        // },
                        // {field: 'doctor_id', title: __('Doctor_name'), searchList: Config.doctors,
                        //     formatter:function (value, row, index){
                        //         return value ? Config.doctors[value] : __('None');
                        //     }
                        // },

                        {field: 'balance', title: __('Balance'), searchable:false},
                        // {field: 'integral', title: __('Integral'), searchable:false},
                        {field: 'patient_num', title: __('Patient_num'), searchable:false},
                        {field: 'last_consumption_time', title: __('Last_consumption_time'),
                            formatter: function (value, row, index) {
                                return value ? Moment(parseInt(value) * 1000).format("YYYY-MM-DD HH:mm") : __('None');
                            },
                            operate: 'BETWEEN',
                            type: 'datetime',
                            addclass: 'datetimepicker',
                            data: 'data-date-format="YYYY-MM-DD"'},
                        {field: 'operate', title: __('Operate'), table: table,
                            // events: Table.api.events.operate,
                            // formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '会员详情',
                                    icon: 'fa fa-list',
                                    classname: 'btn btn-info btn-xs btn-detail btn-dialog',
                                    url: $.fn.bootstrapTable.defaults.extend.detail_url,
                                },
                                // {
                                //     name: 'patientlist',
                                //     text: '病人列表',
                                //     icon: 'fa fa-list',
                                //     classname: 'btn btn-info btn-xs btn-detail btn-dialog',
                                //     url: $.fn.bootstrapTable.defaults.extend.patientlist_url,
                                // },
                            ],
                            formatter: Table.api.formatter.buttons
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            //充值按钮事件
            Controller.api.recharge();
            //出生日期限制
            Controller.api.birthTimeValidator();
            Controller.api.bindevent();
        },
        edit: function () {
            $(document).on('dblclick','#edit-form',function (event) {
                //判断会员是否被禁用
                if ($('.btn-pay').hasClass('disabled')){
                    Layer.alert("该会员已被禁用，不可编辑");
                    return;
                }
                $('.able').removeAttr('disabled');
                $(".able").selectpicker("refresh");
            });
            //充值按钮事件
            Controller.api.recharge();
            //出生日期限制
            Controller.api.birthTimeValidator();
            Controller.api.bindevent();
        },
        detail: function () {
            //禁用按钮点击事件
            $(document).on('click', '.btn-forbidden', function (event) {
                var timer = '';
                Fast.api.ajax({
                    url: "user/member/forbidden/ids/"+Config.memberId,
                }, function (data, ret) {
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        window.parent.Layer.closeAll();
                    },1000)
                }, function (data, ret) {
                    // Layer.alert(ret.msg);
                });
            });
            //预约关联跳转
            $(".btn-appointment").on('click', function () {
                var id = $(this).attr('addtabs');//导航id
                var patient_in_member_id = $(this).attr('patient_in_member_id');//病人id
                var addtabsEle = top.window.$("ul.sidebar-menu li a[addtabs="+id+"]");//导航条元素
                if (addtabsEle.size() > 0) {
                    addtabsEle.trigger("click");
                    var addtabsIframeEle =  top.window.$("iframe[src='/admin/user/appointment?addtabs=1']");//对应的选项卡iframe
                    //把病人id赋值给新增预约页面
                    addtabsIframeEle.attr('patient_in_member_id',patient_in_member_id);
                    addtabsIframeEle.contents().find('.btn-add-appointment').trigger("click");//跨iframe参数传不过去？
                }
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            recharge:function () {
                //充值按钮
                $(document).on('click', '.btn-pay', function () {
                    var chargeInfo = JSON.parse($(this).attr("chargeInfo"));
                    if (chargeInfo) {
                        Layer.open({
                            content: Template("pay",chargeInfo),
                            area: ['500px', '410px'],
                            title: "支付",
                            resize: false,
                            yes: function () {
                                var pay_way = $("#mytab.nav-pills > li.active > a").attr('pay_way');
                                var already_paid = $(".already_paid").val();
                                //新增会员时
                                if(typeof (chargeInfo.action) !== "undefined" && chargeInfo.action == "addmember"){
                                    $("#c-balance-show").text(already_paid);
                                    $("#c-balance" ).val(already_paid);
                                    $("#c-pay_way" ).val(pay_way);
                                    Layer.closeAll();
                                }else{
                                    //编辑会员时
                                    Fast.api.ajax({
                                        url: 'user/member/pay',
                                        data: {id:chargeInfo.id,pay_way:pay_way,already_paid:already_paid},
                                    }, function (data, ret) {
                                        Layer.closeAll();
                                        // window.location.reload();
                                        Layer.alert(ret.msg);
                                        var balance = $('#c-balance').val();
                                        $('#c-balance').val(parseInt(balance)+parseInt(already_paid));
                                    }, function (data, ret) {
                                        Layer.alert(ret.msg);
                                    });
                                }
                            }
                        });
                    }
                });
            },
            //出生日期限制
            birthTimeValidator:function () {
                var dateOption = {
                    format: 'YYYY-MM-DD',
                    icons: {
                        time: 'fa fa-clock-o',
                        date: 'fa fa-calendar',
                        up: 'fa fa-chevron-up',
                        down: 'fa fa-chevron-down',
                        previous: 'fa fa-chevron-left',
                        next: 'fa fa-chevron-right',
                        today: 'fa fa-history',
                        clear: 'fa fa-trash',
                        close: 'fa fa-remove'
                    },
                    showTodayButton: true,
                    showClose: true,
                    maxDate:Moment().format("YYYY-MM-DD"),
                    // maxDate:Moment().add(7,'days').format("YYYY-MM-DD"),
                    // disabledDates:['2018-03-30'],
                };
                $('#c-birth_time').datetimepicker(dateOption)

            }
        }
    };
    return Controller;
});