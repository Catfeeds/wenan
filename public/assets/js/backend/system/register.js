define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'bootstrap-datetimepicker'], function ($, undefined, Backend, Table, Form, Datetimepicker) {

    var Controller = {
        index: function(){
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
                pagination: false,
                //toolbarAlign: 'right',
                extend: {
                    index_url: 'system/register/index',
                    //add_url: 'system/register/edit',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: [
                    [
                        {field: 'name', title: '', cellStyle: {classes: 'docInfo'}},
                        {field: 'morning', title: '上午', cellStyle: {classes: 'showDetail'}},
                        {field: 'afternoon', title: '下午', cellStyle: {classes: 'showDetail'}},
                        {field: 'evening', title: '晚上', cellStyle: {classes: 'showDetail'}}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            var date, dd = new Date(), showDate;
            var year = dd.getFullYear(), month = dd.getMonth() + 1, day = dd.getDate();
            function mGetDate(myyear, mymonth, myday, addDayCount) {
                //console.log(year + '||' + month + '||' + day + '||mGetDate1');
                dd = new Date(myyear, mymonth - 1, myday);
                dd.setDate(dd.getDate() + addDayCount);//获取AddDayCount天后的日期
                year = dd.getFullYear();
                month = dd.getMonth() + 1;
                if (month < 10) {
                    mymonth = '0' + month;
                } else {
                    mymonth = month;
                }
                day = dd.getDate();
                if (day < 10) {
                    myday = '0' + day;
                } else {
                    myday = day;
                }
                date = year + '-' + mymonth + '-' + myday;
                showDate = year + '/' + mymonth + '/' + myday;
                //console.log(year + '||' + month + '||' + day + '||mGetDate2');
            }
            mGetDate(year, month, day, 0);
            var currentDay = Number(date.replace(/-/g, ""));

            /**
             * 下面是一些基础函数，解决mouseover与mouserout事件不停切换的问题（问题不是由冒泡产生的）
             */
            function checkHover(e, target) {
                if (getEvent(e).type == "mouseover") {
                    return !contains(target, getEvent(e).relatedTarget
                        || getEvent(e).fromElement)
                        && !((getEvent(e).relatedTarget || getEvent(e).fromElement) === target);
                } else {
                    return !contains(target, getEvent(e).relatedTarget
                        || getEvent(e).toElement)
                        && !((getEvent(e).relatedTarget || getEvent(e).toElement) === target);
                }
            }

            function contains(parentNode, childNode) {
                if (parentNode.contains) {
                    return parentNode != childNode && parentNode.contains(childNode);
                } else {
                    return !!(parentNode.compareDocumentPosition(childNode) & 16);
                }
            }
            //取得当前window对象的事件
            function getEvent(e) {
                return e || window.event;
            }

            $(function(){
                var params = {date: date, hos_id: Config.hospital.id};
                //console.log(Config);
                var edit_attr = table.data("operate-edit");
                var toolhtml = [];
                toolhtml.push('<div class="bs-bars pull-left" style="font-size:20px;">');
                toolhtml.push('<div id="toolbar" class="toolbar"><a href="javascript:;" class="btn btn-primary btn-refresh hidden"><i class="fa fa-refresh"></i></a></div>');
                if (Config.admin.group_id == 1) {
                    toolhtml.push(Config.selHtml);
                } else {
                    toolhtml.push(Config.hospital.hos_name);
                }
                toolhtml.push('<span style="margin-left:350px;"></span>');
                toolhtml.push('<input type="button" class="select-day btn btn-success btn-embossed" class="btn btn-success btn-embossed" value="" style="width:50px;"/>');
                toolhtml.push('<input type="button" id="select-day" class="btn btn-success btn-embossed" value="日期" style="margin-left:-50px;"/>');
                toolhtml.push('<input type="button" id="prev-day" class="btn btn-success btn-embossed" value="<" style="margin-left:20px;"/>');
                toolhtml.push('<span class="btn btn-success btn-embossed" id="current-day" style="margin-left: 20px;width: 140px;">' + showDate + '</span>');
                toolhtml.push('<input type="button" id="next-day" class="btn btn-success btn-embossed" value=">" style="margin-left: 20px;"/>');
                toolhtml.push('</div>');
                $('.fixed-table-toolbar').append(toolhtml.join(''));

                function refreshDate() {
                    $("#current-day").html(showDate);
                    $('.select-day').val(date);
                    params.date = date;
                    table.bootstrapTable("refresh", {query: params});
                }

                // 刷新按钮事件
                $(".fixed-table-toolbar").on('click', Table.config.refreshbtn, function () {
                    table.bootstrapTable("refresh", {query: params});
                });

                $(".fixed-table-toolbar").on('change', '#hos_id', function(){
                    params.hos_id = $(this).val();
                    refreshDate();
                });

                $(".fixed-table-toolbar").on('click', '#prev-day', function(){
                    //console.log(year + '||' + month + '||' + day + '||prev1');
                    mGetDate(year, month, day, -1);
                    //console.log(year + '||' + month + '||' + day + '||prev2');
                    refreshDate();
                });

                $(".fixed-table-toolbar").on('click', '#next-day', function(){
                    //console.log(year + '||' + month + '||' + day + '||next1');
                    mGetDate(year, month, day, +1);
                    //console.log(year + '||' + month + '||' + day + '||next2');
                    refreshDate();
                });

                $('.select-day').datetimepicker({
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
                    //debug: true
                    //datepickerInput: '#current-day'
                });
                $('.select-day').on("dp.hide",function(data){
                    //console.log(data);
                    var selyear = data.date.year();
                    var selmonth = data.date.month();
                    selmonth = selmonth + 1;
                    var selday = data.date.date();
                    //console.log(selyear + '||' + selmonth + '||' + selday + '||select1');
                    if (selyear == year && selmonth == month && selday == day) {
                        return false;
                    }
                    year = selyear;
                    month = selmonth;
                    day = selday;
                    mGetDate(year, month, day, 0);
                    //console.log(year + '||' + month + '||' + day + '||select2');
                    refreshDate();
                });

                $(".fixed-table-toolbar").on('click', '#select-day', function(){
                    $('.select-day').datetimepicker('show');
                });

                table.on('mouseover', '.showDetail', function(e){
                    if (checkHover(e,this)) {
                        //console.log('showDetail-mouseover');
                        var detailHtml = '', detailCss = [], doctorCss = [];
                        var trSize = table.find('tr').size(), index = $(this).parent().data('index');
                        var ids = $(this).parent().children('.docInfo').data('ids').toString(), names = $(this).parent().children('.docInfo').data('names').toString(), regnums = $(this).data('regnums').toString();
                        var isNewDay = Number(date.replace(/-/g, "")) >= currentDay ? true : false;

                        if (trSize - index == 2) {
                            detailCss.push('top: -32px;');
                        } else {
                            detailCss.push('top: 32px;');
                        }
                        detailCss.push('left: 0px;');
                        detailCss.push('width: ' + $(this).outerWidth() + 'px;');
                        detailCss.push('position: absolute;');
                        detailCss.push('z-index: 100;');
                        detailCss.push('background-color: #ffffff;');
                        detailCss.push('border: 1px solid #f9eded;');
                        detailCss = detailCss.join('');

                        doctorCss.push('height: 30px;');
                        doctorCss.push('line-height: 30px;');
                        doctorCss = doctorCss.join('');

                        detailHtml += '<div class="departs" style="' + detailCss + '">';

                        if (ids != '') {
                            ids = ids.split(',');
                            names = names.split(',');
                            regnums = regnums.split(',');
                            for (var i = 0; i < ids.length; i++) {
                                if (ids[i] != '') {
                                    detailHtml += '<div class="doctor" style="' + doctorCss + '"><li>' + names[i] + '</li><li>' + regnums[i] + '</li><li>';
                                    if (edit_attr && isNewDay) {
                                        detailHtml += '<a href="javascript:;" class="btn btn-xs btn-success editone" data-id="' + ids[i] + '" title=""><i class="fa fa-pencil"></i></a>';
                                    }
                                    detailHtml += '</li></div>';
                                }
                            }
                        }
                        detailHtml += '</div>';

                        $(this).css('background-color', '#666666');
                        $(this).append(detailHtml);
                    }
                });

                table.on('mouseout', '.showDetail', function(e){
                    if (checkHover(e,this)) {
                        //console.log('showDetail-mouseout');
                        var index = $(this).parent().data('index');
                        //console.log(index + '||index');
                        if (index % 2 == 0) {
                            $(this).css('background-color', '#f9f9f9');
                        } else {
                            $(this).css('background-color', '');
                        }
                        $('.departs').remove();
                    }
                });

                //编辑医生挂号
                table.on('click', '.editone', function(){
                    var ids = $(this).data('id');
                    Fast.api.open('system/register/edit/ids/' + ids + '/date/' + params.date, '挂号', {});
                });

                //挂号锁定
                table.on('click', '.lockone', function(){
                    var ids = $(this).data('id');
                    Fast.api.open('system/register/lock/hosId/' + params.hos_id + '/date/' + params.date + '/ids/' + ids, '挂号锁定', {});
                });

                //挂号锁定
                $(".fixed-table-toolbar").on('click', '.btn-lock', function(){
                    Fast.api.open('system/register/lock/hosId/' + params.hos_id + '/date/' + params.date, '挂号锁定', {});
                });
            })
        },
        edit: function(){
            Form.api.bindevent($("form[role=form]"));
            $(function(){
                var startDay = Config.work_day, endDay = Config.work_day;
                $('.datetimepicker').datetimepicker({
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
                    showClose: true
                });
                $('.datetimepicker').datetimepicker('minDate', Config.today);
                $('.datetimepicker').on("dp.classify",function(data){
                    //console.log(data);
                    var specDate = Config.specDate;
                    //console.log(specDate);
                    if (specDate == '') {
                        return false;
                    }
                    var selyear = data.date.year(), selmonth = data.date.month(), selday = data.date.date();
                    selmonth = selmonth + 1;
                    if (selmonth < 10) {
                        selmonth = '0' + selmonth;
                    }
                    if (selday < 10) {
                        selday = '0' + selday;
                    }
                    var day = selyear + '-' + selmonth + '-' + selday;
                    //console.log(day);
                    if (specDate.indexOf(day) != -1) {
                        data.classNames.push('spec');
                    }
                });
                $('#start_day').on("dp.hide",function(data){
                    //console.log(data);
                    var selyear = data.date.year();
                    var selmonth = data.date.month();
                    selmonth = selmonth + 1;
                    var selday = data.date.date();
                    if (selmonth < 10) {
                        selmonth = '0' + selmonth;
                    }
                    if (selday < 10) {
                        selday = '0' + selday;
                    }
                    //console.log(selyear + '||' + selmonth + '||' + selday + '||select1');
                    //if (startDay != selyear + '-' + selmonth + '-' + selday) {
                        $('.morning').html('<select class="form-control selectpicker" id="morning" data-rule="required" name="morning"><option value="">加载中...</option></select>');
                        $("#morning").selectpicker("refresh");
                        $('.afternoon').html('<select class="form-control selectpicker" id="afternoon" data-rule="required" name="afternoon"><option value="">加载中...</option></select>');
                        $("#afternoon").selectpicker("refresh");
                        $('.evening').html('<select class="form-control selectpicker" id="evening" data-rule="required" name="evening"><option value="">加载中...</option></select>');
                        $("#evening").selectpicker("refresh");
                        $.ajax({
                            url: "system/register/getDoctorRegister",
                            type: 'post',
                            dataType: 'json',
                            data: {startDay: selyear + '-' + selmonth + '-' + selday, doctor_id: $('#doctor_id').val(), type: 1},
                            success: function (ret) {
                                if (ret.hasOwnProperty("code")) {
                                    if (ret.code === 1) {
                                        $('.morning').html(ret.data.morning);
                                        $("#morning").selectpicker("refresh");
                                        $('.afternoon').html(ret.data.afternoon);
                                        $("#afternoon").selectpicker("refresh");
                                        $('.evening').html(ret.data.evening);
                                        $("#evening").selectpicker("refresh");
                                    } else {
                                        $('#morning').html('');
                                        $('#afternoon').html('');
                                        $('#evening').html('');
                                        Backend.api.toastr.error(ret.msg);
                                    }
                                }
                            }, error: function (e) {
                                $('#morning').html('');
                                $('#afternoon').html('');
                                $('#evening').html('');
                                Backend.api.toastr.error(e.message);
                            }
                        });
                    //}
                    startDay = selyear + '-' + selmonth + '-' + selday;
                    $('#end_day').val(startDay);
                    $('#end_day').datetimepicker('minDate', startDay);
                });
                $('#end_day').on("dp.hide",function(data){
                    //console.log(data);
                    var selyear = data.date.year();
                    var selmonth = data.date.month();
                    selmonth = selmonth + 1;
                    var selday = data.date.date();
                    if (selmonth < 10) {
                        selmonth = '0' + selmonth;
                    }
                    if (selday < 10) {
                        selday = '0' + selday;
                    }
                    //console.log(selyear + '||' + selmonth + '||' + selday + '||select1');
                    //if (endDay != selyear + '-' + selmonth + '-' + selday) {
                        $('.morning').html('<select class="form-control selectpicker" id="morning" data-rule="required" name="morning"><option value="">加载中...</option></select>');
                        $("#morning").selectpicker("refresh");
                        $('.afternoon').html('<select class="form-control selectpicker" id="afternoon" data-rule="required" name="afternoon"><option value="">加载中...</option></select>');
                        $("#afternoon").selectpicker("refresh");
                        $('.evening').html('<select class="form-control selectpicker" id="evening" data-rule="required" name="evening"><option value="">加载中...</option></select>');
                        $("#evening").selectpicker("refresh");
                        $.ajax({
                            url: "system/register/getDoctorRegister",
                            type: 'post',
                            dataType: 'json',
                            data: {startDay: startDay, endDay: selyear + '-' + selmonth + '-' + selday, doctor_id: $('#doctor_id').val(), type: 2},
                            success: function (ret) {
                                if (ret.hasOwnProperty("code")) {
                                    if (ret.code === 1) {
                                        $('.morning').html(ret.data.morning);
                                        $("#morning").selectpicker("refresh");
                                        $('.afternoon').html(ret.data.afternoon);
                                        $("#afternoon").selectpicker("refresh");
                                        $('.evening').html(ret.data.evening);
                                        $("#evening").selectpicker("refresh");
                                    } else {
                                        $('#morning').html('');
                                        $('#afternoon').html('');
                                        $('#evening').html('');
                                        Backend.api.toastr.error(ret.msg);
                                    }
                                }
                            }, error: function (e) {
                                $('#morning').html('');
                                $('#afternoon').html('');
                                $('#evening').html('');
                                Backend.api.toastr.error(e.message);
                            }
                        });
                    //}
                    endDay = selyear + '-' + selmonth + '-' + selday;
                });

                /*$('#depart_id').change(function(){
                    var depart_id = $(this).val(), hos_id = $('#hos_id').val();
                    $('.doctor_id').html('<select class="form-control selectpicker" id="doctor_id" data-rule="required" name="doctor_id"><option value="">加载中...</option></select>');
                    $("#doctor_id").selectpicker("refresh");
                    $.ajax({
                        url: "system/hospital/getHosDepDoctor",
                        type: 'post',
                        dataType: 'json',
                        data: {hos_id: hos_id, depart_id: depart_id},
                        success: function (ret) {
                            if (ret.hasOwnProperty("code")) {
                                if (ret.code === 1) {
                                    $('.doctor_id').html(ret.data);
                                    $("#doctor_id").selectpicker("refresh");
                                } else {
                                    $('#doctor_id').html('');
                                    Backend.api.toastr.error(ret.msg);
                                }
                            }
                        }, error: function (e) {
                            $('#depart_id').html('');
                            Backend.api.toastr.error(e.message);
                        }
                    });
                })*/
            })
        },
        lock: function(){
            Form.api.bindevent($("form[role=form]"));
            $(function(){
                var startDay = Config.work_day;
                $('.datetimepicker').datetimepicker({
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
                    //debug: true
                });
                $('.datetimepicker').datetimepicker('minDate', Config.today);
                $('.datetimepicker').on("dp.classify",function(data){
                    //console.log(data);
                    var specDate = Config.specDate;
                    //console.log(specDate);
                    if (specDate == '') {
                        return false;
                    }
                    var selyear = data.date.year(), selmonth = data.date.month(), selday = data.date.date();
                    selmonth = selmonth + 1;
                    if (selmonth < 10) {
                        selmonth = '0' + selmonth;
                    }
                    if (selday < 10) {
                        selday = '0' + selday;
                    }
                    var day = selyear + '-' + selmonth + '-' + selday;
                    //console.log(day);
                    if (specDate.indexOf(day) != -1) {
                        data.classNames.push('spec');
                    }
                });
                $('.datetimepicker').on("dp.hide",function(data){
                    //console.log(data);
                    var selyear = data.date.year();
                    var selmonth = data.date.month();
                    selmonth = selmonth + 1;
                    var selday = data.date.date();
                    if (selmonth < 10) {
                        selmonth = '0' + selmonth;
                    }
                    if (selday < 10) {
                        selday = '0' + selday;
                    }
                    //console.log(selyear + '||' + selmonth + '||' + selday + '||select1');
                    if (startDay != selyear + '-' + selmonth + '-' + selday) {
                        $('.morning_lock').html('<select class="form-control selectpicker" id="morning_lock" data-rule="required" name="morning_lock"><option value="">加载中...</option></select>');
                        $("#morning_lock").selectpicker("refresh");
                        $('.afternoon_lock').html('<select class="form-control selectpicker" id="afternoon_lock" data-rule="required" name="afternoon_lock"><option value="">加载中...</option></select>');
                        $("#afternoon_lock").selectpicker("refresh");
                        $('.evening_lock').html('<select class="form-control selectpicker" id="evening_lock" data-rule="required" name="evening_lock"><option value="">加载中...</option></select>');
                        $("#evening_lock").selectpicker("refresh");
                        $.ajax({
                            url: "system/register/getDoctorLock",
                            type: 'post',
                            dataType: 'json',
                            data: {startDay: selyear + '-' + selmonth + '-' + selday, doctor_id: $('#doctor_id').val()},
                            success: function (ret) {
                                if (ret.hasOwnProperty("code")) {
                                    if (ret.code === 1) {
                                        $('.morning_lock').html(ret.data.morning_lock);
                                        $("#morning_lock").selectpicker("refresh");
                                        $('.afternoon_lock').html(ret.data.afternoon_lock);
                                        $("#afternoon_lock").selectpicker("refresh");
                                        $('.evening_lock').html(ret.data.evening_lock);
                                        $("#evening_lock").selectpicker("refresh");
                                    } else {
                                        $('#morning_lock').html('');
                                        $('#afternoon_lock').html('');
                                        $('#evening_lock').html('');
                                        Backend.api.toastr.error(ret.msg);
                                    }
                                }
                            }, error: function (e) {
                                $('#morning_lock').html('');
                                $('#afternoon_lock').html('');
                                $('#evening_lock').html('');
                                Backend.api.toastr.error(e.message);
                            }
                        });
                    }
                    startDay = selyear + '-' + selmonth + '-' + selday;
                });

                /*$('#depart_id').change(function(){
                    var depart_id = $(this).val(), hos_id = $('#hos_id').val();
                    $('.doctor_id').html('<select class="form-control selectpicker" id="doctor_id" data-rule="required" name="doctor_id"><option value="">加载中...</option></select>');
                    $("#doctor_id").selectpicker("refresh");
                    $.ajax({
                        url: "system/hospital/getHosDepDoctor",
                        type: 'post',
                        dataType: 'json',
                        data: {hos_id: hos_id, depart_id: depart_id},
                        success: function (ret) {
                            if (ret.hasOwnProperty("code")) {
                                if (ret.code === 1) {
                                    $('.doctor_id').html(ret.data);
                                    $("#doctor_id").selectpicker("refresh");
                                } else {
                                    $('#doctor_id').html('');
                                    Backend.api.toastr.error(ret.msg);
                                }
                            }
                        }, error: function (e) {
                            $('#depart_id').html('');
                            Backend.api.toastr.error(e.message);
                        }
                    });
                })*/

                $('.btn-success').click(function(){
                    var index = Layer.confirm(
                        '锁定号只能增加，不能减少，确定要锁定吗？',
                        {icon: 3, title: __('Warning'), shadeClose: true},
                        function () {
                            Layer.close(index);
                            //alert(123);
                            $('#edit-form').submit();
                            return false;
                        }
                    )
                    return false;
                })
            })
        }
    };
    return Controller;
});