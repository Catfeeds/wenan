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
                    index_url: 'doctor/workload/index',
                }
            });

            var table = $("#table");

            var columns = [
                [
                    {field: 'name', title: '客户姓名', searchable: false},
                    {field: 'telphone', title: '手机号', searchable: false},
                    {field: 'gender', title: '性别', searchable: false,
                        formatter:function (value, row, index){
                            return value == 1 ? '男' :  '女';
                        }
                    },
                    {field: 'day', title: '预约日期', searchable: false, formatter: Table.api.formatter.datetime},
                    {field: 'start_time', title: '开始时间', searchable: false, formatter: Table.api.formatter.datetime},
                    {field: 'end_time', title: '结束时间',
                        operate: 'BETWEEN',
                        type: 'datetime',
                        addclass: 'datetimepicker',
                        data: 'data-date-format="YYYY-MM-DD"',
                        formatter: Table.api.formatter.datetime,
                    },
                    {field: 'createtime', title: '创建时间', searchable: false, formatter: Table.api.formatter.datetime},
                    {field: 'hos_name', title: '医馆', searchable: false, searchList: Config.hosList},
                    {field: 'doctor_name', title: '医生', searchable: false, searchList: Config.adminList},
                ]
            ];
            if (Config.admin.group_id == 1) {
                columns[0][7].searchable = true;
                columns[0][8].searchable = true;
            } else if (Config.admin.group_id == 2) {
                columns[0][8].searchable = true;
            }

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: columns
            });

            $('#toolbar').append(
                '<a id="today" class="btn btn-success">日</a><a id="week" class="btn btn-success" style="margin-left:5px;">周</a><a id="month" class="btn btn-success" style="margin-left:5px;">月</a>'
            );

            // 为表格绑定事件
            Table.api.bindevent(table);

            $(function(){
                $('input[name="end_time"]').val(Config.startDay);
                $('input[name="end_time"]').eq(1).val(Config.endDay);

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
                    $('input[name="end_time"]').val(Config.endDay);
                    $('input[name="end_time"]').eq(1).val(Config.endDay);
                    $("form.form-commonsearch").trigger("submit");
                })

                $('#week').click(function(){
                    $('input[name="end_time"]').val(Config.weekStart);
                    $('input[name="end_time"]').eq(1).val(Config.weekEnd);
                    $("form.form-commonsearch").trigger("submit");
                })

                $('#month').click(function(){
                    $('input[name="end_time"]').val(Config.startDay);
                    $('input[name="end_time"]').eq(1).val(Config.monthEnd);
                    $("form.form-commonsearch").trigger("submit");
                })
            })
        }
    };
    return Controller;
});