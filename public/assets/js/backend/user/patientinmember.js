define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/patientinmember/index',
                    add_url: 'user/patientinmember/add/memberId/'+Config.memberId,
                    edit_url: 'user/patientinmember/edit',
                    del_url: 'user/patientinmember/del',
                    multi_url: 'user/patientinmember/multi',
                    table: 'patient_in_member',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        // {field: 'id', title: __('Id')},
                        {field: 'member_id', title: __('Member_id')},
                        {field: 'name', title: __('Name')},
                        {field: 'gender', title: __('Gender')},
                        {field: 'birth_time', title: __('Birth_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'relation', title: __('Relation')},
                        {field: 'return_cycle', title: __('Return_cycle')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            //取消事件
            $(document).on('click','.btn-cancel',function (event) {
                event.preventDefault();
                window.location.href = '/admin/user/member/detail/ids/'+Config.memberId;
            });
            //返回会员详情按钮
            $(document).on('click','.btn-return-detail',function (event) {
                event.preventDefault();
                window.location.href = '/admin/user/member/detail/ids/'+Config.memberId;
            });
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"),function (ret,data) {
                    parent.Toastr.success(data.msg);
                    window.location.href = data.url;
                });
            }
        }
    };
    return Controller;
});