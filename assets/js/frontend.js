(function ($) {
    'use strict';

    /* ── Dropdown toggle ── */
    window.toggleSharvaDropdown = function () {
        var list  = document.getElementById('sharvaDropdownList');
        var arrow = document.getElementById('sharvaArrow');
        if (!list) return;
        var isOpen = list.classList.toggle('open');
        if (arrow) arrow.style.transform = isOpen ? 'rotate(180deg)' : 'rotate(0deg)';
    };

    // Close dropdown when clicking outside
    $(document).on('click', function (e) {
        var wrap = $('.sharva-dropdown-wrap');
        if (wrap.length && !wrap.is(e.target) && wrap.has(e.target).length === 0) {
            $('#sharvaDropdownList').removeClass('open');
            $('#sharvaArrow').css('transform', 'rotate(0deg)');
        }
    });

    /* ── Reset dropdown to default state ── */
    function resetSharvaDropdown() {
        $('#sharvaSelectedText')
            .text('Select Ring Size')
            .css({ 'color': '#888', 'font-weight': '400' });

        $('.sharva-dropdown-item').removeClass('active');

        $('#sharvaDropdownSelected').css({
            'border-color': '#ccc',
            'background':   '#fff'
        });

        $('#sharvaDropdownList').removeClass('open');
        $('#sharvaArrow').css('transform', 'rotate(0deg)');
    }

    /* ── Select a size from dropdown ── */
    window.selectSharvaSize = function (el) {
        var $el   = $(el);
        var slug  = $el.data('value');
        var title = $el.data('title');

        // 1. Update dropdown display text
        $('#sharvaSelectedText')
            .text('Size: ' + title)
            .css({ 'color': '#1a1a1a', 'font-weight': '600' });

        // 2. Mark selected item
        $('.sharva-dropdown-item').removeClass('active');
        $el.addClass('active');

        // 3. Close dropdown
        $('#sharvaDropdownList').removeClass('open');
        $('#sharvaArrow').css('transform', 'rotate(0deg)');

        // 4. Click the CFVSW swatch on the page
        var $swatchContainer = $('.cfvsw-swatches-container[swatches-attr="attribute_pa_ring-size"]');
        if ($swatchContainer.length) {
            $swatchContainer
                .find('.cfvsw-swatches-option[data-slug="' + slug + '"]')
                .click();
        }

        // 5. Set the hidden WooCommerce select
        var $sel = $('select[name="attribute_pa_ring-size"]');
        if ($sel.length) {
            $sel.val(slug);
            $sel.get(0).dispatchEvent(new Event('input',  { bubbles: true }));
            $sel.get(0).dispatchEvent(new Event('change', { bubbles: true }));
            $sel.trigger('change');
            $('.variations_form').trigger('woocommerce_variation_select_change');
            $('.variations_form').trigger('check_variations');
        }

        // 6. Green confirmation border on dropdown box
        $('#sharvaDropdownSelected').css({
            'border-color': '#2e7d32',
            'background':   '#f0faf0'
        });
    };

    /* ── Clear / Reset button ── */
    $(document).on('click', 'a.reset_variations', function () {
        resetSharvaDropdown();
    });

    /* ── Sync: if user clicks CFVSW swatch directly,
          update our dropdown display too ── */
    $(document).on(
        'click',
        '.cfvsw-swatches-container[swatches-attr="attribute_pa_ring-size"] .cfvsw-swatches-option',
        function () {
            var $swatch = $(this);
            var slug    = $swatch.data('slug');
            var title   = $swatch.data('title') || slug;

            $('#sharvaSelectedText')
                .text('Size: ' + title)
                .css({ 'color': '#1a1a1a', 'font-weight': '600' });

            $('.sharva-dropdown-item').each(function () {
                $(this).toggleClass('active', $(this).data('value') === slug);
            });

            $('#sharvaDropdownSelected').css({
                'border-color': '#2e7d32',
                'background':   '#f0faf0'
            });
        }
    );

    /* ── Size Guide Table popup ── */
    window.openSizeGuideTable = function (e) {
        e.preventDefault();
        $('#sharvaGuideModal').addClass('open');
        $('body').css('overflow', 'hidden');
    };

    window.closeSizeGuideTable = function () {
        $('#sharvaGuideModal').removeClass('open');
        $('body').css('overflow', '');
    };

    // Close on backdrop click
    $(document).on('click', function (e) {
        var $modal = $('#sharvaGuideModal');
        if ($modal.length && $(e.target).is($modal)) closeSizeGuideTable();
    });

    // Close on ESC key
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') closeSizeGuideTable();
    });

})(jQuery);