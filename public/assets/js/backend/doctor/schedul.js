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
                    index_url: 'doctor/schedul/index',
                }
            });
            var BootstrapTable = $.fn.bootstrapTable.Constructor;
            var getFieldIndex = function (columns, field) {
                var index = -1;
                $.each(columns, function (i, column) {
                    if (column.field === field) {
                        index = i;
                        return false;
                    }
                    return true;
                });
                return index;
            };
            BootstrapTable.prototype.showColumn = function (field, visible, weekend, canSet) {
                var fieldIndex = getFieldIndex(this.columns, field)
                if (fieldIndex === -1) {
                    return;
                }
                if (weekend) {
                    this.columns[fieldIndex].cellStyle.classes = 'weekend';
                } else {
                    this.columns[fieldIndex].cellStyle.classes = '';
                }
                this.toggleColumn(fieldIndex, visible, true);
            };

            var table = $("#table");

            function mGetDate(year, month) {
                var d = new Date(year, month, 0);
                return d.getDate();
            }
            var columns = [[{field: 'username', title: '', cellStyle: {classes: 'admin_id'}}]], column, field, dt;
            var date = new Date();
            var year = date.getFullYear();
            var month = date.getMonth() + 1;
            var currentDay = date.getDate();
            var totalDay = mGetDate(year, month);
            for (var k = 1; k <= 31; k++) {
                field = 'day_' + k;
                dt = new Date(year + '-' + month + '-' + k);
                //console.log(year + '||' + month + '||' + k + '||' + dt.getDay());
                column = {field: field, title: k, cellStyle: {classes: ''}, visible: true};
                if (dt.getDay() == 0 || dt.getDay() == 6) {
                    //columns[0].push({field: field, title: k, cellStyle: {css: {'background-color': '#ccffff'}}});
                    //columns[0].push({field: field, title: k, cellStyle: {classes: 'setwork weekend'}});
                    column.cellStyle.classes = 'weekend';
                } else {
                    //columns[0].push({field: field, title: k});
                    //columns[0].push({field: field, title: k, cellStyle: {classes: 'setwork'}});
                }
                if (k > totalDay) {
                    column.visible = false;
                }
                columns[0].push(column);
            }
            //console.log(columns);

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: columns
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            $(function(){
                if (month < 10) {
                    month = '0' + month;
                }
                var date = year + '-' + month, showDate = year + '/' + month;
                var params = {date: date, hos_id: Config.hospital.id};
                //console.log(Config);

                var toolhtml = [];
                toolhtml.push('<div class="bs-bars pull-left" style="font-size:20px;">');
                if (Config.admin.group_id == 1) {
                    toolhtml.push(Config.selHtml);
                } else {
                    toolhtml.push(Config.hospital.hos_name);
                }
                toolhtml.push('<span style="margin-left:350px;"></span>');
                toolhtml.push('<input type="button" class="select-month btn btn-success btn-embossed" class="btn btn-success btn-embossed" value="" style="width:50px;"/>');
                toolhtml.push('<input type="button" id="select-month" class="btn btn-success btn-embossed" value="月份" style="margin-left:-50px;"/>');
                toolhtml.push('<input type="button" id="prev-month" class="btn btn-success btn-embossed" value="<" style="margin-left:20px;"/>');
                toolhtml.push('<span class="btn btn-success btn-embossed" id="current-month" style="margin-left: 20px;width: 140px;">' + showDate + '</span>');
                toolhtml.push('<input type="button" id="next-month" class="btn btn-success btn-embossed" value=">" style="margin-left: 20px;"/>');
                toolhtml.push('</div>');
                $('.fixed-table-toolbar').html(toolhtml.join(''));

                function refreshDate() {
                    $("#current-month").html(showDate);
                    $('.select-month').val(showDate);
                    params.date = date;
                    totalDay = mGetDate(year, month);
                    var reDate = new Date();
                    var reYear = reDate.getFullYear();
                    var reMonth = reDate.getMonth() + 1;
                    if (reMonth < 10) {
                        reMonth = '0' + reMonth;
                    }
                    var reDay = reDate.getDate();
                    if (reDay < 10) {
                        reDay = '0' + reDay;
                    }
                    //console.log(reYear+''+reMonth+''+reDay);
                    var thisK, canSet = false;
                    for (var k = 1; k <= 31; k++) {
                        dt = new Date(year + '-' + month + '-' + k);
                        var isWeekEnd = false;
                        if (dt.getDay() == 0 || dt.getDay() == 6) {
                            isWeekEnd = true;
                        }
                        if (k <= totalDay) {
                            if (k < 10) {
                                thisK = '0' + k;
                            } else {
                                thisK = k;
                            }
                            //console.log(year+''+month+''+thisK);
                            if (Number(year+''+month+''+thisK) >= Number(reYear+''+reMonth+''+reDay)) {
                                canSet = true;
                            }
                            table.bootstrapTable("showColumn", 'day_' + k, true, isWeekEnd, canSet);
                        } else {
                            table.bootstrapTable("showColumn", 'day_' + k, false, isWeekEnd, canSet);
                        }
                    }
                    table.bootstrapTable("refresh", {query: params});
                }

                $(".fixed-table-toolbar").on('change', '#hos_id', function(){
                    params.hos_id = $(this).val();
                    refreshDate();
                });

                $(".fixed-table-toolbar").on('click', '#prev-month', function(){
                    //console.log(date + '||prev1');
                    var arr = date.split('-');
                    year = parseInt(arr[0]); //获取当前日期的年份
                    month = parseInt(arr[1]); //获取当前日期的月份
                    month = month - 1;
                    if (month == 0) {
                        year = year - 1;
                        month = 12;
                    }
                    if (month < 10) {
                        month = '0' + month;
                    }
                    date = year + '-' + month;
                    //console.log(date + '||prev2');
                    showDate = year + '/' + month;
                    refreshDate();
                });

                $(".fixed-table-toolbar").on('click', '#next-month', function(){
                    //console.log(date + '||next');
                    var arr = date.split('-');
                    year = parseInt(arr[0]); //获取当前日期的年份
                    month = parseInt(arr[1]); //获取当前日期的月份
                    month = month + 1;
                    if (month == 13) {
                        year = year + 1;
                        month = 1;
                    }
                    if (month < 10) {
                        month = '0' + month;
                    }
                    date = year + '-' + month;
                    showDate = year + '/' + month;
                    refreshDate();
                });

                $('.select-month').datetimepicker({
                    format: 'YYYY-MM',
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
                    //datepickerInput: '#current-month'
                });
                $('.select-month').on("dp.hide",function(data){
                    //console.log(date + '||dp.hide1');
                    month = data.date.month();
                    year = data.date.year();
                    month = month + 1;
                    if (month < 10) {
                        month = '0' + month;
                    }
                    date = year + '-' + month;
                    //console.log(date + '||dp.hide2');
                    if (params.date == date) {
                        return false;
                    }
                    params.date = date;
                    totalDay = mGetDate(year, month);
                    showDate = year + '/' + month;
                    //console.log(date + '||dp.hide3');
                    refreshDate();
                });

                $(".fixed-table-toolbar").on('click', '#select-month', function(){
                    $('.select-month').datetimepicker('show');
                });
            })
        }
    };
    return Controller;
});