define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'bootstrap-datetimepicker'], function ($, undefined, Backend, Table, Form, Datetimepicker) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showToggle: false,
                showRefresh: false,
                showColumns: false,
                showToolbar: false,
                //commonSearch: false,
                searchFormVisible: true,
                showExport: false,
                search: false,
                //pageSize: 2,
                //pageList: [2, 25, 50, 100],
                //toolbarAlign: 'right',
                extend: {
                    index_url: 'doctor/appointment/index',
                    newfee_url: 'user/chargeinfo/add',
                    member_url: 'user/member/detail',
                    upload_url: 'user/Patientinmembercase/index',
                }
            });

            var table = $("#table");
            var add_attr = table.data("operate-add");

            var columns = [
                [
                    {field: 'start_time', title: '预约时间',
                        operate: 'BETWEEN',
                        type: 'datetime',
                        addclass: 'datetimepicker',
                        data: 'data-date-format="YYYY-MM-DD HH:mm"',
                        formatter: function (value, row, index) {
                            if (value) {
                                var day = Moment(parseInt(value) * 1000).format("YYYY-MM-DD");
                                var startTime = Moment(parseInt(value) * 1000).format("HH:mm");
                                var endTime = Moment(parseInt(row['end_time']) * 1000).format("HH:mm");
                                return  day + ' ' + startTime + ' - ' + endTime;
                            } else {
                                return  __('None');
                            }
                        },
                    },
                    {field: 'member_name', title: '会员姓名', searchable: false},
                    {field: 'name', title: '病人姓名', searchable: true},
                    {field: 'createtime', title: '创建时间', searchable: false, formatter: Table.api.formatter.datetime},
                    {field: 'medical_status', title: '预约状态', searchable: false},
                    {field: 'hos_name', title: '医馆', searchable: false, searchList: Config.hosList},
                    {field: 'doctor_name', title: '预约人', searchable: false, searchList: Config.adminList},
                    {field: 'operate', title: __('Operate'), table: table,
                        events: {
                            'click .btn-confirm': function (e, value, row, index) {
                                e.stopPropagation();
                                if (row.member_status != 1){
                                    Layer.alert('该会员已被禁用');
                                    return false;
                                }
                                var that = this;
                                var index = Layer.confirm(
                                    '确认该预约吗？',
                                    {icon: 3, title: __('Warning'), shadeClose: true},
                                    function () {
                                        //console.log(row.id);
                                        //return false;
                                        Layer.close(index);
                                        $.ajax({
                                            url: "user/appointment/appointmentConfirm/ids/" + row.id,
                                            type: "GET",
                                            success: function(ret) {
                                                table.bootstrapTable('refresh');
                                            }
                                        });
                                    }
                                );
                            },
                            'click .btn-upload': function (e, value, row, index) {
                                if (row.member_status != 1){
                                    Layer.alert('该会员已被禁用');
                                    return false;
                                }
                                Fast.api.open($.fn.bootstrapTable.defaults.extend.upload_url + '/patientInMemberId/' + row.patient_in_member_id, '上传病例', {});
                            },
                            'click .btn-newfee': function (e, value, row, index) {
                                console.log(row)
                                if (row.member_status != 1){
                                    Layer.alert('该会员已被禁用');
                                    return false;
                                }
                                Fast.api.open($.fn.bootstrapTable.defaults.extend.newfee_url + '/patientInMemberId/' + row.patient_in_member_id + '/patientId/-1', '新增收费', {});
                            },
                            'click .btn-look': function (e, value, row, index) {
                                Fast.api.open($.fn.bootstrapTable.defaults.extend.member_url + '/ids/' + row.member_id, '查看会员详情', {});
                            },
                        },
                        formatter: function (value, row, index) {
                            if (Config.admin.id == row.doctor_id) {
                                if (row.allowSure == 1) {
                                    this.buttons = [{
                                        name: 'confirm',
                                        text: '确认预约',
                                        classname: 'btn btn-success btn-xs btn-confirm',
                                    }];
                                } else {
                                    this.buttons = [];
                                }
                                this.buttons.push(
                                    {
                                        name: 'upload',
                                        text: '上传病例',
                                        classname: 'btn btn-success btn-xs btn-upload',
                                    },
                                    {
                                        name: 'look',
                                        text: '查看会员详情',
                                        classname: 'btn btn-success btn-xs btn-look',
                                    }
                                );
                                if (add_attr) {
                                    this.buttons.push(
                                        {
                                            name: 'newfee',
                                            text: '新增收费',
                                            classname: 'btn btn-xs btn-newfee',
                                        }
                                    );
                                }
                            } else {
                                return '';
                            }
                            //console.log(this.buttons[1]);
                            return Table.api.formatter.operate.call(this, value, row, index);
                        },
                        buttons: [],
                    }
                ]
            ];
            if (Config.admin.group_id == 1) {
                columns[0][5].searchable = true;
                columns[0][6].searchable = true;
            } else if (Config.admin.group_id == 2) {
                columns[0][6].searchable = true;
            }

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: columns
            });

            $('#toolbar').append(
                '<a id="today" class="btn btn-success">日</a><a id="week" class="btn btn-success" style="margin-left:5px;">周</a><a id="month" class="btn btn-success" style="margin-left:5px;">月</a><a id="setup" class="btn btn-success" style="margin-left:5px;">设置预约时间</a>'
            );

            // 为表格绑定事件
            Table.api.bindevent(table);

            $(function(){
                $('input[name="start_time"]').val(Config.monthStart);
                $('input[name="start_time"]').eq(1).val(Config.monthEnd);

                $('select[name="hos_name"]').change(function(){
                    var hos_id = $(this).val();
                    if (hos_id == '') {
                        $('select[name="doctor_name"]').html('<option value="">选择</option>');
                        return false;
                    }
                    $.ajax({
                        url: "system/hospital/getHosDoctor",
                        type: 'post',
                        dataType: 'json',
                        data: {hos_id: hos_id},
                        success: function (ret) {
                            if (ret.hasOwnProperty("code")) {
                                if (ret.code === 1) {
                                    $('select[name="doctor_name"]').html(ret.data);
                                } else {
                                    Backend.api.toastr.error(ret.msg);
                                }
                            }
                        }, error: function (e) {
                            Backend.api.toastr.error(e.message);
                        }
                    });
                })

                $('#today').click(function(){
                    $('input[name="start_time"]').val(Config.todayStart);
                    $('input[name="start_time"]').eq(1).val(Config.todayEnd);
                    $("form.form-commonsearch").trigger("submit");
                })

                $('#week').click(function(){
                    $('input[name="start_time"]').val(Config.weekStart);
                    $('input[name="start_time"]').eq(1).val(Config.weekEnd);
                    $("form.form-commonsearch").trigger("submit");
                })

                $('#month').click(function(){
                    $('input[name="start_time"]').val(Config.monthStart);
                    $('input[name="start_time"]').eq(1).val(Config.monthEnd);
                    $("form.form-commonsearch").trigger("submit");
                })

                $('#setup').click(function(){
                    Fast.api.open('doctor/appointment/setup', '设置作息时间', {area: [$(window).width() > 800 ? '800px' : '95%', '400px']});
                })
            })
        },
        setup: function () {
            Form.api.bindevent($("form[role=form]"));
            $(function(){
                $('.interval').click(function(){
                    var time = Number($(this).attr('time')), op = $(this).attr('op'), interval = Number($('#appoint_interval').val());
                    if (op == '+') {
                        interval += time;
                    } else if (op == '-') {
                        interval -= time;
                    } else {
                        interval = time;
                    }
                    if (interval < 5) {
                        interval = 5;
                        Backend.api.toastr.error('间隔最小为5分钟');
                    }
                    $('#appoint_interval').val(interval);
                })
            })
        }
    };
    return Controller;
});