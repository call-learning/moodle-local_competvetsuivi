define(['jquery', 'jqueryui'], function($) {
    return {
        init: function(tableid) {
            var table = $('#' + tableid);
            table.find('tr[data-comp-level]').hide();
            table.find('tr[data-comp-level="1"]').show();
            table.find('.collapse-btn').hide();
            table.find('.expand-btn').show();
            var toggleFn = function(element, iscollapse) {
                var parentrow = $(element).parents('tr');
                var currentlevel = parseInt(parentrow.data('comp-level'));
                var path = parentrow.data('comp-path');
                if (iscollapse) {
                    $(element).siblings('.expand-btn').show();
                    parentrow.siblings('tr[data-comp-path^="' + path + '"]').hide();
                    parentrow.siblings('tr[data-comp-path^="' + path + '"] .expand-btn').show();
                    parentrow.siblings('tr[data-comp-path^="' + path + '"] .collapse-btn').hide();
                } else {
                    $(element).siblings('.collapse-btn').show();
                    parentrow.siblings('tr[data-comp-level="' + (currentlevel + 1) + '"][data-comp-path^="' + path + '"]').show();
                }
                $(element).hide();
            };
            table.find('.expand-btn').click(function() {
                toggleFn(this, false);
            });
            table.find('.collapse-btn').click(function() {
                toggleFn(this, true);
            });
        },
    };
});
