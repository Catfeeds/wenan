define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'template', 'bootstrap-datetimepicker'], function ($, undefined, Backend, Table, Form, Template, Datetimepicker) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/register/index',
                    add_url: 'user/register/add',
                    edit_url: 'user/register/edit',
                    del_url: 'user/register/del',
                    multi_url: 'user/register/multi',
                    table: 'register',
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
                sortName: 'id',
                columns: [
                    [
                        // {checkbox: true},
                        // {field: 'id', title: __('Id'), searchable:false},
                        {field: 'name', title: __('Name')},
                        {field: 'gender', title: __('Gender'), searchable:false,
                            formatter:function (value, row, index){
                                var gender = {1:'男', 2:'女'}
                                return gender[value];
                            }
                        },
                        {field: 'treatment_department', title: __('Treatment_department'),  searchList: Config.treatment_department,
                            formatter:function (value, row, index){
                                return Config.treatment_department[value];
                            }
                        },
                        {field: 'doctor_id', title: __('Doctor_name'), searchList: Config.doctors,
                            formatter:function (value, row, index){
                                return Config.doctors[value];
                            }
                        },
                        {field: 'createtime', title: __('Register_time'),
                            operate: 'BETWEEN',
                            type: 'datetime',
                            addclass: 'datetimepicker',
                            data: 'data-date-format="YYYY-MM-DD"',
                            formatter:function (value, row, index){
                                return value ? Moment(parseInt(value) * 1000).format("YYYY-MM-DD HH:mm") : __('None');
                            }

                        },
                        {field: 'operate', title: __('Operate'), table: table,
                            // events: Table.api.events.operate,
                            // formatter: Table.api.formatter.operate,
                            buttons: [{
                                name: 'detail',
                                text: __('Detail'),
                                icon: 'fa fa-list',
                                classname: 'btn btn-info btn-xs btn-detail btn-dialog',
                                url: 'user/register/detail'
                            }],
                            formatter: Table.api.formatter.buttons
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //添加按钮事件（预约，并确认）
            $('.btn-add-appointment').on('click',function () {
                var patient_in_member_id =top.window.$("iframe[src='/admin/user/register?addtabs=1']").attr('patient_in_member_id');
                patient_in_member_id = patient_in_member_id ? patient_in_member_id : 0;
                var url = 'user/appointment/add/appointTime/0/doctorId/0/patientInMemberId/'+patient_in_member_id+'/tag/1';
                Fast.api.open(url,'新增预约',{});
                top.window.$("iframe[src='/admin/user/register?addtabs=1']").removeAttr('patient_in_member_id');
            });
            //是否有从患者页面传来的病人id，有则打开新增页面
            var patient_in_member_id = top.window.$("iframe[src='/admin/user/register?addtabs=1']").attr('patient_in_member_id');
            if (typeof patient_in_member_id !='undefined'){
                $('.btn-add-appointment').trigger("click");
            }
        },
        add: function () {
            //autocomplete
            require(['jquery-ui'], function () {
                $.widget( "custom.autocomplete", $.ui.autocomplete, {
                    _renderItem: function( ul, item ) {
                        return $( "<li>" )
                            .addClass("list-group-item")
                            .append('<a style="display:block;" ><span>'+item.label+'</span><span  style="padding-left:10px;">'+item.telphone+'</span></a>')
                            .appendTo( ul );
                    },
                    _renderMenu: function( ul, items ) {
                        var that = this;
                        $.each( items, function( index, item ) {
                            that._renderItemData( ul, item );
                        });
                        $( ul ).addClass( "list-group" ).css({"position":"absolute","z-index":10});
                    }
                });
                $('.autocomplete').autocomplete({
                    source: function( request, response ) {
                        var term = request.term;
                        $.ajax({
                            url: "user/member/getMember",
                            type: "POST",
                            dataType: "json",
                            data: {
                                keyword: term
                            },
                            success: function(data) {
                                response(data);
                            }
                        });
                    },
                    minLength: 1,
                    select: function( event, ui ) {
                        $('#c-name').val(ui.item.label);
                        $('#c-member_id').val(ui.item.id);
                        $('#c-patient_in_member_id').val(ui.item.patientInMemberId);
                        $('#c-telphone').val(ui.item.telphone);
                        $('#c-gender').selectpicker('val', ui.item.gender);
                    }
                })
            });

            //科室医生关联事件
            $('#c-treatment_department').on('change',function () {
                var depart_id = $(this).val();
                $.ajax({
                    url: "user/register/getDoctor",
                    type: 'post',
                    dataType: 'json',
                    data: {depart_id: depart_id},
                    success: function (ret) {
                        if (ret.hasOwnProperty("code")) {
                            var data = ret.hasOwnProperty("data") && ret.data != "" ? ret.data : "";
                            if (ret.code === 1) {
                                $('#c-doctor_id').html(data);
                                $("#c-doctor_id").selectpicker("refresh");
                                //获取医生姓名
                                var doctor_name =  $('#c-doctor_id').find("option:selected").text();
                                $('#c-doctor_name').val(doctor_name);
                                //获取剩余挂号数
                                Controller.api.getRemainderRegisterNum();

                            } else {
                                $('#depart_id').html('');
                                Backend.api.toastr.error(ret.msg);
                            }
                        }
                    },
                    error: function (e) {
                        $('#c-doctor_id').selectpicker('val', '');
                        Backend.api.toastr.error(e.message);
                    }
                });
            })

            //获取剩余挂号
            $('#c-doctor_id,#c-stage').on('change',function () {
                Controller.api.getRemainderRegisterNum();
            });


            $('#c-register_time').datetimepicker({
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
                minDate:Moment().format("YYYY-MM-DD"),
                maxDate:Moment().add(7,'days').format("YYYY-MM-DD"),
                // disabledDates:['2018-03-30'],
            }).on('dp.change', function(e){
                Controller.api.getRemainderRegisterNum();
            });



            //获取医生姓名
            $('#c-doctor_id').on('change',function () {
                var doctor_name = $(this).find("option:selected").text();
               $('#c-doctor_name').val(doctor_name);
            })
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        detail: function () {
            //取消按钮点击事件
            $(document).on('click', '.btn-cancel', function (event) {
                var ids = $(this).attr("registerId");
                var timer = '';
                Fast.api.ajax({
                    url: "user/register/cancel/ids/"+ids,
                }, function (data, ret) {
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        window.parent.Layer.closeAll();
                    },1000)
                }, function (data, ret) {
                    Layer.alert(ret.msg);
                });
            });
            //支付按钮点击事件
            $(document).on('click', '.btn-pay', function () {
                var chargeInfo = JSON.parse($(this).attr("chargeInfo"));
                if (chargeInfo.should_pay > 0) {
                    /*鼠标点击下去的时候，决定是否选中*/
                    $("#iscard").bind("mousedown",function(event){
                        var radioChecked = $(this).prop("checked");
                        $(this).prop('checked', !radioChecked);
                        return false;
                    });
                    Layer.open({
                        content: Template("pay",chargeInfo),
                        area: ['500px', '410px'],
                        title: "支付",
                        resize: false,
                        yes: function () {
                            var pay_way = $("#mytab.nav-pills > li.active > a").attr('pay_way');
                            console.log($('#iscard').prop("checked"));
                            if ($('#iscard').prop("checked")){
                                pay_way = $('#iscard').val();
                            }
                            var already_paid = $(".already_paid").val();
                            $.ajax({
                                type: 'post',
                                dataType: 'json',
                                url: 'user/chargeinfo/pay',
                                data: {id:chargeInfo.id,pay_way:pay_way,already_paid:already_paid},
                                success:function (ret) {
                                    Layer.closeAll();
                                    Layer.alert(ret.msg);
                                },
                                error: function (e) {
                                    Backend.api.toastr.error(e.message);
                                }
                            });
                        }
                    });
                }else{
                    Layer.alert("无未付挂号费");
                }
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            getRemainderRegisterNum:function () {
                //医生
                var doctor_id = $('#c-doctor_id').val();
                //挂号时间
                var register_time = $("#c-register_time").val();
                //科室
                var treatment_department = $("#c-treatment_department").val();
                //上下午，晚上阶段
                var stage = $("#c-stage").val();
                $.ajax({
                    url: "user/register/getRemainderRegister",
                    type: 'post',
                    dataType: 'json',
                    data: {
                        doctor_id: doctor_id,
                        register_time:register_time,
                        treatment_department:treatment_department,
                        stage:stage
                    },
                    success: function (ret) {
                        // var ret = JSON.parse(ret);
                        if (ret.code === 1) {
                            $(".remainderRegister").text(ret.data);
                            $('#c-remainder_register').val(ret.data);
                        } else {
                            $(".remainderRegister").text(0);
                            $('#c-remainder_register').val(0);
                            Backend.api.toastr.error(ret.msg);
                        }
                    }, error: function (e) {
                        $(".remainderRegister").text(0);
                        $('#c-remainder_register').val(0);
                        Backend.api.toastr.error(e.message);
                    }
                });
            }
        }
    };
    return Controller;
});