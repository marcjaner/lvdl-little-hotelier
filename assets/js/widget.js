(function () {
	'use strict';

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

	function validate(payload, maxStayDays) {
		var errors = [];
		var checkIn = parseDate(payload.checkInDate);
		var checkOut = parseDate(payload.checkOutDate);

		if (!checkIn) {
			errors.push('Check-in date is required and must be valid.');
		}
		if (!checkOut) {
			errors.push('Check-out date is required and must be valid.');
		}
		if (checkIn && checkOut) {
			var nights = diffDays(checkIn, checkOut);
			if (nights <= 0) {
				errors.push('Check-out must be after check-in.');
			}
			if (nights > maxStayDays) {
				errors.push('Stay cannot exceed ' + maxStayDays + ' days.');
			}
		}
		if (payload.adults < 1) {
			errors.push('At least 1 adult is required.');
		}
		if (payload.children < 0 || payload.infants < 0) {
			errors.push('Guest counts cannot be negative.');
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

		var clientErrors = validate(payload, maxStayDays);
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
					serverErrors.push((data && data.message) || lvdlLhWidget.i18n.genericError);
				}
				renderErrors(errorNode, serverErrors);
				return;
			}

			if (data && data.redirect_url) {
				window.location.assign(data.redirect_url);
				return;
			}

			renderErrors(errorNode, [lvdlLhWidget.i18n.genericError]);
		} catch (e) {
			renderErrors(errorNode, [lvdlLhWidget.i18n.genericError]);
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
})();
