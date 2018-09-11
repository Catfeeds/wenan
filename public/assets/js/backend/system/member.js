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
                    index_url: 'system/member/index',
                    add_url: 'system/member/add',
                    delt_url: 'system/member/del',
                    forbidden_url: 'system/member/forbidden',
                    startus_url: 'system/member/startus',
                }
            });
            Table.api.multi = function (action, ids, table, element) {
                var options = table.bootstrapTable('getOptions');
                var data = element ? $(element).data() : {};
                var url = typeof data.url !== "undefined" ? data.url : (action == "delt" ? options.extend.delt_url : (action == "forbidden" ? options.extend.forbidden_url : (action == "startus" ? options.extend.startus_url : options.extend.multi_url)));
                url = url + (url.match(/(\?|&)+/) ? "&ids=" : "/ids/") + ($.isArray(ids) ? ids.join(",") : ids);
                var params = typeof data.params !== "undefined" ? (typeof data.params == 'object' ? $.param(data.params) : data.params) : '';
                var options = {url: url, data: {action: action, ids: ids, params: params}};
                Fast.api.ajax(options, function (data) {
                    table.bootstrapTable('refresh');
                });
            };
            /*var BootstrapTable = $.fn.bootstrapTable.Constructor;
            BootstrapTable.prototype.initServer = function (silent, query, url) {
                alert(666);
            }
            BootstrapTable.prototype.onCommonSearch = function () {
                var searchQuery = getSearchQuery(this);
                var params = getQueryParams(this.options.queryParams({}), searchQuery, true);
                this.trigger('common-search', this, params, searchQuery);
                this.options.pageNumber = 1;
                this.options.queryParams = function () {
                    return params;
                };
                this.refresh({query: params});
            };*/

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: [
                    [
                        // {field: 'id', title: '序号', searchable: false},
                        {field: 'username', title: '姓名', operate: 'LIKE'},
                        {field: 'group_id', title: '权限角色', searchList: Config.groupdata,
                            formatter:function (value, row, index){
                                return row.old_group_id == 2 ? '<span style="color: red;">' + value + '</span>' : value;
                            }
                        },
                        {field: 'hos_name', title: '医馆', searchable: false},
                        {field: 'depart_name', title: '科室', searchable: false},
                        {field: 'logintime', title: '最后操作时间', formatter: Table.api.formatter.datetime, searchable: false},
                        {field: 'phone', title: '账号名', searchable: false},
                        {field: 'operate', title: __('Operate'), table: table,
                            events: {
                                'click .btn-forbidden': function (e, value, row, index) {
                                    e.stopPropagation();
                                    var that = this;
                                    var top = $(that).offset().top - $(window).scrollTop();
                                    var left = $(that).offset().left - $(window).scrollLeft() - 260;
                                    if (top + 154 > $(window).height()) {
                                        top = top - 154;
                                    }
                                    if ($(window).width() < 480) {
                                        top = left = undefined;
                                    }
                                    var index = Layer.confirm(
                                        '确认禁用该账户，禁用后此账户将不可再登录操作本系统，且该账户下操作正在进行的业务也将同时被关闭？',
                                        {icon: 3, title: __('Warning'), shadeClose: true},
                                        function () {
                                            var table = $(that).closest('table');
                                            var options = table.bootstrapTable('getOptions');
                                            Table.api.multi("forbidden", row[options.pk], table, that);
                                            Layer.close(index);
                                        }
                                    );
                                },
                                'click .btn-delt-true': function (e, value, row, index) {
                                    e.stopPropagation();
                                    var table = $(this).closest('table');
                                    var options = table.bootstrapTable('getOptions');
                                    Table.api.multi("delt", row[options.pk], table, this);
                                },
                                'click .btn-startus': function (e, value, row, index) {
                                    e.stopPropagation();
                                    var table = $(this).closest('table');
                                    var options = table.bootstrapTable('getOptions');
                                    Table.api.multi("startus", row[options.pk], table, this);
                                }
                            },
                            formatter: function (value, row, index) {
                                //console.log(row.logintime);
                                //console.log(row.logintime == 0);
                                if (row.status == -1) {
                                    return '已删除';
                                } else if (row.status == 2) {
                                    //return '已禁用';
                                    this.buttons = [];
                                    this.buttons.push({
                                        name: 'startus',
                                        text: '启用',
                                        classname: 'btn btn-xs btn-startus',
                                    });
                                } else if (row.status != 1) {
                                    return '未知状态';
                                } else if (row.old_group_id == 2) {
                                    return '';
                                } else {
                                    this.buttons = [];
                                    this.buttons.push({
                                        name: 'forbidden',
                                        text: '禁用',
                                        classname: 'btn btn-xs btn-forbidden',
                                    });
                                    if (row.logintime == 0) {
                                        this.buttons.push({
                                            name: 'new-del',
                                            text: '删除',
                                            classname: 'btn btn-xs btn-delt btn-delt-true',
                                            extend: '',
                                        });
                                    } else {
                                        this.buttons.push({
                                            name: 'new-del',
                                            text: '删除',
                                            classname: 'btn btn-xs btn-delt',
                                            extend: "style='color:#d2d6de;' disabled='disabled'",
                                        });
                                    }
                                }
                                //console.log(this.buttons[1]);
                                return Table.api.formatter.operate.call(this, value, row, index);
                            },
                            buttons: [],
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Form.api.bindevent($("form[role=form]"));
            $(function(){
                $('#hos_id').change(function(){
                    var hos_id = $(this).val();
                    $('.depart_id').html('<select class="form-control selectpicker" id="depart_id" data-rule="required" name="depart_id"><option value="">加载中...</option></select>');
                    $("#depart_id").selectpicker("refresh");
                    $('.group_id').html('<select class="form-control selectpicker" id="group_id" data-rule="required" name="group_id"><option value="">加载中...</option></select>');
                    $("#group_id").selectpicker("refresh");
                    $.ajax({
                        url: "system/hospital/getHosDep",
                        type: 'post',
                        dataType: 'json',
                        data: {hos_id: hos_id},
                        success: function (ret) {
                            if (ret.hasOwnProperty("code")) {
                                if (ret.code === 1) {
                                    $('.depart_id').html(ret.data.depart_html);
                                    $("#depart_id").selectpicker("refresh");
                                    $('.group_id').html(ret.data.group_html);
                                    $("#group_id").selectpicker("refresh");
                                } else {
                                    $('#depart_id').html('');
                                    $("#depart_id").selectpicker("refresh");
                                    $('#group_id').html('');
                                    $("#group_id").selectpicker("refresh");
                                    Backend.api.toastr.error(ret.msg);
                                }
                            }
                        }, error: function (e) {
                            $('#depart_id').html('');
                            Backend.api.toastr.error(e.message);
                        }
                    });
                })
            })
        },
        edit: function () {
            Form.api.bindevent($("form[role=form]"));
        }
    };
    return Controller;
});