(function ($) {
    'use strict';

    function getActiveSidebarItem($menu) {
        var $activeSubItem = $menu.find('.treeview-menu > li.active:visible').first();

        if ($activeSubItem.length) {
            return $activeSubItem;
        }

        return $menu.find('> li.active:visible').first();
    }

    function isScrollable($element) {
        return $element.length && $element[0].scrollHeight > ($element.innerHeight() + 1);
    }

    function scrollSidebarToActiveItem() {
        var $mainSidebar = $('.main-sidebar').first();
        var $sidebar = $mainSidebar.find('.sidebar').first();
        var $menu = $mainSidebar.find('.sidebar-menu').first();

        if (!$mainSidebar.length || !$sidebar.length || !$menu.length) {
            return;
        }

        var $activeItem = getActiveSidebarItem($menu);

        if (!$activeItem.length) {
            return;
        }

        var hasSlimScroll = $.fn.slimScroll && $sidebar.parent().hasClass('slimScrollDiv');
        var $scrollContainer = hasSlimScroll || !isScrollable($mainSidebar) ? $sidebar : $mainSidebar;
        var currentScroll = $scrollContainer.scrollTop();
        var containerHeight = $scrollContainer.innerHeight();
        var itemTop = $activeItem.offset().top - $scrollContainer.offset().top;
        var centeredOffset = (containerHeight / 2) - ($activeItem.outerHeight() / 2);
        var targetScroll = Math.max(0, currentScroll + itemTop - centeredOffset);

        if (hasSlimScroll) {
            $sidebar.slimScroll({ scrollTo: targetScroll + 'px' });
        } else {
            $scrollContainer.scrollTop(targetScroll);
        }
    }

    $(function () {
        scrollSidebarToActiveItem();

        // AdminLTE may recreate slimScroll during layout fixes, so run once more after it settles.
        window.setTimeout(scrollSidebarToActiveItem, 250);
    });
})(jQuery);
