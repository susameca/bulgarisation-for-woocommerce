var wooBgImpossiblePriceTimers = {};

function debounce(key, callback, delay) {
	if (wooBgImpossiblePriceTimers[key]) {
		clearTimeout(wooBgImpossiblePriceTimers[key]);
	}

	wooBgImpossiblePriceTimers[key] = setTimeout(function() {
		callback();
		delete wooBgImpossiblePriceTimers[key];
	}, delay);
}

function getDecimalSeparator() {
	return String(wooBgImpossiblePrices.decimalSeparator || '.');
}

function getThousandSeparator() {
	return String(wooBgImpossiblePrices.thousandSeparator || ',');
}

function normalizePriceString(value) {
	value = String(value || '').trim();

	var thousandSeparator = getThousandSeparator();
	var decimalSeparator  = getDecimalSeparator();

	if (thousandSeparator) {
		value = value.split(thousandSeparator).join('');
	}

	if (decimalSeparator && decimalSeparator !== '.') {
		value = value.replace(decimalSeparator, '.');
	}

	value = value.replace(',', '.');

	return value;
}

function parsePrice(value) {
	value = normalizePriceString(value);

	if (!value || !/^\d+(\.\d+)?$/.test(value)) {
		return null;
	}

	var parsed = parseFloat(value);
	return isFinite(parsed) ? parsed : null;
}

function formatPriceForInput(num, decimals) {
	var formatted = Number(num).toFixed(decimals);
	var decimalSeparator = getDecimalSeparator();

	if (decimalSeparator !== '.') {
		formatted = formatted.replace('.', decimalSeparator);
	}

	return formatted;
}

function valuesAreEqual(a, b, decimals) {
	return Number(a).toFixed(decimals) === Number(b).toFixed(decimals);
}

function getFieldKey($input) {
	var name = String($input.attr('name') || '');
	var id   = String($input.attr('id') || '');

	if (name) {
		return 'name:' + name;
	}

	return 'id:' + id;
}

function getFieldWrap($input) {
	var $wrap = $input.closest('.form-field, p.form-row');

	if (!$wrap.length) {
		$wrap = $input.parent();
	}

	return $wrap;
}

function getTaxRateForInput($input) {
    var pricesIncludeTax = !!wooBgImpossiblePrices.pricesIncludeTax;
    var taxesEnabled     = !!wooBgImpossiblePrices.taxesEnabled;
    var fallbackTaxRate  = parseFloat(wooBgImpossiblePrices.fallbackTaxRate);

    if (!isFinite(fallbackTaxRate) || fallbackTaxRate <= 0) {
        fallbackTaxRate = 0.20;
    }

    if (!taxesEnabled || !pricesIncludeTax) {
        return fallbackTaxRate;
    }

    var parentTaxRate = parseFloat(wooBgImpossiblePrices.parentTaxRate);

    if (!isFinite(parentTaxRate) || parentTaxRate < 0) {
        parentTaxRate = 0;
    }

    function getRateByClassSlug(taxClass) {
        taxClass = String(taxClass || '');

        if (Object.prototype.hasOwnProperty.call(wooBgImpossiblePrices.taxRates, taxClass)) {
            var rate = parseFloat(wooBgImpossiblePrices.taxRates[taxClass]);

            if (isFinite(rate) && rate >= 0) {
                return rate;
            }
        }

        return null;
    }

    var $variation = $input.closest('.woocommerce_variation');

    if ($variation.length) {
        var $variationTaxClass = $variation.find('select[name^="variable_tax_class"]');
        var variationTaxClass = $variationTaxClass.length ? String($variationTaxClass.val() || '') : '';

        if (!variationTaxClass || variationTaxClass === 'parent') {
            if (parentTaxRate > 0) {
                return parentTaxRate;
            }

            return fallbackTaxRate;
        }

        var variationRate = getRateByClassSlug(variationTaxClass);

        if (variationRate !== null) {
            return variationRate;
        }

        return fallbackTaxRate;
    }

    var $mainTaxClass = $('#_tax_class');
    var mainTaxClass = $mainTaxClass.length ? String($mainTaxClass.val() || '') : '';
    var mainRate = getRateByClassSlug(mainTaxClass);

    if (mainRate !== null) {
        return mainRate;
    }

    if (mainTaxClass === '' && parentTaxRate > 0) {
        return parentTaxRate;
    }

    return fallbackTaxRate;
}

function findNearestPossibleGross(grossPrice, taxRate, decimals) {
	grossPrice = parseFloat(grossPrice);

	if (!isFinite(grossPrice) || grossPrice <= 0 || !isFinite(taxRate) || taxRate <= 0) {
		return null;
	}

	var minorUnit = Math.pow(10, decimals);
	var multiplier = 1 + taxRate;
	var approxNetMinor = (grossPrice / multiplier) * minorUnit;
	var base = Math.floor(approxNetMinor);
	var candidates = [];
	var preferHigherOnTie = !!wooBgImpossiblePrices.preferHigherOnTie;

	for (var i = -3; i <= 3; i++) {
		var netMinor = base + i;

		if (netMinor < 0) {
			continue;
		}

		var exactGross = (netMinor / minorUnit) * multiplier;
		var roundedGross = Number(
			(Math.round(exactGross * minorUnit) / minorUnit).toFixed(decimals)
		);

		candidates.push({
			gross: roundedGross,
			exactDiff: Math.abs(exactGross - grossPrice),
			roundedDiff: Math.abs(roundedGross - grossPrice)
		});
	}

	if (!candidates.length) {
		return null;
	}

	candidates.sort(function(a, b) {
		// first compare exact mathematical distance
		if (a.exactDiff !== b.exactDiff) {
			return a.exactDiff - b.exactDiff;
		}

		// then compare rounded distance
		if (a.roundedDiff !== b.roundedDiff) {
			return a.roundedDiff - b.roundedDiff;
		}

		// only then use tie-break preference
		if (preferHigherOnTie) {
			return b.gross - a.gross;
		}

		return a.gross - b.gross;
	});

	return candidates[0].gross;
}

function ensureAnchor($input) {
	var fieldKey = getFieldKey($input);
	var anchorClass = 'woo-bg-impossible-price-anchor';
	var safeFieldKey = fieldKey.replace(/"/g, '&quot;');
	var $wrap = getFieldWrap($input);
	var $targetWrap = $wrap;
	var $variation = $input.closest('.woocommerce_variation');

	if ($variation.length) {
		var inputName = String($input.attr('name') || '');

		if (
			inputName.indexOf('variable_regular_price') === 0 ||
			inputName.indexOf('variable_sale_price') === 0
		) {
			var $saleInput = $variation.find('input[name^="variable_sale_price"]').first();

			if ($saleInput.length) {
				$targetWrap = getFieldWrap($saleInput);
			}
		}
	}

	var $next = $targetWrap.next('p.form-field.' + anchorClass + '[data-field-key="' + fieldKey + '"]');

	if ($next.length) {
		return $next;
	}

	var $anchor = $('<p class="form-field ' + anchorClass + '" data-field-key="' + safeFieldKey + '"></p>');
	$anchor.insertAfter($targetWrap);

	return $anchor;
}

function clearAnchor($input, keepSuccess) {
	var $anchor = ensureAnchor($input);

	if (keepSuccess) {
		$anchor.find('.woo-bg-impossible-price-notice').remove();
	} else {
		$anchor.empty();
	}
}

function buildNoticeHtml(suggestedValue, fieldKey) {
	return '' +
		'<div class="woo-bg-impossible-price-notice" data-field-key="' + fieldKey + '">' +
			'<div class="woo-bg-impossible-price-notice__text">' +
				'<strong>' + wooBgImpossiblePrices.i18n.warningPrefix + '</strong><br>' +
				wooBgImpossiblePrices.i18n.suggested + ' <strong>' + suggestedValue + '</strong>' +
			'</div>' +
			'<div class="woo-bg-impossible-price-notice__actions">' +
				'<button type="button" class="button button-primary woo-bg-apply-corrected-price" data-value="' + suggestedValue + '" data-field-key="' + fieldKey + '">' +
					wooBgImpossiblePrices.i18n.apply +
				'</button>' +
			'</div>' +
		'</div>';
}

function buildSuccessHtml(fieldKey) {
	return '' +
		'<div class="woo-bg-impossible-price-success" data-field-key="' + fieldKey + '">' +
			wooBgImpossiblePrices.i18n.success +
		'</div>';
}

function renderWarning($input, suggestedValue) {
	var fieldKey = getFieldKey($input);
	var $anchor = ensureAnchor($input);

	$anchor.find('.woo-bg-impossible-price-success').remove();
	$anchor.find('.woo-bg-impossible-price-notice').remove();
	$anchor.append(buildNoticeHtml(suggestedValue, fieldKey));
}

function renderSuccess($input) {
	var fieldKey = getFieldKey($input);
	var $anchor = ensureAnchor($input);

	$anchor.find('.woo-bg-impossible-price-notice').remove();
	$anchor.find('.woo-bg-impossible-price-success').remove();
	$anchor.append(buildSuccessHtml(fieldKey));
}

function checkField($input) {
	clearAnchor($input, false);

	if ($input.data('wooBgApplyingCorrection')) {
		return;
	}

	var rawValue = String($input.val() || '').trim();
	if (!rawValue) {
		return;
	}

	var decimals     = parseInt(wooBgImpossiblePrices.priceDecimals, 10) || 2;
	var currentValue = parsePrice(rawValue);
	var taxRate      = getTaxRateForInput($input);

	if (currentValue === null || !taxRate || taxRate <= 0) {
		return;
	}

	var suggested = findNearestPossibleGross(currentValue, taxRate, decimals);

	if (suggested === null || valuesAreEqual(currentValue, suggested, decimals)) {
		return;
	}

	renderWarning($input, formatPriceForInput(suggested, decimals));
}

function checkFieldDebounced($input) {
	var fieldKey = getFieldKey($input);

	debounce(fieldKey, function() {
		checkField($input);
	}, 200);
}

function bindField($input) {
	if (!$input.length || $input.data('wooBgImpossibleBound')) {
		return;
	}

	$input.data('wooBgImpossibleBound', true);

	$input.on('input.wooBgImpossiblePrices', function() {
		var $this = $(this);
		clearAnchor($this, false);
		checkFieldDebounced($this);
	});

	$input.on('blur.wooBgImpossiblePrices change.wooBgImpossiblePrices', function() {
		var $this = $(this);

		if ($this.data('wooBgApplyingCorrection')) {
			return;
		}

		checkField($this);
	});

	checkField($input);
}

function initSimpleFields() {
	bindField($('#_regular_price'));
	bindField($('#_sale_price'));
}

function initVariationFields(scope) {
	var $scope = scope ? $(scope) : $(document);

	$scope.find('input[name^="variable_regular_price"]').each(function() {
		bindField($(this));
	});

	$scope.find('input[name^="variable_sale_price"]').each(function() {
		bindField($(this));
	});
}

function findInputByFieldKey(fieldKey, $origin) {
	var $variation = $origin.closest('.woocommerce_variation');
	var $pool = $variation.length
		? $variation.find('input')
		: $(document).find('#_regular_price, #_sale_price, input[name^="variable_regular_price"], input[name^="variable_sale_price"]');
	var $match = $();

	$pool.each(function() {
		var $input = $(this);

		if (getFieldKey($input) === fieldKey) {
			$match = $input;
			return false;
		}
	});

	return $match;
}

if ( $('body').hasClass('woocommerce_woo-bg-impossible-vat-price') ) {
	$(document).on('mousedown', '.woo-bg-apply-corrected-price', function(e) {
		e.preventDefault();
	});

	$(document).on('click', '.woo-bg-apply-corrected-price', function(e) {
		e.preventDefault();

		var $btn = $(this);
		var value = String($btn.data('value') || '');
		var fieldKey = String($btn.data('field-key') || '');
		var $input = findInputByFieldKey(fieldKey, $btn);

		if (!$input.length) {
			return;
		}

		$input.data('wooBgApplyingCorrection', true);
		$input.val(value);
		renderSuccess($input);

		setTimeout(function() {
			$input.removeData('wooBgApplyingCorrection');
		}, 300);
	});

	$(document).on('change', '#_tax_class', function() {
		initSimpleFields();
		initVariationFields(document);
	});

	$(document).on('change', 'select[name^="variable_tax_class"]', function() {
		initVariationFields($(this).closest('.woocommerce_variation'));
	});

	$(document).on('woocommerce_variations_loaded', function() {
		initVariationFields(document);
	});

	$(document).ajaxComplete(function(event, xhr, settings) {
		if (settings && settings.data && String(settings.data).indexOf('load_variations') !== -1) {
			initVariationFields(document);
		}
	});

	initSimpleFields();
	initVariationFields(document);
}