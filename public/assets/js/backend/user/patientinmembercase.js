define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/patientinmembercase/index/patientInMemberId/'+Config.patientInMemberInfo.id,
                    add_url: 'user/patientinmembercase/add/patientInMemberId/'+Config.patientInMemberInfo.id,
                    edit_url: 'user/patientinmembercase/edit',
                    del_url: 'user/patientinmembecase/del',
                    multi_url: 'user/patientinmembercase/multi',
                    table: 'patient_in_member_case',
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
                        {field: 'admin_name', title: __('Admin_name')},
                        {field: 'image', title: __('Image'), formatter: Controller.api.formatter.thumb},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                    ]
                ]
            });
            //添加病例按钮
            $('.btn-add-case').on('click',function () {
                if (Config.memberInfo.status != 1){
                    Layer.alert('会员被禁用，不可添加病例');
                    return;
                }
                if(typeof Config.patientInfo == 'undefined'){
                    window.location.href = '/admin/user/patientinmembercase/add/patientInMemberId/'+Config.patientInMemberInfo.id+'/chooseBtn/'+Config.chooseBtn+'/patientId/0';
                }else{
                    window.location.href = '/admin/user/patientinmembercase/add/patientInMemberId/'+Config.patientInMemberInfo.id+'/chooseBtn/'+Config.chooseBtn+'/patientId/'+Config.patientInfo.id;
                }
            });

            if (Config.chooseBtn == 1){//返回会员详情按钮
                var btnHtml = '<a href = "/admin/user/member/detail/ids/'+Config.patientInMemberInfo.member_id + '" class="btn btn-success btn-return-detail" title="返回会员详情"><i class=""></i>返回会员详情</a>';
                $('#toolbar').append(btnHtml)
            }else if (Config.chooseBtn == 2){ //返回患者详情按钮
                var btnHtml = '<a href = "/admin/user/patientvisitrecord/detail/ids/'+Config.patientInfo.id+ '" class="btn btn-success btn-return-patient-detail" title="返回患者详情"><i class=""></i>返回患者详情</a>';
                $('#toolbar').append(btnHtml)
            }
            //返回会员详情按钮
            // $('.btn-return-detail').on('click',function () {
            //     window.location.href = '/admin/user/member/detail/ids/'+Config.patientInMemberInfo.member_id;
            // });
            //返回患者详情按钮
            // $('.btn-return-patient-detail').on('click',function () {
            //     window.location.href = '/admin/user/patientvisitrecord/detail/ids/'+Config.patientInfo.id;
            // });
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
                Form.api.bindevent($("form[role=form]"),function (ret,data) {
                    parent.Toastr.success(data.msg);
                    window.location.href = data.url;
                });
            },
            formatter: {
                thumb: function (value, row, index) {
                    if (value) {
                        return '<a href="' + value + '" target="_blank"><img src="' + value + '" alt="" style="max-height:90px;max-width:120px"></a>';
                    } else {
                        return '<a href="' + value + '" target="_blank">' + __('None') + '</a>';
                    }
                },
            }
        }
    };
    return Controller;
});