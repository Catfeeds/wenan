define(['jquery', 'bootstrap', 'backend', 'table', 'form','template'], function ($, undefined, Backend, Table, Form,Template) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/patientvisitrecord/index',
                    add_url: 'user/patientvisitrecord/add',
                    edit_url: 'user/patientvisitrecord/edit',
                    del_url: 'user/patientvisitrecord/del',
                    table: 'patient_visit_record',
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

                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {field: 'name', title: __('Name'), operate:'LIKE %...%'},
                        {field: 'gender', title: __('Gender'), searchable:false,
                            formatter:function (value, row, index){
                                var gender = {1:'男', 2:'女'}
                                return value?gender[value]:'无';
                            }
                        },
                        {field: 'member.telphone', title: __('Telphone'),
                            formatter:function (value, row, index){
                                return Config.privacy?value:value.substr(0, 3) + '****' + value.substr(7);
                            }
                        },
                        {field: 'hos_id', title: __('Hos_name'),searchList: Config.hospital,
                            formatter:function (value, row, index){
                                return Config.hospital[value];
                            }
                        },
                        {field: 'treatment_department', title: __('Treatment_department'),placeholder: '选择科室',
                            searchList: function(vObjCol){
                                return Controller.api.formatCommonChoose(vObjCol,Config.treatment_department)
                            },
                            formatter:function (value, row, index){
                                return Config.treatment_department[value];
                            }
                        },
                        {field: 'doctor_register_id', title: __('Doctor_name'), searchList: Config.doctors,
                            formatter:function (value, row, index){
                                if(row['doctor_register_id']<=0){
                                    return Config.doctors[row['doctor_appointment_id']];
                                }
                                return Config.doctors[value];
                            }
                        },
                        {field: 'appointment_time', title: __('Appointment_time'),
                            operate: 'BETWEEN',
                            type: 'datetime',
                            addclass: 'datetimepicker',
                            data: 'data-date-format="YYYY-MM-DD HH:mm"',
                            formatter: function (value, row, index) {
                                if(value){
                                    var day = Moment(parseInt(value) * 1000).format("YYYY-MM-DD");
                                    var startTime = Moment(parseInt(value) * 1000).format("HH:mm");
                                    var endTime = Moment(parseInt(row['appointment']['end_time']) * 1000).format("HH:mm")
                                    return  day+' '+ startTime+'-'+endTime;
                                }else{
                                    return  __('None');
                                }
                            },
                        },
                        {field: 'register_time', title: __('Register_time'),
                            operate: 'BETWEEN',
                            type: 'datetime',
                            addclass: 'datetimepicker',
                            data: 'data-date-format="YYYY-MM-DD"',
                            formatter:function (value, row, index) {
                                // var stage = {1:'上午',2:'下午',3:'晚上'}
                                return value ? Moment(parseInt(value) * 1000).format("YYYY-MM-DD HH:mm") :  __('None');
                            }
                        },
                        {field: 'member.open_member', title: __('Open_member'), searchable:false,
                            formatter:function (value, row, index){
                              var open_member = {0:'否', 1:'是'}
                              return open_member[value];
                            }
                        },
                        {field: 'operate', title: __('Operate'), table: table,
                            buttons: [{
                                name: 'detail',
                                text: __('Detail'),
                                icon: 'fa fa-list',
                                classname: 'btn btn-info btn-xs btn-detail btn-dialog',
                                url: 'user/patientvisitrecord/detail'
                            }],
                            formatter: Table.api.formatter.buttons
                            // events: Table.api.events.operate,
                            // formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        detail: function () {
            //预约关联跳转
            $(".btn-appointment").on('click', function () {
                var id = $(this).attr('addtabs');//导航id
                var patient_in_member_id = $(this).attr('patient_in_member_id');//病人id
                var addtabsEle = top.window.$("ul.sidebar-menu li a[addtabs="+id+"]");//导航条元素
                if (addtabsEle.size() > 0) {
                    addtabsEle.trigger("click");
                    var addtabsIframeEle =  top.window.$("iframe[src='/admin/user/appointment?addtabs=1']");//对应的选项卡iframe
                    //把病人id赋值给新增预约页面
                    addtabsIframeEle.attr('patient_in_member_id',patient_in_member_id);
                    addtabsIframeEle.contents().find('.btn-add-appointment').trigger("click");//跨iframe参数传不过去？
                }
            });
            //挂号跳转
            $(".btn-register").on('click', function () {
                var id = $(this).attr('addtabs');//导航id
                var patient_in_member_id = $(this).attr('patient_in_member_id');//病人id
                var addtabsEle = top.window.$("ul.sidebar-menu li a[addtabs="+id+"]");//导航条元素
                if (addtabsEle.size() > 0) {
                    addtabsEle.trigger("click");
                    var addtabsIframeEle =  top.window.$("iframe[src='/admin/user/register?addtabs=1']");//对应的选项卡iframe
                    console.log(addtabsIframeEle)
                    console.log(patient_in_member_id)
                    //把病人id赋值给新增预约页面
                    addtabsIframeEle.attr('patient_in_member_id',patient_in_member_id);
                    addtabsIframeEle.contents().find('.btn-add-appointment').trigger("click");//跨iframe参数传不过去？
                }
            });
            //付费按钮
            $(document).on('click', '.btn-pay', function () {
                var chargeInfo = JSON.parse($(this).attr("chargeInfo"));
                if (chargeInfo.should_pay > 0) {
                    Layer.open({
                        content: Template("pay",chargeInfo),
                        area: ['500px', '410px'],
                        title: "支付",
                        resize: false,
                        yes: function () {
                            var tipmsg = '';
                            var already_paid = $(".already_paid").val();
                            var pay_way = $("#mytab.nav-pills > li.active > a").attr('pay_way');
                            var paywayobj ={
                                1:'cash',//现金
                                2:'zhifubao',//支付宝
                                3:'weixin',   //微信
                                4:'iscard'//卡内
                            };
                            tipmsg = '您需要'+__(paywayobj[pay_way])+'支付'+ chargeInfo.should_pay +'元';
                            //组合卡内余额
                            if($('#iscard').prop("checked")){
                                //卡内余额不足，弹出提示信息
                                if(chargeInfo.balance < chargeInfo.should_pay){
                                    var extpay = chargeInfo.should_pay-chargeInfo.balance;
                                    tipmsg = '卡内余额不足,需'+__(paywayobj[pay_way])+'另付款'+ extpay +'元';
                                    if (pay_way != 1){
                                        tipmsg += '<div style="text-align: center;"><img class=""  src="/assets/img/zhifu/'+paywayobj[pay_way]+'.png" /></div>';
                                    }
                                    pay_way = $('#iscard').val()+','+pay_way;
                                }else{
                                    pay_way = $('#iscard').val();
                                    tipmsg = '卡内扣款'+chargeInfo.should_pay+'元';
                                }
                            }
                            //防止确定键重复点击
                            var forbidendbc = false;
                            Layer.confirm(tipmsg, {
                                btn: ['确定','取消'] //按钮
                            }, function(){
                                if (forbidendbc){
                                    return;
                                }
                                forbidendbc = true;
                                $.ajax({
                                    type: 'post',
                                    dataType: 'json',
                                    url: 'user/chargeinfo/pay',
                                    data: {id:chargeInfo.id,pay_way:pay_way,already_paid:already_paid},
                                    success:function (ret) {
                                        Layer.closeAll();
                                        // Layer.alert(ret.msg);
                                        forbidendbc = false;
                                        location.reload();//刷新
                                    },
                                    error:function (e) {
                                        Backend.api.toastr.error(e.message);
                                        forbidendbc = false;
                                    }
                                });
                            }, function(){
                                return;
                            });
                        }
                    });
                }else{
                    Layer.alert("没有未付款项");
                }
            });

            //确认预约
            $(".btn-appointment-confirm").on('click', function () {
                var appointment_id = $(this).attr('appointment_id');//预约id
                $.ajax({
                    url: "/admin/user/appointment/appointmentConfirm/ids/"+appointment_id,
                    type: "GET",
                    success: function(ret) {
                        Layer.alert(ret.msg);
                        window.location.reload();
                    }
                });
            });
        },
        visit: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    visit_list_url: 'user/patientvisitrecord/visit/patientInMemberId/'+Config.patientInMemberInfo.id,
                    table: 'patient_visit_record',
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
                url: $.fn.bootstrapTable.defaults.extend.visit_list_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {field: 'createtime', title:'来访时间',
                            formatter:function (value,row,index) {
                                return value ? Moment(parseInt(value) * 1000).format("YYYY-MM-DD") : __('None');
                            }
                        },
                        {field: 'doctor_appointment_name', title:'预约医生'},
                        {field: 'admin_name', title:'操作者'},
                        {field: 'treatment_department', title: __('Treatment_department'),
                            formatter:function (value, row, index){
                                return Config.treatment_department[value];
                            }
                        },
                    ]
                ]
            });
            //返回会员详情按钮
            $('.btn-return-detail').on('click',function () {
                window.location.href = '/admin/user/member/detail/ids/'+Config.patientInMemberInfo.member_id;
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatCommonChoose:function (vObjCol,searchList) {
                var sprintf = $.fn.bootstrapTable.utils.sprintf;
                var htmlForm = [];
                var isArray = vObjCol.searchList.constructor === Array;
                var searchListHtml = [];
                searchListHtml.push(sprintf('<option value="">%s</option>', vObjCol.placeholder));
                $.each(searchList, function (key, value) {
                    var isSelect = (isArray ? value : key) == vObjCol.defaultValue ? 'selected' : '';
                    searchListHtml.push(sprintf("<option value='" + (isArray ? value : key) + "' %s>" + value + "</option>", isSelect));
                });
                htmlForm.push(sprintf('<select class="form-control" name="%s" %s>%s</select>', vObjCol.field, "", searchListHtml.join('')));
                return htmlForm.join('')
            }
        }
    };
    return Controller;
});