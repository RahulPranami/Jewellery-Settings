/**
 * Admin JavaScript for Jewellery Settings
 */

jQuery(document).ready(function ($) {
	'use strict';

	// Update making description based on type
	function updateMakingDescription() {
		const goldType = $('#gold_making_type').val();
		const silverType = $('#silver_making_type').val();

		$('#gold_making_desc').text(
			goldType === 'percentage'
				? '(% of metal price)'
				: '(per gram)'
		);

		$('#silver_making_desc').text(
			silverType === 'percentage'
				? '(% of metal price)'
				: '(per gram)'
		);
	}

	// Update derived gold prices when base price changes (LIVE)
	$('#gold_price').on('input change', function () {
		const basePrice = parseFloat($(this).val()) || 0;
		const price18k = (basePrice * (18 / 24)).toFixed(2);
		const price14k = (basePrice * (14 / 24)).toFixed(2);
		const price9k = (basePrice * (9 / 24)).toFixed(2);

		// Update the readonly display fields
		$('#gold_18k_price').val(price18k);
		$('#gold_14k_price').val(price14k);
		$('#gold_9k_price').val(price9k);

		// Add a visual highlight to show values updated
		$('.jewellery-derived-prices').addClass('updated');
		setTimeout(function() {
			$('.jewellery-derived-prices').removeClass('updated');
		}, 300);
	});

	// Making type change handlers with visual feedback
	$('#gold_making_type, #silver_making_type').on('change', function () {
		updateMakingDescription();
		// Add subtle highlight to show change
		$(this).closest('tr').addClass('updated');
		setTimeout(function() {
			$(this).closest('tr').removeClass('updated');
		}, 300);
	});

	// Initialize description on page load
	updateMakingDescription();

	// Preview Price Calculator with enhanced UX
	$('#preview_calculate').on('click', function () {
		const weight = parseFloat($('#preview_weight').val()) || 0;
		const metal = $('#preview_metal').val();
		const purity = parseFloat($('#preview_purity').val()) || 24;
		const diamond = parseFloat($('#preview_diamond').val()) || 0;

		if (weight <= 0) {
			$('#preview_result').hide();
			return;
		}

		// Show loading state
		const $btn = $(this);
		const originalText = $btn.text();
		$btn.text('Calculating...').prop('disabled', true);

		$.ajax({
			url: jewellerySettings.ajaxUrl,
			method: 'POST',
			data: {
				action: 'jewellery_preview_price',
				nonce: jewellerySettings.nonce,
				weight: weight,
				metal: metal,
				purity: purity,
				diamond: diamond,
			},
			success: function (response) {
				if (response.success) {
					const data = response.data;
					const finalPrice = (data.final_price || 0).toFixed(2);
					const metalPrice = (data.metal_price || 0).toFixed(2);
					const making = (data.making || 0).toFixed(2);
					const diamondPrice = (data.diamond_price || 0).toFixed(2);
					const otherCharges = (data.other_charges || 0).toFixed(2);

					$('#preview_price').text('₹' + finalPrice);

					let breakdown = '';
					breakdown += '<div><strong>Metal:</strong> ₹' + metalPrice + '</div>';
					breakdown += '<div><strong>Making:</strong> ₹' + making + '</div>';
					if (parseFloat(diamondPrice) > 0) {
						breakdown += '<div><strong>Diamond:</strong> ₹' + diamondPrice + '</div>';
					}
					if (parseFloat(otherCharges) > 0) {
						breakdown += '<div><strong>Other Charges:</strong> ₹' + otherCharges + '</div>';
					}

					$('#preview_breakdown').html(breakdown);
					$('#preview_result').slideDown(200);
				} else {
					alert('Error: ' + response.data.message);
					$('#preview_result').slideUp(200);
				}
			},
			error: function () {
				alert('AJAX error occurred');
				$('#preview_result').slideUp(200);
			},
			complete: function () {
				$btn.text(originalText).prop('disabled', false);
			},
		});
	});

	// Allow Enter key in preview inputs
	$('#preview_weight, #preview_diamond').on('keypress', function (e) {
		if (e.which === 13) {
			$('#preview_calculate').click();
			return false;
		}
	});

	// Sync Prices Button
	let isSyncing = false;
	let currentOffset = 0;

	$('#sync_button').on('click', function () {
		if (isSyncing) return;

		isSyncing = true;
		currentOffset = 0;

		$(this).prop('disabled', true);
		$('#sync_progress').show();
		$('#progress_fill').css('width', '0%');
		$('#sync_status').text('Starting sync...');

		performSync();
	});

	function performSync() {
		$.ajax({
			url: jewellerySettings.ajaxUrl,
			method: 'POST',
			data: {
				action: 'jewellery_sync_prices',
				nonce: jewellerySettings.nonce,
				offset: currentOffset,
			},
			success: function (response) {
				if (response.success) {
					const data = response.data;

					if (data.complete) {
						// Sync is complete - show success
						$('#progress_fill').css('width', '100%').text('100%');
						$('#sync_status').html(
							'<strong style="color: #28a745;">✓ Sync Completed Successfully!</strong><br/>' +
							'<span style="font-size: 12px;">Products processed: <strong>' + data.products + '</strong><br/>' +
							'Variations updated: <strong>' + data.variations + '</strong></span>'
						);

						setTimeout(function () {
							isSyncing = false;
							$('#sync_button').prop('disabled', false).text('Sync All Prices');
							// Auto reload to show updated prices
							location.reload();
						}, 2500);
					} else {
						// Continue syncing - update progress
						currentOffset = data.offset;
						const totalProducts = data.total_products || currentOffset;
						const progress = Math.min(95, (currentOffset / totalProducts) * 100);

						$('#progress_fill')
							.css('width', progress + '%')
							.text(Math.round(progress) + '%');

						$('#sync_status').html(
							'<strong>Processing...</strong><br/>' +
							'Batch: ' + data.products + ' products<br/>' +
							'Total updated: ' + data.variations + ' variations'
						);

						// Small delay before next batch
						setTimeout(performSync, 300);
					}
				} else {
					// Sync failed
					$('#progress_fill').css('width', '0%').css('background', '#dc3545');
					$('#sync_status').html(
						'<strong style="color: #dc3545;">✗ Sync Failed</strong><br/>' +
						'<span style="font-size: 12px;">Please check your settings and try again.</span>'
					);
					isSyncing = false;
					$('#sync_button').prop('disabled', false).text('Sync All Prices');
				}
			},
			error: function () {
				// AJAX error
				$('#progress_fill').css('width', '0%').css('background', '#dc3545');
				$('#sync_status').html(
					'<strong style="color: #dc3545;">✗ AJAX Error</strong><br/>' +
					'<span style="font-size: 12px;">Please check your connection and try again.</span>'
				);
				isSyncing = false;
				$('#sync_button').prop('disabled', false).text('Sync All Prices');
			},
		});
	}
});
