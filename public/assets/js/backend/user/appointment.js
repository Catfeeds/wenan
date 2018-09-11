define(['jquery', 'bootstrap', 'form','template', 'bootstrap-datetimepicker','fullcalendar', 'jquery-ui'], function ($, undefined, Form, Template) {

    var Controller = {
        index: function () {
            //新增预约按钮
            $('.btn-add-appointment').on('click',function (event) {
                var patient_in_member_id =top.window.$("iframe[src='/admin/user/appointment?addtabs=1']").attr('patient_in_member_id');
                doctor_id = $("#c-doctor_id").val();
                Controller.api.openAddAppointment(Moment().unix(),doctor_id,patient_in_member_id);
                top.window.$("iframe[src='/admin/user/appointment?addtabs=1']").removeAttr('patient_in_member_id')
            });
            //是否有从患者页面传来的病人id，有则打开新增页面
            var patient_in_member_id =top.window.$("iframe[src='/admin/user/appointment?addtabs=1']").attr('patient_in_member_id');
            if (typeof patient_in_member_id !='undefined'){
                $('.btn-add-appointment').trigger("click");
            }
            //fullcalendar
            Controller.api.getfullcalendar();
            //切换医生事件
            $('#c-doctor_id').on('change',function () {
                $('#calendar').fullCalendar('refetchEvents');
            });
           //事件拖拽
            $('#external-events div.external-event').each(function () {
                var eventObject = {
                    title: $.trim($(this).text())
                };
                $(this).data('eventObject', eventObject);
                $(this).data('duration', '00:30'); // 1 hours
                $(this).draggable({
                    zIndex: 1070,
                    revert: true,
                    revertDuration: 0,
                    opacity:0
                })
            });

        },
        add: function () {
            var forbiddenDbclick = false;//防止连续点击查询客户按钮

            //查询客户
            $(document).on('click', ".btn-search-icon", function () {
                if (forbiddenDbclick){
                    return false;
                }
                forbiddenDbclick = true;
                var term = $("#c-member").val();
                $("#ul-members").empty();
                $.ajax({
                    url: "user/member/getMember",
                    type: "POST",
                    dataType: "json",
                    data: {
                        keyword: term
                    },
                    success: function(ret) {
                        forbiddenDbclick = false;
                      if(ret.length>0){
                          var item = '';
                          $.each(ret, function (i, j) {
                              item += '<li class="list-group-item li-member" data-member = '+JSON.stringify(j)+'><span>' + j.label+ '</span><span style="padding-left:10px;">' + j.telphone+ '</span></li>';
                          });
                          $("#ul-members").append(item);
                      }
                    }
                });
            });

            //点击查询出的病人
            $(document).on('click', ".li-member", function () {
                var member = $(this).data('member');
                $("#c-name").val(member.label);
                $("#c-member_id").val(member.id);
                $("#c-patient_in_member_id").val(member.patientInMemberId);
                $("#c-telphone").val(member.telphone);
                $('#c-gender').selectpicker('val', member.gender);
                $("#ul-members").empty();
            });

            //清空搜索出的病人
            $(document).on('click', function () {
                $("#ul-members").empty();
            });

            $('#c-doctor_id').change(function(){
                var selectedId = $(this).find('option:selected').val();
                // console.log(selectedId)
                $.ajax({
                    url: "user/appointment/getAppointmentTime",
                    type: "GET",
                    data: {
                        doctorId:selectedId,
                    },
                    success: function(ret) {
                        console.log(ret.start_time);

                        $('#c-start_time').val(Moment(ret.start_time*1000).format('HH:mm'));
                        $('#c-end_time').val(Moment(ret.end_time*1000).format('HH:mm'));
                        $('#c-start_time').datetimepicker('stepping',ret.interval_time);
                        // $('#c-end_time').datetimepicker('minDate',ret.end_time);
                    }
                });            
            });
            //预约时间验证
            Controller.api.appointmentValidator();

            //更新select
            Controller.api.buildSelcetChange();
            Controller.api.bindevent();
        },
        edit: function () {

            //预约时间验证
            Controller.api.appointmentValidator();

            Controller.api.buildSelcetChange();
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                var form = $("form[role=form]");
                var success = function (data, ret) {
                    //提示及关闭当前窗口
                    var msg = ret.hasOwnProperty("msg") && ret.msg !== "" ? ret.msg : __('Operation completed');
                    parent.Toastr.success(msg);
                    if (ret.data.tag !=1){
                        parent.$('#calendar').fullCalendar('refetchEvents');
                    }else{
                        parent.$(".btn-refresh").trigger("click");
                    }
                    // parent.$(".fc-refresh-button").trigger("click");
                    var index = parent.Layer.getFrameIndex(window.name);
                    parent.Layer.close(index);
                    parent.Layer.close(Layer.index);
                };
                Form.api.bindevent(form, success);
            },
            getfullcalendar:function () {
                var monthNames = ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
                    dayNames = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'],
                    status = {0: '取消看诊', 1:'预约中', 2:'看诊中', 3:'看诊结束'},
                    doctor_id = $("#c-doctor_id").val();
                businessHours = [
                    {
                        dow:[0,2,3,4,5,6],
                        end:'18:00',
                        start:'09:00'
                    },
                ];
                $('#calendar').fullCalendar({
                    /**
                     *  默认显示的视图
                     *  month       一页显示一月, 日历样式
                     basicWeek   一页显示一周, 无特殊样式
                     basicDay    一页显示一天, 无特殊样式
                     agendaWeek  一页显示一周, 显示详细的24小时表格
                     agendaDay   一页显示一天, 显示详细的24小时表格
                     listYear    年列表（V3.*支持）
                     listMonth   月列表（V3.*支持）
                     listWeek    周列表（V3.*支持）
                     listDay     日列表（V3.*支持）
                     */
                    customButtons: {            //自定义header属性中按钮[customButtons与header并用]
                        add: {
                            text: '新增预约',
                            click: function() { //patient_in_member_id患者表直接跳转用到
                                $('.btn-add-appointment').trigger("click");
                            }
                        },
                        refresh: {
                            text: '刷新',
                            click: function() {
                                $('#calendar').fullCalendar('refetchEvents');
                            }
                        },
                        month: {
                            text: '月',
                            click: function() {
                                $('#calendar').fullCalendar('changeView', 'month');
                                $('#calendar').fullCalendar('refetchEvents');
                            }
                        },
                        agendaWeek: {
                            text: '周',
                            click: function() {
                                $('#calendar').fullCalendar('changeView', 'agendaWeek');
                                $('#calendar').fullCalendar('refetchEvents');
                            }
                        },
                        listWeek: {
                            text: '周列表',
                            click: function() {
                                $('#calendar').fullCalendar('changeView', 'listWeek');
                                $('#calendar').fullCalendar('refetchEvents');
                            }
                        },
                        agendaDay: {
                            text: '日',
                            click: function() {
                                $('#calendar').fullCalendar('changeView', 'agendaDay',Moment().format('YYYY-MM-DD'));
                                $('#calendar').fullCalendar('refetchEvents');
                            }
                        },
                        prev: {
                            text: '<',
                            click: function() {
                                $('#calendar').fullCalendar('prev');
                                if ($('#calendar').fullCalendar('getView').name == 'agendaDay'){
                                    $('#calendar').fullCalendar('refetchEvents');
                                }
                            }
                        },
                        next: {
                            text: '>',
                            click: function() {
                                $('#calendar').fullCalendar('next');
                                if ($('#calendar').fullCalendar('getView').name == 'agendaDay'){
                                    $('#calendar').fullCalendar('refetchEvents');
                                }
                            }
                        },
                    },

                    header:{
                        left:'refresh,add,prev,next,today',
                        center:'title',
                        right:'month,agendaWeek,agendaDay,listWeek',
                    },
                    views: {
                        basic: {
                            titleFormat: 'YYYY-MM-DD'
                        },
                        agenda: {
                            titleFormat: 'YYYY/MM/DD',
                        },
                        month: {
                            titleFormat: 'YYYY-MM'
                        },
                        week: {
                            titleFormat: 'YYYY/MM/DD'
                        },
                        day: {
                            titleFormat: 'YYYY-MM-DD'
                        }
                    },
                    buttonText: {today:'今天',month:'月',week:'周',day:'日',listWeek:'周列表'},  //对应顶部操作按钮的名称自定义
                    defaultView: 'agendaWeek',
                    timezone: 'local',
                    // aspectRatio :1.35,        //宽度:高度 比例，默认1.35，可自定义 值越小高度越高
                    // navLinks: true,
                    selectable:true,
                    editable: true,
                    droppable: true,
                    // dragOpacity:0,
                    // snapDuration :10,
                    selectOverlap: true,
                    firstDay: 1,
                    allDaySlot: false,
                    // snapDuration : "1:00:00",      //其实就是动态创建一个日程时，默认创建多长的时间
                    slotMinutes: 30, //一格时间槽代表多长时间，默认00:30:00（30分
                    // defaultEventMinutes: 30,//在Event Object中如果没有end参数时使用，如start=7:00pm，则该日程对象时间范围就是7:00~9:00
                    agendaEventMinHeight: 18.4,
                    slotLabelFormat: 'HH:mm',
                    timeFormat: 'HH:mm',           //全局的日期显示格式(自定义成如12:00或12am等)
                    slotEventOverlap: true,       //相同时间段的多个日程视觉上是否允许重叠，默认true允许
                    displayEventTime: true,       //每一个日程块中是否显示时间，默认true显示
                    displayEventEnd: true,         //是否显示日程块中的“结束时间”，默认true，如果false则只显示开始时间
                    nowIndicator: true,            //周/日视图中显示今天当前时间点（以红线标记），默认false不显示
                    eventStartEditable: false,      //Event日程开始时间可以改变，默认true，如果是false其实就是指日程块不能随意拖动，只能上下拉伸改变他的endTime
                    eventDurationEditable: false,  //Event日程的开始结束时间距离是否可以改变，默认true，如果是false则表示开始结束时间范围不能拉伸，只能拖拽
                    // firstHour:8,
                    // minTime:'06:00',
                    // maxTime:'24:00',
                    scrollTime:'07:00:00',
                    monthNames: monthNames,
                    monthNamesShort: monthNames,
                    dayNames: dayNames,
                    dayNamesShort: dayNames,
                    // selectConstraint : [],        //限制用户选择指定时间段的日程数据：an event ID, "businessHours", object
                    events: function(start, end, timezone, callback) {
                        //获取视图类型
                        doctor_id = $("#c-doctor_id").val();
                       if ($('#calendar').fullCalendar('getView').name == 'agendaDay'){
                           doctor_id = '';
                           var moment = $('#calendar').fullCalendar('getDate');
                           start = Moment(moment.format("YYYY-MM-DD"));
                           end = Moment(moment.format("YYYY-MM-DD")).add(1,'days');
                       }
                        $.ajax({
                            url: 'user/appointment/getAppointmentEvents',
                            type:'post',
                            dataType: 'json',
                            data: {
                                start: start.unix(),
                                end: end.unix(),
                                doctor_id: doctor_id,
                            },
                            success: function (ret) {
                                var events = [];
                                //events事件渲染
                                $.each(ret.data.events,function (index,item) {
                                    var title,
                                        backgroundColor = '#18bc9c';
                                    if (item.is_occupy){
                                        title = '取消占用';
                                        backgroundColor = '#f39c12';
                                        // borderColor
                                    }else{
                                        item['statusFormat'] = status[item['status']];//预约状态判断
                                        if (item['end_time']<Moment().unix()){
                                            item['statusFormat'] = status[3];
                                        }
                                        if (item.status == 1 && (item.start_time < (Moment().unix()-5*60))){
                                            backgroundColor = 'red';
                                        }
                                        title = item.name + item['statusFormat']
                                    }
                                    // if(item.start_time<Moment().unix()){
                                    //     item.status = 3;
                                    // }
                                    events.push({
                                        title: title ,
                                        start: Moment.unix(item.start_time).format('YYYY-MM-DD HH:mm'),
                                        end:Moment.unix(item.end_time).format('YYYY-MM-DD HH:mm'),
                                        id :item.id,
                                        item:item,
                                        backgroundColor:backgroundColor
                                    });
                                });
                                callback(events);
                                // $('#calendar').fullCalendar('option', 'businessHours', businessHours);
                                $('#calendar').fullCalendar('option', 'businessHours', ret.data.businessHours);
                            }
                        });
                    },
                    drop: function (startDate, allDay) {
                        var startUnix = startDate.unix(),
                            slotMinutes = $('#calendar').fullCalendar('option','slotMinutes'),//一格所占时间（分钟）
                            endUnix = startUnix+slotMinutes*60,
                            dow= startDate.day(),
                            start = startDate.format("HH:mm");
                        //判断是否可以点击
                        if(Controller.api.appointmentTimeCheck(start,endUnix,dow)){
                            return false;
                        }
                        var originalEventObject = $(this).data('eventObject'),
                            copiedEventObject = $.extend({}, originalEventObject);
                        if (copiedEventObject.title=='预约'){
                            Controller.api.openAddAppointment(startUnix, $("#c-doctor_id").val());
                        }
                        if (copiedEventObject.title=='占用'){
                            Controller.api.appointmentOccupy(doctor_id,startUnix,endUnix);
                        }
                    },
                    select: function(startDate, endDate, allDay, jsEvent, view){

                        var dow= startDate.day(),
                            start = startDate.format("HH:mm"),
                            endUnix = endDate.unix();
                        //判断是否可以点击
                        if(Controller.api.appointmentTimeCheck(start,endUnix,dow)){
                            return false;
                        }
                        //占用预约弹框
                        Layer.confirm('预约或占用该时间段？', {
                            btn: ['预约','占用'] //按钮
                        }, function(){
                            Controller.api.openAddAppointment(startDate.unix(),doctor_id);
                        }, function(){
                            //占用
                            Controller.api.appointmentOccupy(doctor_id,startDate.unix(),endDate.unix());
                        });
                    },
                    eventClick: function(event, jsEvent, view) {//日程区块，单击时触发
                        var item = event.item;
                        if (item.is_occupy){
                            //判断是否可以点击
                            var dow= Moment(event.start).day(),
                                start = Moment(event.start).format("HH:mm"),
                                endUnix = Moment(event.end).unix();
                            if(Controller.api.appointmentTimeCheck(start,endUnix,dow)){
                                Toastr.error('过去时间或休息时间不可取消占用');
                                return false;
                            }
                            Controller.api.occupyCancel(item.id);
                        }else{
                            item['Moment'] = Moment;
                            // item['statusFormat'] = status[item['status']];//预约状态判断
                            // if (item['start_time']>time()){
                            //     item['statusFormat'] = status[3];
                            // }
                            item['project_name'] = Config.project_type[item['project_type']];

                            //如果不是正常状态的预约
                            var btn = ['确认预约','取消预约','修改预约'];
                            var skin = 'layui-layer-fast';
                            if (item.status !==1){//状态不为已预约
                                skin += ' layui-layer-lan';//按钮全部置灰
                            }else if(item.status ==1 && item.start_time < Moment().unix()){//状态为已预约，并且预约开始时间小于系统当前时间
                                btn = ['确认预约','取消预约'];
                            }
                            //预约弹框
                            Layer.confirm( Template("appointmentInfo",item), {
                                skin:skin,
                                btn: btn, //按钮
                                btn3:function () {
                                    Controller.api.openEditAppointment(item.id);
                                }
                            }, function(){
                                if (item.start_time > Moment().unix()){//未到预约开始时间，不可确认
                                    Layer.msg('未到预约时间，不可确认');
                                    return false;
                                }
                                Controller.api.appointmentConfirm(item.id);
                            }, function(){
                                Controller.api.appointmentCancel(item.id);
                            });
                        }
                        //未到预约时间，确认预约按钮置灰
                        if (item.start_time > Moment().unix() && item.status ==1){//未到预约开始时间，不可确认
                            if(!$('.layui-layer-btn0').hasClass('btn disabled')){
                                $('.layui-layer-btn0').addClass('btn disabled')
                            }
                        }else{
                            if($('.layui-layer-btn0').hasClass('btn disabled')){
                                $('.layui-layer-btn0').removeClass('btn disabled')
                            }
                        }
                        //状态不为已预约，按钮全部置灰

                    }
                });
            },
            openAddAppointment:function (start_time,doctor_id,patient_in_member_id) {
                 var url = 'user/appointment/add';
                 var appointTime = start_time;
                 var params = '/appointTime/'+appointTime+'/doctorId/'+doctor_id;
                 if (typeof patient_in_member_id != 'undefined'){ //从患者表中直接跳转用到患者
                     params += '/patientInMemberId/'+patient_in_member_id
                 }
                Layer.close(Layer.index);//关闭弹框
                 Fast.api.open(url+params, '新增预约', {});
            },
            openEditAppointment:function (id) {
                var url = 'user/appointment/edit';
                var params = '/ids/'+id;
                Fast.api.open(url+params, '编辑预约', {});
            },
            appointmentOccupy:function (doctor_id,startUnix,endUnix) {
                //占用
                $.ajax({
                    url: "user/appointment/appointmentOccupy",
                    type: "POST",
                    dataType: "json",
                    data: {
                        doctor_id:doctor_id,
                        start_time:startUnix,
                        end_time :endUnix
                    },
                    success: function(data) {
                        $('#calendar').fullCalendar('refetchEvents');
                    }
                });
            },
            appointmentCancel:function (id) {
                $.ajax({
                    url: "user/appointment/appointmentCancel/ids/"+id,
                    type: "GET",
                    dataType: "json",
                    success: function(ret) {

                        Toastr.success(ret.msg);
                        $('#calendar').fullCalendar('refetchEvents');
                        Controller.api.updateUnConfirmNum(ret.data.unConfirmNum);
                    }
                });
            },
            occupyCancel:function(id){
                $.ajax({
                    url: "user/appointment/occupyCancel",
                    type: "POST",
                    dataType: "json",
                    data: {id: id,},
                    success: function(ret) {
                        Toastr.success(ret.msg);
                        $('#calendar').fullCalendar('refetchEvents');
                    }
                });
            },
            appointmentConfirm:function(id){
                $.ajax({
                    url: "user/appointment/appointmentConfirm/ids/"+id,
                    type: "GET",
                    success: function(ret) {
                        $('#calendar').fullCalendar('refetchEvents');
                        Controller.api.updateUnConfirmNum(ret.data.unConfirmNum);
                        Layer.close(Layer.index);
                    }
                });
            },
            appointmentTimeCheck:function (start,endUnix,dow) {
                //1.是否为过去时间
                if(endUnix < Moment().unix()){
                    return true;
                }
                //2.是否处于工作时间
                var businessHours = $('#calendar').fullCalendar('option', 'businessHours'),
                    coditions = new Array();
                $.each(businessHours,function (index,item) {
                    if($.inArray(dow, item.dow)!==-1){
                        if (start>=item.start && start<item.end){
                            coditions.push('true');
                        }else{
                            coditions.push('false');
                        }
                    }else{
                        coditions.push('false');
                    }
                });
                if($.inArray('true', coditions)=='-1'){
                    return true;
                }
            },
            buildSelcetChange:function () {
                $('#c-doctor_id').on('hidden.bs.select', function (e) {
                    var selectedText = $(this).find('option:selected').text();
                    $('#c-doctor_name').val(selectedText)
                });
            },
            updateUnConfirmNum:function (unConfirmNum) {
                var unConfirmNum = parseInt(unConfirmNum);
                if (unConfirmNum>0){
                    $('#unConfirmNum').empty().append('<label>您有'+unConfirmNum+'条预约未确认</label>')
                }else{
                    $('#unConfirmNum').empty();
                }
            },
            //预约时间格式化
            appointmentValidator:function () {
                //预约日期
                var dateOption = {
                    format: 'YYYY-MM-DD',
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
                    minDate:Moment().format("YYYY-MM-DD"),
                    // maxDate:Moment().add(7,'days').format("YYYY-MM-DD"),
                    // disabledDates:['2018-03-30'],
                };
                //挂号预约只能挂今天的号
                if (Config.tag == 1){
                    dateOption.maxDate = Moment().format("YYYY-MM-DD");
                }
                $('#c-day').datetimepicker(dateOption).on('dp.change',function () {
                    var date = $(this).val();
                    if ($(this).val() > Moment().format("YYYY-MM-DD")){
                        $('#c-start_time').datetimepicker('minDate',0);
                        $('#c-end_time').datetimepicker('minDate',0);
                    }else{
                        $('#c-start_time').datetimepicker('minDate',Moment());
                        $('#c-end_time').datetimepicker('minDate',Moment().add(Config.appointTimeArr.interval_time,'minutes'));
                    }
                });

                //预约开始时间
                var startTimeOption = {
                    format: 'HH:mm',
                    stepping:Config.appointTimeArr.interval_time,

                };
                //预约结束时间
                var endTimeOption = {
                    format: 'HH:mm',
                    stepping:Config.appointTimeArr.interval_time,
                };
                if ( $('#c-day').val() <= Moment().format("YYYY-MM-DD")){
                    startTimeOption.minDate = Moment();
                    endTimeOption.minDate = Moment().add(Config.appointTimeArr.interval_time,'minutes');
                }
                $('#c-start_time').datetimepicker(startTimeOption).on('dp.change',function () {
                    var date = $('#c-day').val();
                    var startTime = $(this).val();
                    var endTime = Moment(date+' '+startTime).add(Config.appointTimeArr.interval_time,'minutes').format('HH:mm')
                    $('#c-end_time').val(endTime);
                });
                $('#c-end_time').datetimepicker(endTimeOption).on('dp.change',function () {
                    var date = $('#c-day').val();
                    var endTime = $(this).val();
                    var startTime = Moment(date+' '+endTime).subtract(Config.appointTimeArr.interval_time,'minutes').format('HH:mm');
                    $('#c-start_time').val(startTime);
                });

                $('#c-start_time').val(Moment(Config.appointTimeArr.start_time*1000).format('HH:mm'));
                $('#c-end_time').val(Moment(Config.appointTimeArr.end_time*1000).format('HH:mm'));
                $('form#add-form').data("validator-options",{
                    rules:{
                        appointmentValidator: function(element, params) {
                            console.log(element);//为何执行两遍？
                            return  $('#c-start_time').val() <  $('#c-end_time').val() || '结束时间必须大于开始时间';
                        }
                    }
                })
            }
        }
    };
    return Controller;
});