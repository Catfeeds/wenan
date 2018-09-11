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
                    index_url: 'system/performance/index',
                }
            });

            var table = $("#table");

            var columns = [
                [
                    {field: 'member_name', title: '会员名', searchable: false},
                    {field: 'telphone', title: '会员手机号', searchable: false},
                    {field: 'createtime', title: '销售时间', formatter: Table.api.formatter.datetime},
                    {field: 'should_pay', title: '金额', searchable: false},
                    {field: 'status', title: '支付状态', searchList: Config.statusList,
                        formatter:function (value, row, index){
                            return value == 1 ? '已支付' :  '未支付';
                        }
                    },
                    {field: 'fee_id', title: '费用类型', searchable: false},
                    {field: 'hos_fee_name', title: '费用名称', searchable: false},
                    {field: 'hos_name', title: '操作者医馆', searchable: false, searchList: Config.hosList},
                    {field: 'admin_name', title: '操作者', searchable: false, searchList: Config.adminList},
                    //{field: 'update_time', title: '更新时间', formatter: Table.api.formatter.datetime, searchable: false}
                ]
            ];
            if (Config.admin.group_id == 1) {
                columns[0][7].searchable = true;
                columns[0][8].searchable = true;
            } else {
                columns[0][8].searchable = true;
            }

            var searchObj = {filter: {createtime: Config.month}, op: {createtime: "="}};
            searchObj.filter = JSON.stringify(searchObj.filter);
            searchObj.op = JSON.stringify(searchObj.op);
            $.extend($.fn.bootstrapTable.defaults, {
                onLoadSuccess: function (data) {
                    $('#payedAmount').html('已支付金额总计：' + data.payedAmount);
                    return false;
                },
                onCommonSearch: function (field, text) {
                    searchObj = text;
                    return false;
                }
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: columns
            });

            $('#toolbar').append(
                '<a id="export" target="hiddenwin" class="btn btn-success">导出</a><a id="payedAmount" style="margin-left:10px;"></a>'
            );

            // 为表格绑定事件
            Table.api.bindevent(table);

            $(function(){
                $('input[name="createtime"]').val(Config.month);

                $('input[name="createtime"]').datetimepicker({
                    format: 'YYYY-MM',
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
                    //debug: true
                });

                $('select[name="hos_name"]').change(function(){
                    var hos_id = $(this).val();
                    if (hos_id == '') {
                        $('select[name="admin_name"]').html('<option value="">选择</option>');
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
                                    $('select[name="admin_name"]').html(ret.data);
                                } else {
                                    Backend.api.toastr.error(ret.msg);
                                }
                            }
                        }, error: function (e) {
                            Backend.api.toastr.error(e.message);
                        }
                    });
                })

                $('#export').click(function(){
                    //$("form.form-commonsearch").trigger("submit");
                    //searchObj.export = 1;
                    //console.log(searchObj);
                    /*$.ajax({
                        url: "system/performance/index",
                        type: 'get',
                        dataType: 'json',
                        data: searchObj,
                        success: function (ret) {
                            if (ret.hasOwnProperty("code")) {
                                if (ret.code === 1) {
                                    $('select[name="admin_name"]').html(ret.data);
                                } else {
                                    Backend.api.toastr.error(ret.msg);
                                }
                            }
                        }, error: function (e) {
                            Backend.api.toastr.error(e.message);
                        }
                    });*/
                    $('#hiddenwin').attr('src', '/admin/system/performance/export?filter=' + searchObj.filter + '&op=' + searchObj.op + '&export=1');
                })
            })
        }
    };
    return Controller;
});