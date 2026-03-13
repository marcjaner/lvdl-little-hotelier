(function () {
	'use strict';

	function parseColorToRgb(value) {
		if (!value) {
			return null;
		}

		var hex = value.trim().match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
		if (hex) {
			var raw = hex[1];
			if (raw.length === 3) {
				raw = raw[0] + raw[0] + raw[1] + raw[1] + raw[2] + raw[2];
			}
			return {
				r: parseInt(raw.slice(0, 2), 16),
				g: parseInt(raw.slice(2, 4), 16),
				b: parseInt(raw.slice(4, 6), 16)
			};
		}

		var rgb = value.trim().match(/^rgba?\(([^)]+)\)$/i);
		if (!rgb) {
			return null;
		}

		var parts = rgb[1].split(',').map(function (p) {
			return p.trim();
		});
		if (parts.length < 3) {
			return null;
		}

		return {
			r: Math.max(0, Math.min(255, parseInt(parts[0], 10))),
			g: Math.max(0, Math.min(255, parseInt(parts[1], 10))),
			b: Math.max(0, Math.min(255, parseInt(parts[2], 10)))
		};
	}

	function relativeLuminance(rgb) {
		function channel(v) {
			var c = v / 255;
			return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
		}
		return (0.2126 * channel(rgb.r)) + (0.7152 * channel(rgb.g)) + (0.0722 * channel(rgb.b));
	}

	function applyHoverContrast() {
		var forms = document.querySelectorAll('.lvdl-lh-widget.lvdl-lh-has-color');
		forms.forEach(function (form) {
			var styles = window.getComputedStyle(form);
			var resolved = styles.getPropertyValue('--lvdl-lh-text-color').trim();
			var rgb = parseColorToRgb(resolved);
			if (!rgb) {
				form.style.setProperty('--lvdl-lh-hover-text-color', 'var(--base-2, #f5f5fc)');
				return;
			}

			var luminance = relativeLuminance(rgb);
			if (luminance > 0.5) {
				form.style.setProperty('--lvdl-lh-hover-text-color', 'var(--contrast-3, #393c5d)');
				return;
			}

			form.style.setProperty('--lvdl-lh-hover-text-color', 'var(--base-2, #f5f5fc)');
		});
	}

	function parseDate(value) {
		if (!value) {
			return null;
		}
		var parts = value.split('-');
		if (parts.length !== 3) {
			return null;
		}
		var date = new Date(Date.UTC(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10)));
		if (Number.isNaN(date.getTime())) {
			return null;
		}
		return date;
	}

	function diffDays(start, end) {
		var msPerDay = 24 * 60 * 60 * 1000;
		return Math.round((end.getTime() - start.getTime()) / msPerDay);
	}

	function parseIsoDateToLocal(value) {
		if (!value) {
			return null;
		}

		var parts = value.split('-');
		if (parts.length !== 3) {
			return null;
		}

		var year = parseInt(parts[0], 10);
		var month = parseInt(parts[1], 10) - 1;
		var day = parseInt(parts[2], 10);
		var date = new Date(year, month, day);
		if (Number.isNaN(date.getTime())) {
			return null;
		}

		return date;
	}

	function formatDisplayDate(value, locale, placeholder) {
		var date = parseIsoDateToLocal(value);
		if (!date) {
			return placeholder;
		}

		try {
			return new Intl.DateTimeFormat(locale || 'en', {
				day: 'numeric',
				month: 'short',
				year: 'numeric'
			}).format(date);
		} catch (e) {
			return new Intl.DateTimeFormat('en', {
				day: 'numeric',
				month: 'short',
				year: 'numeric'
			}).format(date);
		}
	}

	function syncDateDisplay(input) {
		if (!(input instanceof HTMLInputElement)) {
			return;
		}

		var wrapper = input.closest('.lvdl-lh-date-input-wrap');
		if (!wrapper) {
			return;
		}

		var valueNode = wrapper.querySelector('[data-date-display-value]');
		if (!valueNode) {
			return;
		}

		var placeholder = String(input.dataset.displayPlaceholder || 'Select date');
		var locale = String(input.dataset.displayLocale || 'en');
		valueNode.textContent = formatDisplayDate(input.value, locale, placeholder);
	}

	function syncAllDateDisplays() {
		document.querySelectorAll('.lvdl-lh-date-input[data-date-input]').forEach(function (input) {
			syncDateDisplay(input);
		});
	}

	function getFormI18n(form) {
		var defaults = {
			checkInInvalid: 'Check-in date is required and must be valid.',
			checkOutInvalid: 'Check-out date is required and must be valid.',
			checkOutAfter: 'Check-out must be after check-in.',
			stayMax: 'Stay cannot exceed {days} days.',
			adultsMin: 'At least 1 adult is required.',
			guestsNegative: 'Guest counts cannot be negative.',
			genericError: 'Something went wrong. Please try again.'
		};
		if (!form) {
			return defaults;
		}

		return {
			checkInInvalid: String(form.dataset.i18nCheckInInvalid || defaults.checkInInvalid),
			checkOutInvalid: String(form.dataset.i18nCheckOutInvalid || defaults.checkOutInvalid),
			checkOutAfter: String(form.dataset.i18nCheckOutAfter || defaults.checkOutAfter),
			stayMax: String(form.dataset.i18nStayMax || defaults.stayMax),
			adultsMin: String(form.dataset.i18nAdultsMin || defaults.adultsMin),
			guestsNegative: String(form.dataset.i18nGuestsNegative || defaults.guestsNegative),
			genericError: String(form.dataset.i18nGenericError || defaults.genericError)
		};
	}

	function validate(payload, maxStayDays, i18n) {
		var errors = [];
		var checkIn = parseDate(payload.checkInDate);
		var checkOut = parseDate(payload.checkOutDate);

		if (!checkIn) {
			errors.push(i18n.checkInInvalid);
		}
		if (!checkOut) {
			errors.push(i18n.checkOutInvalid);
		}
		if (checkIn && checkOut) {
			var nights = diffDays(checkIn, checkOut);
			if (nights <= 0) {
				errors.push(i18n.checkOutAfter);
			}
			if (nights > maxStayDays) {
				errors.push(i18n.stayMax.replace('{days}', String(maxStayDays)));
			}
		}
		if (payload.adults < 1) {
			errors.push(i18n.adultsMin);
		}
		if (payload.children < 0 || payload.infants < 0) {
			errors.push(i18n.guestsNegative);
		}

		return errors;
	}

	function serializeForm(form) {
		var data = new FormData(form);
		return {
			nonce: lvdlLhWidget.nonce,
			checkInDate: String(data.get('checkInDate') || ''),
			checkOutDate: String(data.get('checkOutDate') || ''),
			adults: parseInt(data.get('adults') || '0', 10),
			children: parseInt(data.get('children') || '0', 10),
			infants: parseInt(data.get('infants') || '0', 10),
			promocode: String(data.get('promocode') || ''),
			currency: String(data.get('currency') || ''),
			locale: String(data.get('locale') || ''),
			trackPage: String(data.get('trackPage') || 'yes'),
			channel_code: String(data.get('channel_code') || '')
		};
	}

	function renderErrors(container, errors) {
		if (!container) {
			return;
		}
		container.innerHTML = '';
		if (!errors.length) {
			return;
		}
		var ul = document.createElement('ul');
		errors.forEach(function (error) {
			var li = document.createElement('li');
			li.textContent = error;
			ul.appendChild(li);
		});
		container.appendChild(ul);
	}

	async function requestFreshNonce() {
		var response = await fetch(lvdlLhWidget.nonceUrl, {
			method: 'GET',
			headers: {
				'Content-Type': 'application/json'
			}
		});
		if (!response.ok) {
			return null;
		}
		var data = await response.json();
		if (!data || !data.nonce) {
			return null;
		}
		return String(data.nonce);
	}

	async function requestBookingUrl(payload) {
		var response = await fetch(lvdlLhWidget.restUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json'
			},
			body: JSON.stringify(payload)
		});
		var data = await response.json();
		return { response: response, data: data };
	}

	async function submitForm(form) {
		var button = form.querySelector('button[type="submit"]');
		var errorNode = form.querySelector('.lvdl-lh-errors');
		var payload = serializeForm(form);
		var maxStayDays = Number(lvdlLhWidget.config && lvdlLhWidget.config.maxStayDays ? lvdlLhWidget.config.maxStayDays : 28);
		var i18n = getFormI18n(form);

		var clientErrors = validate(payload, maxStayDays, i18n);
		if (clientErrors.length) {
			renderErrors(errorNode, clientErrors);
			return;
		}

		renderErrors(errorNode, []);
		if (button) {
			button.disabled = true;
		}

		try {
			var result = await requestBookingUrl(payload);
			var response = result.response;
			var data = result.data;

			if (!response.ok && data && data.code === 'lvdl_lh_invalid_nonce') {
				var freshNonce = await requestFreshNonce();
				if (freshNonce) {
					lvdlLhWidget.nonce = freshNonce;
					payload.nonce = freshNonce;
					result = await requestBookingUrl(payload);
					response = result.response;
					data = result.data;
				}
			}

			if (!response.ok) {
				var serverErrors = [];
				if (data && data.data && data.data.field_errors) {
					Object.keys(data.data.field_errors).forEach(function (key) {
						serverErrors.push(data.data.field_errors[key]);
					});
				}
				if (!serverErrors.length) {
					serverErrors.push((data && data.message) || i18n.genericError);
				}
				renderErrors(errorNode, serverErrors);
				return;
			}

			if (data && data.redirect_url) {
				window.location.assign(data.redirect_url);
				return;
			}

			renderErrors(errorNode, [i18n.genericError]);
		} catch (e) {
			renderErrors(errorNode, [i18n.genericError]);
		} finally {
			if (button) {
				button.disabled = false;
			}
		}
	}

	document.addEventListener('submit', function (event) {
		var form = event.target;
		if (!(form instanceof HTMLFormElement)) {
			return;
		}
		if (!form.classList.contains('lvdl-lh-widget')) {
			return;
		}

		event.preventDefault();
		submitForm(form);
	});

	document.addEventListener('input', function (event) {
		var target = event.target;
		if (!(target instanceof HTMLInputElement)) {
			return;
		}
		if (!target.matches('.lvdl-lh-date-input[data-date-input]')) {
			return;
		}

		syncDateDisplay(target);
	});

	document.addEventListener('change', function (event) {
		var target = event.target;
		if (!(target instanceof HTMLInputElement)) {
			return;
		}
		if (!target.matches('.lvdl-lh-date-input[data-date-input]')) {
			return;
		}

		syncDateDisplay(target);
	});

	applyHoverContrast();
	syncAllDateDisplays();
})();
