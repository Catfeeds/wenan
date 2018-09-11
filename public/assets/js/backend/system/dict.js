define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'system/dict/index',
                    add_url: 'system/dict/add',
                    edit_url: 'system/dict/edit',
                    del_url: 'system/dict/del',
                    multi_url: 'system/dict/multi',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: [
                    [
                        {field: 'state', checkbox: true, },
                        // {field: 'id', title: '序号'},
                        {field: 'dict_name', title: '字典名称'},
                        {field: 'dict_value', title: '字典标识'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: function (value, row, index) {
                                return Table.api.formatter.operate.call(this, value, row, index);
                            }}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Form.api.bindevent($("form[role=form]"));
            var current = 0;
            $(function(){
                $('#add_dict').click(function(){
                    current++;
                    var html = '<tr>' +
                        '<td><input type="text" name="dict_data['+current+'][dict_data_value]" required  lay-verify="required" autocomplete="off" class = "layui-input"/></td>\n' +
                        '<td><input type="text" name="dict_data['+current+'][dict_data_name]" required  lay-verify="required" autocomplete="off" class = "layui-input"/></td>\n' +

                        '                <td><input type="text" name="dict_data['+current+'][sort]"  autocomplete="off" class = "layui-input"/></td>\n' +
                        '                <td><button class="layui-btn layui-btn-small layui-btn-primary del-dict">删除</button></td>'
                    $("#dict_body").append(html);
                })
                $("#dict_body").on('click', '.del-dict', function(){
                    $(this).parent().parent().remove();
                })
            })
        },
        edit: function () {
            Form.api.bindevent($("form[role=form]"));
            var current = Config.dictColumn;
            $(function(){
                $('#add_dict').click(function(){
                    current++;
                    var html = '<tr>' +
                        '<td><input type="text" name="dict_data['+current+'][dict_data_value]" required  lay-verify="required" autocomplete="off" class = "layui-input"/></td>\n' +
                        '<td><input type="text" name="dict_data['+current+'][dict_data_name]" required  lay-verify="required" autocomplete="off" class = "layui-input"/></td>\n' +

                        '                <td><input type="text" name="dict_data['+current+'][sort]"  autocomplete="off" class = "layui-input"/></td>\n' +
                        '                <td><button class="layui-btn layui-btn-small layui-btn-primary del-dict">删除</button></td>'
                    $("#dict_body").append(html);
                })
                $("#dict_body").on('click', '.del-dict', function(){
                    $(this).parent().parent().remove();
                })
            })
        }
    };
    return Controller;
});