define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jstree'], function ($, undefined, Backend, Table, Form, undefined) {
    //读取选中的条目
    $.jstree.core.prototype.get_all_checked = function (full) {
        var obj = this.get_selected(), i, j;
        for (i = 0, j = obj.length; i < j; i++) {
            obj = obj.concat(this.get_node(obj[i]).parents);
        }
        obj = $.grep(obj, function (v, i, a) {
            return v != '#';
        });
        obj = obj.filter(function (itm, i, a) {
            return i == a.indexOf(itm);
        });
        return full ? $.map(obj, $.proxy(function (i) {
            return this.get_node(i);
        }, this)) : obj;
    };
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showToggle: false,
                showRefresh: false,
                showColumns: false,
                showToolbar: false,
                commonSearch: false,
                searchFormVisible: false,
                showExport: false,
                search: false,
                extend: {
                    index_url: 'system/group/index',
                    add_url: 'system/group/add',
                    new_del_url: 'system/group/del',
                    edit_url: 'system/group/edit',
                }
            });
            Table.api.multi = function (action, ids, table, element) {
                var options = table.bootstrapTable('getOptions');
                var data = element ? $(element).data() : {};
                var url = typeof data.url !== "undefined" ? data.url : (action == "del" ? options.extend.new_del_url : options.extend.multi_url);
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
                columns: [
                    [
                        {field: 'id', title: '序号', searchable: false},
                        {field: 'name', title: '名称', operate: false},
                        //{field: 'status', title: '启用状态', searchable: false},
                        {field: 'operate', title: __('Operate'), table: table,
                            events: {
                                'click .btn-editone': function (e, value, row, index) {
                                    e.stopPropagation();
                                    var options = $(this).closest('table').bootstrapTable('getOptions');
                                    Fast.api.open(options.extend.edit_url + (options.extend.edit_url.match(/(\?|&)+/) ? "&ids=" : "/ids/") + row[options.pk], __('Edit'), $(this).data() || {});
                                },
                                'click .btn-delone': function (e, value, row, index) {
                                    e.stopPropagation();
                                    if (row.num > 0) {
                                        Layer.alert('【' + row.name + '】身份当前用户数为' + row.num + '，请先删除用户');
                                        return false;
                                    }
                                    var that = this;
                                    var index = Layer.confirm(
                                        '确认是否删除【' + row.name + '】身份属性，该身份当前用户数为' + row.num + '？',
                                        {icon: 3, title: __('Warning'), shadeClose: true},
                                        function () {
                                            var table = $(that).closest('table');
                                            var options = table.bootstrapTable('getOptions');
                                            Table.api.multi("del", row[options.pk], table, that);
                                            Layer.close(index);
                                        }
                                    );
                                }
                            },
                            formatter: function (value, row, index) {
                                if (row.status == -1) {
                                    return '已删除';
                                }
                                return Table.api.formatter.operate.call(this, value, row, index);
                            },
                            buttons: [
                                {name: 'new-del', icon: 'fa fa-trash', classname: 'btn btn-xs btn-danger btn-delone'}
                            ],
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Form.api.bindevent($("form[role=form]"));
        },
        edit: function () {
            Controller.api.bindevent();
            $("#submit").click(function(){
                var index = Layer.confirm(
                    '确认编辑管理【' + Config.group.name + '】权限，修改后将会影响所有【' + Config.group.name + '】身份的账号权限？',
                    {icon: 3, title: __('Warning'), shadeClose: true},
                    function () {
                        $("form[role=form]").submit();
                        //Layer.close(index);
                    }
                );
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"), null, null, function () {
                    if ($("#treeview").size() > 0) {
                        var r = $("#treeview").jstree("get_all_checked");
                        $("input[name='row[rules]']").val(r.join(','));
                    }
                    return true;
                });
                //渲染权限节点树
                $.ajax({
                    url: "auth/group/allroletree",
                    type: 'post',
                    dataType: 'json',
                    data: {id: Config.group.id, pid: Config.group.pid},
                    success: function (ret) {
                        if (ret.hasOwnProperty("code")) {
                            var data = ret.hasOwnProperty("data") && ret.data != "" ? ret.data : "";
                            if (ret.code === 1) {
                                //销毁已有的节点树
                                $("#treeview").jstree("destroy");
                                Controller.api.rendertree(data);
                            } else {
                                Backend.api.toastr.error(ret.data);
                            }
                        }
                    }, error: function (e) {
                        Backend.api.toastr.error(e.message);
                    }
                });
                //全选和展开
                $(document).on("click", "#checkall", function () {
                    $("#treeview").jstree($(this).prop("checked") ? "check_all" : "uncheck_all");
                });
                $(document).on("click", "#expandall", function () {
                    $("#treeview").jstree($(this).prop("checked") ? "open_all" : "close_all");
                });
            },
            rendertree: function (content) {
                $("#treeview")
                    .on('redraw.jstree', function (e) {
                        $(".layer-footer").attr("domrefresh", Math.random());
                    })
                    .jstree({
                        "themes": {"stripes": true},
                        "checkbox": {
                            "keep_selected_style": false,
                        },
                        "types": {
                            "root": {
                                "icon": "fa fa-folder-open",
                            },
                            "menu": {
                                "icon": "fa fa-folder-open",
                            },
                            "file": {
                                "icon": "fa fa-file-o",
                            }
                        },
                        "plugins": ["checkbox", "types"],
                        "core": {
                            'check_callback': true,
                            "data": content
                        }
                    });
            }
        }
    };
    return Controller;
});