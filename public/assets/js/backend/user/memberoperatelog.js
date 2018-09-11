define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/memberoperatelog/index/ids/'+Config.member_id,
                    add_url: 'user/memberoperatelog/add',
                    edit_url: 'user/memberoperatelog/edit',
                    del_url: 'user/memberoperatelog/del',
                    multi_url: 'user/memberoperatelog/multi',
                    table: 'member_operate_log',
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

                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        // {checkbox: true},visible
                        {field: 'createtime', title: __('Createtime'), formatter: Table.api.formatter.datetime},
                        {field: 'admin_name', title: __('Admin_name')},
                        {field: 'content', title: __('Content')},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            //添加返回详情按钮
            $('#toolbar').append(
                '<a href="/admin/user/member/detail/ids/'+Config.member_id+'" class="btn btn-primary btn-returnMemberDetail"><i class=""></i>返回会员详情</a>'
            );
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});