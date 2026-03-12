/**
 * WallPress Gallery — Admin Script
 * Vanilla JS. No jQuery dependency.
 */
/* global wpg_admin, wp */

(function () {
	'use strict';

	var ajaxurl = wpg_admin ? wpg_admin.ajaxurl : '';
	var nonce   = wpg_admin ? wpg_admin.nonce   : '';

	// ── Utilities ─────────────────────────────────────────────────────────────
	function postAjax(action, data, callback) {
		var fd = new FormData();
		fd.append('action', action);
		fd.append('nonce',  nonce);
		Object.keys(data).forEach(function (k) {
			if (Array.isArray(data[k])) {
				data[k].forEach(function (v) { fd.append(k + '[]', v); });
			} else {
				fd.append(k, data[k]);
			}
		});
		fetch(ajaxurl, { method: 'POST', body: fd })
			.then(function (r) { return r.json(); })
			.then(callback)
			.catch(function (e) { console.error(action, e); });
	}

	function debounce(fn, ms) {
		var t;
		return function () {
			clearTimeout(t);
			t = setTimeout(fn.bind(this, arguments), ms);
		};
	}

	function formatBytes(bytes) {
		if (bytes < 1024)     { return bytes + ' B'; }
		if (bytes < 1048576)  { return (bytes / 1024).toFixed(1) + ' KB'; }
		return (bytes / 1048576).toFixed(1) + ' MB';
	}

	// ══════════════════════════════════════════════════════════════════════════
	// BULK UPLOAD
	// ══════════════════════════════════════════════════════════════════════════
	var dropzone     = document.getElementById('wpg-dropzone');
	var fileInput    = document.getElementById('wpg-file-input');
	var fileListWrap = document.getElementById('wpg-file-list-wrap');
	var fileListEl   = document.getElementById('wpg-file-list');
	var fileCountEl  = document.getElementById('wpg-file-count');
	var clearBtn     = document.getElementById('wpg-clear-files');
	var uploadBtn    = document.getElementById('wpg-start-upload');
	var progressWrap = document.getElementById('wpg-progress-wrap');
	var progressFill = document.getElementById('wpg-progress-fill');
	var progressLbl  = document.getElementById('wpg-progress-label');
	var resultsEl    = document.getElementById('wpg-upload-results');
	var summaryEl    = document.getElementById('wpg-upload-summary');
	var summaryText  = document.getElementById('wpg-summary-text');
	var uploadMoreBtn= document.getElementById('wpg-upload-more');

	/** @type {File[]} */
	var selectedFiles = [];

	if (dropzone) {
		// Open file browser on click / keyboard
		dropzone.addEventListener('click', function () { fileInput.click(); });
		dropzone.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') { fileInput.click(); }
		});

		// Drag events
		['dragenter', 'dragover'].forEach(function (evt) {
			dropzone.addEventListener(evt, function (e) {
				e.preventDefault();
				dropzone.classList.add('dragover');
			});
		});
		['dragleave', 'drop'].forEach(function (evt) {
			dropzone.addEventListener(evt, function (e) {
				e.preventDefault();
				dropzone.classList.remove('dragover');
			});
		});
		dropzone.addEventListener('drop', function (e) {
			addFiles(Array.prototype.slice.call(e.dataTransfer.files));
		});

		fileInput.addEventListener('change', function () {
			addFiles(Array.prototype.slice.call(fileInput.files));
			fileInput.value = '';
		});

		if (clearBtn) {
			clearBtn.addEventListener('click', resetUploader);
		}

		if (uploadBtn) {
			uploadBtn.addEventListener('click', startUpload);
		}

		if (uploadMoreBtn) {
			uploadMoreBtn.addEventListener('click', function () {
				resetUploader();
				summaryEl.style.display = 'none';
				dropzone.style.display  = '';
			});
		}
	}

	function addFiles(files) {
		files.forEach(function (f) {
			if (!f.type.startsWith('image/')) { return; }
			selectedFiles.push(f);
		});
		renderFileList();
	}

	function renderFileList() {
		if (!fileListEl) { return; }
		fileListEl.innerHTML = '';

		selectedFiles.forEach(function (f, i) {
			var item = document.createElement('div');
			item.className = 'wpg-file-item';
			item.innerHTML =
				'<span class="wpg-file-item-status">📄</span>' +
				'<span class="wpg-file-item-name" title="' + escHtml(f.name) + '">' + escHtml(f.name) + '</span>' +
				'<span class="wpg-file-item-size">' + formatBytes(f.size) + '</span>' +
				'<button class="wpg-file-item-remove" data-index="' + i + '" title="Remove">✕</button>';
			fileListEl.appendChild(item);
		});

		// Remove buttons
		fileListEl.querySelectorAll('.wpg-file-item-remove').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var idx = parseInt(btn.getAttribute('data-index'), 10);
				selectedFiles.splice(idx, 1);
				renderFileList();
			});
		});

		if (fileCountEl) {
			fileCountEl.textContent = '(' + selectedFiles.length + ')';
		}

		if (fileListWrap) {
			fileListWrap.style.display = selectedFiles.length ? '' : 'none';
		}
	}

	function resetUploader() {
		selectedFiles = [];
		renderFileList();
		if (progressWrap) { progressWrap.style.display = 'none'; }
		if (progressFill) { progressFill.style.width   = '0%'; }
		if (progressLbl)  { progressLbl.textContent     = '0%'; }
		if (resultsEl)    { resultsEl.innerHTML          = ''; }
	}

	function startUpload() {
		if (!selectedFiles.length) { return; }

		// Hide file list, show progress
		if (fileListWrap) { fileListWrap.style.display  = 'none'; }
		if (progressWrap) { progressWrap.style.display  = ''; }
		if (summaryEl)    { summaryEl.style.display      = 'none'; }
		if (resultsEl)    { resultsEl.innerHTML           = ''; }

		var total    = selectedFiles.length;
		var done     = 0;
		var success  = 0;
		var failures = 0;
		var fileCopy = selectedFiles.slice();

		function uploadNext(index) {
			if (index >= fileCopy.length) {
				// All done
				if (summaryEl) { summaryEl.style.display = ''; }
				if (summaryText) {
					summaryText.textContent =
						success + ' uploaded, ' + failures + ' failed.';
				}
				return;
			}

			var f  = fileCopy[index];
			var fd = new FormData();
			fd.append('action', 'wpg_bulk_upload');
			fd.append('nonce',  nonce);
			fd.append('file',   f, f.name);

			var resultItem = document.createElement('div');
			resultItem.className  = 'wpg-file-item';
			resultItem.innerHTML  =
				'<span class="wpg-file-item-status" id="wpg-status-' + index + '">⏳</span>' +
				'<span class="wpg-file-item-name">' + escHtml(f.name) + '</span>' +
				'<span class="wpg-file-item-size wpg-result-msg" id="wpg-msg-' + index + '">Uploading…</span>';
			if (resultsEl) { resultsEl.appendChild(resultItem); }

			fetch(ajaxurl, { method: 'POST', body: fd })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					done++;
					var pct = Math.round((done / total) * 100);
					if (progressFill) { progressFill.style.width   = pct + '%'; }
					if (progressLbl)  { progressLbl.textContent     = pct + '%'; }

					var statusEl = document.getElementById('wpg-status-' + index);
					var msgEl    = document.getElementById('wpg-msg-'    + index);
					if (res.success) {
						success++;
						if (statusEl) { statusEl.textContent = '✅'; }
						if (msgEl && res.data) {
							msgEl.innerHTML = '<a href="' + escHtml(res.data.edit_url) + '" target="_blank" style="color:var(--wpg-admin-accent);">' + escHtml(res.data.title) + '</a>';
						}
					} else {
						failures++;
						if (statusEl) { statusEl.textContent = '❌'; }
						if (msgEl) { msgEl.textContent = (res.data && res.data.message) ? res.data.message : 'Error'; }
					}
					uploadNext(index + 1);
				})
				.catch(function () {
					done++; failures++;
					var statusEl = document.getElementById('wpg-status-' + index);
					var msgEl    = document.getElementById('wpg-msg-'    + index);
					if (statusEl) { statusEl.textContent = '❌'; }
					if (msgEl) { msgEl.textContent = 'Network error'; }
					uploadNext(index + 1);
				});
		}

		uploadNext(0);
	}

	// ══════════════════════════════════════════════════════════════════════════
	// MANAGE IMAGES
	// ══════════════════════════════════════════════════════════════════════════
	var imagesTable  = document.getElementById('wpg-images-table');
	var selectAllCb  = document.getElementById('wpg-select-all');
	var bulkAction   = document.getElementById('wpg-bulk-action');
	var bulkApply    = document.getElementById('wpg-bulk-apply');
	var bulkStatus   = document.getElementById('wpg-bulk-status');

	// Select all
	if (selectAllCb) {
		selectAllCb.addEventListener('change', function () {
			document.querySelectorAll('.wpg-row-check').forEach(function (cb) {
				cb.checked = selectAllCb.checked;
			});
		});
	}

	// Featured toggle
	document.querySelectorAll('.wpg-toggle-featured').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var id = btn.getAttribute('data-id');
			postAjax('wpg_admin_toggle_featured', { post_id: id }, function (res) {
				if (res.success) {
					var isFeatured = res.data.featured;
					btn.textContent = isFeatured ? '★' : '☆';
					btn.style.color = isFeatured ? '#7fff00' : 'var(--wpg-admin-muted)';
					btn.classList.toggle('active', isFeatured);
				}
			});
		});
	});

	// Trending toggle
	document.querySelectorAll('.wpg-toggle-trending').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var id = btn.getAttribute('data-id');
			postAjax('wpg_admin_toggle_trending', { post_id: id }, function (res) {
				if (res.success) {
					var isTrending = res.data.trending;
					btn.textContent = isTrending ? '🔥' : '○';
					btn.style.color = isTrending ? '#ff7700' : 'var(--wpg-admin-muted)';
					btn.classList.toggle('active', isTrending);
				}
			});
		});
	});

	// Delete single image
	document.querySelectorAll('.wpg-delete-image').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var id = btn.getAttribute('data-id');
			if (!confirm('Delete this image? This cannot be undone.')) { return; }
			postAjax('wpg_admin_delete_image', { post_id: id }, function (res) {
				if (res.success) {
					var row = btn.closest('tr');
					if (row) {
						row.style.transition = 'opacity 0.3s';
						row.style.opacity    = '0';
						setTimeout(function () { row.remove(); }, 300);
					}
				}
			});
		});
	});

	// Bulk apply
	if (bulkApply) {
		bulkApply.addEventListener('click', function () {
			var action = bulkAction ? bulkAction.value : '';
			if (!action) { return; }

			var ids = [];
			document.querySelectorAll('.wpg-row-check:checked').forEach(function (cb) {
				ids.push(cb.value);
			});
			if (!ids.length) { return; }

			if (action === 'delete' && !confirm('Delete ' + ids.length + ' image(s)? This cannot be undone.')) {
				return;
			}

			postAjax('wpg_admin_bulk_action', { bulk_action: action, post_ids: ids }, function (res) {
				if (res.success) {
					if (action === 'delete') {
						ids.forEach(function (id) {
							var row = document.querySelector('tr[data-id="' + id + '"]');
							if (row) { row.remove(); }
						});
					}
					if (bulkStatus) {
						bulkStatus.textContent = res.data.processed + ' item(s) processed.';
						setTimeout(function () { bulkStatus.textContent = ''; }, 3000);
					}
				}
			});
		});
	}

	// ══════════════════════════════════════════════════════════════════════════
	// MEDIA UPLOADER (for meta boxes)
	// ══════════════════════════════════════════════════════════════════════════
	// This is handled inline in class-meta-boxes.php and categories.php via
	// script tags that run only when wp.media is available.

	// ── Escape helper ─────────────────────────────────────────────────────────
	function escHtml(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

}());
