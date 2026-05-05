/**
 * TaleemX: shared date/datetime picker behaviour.
 * - Default: past dates not selectable (startDate / minDate = today) unless opted out.
 * - Opt out: class datepicker-allow-past, guestbirthdate, date2, or data-allow-past="1",
 *   or id/name heuristics for DOB, reports, attendance, homework, leave, etc.
 * - Range pairs: "to" date stays on/after "from" (bootstrap datepicker + datetimepicker).
 */
(function ($) {
    'use strict';

    function taleemxAllowPastDates($input) {
        if (!$input || !$input.length) {
            return true;
        }
        var el = $input[0];
        if ($input.hasClass('datepicker-allow-past') || $input.hasClass('date2') || $input.hasClass('guestbirthdate')) {
            return true;
        }
        if ($input.hasClass('date_to')) {
            return true;
        }
        if ($input.hasClass('datetimepicker') || $input.hasClass('time') || $input.hasClass('time_hour')) {
            return true;
        }
        var cls = el.className || '';
        if (cls.indexOf('datetimepicker3') !== -1) {
            return true;
        }
        if ($input.data('allow-past') == 1 || $input.attr('data-allow-past') === '1') {
            return true;
        }
        var id = (el.id || '').toLowerCase();
        var name = (($input.attr('name') || '') + '').toLowerCase();

        if (id.indexOf('meeting_date') !== -1 || id.indexOf('teams_class_date') !== -1) {
            return true;
        }

        if (name === 'date_from' || name === 'date_to') {
            return true;
        }
        if (id === 'date_from' || id === 'date_to') {
            return true;
        }
        if (id === 'date' || name === 'date') {
            return true;
        }

        var pastPattern = /(dob|date_of_birth|birth|admission|measure|disable|homework|submit|evaluation|follow|applieddate|leave_from|leave_to|leavefrom|leaveto|task-date|postdate|sandbox|attend|atteend|daywise|class_date|transaction|accountant|banner|alumni|collect|invoice|due_date|expire|deactivate)/;
        if (pastPattern.test(id) || pastPattern.test(name)) {
            return true;
        }

        if (cls.indexOf('date2') !== -1 || cls.indexOf('sandbox-container') !== -1) {
            return true;
        }

        return false;
    }

    window.taleemxAllowPastDates = taleemxAllowPastDates;

    function taleemxMergeStartDateToday(opts, $el) {
        if (!opts || typeof opts !== 'object') {
            return opts;
        }
        if (opts.startDate !== undefined) {
            return opts;
        }
        if (taleemxAllowPastDates($el)) {
            return opts;
        }
        var out = $.extend(true, {}, opts);
        out.startDate = new Date();
        if (out.todayHighlight === undefined) {
            out.todayHighlight = true;
        }
        return out;
    }

    function taleemxMergeMinMomentToday(opts, $el) {
        if (!opts || typeof opts !== 'object' || typeof moment === 'undefined') {
            return opts;
        }
        if (opts.minDate !== undefined) {
            return opts;
        }
        if (taleemxAllowPastDates($el)) {
            return opts;
        }
        var out = $.extend(true, {}, opts);
        out.minDate = moment().startOf('day');
        return out;
    }

    if ($.fn.datepicker && !$.fn.datepicker.taleemxWrapped) {
        var _datepicker = $.fn.datepicker;
        $.fn.datepicker = function (options) {
            if (arguments.length === 0 || typeof options === 'string') {
                return _datepicker.apply(this, arguments);
            }
            if (typeof options === 'object' && options !== null) {
                var args = arguments;
                var self = this;
                return self.each(function () {
                    var $el = $(this);
                    var merged = taleemxMergeStartDateToday(options, $el);
                    return _datepicker.call($el, merged);
                });
            }
            return _datepicker.apply(this, arguments);
        };
        $.fn.datepicker.taleemxWrapped = true;
        $.extend($.fn.datepicker, _datepicker);
    }

    if ($.fn.datetimepicker && !$.fn.datetimepicker.taleemxWrapped) {
        var _datetimepicker = $.fn.datetimepicker;
        $.fn.datetimepicker = function (options) {
            if (arguments.length === 0 || typeof options === 'string') {
                return _datetimepicker.apply(this, arguments);
            }
            if (typeof options === 'object' && options !== null) {
                var self = this;
                return self.each(function () {
                    var $el = $(this);
                    var merged = taleemxMergeMinMomentToday(options, $el);
                    return _datetimepicker.call($el, merged);
                });
            }
            return _datetimepicker.apply(this, arguments);
        };
        $.fn.datetimepicker.taleemxWrapped = true;
        $.extend($.fn.datetimepicker, _datetimepicker);
    }

    var BS_RANGE_PAIRS = [
        ['#date_from', '#date_to'],
        ['#leave_from_date', '#leave_to_date'],
        ['#leavefrom', '#leaveto'],
        ['#homework_date', '#submit_date'],
        ['#homeworkdate', '#submitdate']
    ];

    function taleemxSyncBootstrapRangeEnd($from, $to) {
        if (!$from.length || !$to.length) {
            return;
        }
        try {
            var d = $from.datepicker('getDate');
            if (d) {
                $to.datepicker('setStartDate', d);
                var td = $to.datepicker('getDate');
                if (td && td < d) {
                    $to.datepicker('setDate', d);
                }
            }
        } catch (e) {}
    }

    function taleemxSyncBootstrapRangeStart($from, $to) {
        if (!$from.length || !$to.length) {
            return;
        }
        try {
            var d = $to.datepicker('getDate');
            if (d) {
                $from.datepicker('setEndDate', d);
                var fd = $from.datepicker('getDate');
                if (fd && fd > d) {
                    $from.datepicker('setDate', d);
                }
            }
        } catch (e) {}
    }

    function taleemxWireBootstrapRangePairs() {
        BS_RANGE_PAIRS.forEach(function (pair) {
            var $f = $(pair[0]);
            var $t = $(pair[1]);
            if (!$f.length || !$t.length) {
                return;
            }
            $f.off('changeDate.taleemxRange change.taleemxRange').on('changeDate.taleemxRange change.taleemxRange', function () {
                taleemxSyncBootstrapRangeEnd($f, $t);
            });
            $t.off('changeDate.taleemxRange change.taleemxRange').on('changeDate.taleemxRange change.taleemxRange', function () {
                taleemxSyncBootstrapRangeStart($f, $t);
            });
            taleemxSyncBootstrapRangeEnd($f, $t);
        });
    }

    function taleemxWireDatetimeExamAndEvents() {
        $(document).off('dp.change.taleemxExam').on('dp.change.taleemxExam', '#exam_from', function (e) {
            if (!e.date) {
                return;
            }
            var $to = $('#exam_to');
            if ($to.length && $to.data('DateTimePicker')) {
                $to.data('DateTimePicker').minDate(e.date.clone().startOf('day'));
            }
        });
        $(document).off('dp.change.taleemxExamTo').on('dp.change.taleemxExamTo', '#exam_to', function (e) {
            if (!e.date) {
                return;
            }
            var $from = $('#exam_from');
            if ($from.length && $from.data('DateTimePicker')) {
                $from.data('DateTimePicker').maxDate(e.date.clone().endOf('day'));
            }
        });

        $(document).off('dp.change.taleemxEv').on('dp.change.taleemxEv', '.event_from', function (e) {
            if (!e.date) {
                return;
            }
            var $form = $(this).closest('form');
            var $to = $form.length ? $form.find('.event_to').first() : $('.event_to').first();
            if ($to.length && $to.data('DateTimePicker')) {
                $to.data('DateTimePicker').minDate(e.date.clone().startOf('day'));
            }
        });
        $(document).off('dp.change.taleemxEvTo').on('dp.change.taleemxEvTo', '.event_to', function (e) {
            if (!e.date) {
                return;
            }
            var $form = $(this).closest('form');
            var $from = $form.length ? $form.find('.event_from').first() : $('.event_from').first();
            if ($from.length && $from.data('DateTimePicker')) {
                $from.data('DateTimePicker').maxDate(e.date.clone().endOf('day'));
            }
        });
    }

    $(function () {
        taleemxWireBootstrapRangePairs();
        taleemxWireDatetimeExamAndEvents();
        setTimeout(function () {
            taleemxWireBootstrapRangePairs();
            taleemxWireDatetimeExamAndEvents();
        }, 800);
    });
})(jQuery);
