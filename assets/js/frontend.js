/**
 * Frontend JavaScript for Ring Size Guide and Custom Dropdown
 */
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
            var list  = $('#sharvaDropdownList');
            var arrow = $('#sharvaArrow');
            if (list.length) list.removeClass('open');
            if (arrow.length) arrow.css('transform', 'rotate(0deg)');
        }
    });

    /* ── Select a size from dropdown ── */
    window.selectSharvaSize = function (el) {
        var $el = $(el);
        var slug  = $el.data('value');
        var title = $el.data('title');

        // 1. Update dropdown display text
        var $selectedText = $('#sharvaSelectedText');
        if ($selectedText.length) {
            $selectedText.text('Size: ' + title);
            $selectedText.css({
                'color': '#1a1a1a',
                'font-weight': '600'
            });
        }

        // 2. Mark selected item
        $('.sharva-dropdown-item').removeClass('active');
        $el.addClass('active');

        // 3. Close dropdown
        var $list  = $('#sharvaDropdownList');
        var $arrow = $('#sharvaArrow');
        if ($list.length) $list.removeClass('open');
        if ($arrow.length) $arrow.css('transform', 'rotate(0deg)');

        // 4. Click the CFVSW swatch on the page
        var $swatchContainer = $('.cfvsw-swatches-container[swatches-attr="attribute_pa_ring-size"]');
        if ($swatchContainer.length) {
            var $swatch = $swatchContainer.find('.cfvsw-swatches-option[data-slug="' + slug + '"]');
            if ($swatch.length) $swatch.click();
        }

        // 5. Also set the hidden WooCommerce select
        var $sel = $('select[name="attribute_pa_ring-size"]');
        if ($sel.length) {
            $sel.val(slug);
            $sel.get(0).dispatchEvent(new Event('input',  { bubbles: true }));
            $sel.get(0).dispatchEvent(new Event('change', { bubbles: true }));
            
            $sel.trigger('change');
            $('.variations_form').trigger('woocommerce_variation_select_change');
            $('.variations_form').trigger('check_variations');
        }

        // 6. Show confirmation tick on dropdown
        var $dropdownSelected = $('#sharvaDropdownSelected');
        if ($dropdownSelected.length) {
            $dropdownSelected.css({
                'border-color': '#2e7d32',
                'background': '#f0faf0'
            });
        }
    };

    /* ── Size Guide Table popup ── */
    window.openSizeGuideTable = function (e) {
        e.preventDefault();
        var $modal = $('#sharvaGuideModal');
        if ($modal.length) {
            $modal.addClass('open');
            $('body').css('overflow', 'hidden');
        }
    };

    window.closeSizeGuideTable = function () {
        var $modal = $('#sharvaGuideModal');
        if ($modal.length) {
            $modal.removeClass('open');
            $('body').css('overflow', '');
        }
    };

    // Close guide modal on backdrop click or ESC
    $(document).on('click', function (e) {
        var $modal = $('#sharvaGuideModal');
        if ($modal.length && $(e.target).is($modal)) closeSizeGuideTable();
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') closeSizeGuideTable();
    });

    /* ── Sync: if user clicks CFVSW swatch directly, 
          update our dropdown display too ── */
    $(document).on('click', '.cfvsw-swatches-container[swatches-attr="attribute_pa_ring-size"] .cfvsw-swatches-option', function (e) {
        var $swatch = $(this);
        var title = $swatch.data('title') || $swatch.data('slug');
        var slug  = $swatch.data('slug');

        var $selectedText = $('#sharvaSelectedText');
        if ($selectedText.length) {
            $selectedText.text('Size: ' + title);
            $selectedText.css({
                'color': '#1a1a1a',
                'font-weight': '600'
            });
        }

        $('.sharva-dropdown-item').each(function () {
            var $item = $(this);
            $item.toggleClass('active', $item.data('value') === slug);
        });

        var $dropdownSelected = $('#sharvaDropdownSelected');
        if ($dropdownSelected.length) {
            $dropdownSelected.css({
                'border-color': '#2e7d32',
                'background': '#f0faf0'
            });
        }
    });

})(jQuery);
