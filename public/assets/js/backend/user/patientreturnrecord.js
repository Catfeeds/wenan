define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jquery-ui', 'bootstrap-datetimepicker'], function ($, undefined, Backend, Table, Form, Datetimepicker ) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/patientreturnrecord/index/patientInMemberId/'+Config.patientInMemberInfo.id+'/choseBtn/1',
                    add_url: 'user/patientreturnrecord/add/patientInMemberId/'+Config.patientInMemberInfo.id,
                    edit_url: 'user/patientreturnrecord/edit',
                    // del_url: 'user/patientreturnrecord/del',
                    multi_url: 'user/patientreturnrecord/multi',
                    table: 'patient_return_record',
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
                        {field: 'key', title: __('Id')},
                        {field: 'return_time', title: __('Return_time'), addclass:'datetimerange',
                            formatter:function (value,row,index) {
                                return value ? Moment(parseInt(value) * 1000).format("YYYY-MM-DD HH:00") : __('None');
                            }
                        },
                        {field: 'admin_name', title: __('Name')},
                        {field: 'content', title: __('Content')},
                        {field: 'next_time', title: __('Next_time'), operate:'RANGE', addclass:'datetimerange',
                            formatter:function (value,row,index) {
                                return value ? Moment(parseInt(value) * 1000).format("YYYY-MM-DD") : __('None');
                            }
                        },
                        {field: 'operate', title: __('Operate'), table: table,
                            events: {
                                'click .btn-editone-record': function (e, value, row, index) {
                                    e.stopPropagation();
                                    var options = $(this).closest('table').bootstrapTable('getOptions');
                                    window.location.href = "/admin/user/patientreturnrecord/edit" + "/ids/"+ row[options.pk];
                                },
                            },
                            buttons: [{name: 'edit', icon: 'fa fa-pencil', classname: 'btn btn-xs btn-editone-record'}],
                            formatter: function (value, row, index) {
                                var table = this.table;
                                // 操作配置
                                var options = table ? table.bootstrapTable('getOptions') : {};
                                // 默认按钮组
                                var buttons = this.buttons;
                                var html = [];
                                var url, classname, icon, text, title, extend;

                                $.each(buttons, function (i, j) {
                                    var attr = table.data("buttons-" + j.name);
                                    if (typeof attr === 'undefined' || attr) {
                                        url = j.url ? j.url : '';
                                        if (url.indexOf("{ids}") === -1) {
                                            url = url ? url + (url.match(/(\?|&)+/) ? "&ids=" : "/ids/") + row[options.pk] : '';
                                        }
                                        url = Table.api.replaceurl(url, value, row, table);
                                        url = url ? Fast.api.fixurl(url) : 'javascript:;';
                                        if (row.is_send) {
                                            j.classname = 'btn btn-xs btn-editone-record btn-default';
                                        }else{
                                            j.classname = 'btn btn-xs btn-editone-record btn-success';
                                        }
                                        classname = j.classname ? j.classname : 'btn-primary btn-' + name + 'one';
                                        icon = j.icon ? j.icon : '';
                                        text = j.text ? j.text : '';
                                        title = j.title ? j.title : text;
                                        extend = j.extend ? j.extend : '';
                                        if (row.is_send) {
                                            html.push('<button disabled class="' + classname + '" ' + extend + ' title="' + title + '"><i class="' + icon + '"></i>' + (text ? ' ' + text : '') + '</button>');
                                        }else{
                                            html.push('<a href="' + url + '" class="' + classname + '" ' + extend + ' title="' + title + '"><i class="' + icon + '"></i>' + (text ? ' ' + text : '') + '</a>');
                                        }
                                        
                                    }
                                });
                                return html.join(' ');
                            }
                            // events: Table.api.events.operate,
                            // formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });
            //新增回访按钮
            $('.btn-add-record').on('click',function () {
                if (Config.memberInfo.status != 1){
                    Layer.alert('会员被禁用，不可新增回访');
                    return;
                }
                window.location.href = '/admin/user/patientreturnrecord/add/patientInMemberId/'+Config.patientInMemberInfo.id+'/chooseBtn/1';
            });
            //返回会员详情按钮
            $('.btn-return-detail').on('click',function () {
                window.location.href = '/admin/user/member/detail/ids/'+Config.patientInMemberInfo.member_id;
            })
            //返回患者详情按钮
            $('.btn-return-patient-detail').on('click',function () {
                window.location.href = '/admin/user/patientvisitrecord/detail/ids/'+Config.patientInfo.id;
            });
            //设置回访周期
            $('.btn-set-cycle').on('click',function () {
                Layer.prompt({
                    formType: 0,
                    value: Config.patientInMemberInfo.return_cycle,
                    title: '设置回访周期/天',
                    // area: ['800px', '350px'] //自定义文本域宽高
                }, function(value, index, elem){
                   $.ajax({
                       type:'POST',
                       url:'/admin/user/Patientinmember/setCycle',
                       dataType:'json',
                       data:{
                           'id':Config.patientInMemberInfo.id,
                           'return_cycle':value
                       },
                       success:function (ret) {
                           layer.close(index);
                           layer.msg(ret.msg);
                           Config.patientInMemberInfo.return_cycle = value;
                       }
                   })
                });
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            //autocomplete
            Controller.api.contentAutocomplete();
            Controller.api.bindevent();
            Controller.api.datetimepicker();
        },
        edit: function () {
            //autocomplete
            Controller.api.contentAutocomplete();
            Controller.api.bindevent();
            Controller.api.datetimepicker();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"),function (ret,data) {
                    // parent.Toastr.success(data.msg);
                    Layer.alert(data.msg,function () {
                        window.location.href = data.url;
                    });
                    // window.location.href = data.url;
                });
            },
            contentAutocomplete:function () {
                $.widget( "custom.autocomplete", $.ui.autocomplete, {
                    _renderItem: function( ul, item ) {
                        var htmla = '<a style="display:block;" ><span>'+item.label+'</span></a>';
                        if (item.id > 0){//添加删除图标
                            htmla = '<a style="display:block;" ><span>'+item.label+'</span><span class="content-del" contentid = '+item.id+' style="float: right;cursor:pointer;"><i class="fa fa-trash"></i></span></a>'
                        }
                        return $( "<li>" )
                            .addClass("list-group-item")
                            .append(htmla)
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
                            url: "user/patientreturncontent/getReturnContent",
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
                    select:function( event, ui ) {
                        if (ui.item.id == 0){
                            return false;
                        }
                    }
                });
                //回访联想内用删除事件
                $('#ui-id-1').on('click','.content-del',function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    var id = $(this).attr('contentid');
                    var thisParentEle = $(this).parents('.ui-menu-item');
                    if (id > 0){
                        $.ajax({
                            url: "user/patientreturncontent/del/ids/"+id,
                            type: "get",
                            dataType: "json",
                            success: function(ret) {
                                thisParentEle.remove();
                            }
                        });
                    }
                });

            },
            datetimepicker:function () {
                $('.datetimepicker').datetimepicker({
                    format: 'YYYY-MM-DD HH:00',
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
                    stepping:60,
                    showTodayButton: true,
                    showClose: true,
                    minDate: Moment(),
                    tooltips: {
                        incrementHour: '增加小时',
                        decrementHour: '减小小时',
                    },
                    // disabledTimeIntervals: [[0, Moment()]]
                });
            }
        }
    };
    return Controller;
});