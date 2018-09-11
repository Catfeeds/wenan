define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

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
                    index_url: 'system/fee/index',
                    add_url: 'system/fee/add',
                    new_del_url: 'system/fee/del',
                    forbidden_url: 'system/fee/forbidden',
                    new_edit_url: 'system/fee/edit',
                }
            });
            Table.api.multi = function (action, ids, table, element) {
                var options = table.bootstrapTable('getOptions');
                var data = element ? $(element).data() : {};
                var url = typeof data.url !== "undefined" ? data.url : (action == "new-del" ? options.extend.new_del_url : (action == "forbidden" ? options.extend.forbidden_url : options.extend.multi_url));
                url = url + (url.match(/(\?|&)+/) ? "&ids=" : "/ids/") + ($.isArray(ids) ? ids.join(",") : ids);
                var params = typeof data.params !== "undefined" ? (typeof data.params == 'object' ? $.param(data.params) : data.params) : '';
                var options = {url: url, data: {action: action, ids: ids, params: params}};
                Fast.api.ajax(options, function (data) {
                    table.bootstrapTable('refresh');
                });
            };

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: [[
                    // {field: 'id', title: '序号', searchable: false},
                    {field: 'fee_id', title: '费用类型', searchList: Config.feeType},
                    {field: 'fee_name', title: '名称', operate: 'LIKE'},
                    {field: 'price', title: '价格', searchable: false},
                    {field: 'unit', title: '单位', searchable: false},
                    {field: 'update_time', title: '编辑时间', formatter: Table.api.formatter.datetime, searchable: false},
                    {field: 'status', title: '启用状态', searchable: false},
                    {field: 'operate', title: __('Operate'), table: table,
                        events: {
                            'click .btn-forbidden': function (e, value, row, index) {
                                e.stopPropagation();
                                var options = $(this).closest('table').bootstrapTable('getOptions');
                                Fast.api.open(options.extend.forbidden_url + (options.extend.forbidden_url.match(/(\?|&)+/) ? "&ids=" : "/ids/") + row[options.pk], '禁用', $(this).data() || {});
                            },
                            'click .btn-delone': function (e, value, row, index) {
                                e.stopPropagation();
                                var table = $(this).closest('table');
                                var options = table.bootstrapTable('getOptions');
                                Table.api.multi("new-del", row[options.pk], table, this);
                            },
                            'click .btn-editone': function (e, value, row, index) {
                                e.stopPropagation();
                                var options = $(this).closest('table').bootstrapTable('getOptions');
                                Fast.api.open(options.extend.new_edit_url + (options.extend.new_edit_url.match(/(\?|&)+/) ? "&ids=" : "/ids/") + row[options.pk], __('Edit'), $(this).data() || {});
                            },
                        },
                        formatter: function (value, row, index) {
                            if (row.old_status == -1) {
                                return '';
                            } else if (row.old_status == 2) {
                                var timestamp = Date.parse(new Date()) / 1000;
                                this.buttons = [];
                                if (timestamp - row.disable_time >= 3600 * 2) {
                                    this.buttons.push({
                                        name: 'new-edit',
                                        text: '编辑',
                                        classname: 'btn btn-xs btn-editone',
                                    });
                                    this.buttons.push({
                                        name: 'new-del',
                                        text: '删除',
                                        classname: 'btn btn-xs btn-delone',
                                    });
                                }
                            } else if (row.old_status == 0) {
                                this.buttons = [];
                                this.buttons.push({
                                    name: 'new-edit',
                                    text: '编辑',
                                    classname: 'btn btn-xs btn-editone',
                                });
                                this.buttons.push({
                                    name: 'new-del',
                                    text: '删除',
                                    classname: 'btn btn-xs btn-delone',
                                });
                            } else if (row.old_status == 1) {
                                this.buttons = [];
                                this.buttons.push({
                                    name: 'forbidden',
                                    text: '禁用',
                                    classname: 'btn btn-xs btn-forbidden',
                                });
                            }
                            //console.log(this.buttons[1]);
                            return Table.api.formatter.operate.call(this, value, row, index);
                        },
                        buttons: []
                    }
                ]]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Form.api.bindevent($("form[role=form]"));
        },
        edit: function () {
            Form.api.bindevent($("form[role=form]"));
        },
        forbidden: function () {
            Form.api.bindevent($("form[role=form]"));
        }
    };
    return Controller;
});