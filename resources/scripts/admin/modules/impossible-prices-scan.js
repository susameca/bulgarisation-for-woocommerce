let isRunning = false;
let isFixingAll = false;
let currentPage = 1;
let totalPages = 1;
let processed = 0;
let found = 0;
let batchSize = 100;

const i18n = wooBgImpossiblePrices.i18n || {};

const $startBtn   = $('#woo-bg-start-scan');
const $fixAllBtn  = $('#woo-bg-fix-all');
const $status     = $('#woo-bg-scan-status');
const $progress   = $('#woo-bg-progress-bar');
const $progressNo = $('#woo-bg-progress-text');
const $tbody      = $('#woo-bg-results tbody');
const $wrap       = $('#woo-bg-results-wrap');
const $summary    = $('#woo-bg-results-summary');
const $fixSummary = $('#woo-bg-fix-summary');

function formatString(template, values) {
	let output = template || '';
	(values || []).forEach(function(value, index) {
		const token = '%' + (index + 1) + '$s';
		output = output.replace(token, value);
	});
	return output;
}

function t(key, fallback) {
	return i18n[key] || fallback || '';
}

function resetUI() {
	currentPage = 1;
	totalPages = 1;
	processed = 0;
	found = 0;
	$tbody.empty();
	$wrap.hide();
	$summary.text('');
	$fixSummary.text('');
	$status.text(t('preparing', 'Preparing...'));
	$progress.css('width', '0%');
	$progressNo.text('0%');
	$fixAllBtn.prop('disabled', true);
}

function escapeHtml(text) {
	return $('<div>').text(text == null ? '' : text).html();
}

function buildRowHtml(row, statusText) {
	let html = '';

	html += '<tr data-key="' + escapeHtml(row.product_id + '||' + row.price_key) + '" data-product-id="' + escapeHtml(row.product_id) + '" data-price-key="' + escapeHtml(row.price_key) + '" data-target-price="' + escapeHtml(row.nearest_raw) + '">';
	html += '<td>' + escapeHtml(row.product_id) + '</td>';
	html += '<td>' + escapeHtml(row.name) + '</td>';
	html += '<td>' + escapeHtml(row.type) + '</td>';
	html += '<td>' + escapeHtml(row.sku) + '</td>';
	html += '<td>' + escapeHtml(row.price_label) + '</td>';
	html += '<td>' + escapeHtml(row.entered_price_display) + '</td>';
	html += '<td>' + escapeHtml(row.tax_percent_display) + escapeHtml(t('percentSuffix', '%')) + '</td>';
	html += '<td>' + escapeHtml(row.nearest_display) + '</td>';
	html += '<td>' + escapeHtml(row.nearest_down_display) + '</td>';
	html += '<td>' + escapeHtml(row.nearest_up_display) + '</td>';
	html += '<td class="woo-bg-row-status">' + escapeHtml(statusText || t('problematic', 'Problematic')) + '</td>';
	html += '<td><a class="button button-secondary" href="' + escapeHtml(row.edit_link) + '">' + escapeHtml(t('edit', 'Edit')) + '</a> <button type="button" class="button button-primary woo-bg-fix-row">' + escapeHtml(t('fix', 'Fix')) + '</button></td>';
	html += '</tr>';

	return html;
}

function appendRows(rows) {
	if (!rows || !rows.length) {
		return;
	}

	let html = '';

	rows.forEach(function(row) {
		html += buildRowHtml(row, t('problematic', 'Problematic'));
	});

	$tbody.append(html);
	$wrap.show();
}

function updateProgress() {
	let percent = 0;

	if (totalPages > 0) {
		percent = Math.min(100, Math.round(((currentPage - 1) / totalPages) * 100));
	}

	$progress.css('width', percent + '%');
	$progressNo.text(percent + '%');
	$summary.text(formatString(t('checkedFound', 'Checked: %1$s | Problematic: %2$s'), [processed, found]));
}

function updateProblemSummaryCount() {
	const realRows = $('#woo-bg-results tbody tr').filter(function() {
		return $(this).find('td').length > 1;
	}).length;

	found = realRows;
	$summary.text(formatString(t('checkedFound', 'Checked: %1$s | Problematic: %2$s'), [processed, found]));

	if (!realRows) {
		$tbody.html('<tr><td colspan="12">' + escapeHtml(t('noProblematicRemaining', 'No problematic prices remain.')) + '</td></tr>');
		$fixAllBtn.prop('disabled', true);
	}
}

function refreshProductRows(productId, currentRows, statusText) {
	const selector = '#woo-bg-results tbody tr[data-product-id="' + String(productId).replace(/"/g, '\\"') + '"]';
	const $existingRows = $(selector);

	$existingRows.remove();

	if (currentRows && currentRows.length) {
		let html = '';

		currentRows.forEach(function(row) {
			html += buildRowHtml(row, statusText || t('problematic', 'Problematic'));
		});

		if ($('#woo-bg-results tbody tr').length && $('#woo-bg-results tbody tr td[colspan="12"]').length) {
			$tbody.empty();
		}

		$('#woo-bg-results tbody').append(html);
	}

	updateProblemSummaryCount();
}

function finishScan(message) {
	isRunning = false;
	$startBtn.prop('disabled', false).text(t('scanProducts', 'Scan products'));
	$progress.css('width', '100%');
	$progressNo.text('100%');
	$status.text(message);

	if (!found) {
		$wrap.show();
		$tbody.html('<tr><td colspan="12">' + escapeHtml(t('noProblematicFound', 'No problematic prices found.')) + '</td></tr>');
		$fixAllBtn.prop('disabled', true);
		return;
	}

	$fixAllBtn.prop('disabled', false);
}

function runBatch() {
	$.ajax({
		url: wooBgImpossiblePrices.ajaxUrl,
		type: 'POST',
		dataType: 'json',
		data: {
			action: 'woo_bg_scan_impossible_prices_batch',
			nonce: wooBgImpossiblePrices.scanNonce,
			page: currentPage,
			per_page: batchSize
		}
	})
	.done(function(response) {
		if (!response || !response.success || !response.data) {
			isRunning = false;
			$startBtn.prop('disabled', false).text(t('scanProducts', 'Scan products'));
			$status.text(t('scanError', 'An error occurred during scanning.'));
			return;
		}

		const data = response.data;

		totalPages = parseInt(data.total_pages || 1, 10);
		processed += parseInt(data.scanned_in_batch || 0, 10);

		if (data.rows && data.rows.length) {
			appendRows(data.rows);
			found += data.rows.length;
		}

		updateProgress();

		if (data.done) {
			finishScan(t('scanFinished', 'Scanning finished.'));
			return;
		}

		currentPage++;
		$status.text(formatString(t('pageOf', 'Scanning... page %1$s of %2$s'), [currentPage - 1, totalPages]));
		runBatch();
	})
	.fail(function(jqXHR) {
		isRunning = false;
		$startBtn.prop('disabled', false).text(t('scanProducts', 'Scan products'));
		$status.text(getAjaxErrorMessage(jqXHR, t('scanAjaxError', 'AJAX error during scanning.')));
	});
}

function fixSingleRow($row, onDone) {
	const productId   = $row.data('product-id');
	const priceKey    = $row.data('price-key');
	const targetPrice = $row.data('target-price');
	const $statusCell = $row.find('.woo-bg-row-status');
	const $button     = $row.find('.woo-bg-fix-row');

	if (!targetPrice) {
		$statusCell.text(t('missingTargetPrice', 'Missing target price'));
		if (onDone) {
			onDone(false);
		}
		return;
	}

	$button.prop('disabled', true).text(t('fixing', 'Fixing...'));
	$statusCell.text(t('statusFixing', 'Fixing...'));

	$.ajax({
		url: wooBgImpossiblePrices.ajaxUrl,
		type: 'POST',
		dataType: 'json',
		data: {
			action: 'woo_bg_fix_impossible_price',
			nonce: wooBgImpossiblePrices.fixNonce,
			product_id: productId,
			price_key: priceKey,
			target_price: targetPrice
		}
	})
	.done(function(response) {
		if (response && response.success) {
			let statusText = t('fixed', 'Fixed');

			if (response.data && response.data.adjustment_message) {
				statusText = response.data.adjustment_message;
			}

			if (response.data && response.data.current_rows) {
				refreshProductRows(response.data.product_id, response.data.current_rows, statusText);
			} else {
				$statusCell.text(statusText);
				$row.addClass('woo-bg-row-fixed');
				$button.remove();
			}

			if (onDone) {
				onDone(true);
			}
		} else {
			let msg = t('genericError', 'Error');

			if (response && response.data && response.data.message) {
				msg = response.data.message;
			}

			$statusCell.text(msg);
			$button.prop('disabled', false).text(t('fix', 'Fix'));

			if (onDone) {
				onDone(false);
			}
		}
	})
	.fail(function(jqXHR) {
		$statusCell.text(getAjaxErrorMessage(jqXHR, t('ajaxError', 'AJAX error')));
		$button.prop('disabled', false).text(t('fix', 'Fix'));

		if (onDone) {
			onDone(false);
		}
	});
}

function fixAllSequential(index, stats) {
	if (!isFixingAll) {
		return;
	}

	const $rows = $('#woo-bg-results tbody tr').filter(function() {
		return $(this).find('.woo-bg-fix-row').length > 0;
	});

	if (!$rows.length || index >= $rows.length) {
		isFixingAll = false;
		$fixAllBtn.prop('disabled', false).text(t('fixAll', 'Fix all'));
		$fixSummary.text(formatString(t('fixAllSummary', 'Fixed: %1$s | Not fixed: %2$s'), [stats.success, stats.failed]));
		updateProblemSummaryCount();
		return;
	}

	const $row = $($rows.get(index));

	$fixSummary.text(formatString(t('fixAllProgress', 'Fixing all... %1$s / %2$s'), [index + 1, $rows.length]));

	fixSingleRow($row, function(success) {
		if (success) {
			stats.success++;
		} else {
			stats.failed++;
		}

		fixAllSequential(index + 1, stats);
	});
}

function getAjaxErrorMessage(jqXHR, fallback) {
	let message = fallback || t('ajaxError', 'AJAX error');

	if (jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
		return jqXHR.responseJSON.data.message;
	}

	if (jqXHR && jqXHR.responseText) {
		try {
			const parsed = JSON.parse(jqXHR.responseText);

			if (parsed && parsed.data && parsed.data.message) {
				return parsed.data.message;
			}
		} catch (e) {}
	}

	return message;
}

$startBtn.on('click', function(e) {
	e.preventDefault();

	if (isRunning || isFixingAll) {
		return;
	}

	isRunning = true;
	resetUI();
	$startBtn.prop('disabled', true).text(t('scanning', 'Scanning...'));
	runBatch();
});

$(document).on('click', '.woo-bg-fix-row', function(e) {
	e.preventDefault();

	if (isRunning || isFixingAll) {
		return;
	}

	fixSingleRow($(this).closest('tr'));
});

$fixAllBtn.on('click', function(e) {
	e.preventDefault();

	if (isRunning || isFixingAll) {
		return;
	}

	const $rows = $('#woo-bg-results tbody tr').filter(function() {
		return $(this).find('.woo-bg-fix-row').length > 0;
	});

	if (!$rows.length) {
		$fixSummary.text(t('nothingToFix', 'There are no rows to fix.'));
		return;
	}

	isFixingAll = true;
	$fixAllBtn.prop('disabled', true).text(t('fixing', 'Fixing...'));
	fixAllSequential(0, { success: 0, failed: 0 });
});