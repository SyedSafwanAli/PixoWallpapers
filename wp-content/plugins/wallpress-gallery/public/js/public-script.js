/**
 * WallPress Gallery — Public Script
 * Vanilla JS only. No jQuery.
 */
/* global wpg_public */

(function () {
	'use strict';

	var ajaxurl  = wpg_public ? wpg_public.ajaxurl   : '';
	var nonce    = wpg_public ? wpg_public.nonce      : '';
	var perPage  = wpg_public ? parseInt(wpg_public.per_page, 10) : 12;

	// ── Utilities ─────────────────────────────────────────────────────────────

	function postAjax(action, data, callback) {
		var fd = new FormData();
		fd.append('action', action);
		fd.append('nonce',  nonce);
		Object.keys(data).forEach(function (k) {
			fd.append(k, data[k]);
		});
		return fetch(ajaxurl, { method: 'POST', body: fd })
			.then(function (r) { return r.json(); })
			.then(callback)
			.catch(function (e) { console.warn('[WPG] ' + action, e); });
	}

	function debounce(fn, ms) {
		var t;
		return function () {
			var args = arguments;
			var ctx  = this;
			clearTimeout(t);
			t = setTimeout(function () { fn.apply(ctx, args); }, ms);
		};
	}

	// ── Search ────────────────────────────────────────────────────────────────

	var searchInputs = document.querySelectorAll('.wpg-search-input');

	searchInputs.forEach(function (input) {
		var wrap        = input.closest('.wpg-search-wrap');
		var resultsBox  = wrap ? wrap.querySelector('.wpg-search-results') : null;
		var count       = parseInt(input.getAttribute('data-count'), 10) || 12;
		var catPicker   = wrap ? wrap.querySelector('.wpg-sb-cat-picker') : null;
		var clearBtn    = wrap ? wrap.querySelector('.wpg-sb-search-clear') : null;

		if (!resultsBox) { return; }

		function getSelectedCat() {
			return input.getAttribute('data-cat') || '';
		}

		function syncClearBtn() {
			if (clearBtn) { clearBtn.hidden = input.value.trim() === ''; }
		}

		var doSearch = debounce(function () {
			var q   = input.value.trim();
			var cat = getSelectedCat();
			if (q.length < 2) {
				resultsBox.classList.remove('visible');
				resultsBox.innerHTML = '';
				return;
			}

			postAjax('wpg_search', { query: q, count: count, category: cat }, function (res) {
				if (!res.success) { return; }
				resultsBox.innerHTML = res.data.html ||
					'<div class="wpg-search-no-results">No results found.</div>';
				resultsBox.classList.add('visible');
			});
		}, 300);

		input.addEventListener('input', function () { syncClearBtn(); doSearch(); });

		if (clearBtn) {
			clearBtn.addEventListener('click', function () {
				input.value = '';
				syncClearBtn();
				resultsBox.classList.remove('visible');
				resultsBox.innerHTML = '';
				input.focus();
			});
		}

		// ── Category picker dropdown ──
		if (catPicker) {
			var catBtn  = catPicker.querySelector('.wpg-sb-cat-btn');
			var catOpts = catPicker.querySelectorAll('.wpg-sb-cat-opt');
			var catLabel = catPicker.querySelector('.wpg-sb-cat-label');

			catBtn.addEventListener('click', function (e) {
				e.stopPropagation();
				catPicker.classList.toggle('open');
				catBtn.setAttribute('aria-expanded', catPicker.classList.contains('open'));
			});

			catOpts.forEach(function (opt) {
				opt.addEventListener('click', function () {
					catOpts.forEach(function (o) { o.classList.remove('selected'); });
					opt.classList.add('selected');
					catLabel.textContent = opt.getAttribute('data-label');
					input.setAttribute('data-cat', opt.getAttribute('data-cat') || '');
					catPicker.classList.remove('open');
					catBtn.setAttribute('aria-expanded', 'false');
					// Re-trigger search with new category
					if (input.value.trim().length >= 2) { doSearch(); }
				});
			});

			document.addEventListener('click', function (e) {
				if (!catPicker.contains(e.target)) {
					catPicker.classList.remove('open');
					catBtn.setAttribute('aria-expanded', 'false');
				}
			});
		}

		// Close results on outside click or Escape
		document.addEventListener('click', function (e) {
			if (!wrap.contains(e.target)) {
				resultsBox.classList.remove('visible');
			}
		});

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				resultsBox.classList.remove('visible');
				if (catPicker) { catPicker.classList.remove('open'); }
				input.blur();
			}
		});
	});

	// ── Tab Filter (multi-instance safe) ─────────────────────────────────────

	function initCategoryFilter(moduleEl) {
		var tabs     = moduleEl.querySelectorAll('.wpg-tab-btn');
		var gridWrap = moduleEl.querySelector('.wpg-filter-target');
		if (!tabs.length || !gridWrap) { return; }

		// Read per-instance config from the wrapper element
		var defaultColumns = moduleEl.getAttribute('data-columns') || 4;
		var defaultCount   = moduleEl.getAttribute('data-count')   || perPage;
		var defaultOrderby = moduleEl.getAttribute('data-orderby') || 'date';
		var defaultStyle   = moduleEl.getAttribute('data-style')   || 'uniform';
		var paginationType = moduleEl.getAttribute('data-pagination') || 'numbered';

		// Infinite scroll state per instance
		var infiniteObserver = null;
		var infinitePage     = 1;
		var infiniteLoading  = false;
		var infiniteExhausted = false;
		var activeCat        = '';

		function setActiveTab(btn) {
			tabs.forEach(function (b) {
				b.classList.remove('active');
				b.setAttribute('aria-selected', 'false');
			});
			btn.classList.add('active');
			btn.setAttribute('aria-selected', 'true');
		}

		function loadGrid(cat, page, appendMode, onDone) {
			if (!appendMode) { gridWrap.style.opacity = '0.4'; }

			postAjax('wpg_filter_category', {
				category: cat,
				page:     page,
				columns:  defaultColumns,
				count:    defaultCount,
				orderby:  defaultOrderby,
				style:    defaultStyle,
			}, function (res) {
				gridWrap.style.opacity = '1';
				if (!res.success) { return; }

				var innerGrid = gridWrap.querySelector('.wpg-grid-uniform, .wpg-grid-masonry, .wpg-grid-portrait, .wpg-grid-natural, .wpg-grid-spotlight, .wpg-grid-filmstrip');

				if (appendMode && innerGrid) {
					var tmp = document.createElement('div');
					tmp.innerHTML = res.data.html;
					while (tmp.firstChild) { innerGrid.appendChild(tmp.firstChild); }
				} else if (innerGrid) {
					innerGrid.innerHTML = res.data.html;
				} else {
					gridWrap.innerHTML = res.data.html;
				}

				// Reflow masonry spans after AJAX inject
				var mGrid = gridWrap.querySelector('.wpg-grid-masonry');
				if (mGrid && typeof calcMasonrySpans === 'function') {
					// wait for images then recalc
					var mImgs = mGrid.querySelectorAll('img');
					if (mImgs.length) {
						var mLoaded = 0;
						mImgs.forEach(function (img) {
							function mLoad() {
								mLoaded++;
								if (mLoaded === mImgs.length) { calcMasonrySpans(mGrid); }
							}
							if (img.complete) { mLoad(); } else {
								img.addEventListener('load',  mLoad);
								img.addEventListener('error', mLoad);
							}
						});
					} else {
						calcMasonrySpans(mGrid);
					}
				}

				attachCardHandlers(gridWrap);
				if (onDone) { onDone(res.data); }
			});
		}

		// Infinite scroll setup
		function setupInfiniteScroll(cat) {
			if (infiniteObserver) {
				infiniteObserver.disconnect();
				infiniteObserver = null;
			}
			infinitePage      = 1;
			infiniteLoading   = false;
			infiniteExhausted = false;

			if (!window.IntersectionObserver) { return; }

			var sentinel = moduleEl.querySelector('.wpg-infinite-sentinel');
			if (!sentinel) {
				sentinel = document.createElement('div');
				sentinel.className = 'wpg-infinite-sentinel';
				gridWrap.parentNode.insertBefore(sentinel, gridWrap.nextSibling);
			}

			infiniteObserver = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (!entry.isIntersecting || infiniteLoading || infiniteExhausted) { return; }
					infiniteLoading = true;
					infinitePage++;
					loadGrid(cat, infinitePage, true, function (data) {
						infiniteLoading = false;
						if (!data.has_more) {
							infiniteExhausted = true;
							if (infiniteObserver) { infiniteObserver.disconnect(); }
						}
					});
				});
			}, { rootMargin: '200px' });

			infiniteObserver.observe(sentinel);
		}

		// Load more button per instance
		var loadMoreBtn = moduleEl.querySelector('.wpg-load-more-btn');
		if (loadMoreBtn && paginationType === 'load_more') {
			loadMoreBtn.addEventListener('click', function () {
				if (loadMoreBtn.disabled) { return; }
				var currentPage = parseInt(loadMoreBtn.getAttribute('data-page'), 10) || 1;
				var nextPage    = currentPage + 1;
				loadMoreBtn.disabled    = true;
				loadMoreBtn.textContent = 'Loading…';

				loadGrid(activeCat, nextPage, true, function (data) {
					loadMoreBtn.disabled    = false;
					loadMoreBtn.textContent = 'Load More';
					loadMoreBtn.setAttribute('data-page', nextPage);
					if (!data.has_more) { loadMoreBtn.style.display = 'none'; }
				});
			});
		}

		// Tab click handler
		tabs.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var cat = btn.getAttribute('data-cat') || '';
				activeCat = cat;

				setActiveTab(btn);

				// Update URL hash only if this is the primary/first filter on page
				var allFilters = document.querySelectorAll(
					'.wpg-category-filter-module, .wpg-category-filter-wrap'
				);
				if (allFilters[0] === moduleEl) {
					if (cat) {
						history.replaceState(null, '', '#category-' + cat);
					} else {
						history.replaceState(null, '', window.location.pathname + window.location.search);
					}
				}

				// Reset load-more page
				if (loadMoreBtn) {
					loadMoreBtn.setAttribute('data-page', '1');
					loadMoreBtn.style.display = '';
				}

				loadGrid(cat, 1, false, function (data) {
					if (paginationType === 'infinite') {
						setupInfiniteScroll(cat);
					}
					if (loadMoreBtn && !data.has_more) {
						loadMoreBtn.style.display = 'none';
					}
				});
			});
		});

		// Start infinite scroll on load if configured
		if (paginationType === 'infinite') {
			setupInfiniteScroll(activeCat);
		}
	}

	// Init every filter module on the page
	document.querySelectorAll('.wpg-category-filter-module, .wpg-category-filter-wrap').forEach(
		initCategoryFilter
	);

	// ── Numbered Pagination for standalone grid wraps ───────────────────

	document.querySelectorAll('.wpg-grid-wrap').forEach(function (gridWrap) {
		if (gridWrap.closest('.wpg-category-filter-module, .wpg-category-filter-wrap, .wpg-filter-module')) { return; }
		if (gridWrap.getAttribute('data-pagination') !== 'numbered') { return; }

		var pagination = gridWrap.querySelector('.wpg-pagination');
		if (!pagination) { return; }

		var grid = gridWrap.querySelector('.wpg-grid-uniform, .wpg-grid-masonry, .wpg-grid-portrait, .wpg-grid-natural, .wpg-grid-spotlight, .wpg-grid-filmstrip');
		if (!grid) { return; }

		pagination.addEventListener('click', function (e) {
			var btn = e.target.closest('.wpg-page-btn');
			if (!btn || btn.classList.contains('active') || btn.disabled) { return; }

			var page = parseInt(btn.getAttribute('data-page'), 10) || 1;

			pagination.querySelectorAll('.wpg-page-btn').forEach(function (b) { b.classList.remove('active'); });
			btn.classList.add('active');
			gridWrap.style.opacity = '0.5';

			postAjax('wpg_load_more', {
				page:     page,
				orderby:  grid.getAttribute('data-orderby')  || 'date',
				columns:  grid.getAttribute('data-columns')  || 4,
				count:    grid.getAttribute('data-count')    || perPage,
				style:    grid.getAttribute('data-style')    || 'uniform',
				category: grid.getAttribute('data-category') || '',
				tag:      grid.getAttribute('data-tag')      || '',
			}, function (res) {
				gridWrap.style.opacity = '1';
				if (!res.success) { return; }

				// res.data.html contains raw card HTML (no grid wrapper)
				grid.innerHTML = res.data.html;
				attachCardHandlers(grid);

				if (grid.classList.contains('wpg-grid-masonry') && typeof calcMasonrySpans === 'function') {
					var imgs = grid.querySelectorAll('img');
					var done = 0;
					if (!imgs.length) {
						calcMasonrySpans(grid);
					} else {
						imgs.forEach(function (img) {
							function onL() { done++; if (done === imgs.length) { calcMasonrySpans(grid); } }
							if (img.complete) { onL(); } else {
								img.addEventListener('load',  onL);
								img.addEventListener('error', onL);
							}
						});
					}
				}

				gridWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
			});
		});
	});

	// ── Infinite Scroll for standalone grid wraps ────────────────────────────

	document.querySelectorAll('.wpg-grid-wrap').forEach(function (gridWrap) {
		// Skip if inside a filter module (handled by initCategoryFilter or initFilterModule)
		if (gridWrap.closest('.wpg-category-filter-module, .wpg-category-filter-wrap, .wpg-filter-module')) { return; }

		var sentinel = gridWrap.querySelector('.wpg-infinite-sentinel');
		if (!sentinel || !window.IntersectionObserver) { return; }

		var grid = gridWrap.querySelector('.wpg-grid-uniform, .wpg-grid-masonry, .wpg-grid-portrait, .wpg-grid-natural, .wpg-grid-spotlight, .wpg-grid-filmstrip');
		if (!grid) { return; }

		var page      = 1;
		var loading   = false;
		var exhausted = false;

		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting || loading || exhausted) { return; }
				loading = true;
				page++;

				postAjax('wpg_load_more', {
					page:     page,
					orderby:  grid.getAttribute('data-orderby')  || 'date',
					columns:  grid.getAttribute('data-columns')  || 4,
					count:    grid.getAttribute('data-count')    || perPage,
					style:    grid.getAttribute('data-style')    || 'uniform',
					category: grid.getAttribute('data-category') || '',
					tag:      grid.getAttribute('data-tag')      || '',
				}, function (res) {
					loading = false;
					if (!res.success) { return; }

					// Collect new cards during append — never clear existing spans (prevents scroll jump)
					var newCards = [];
					var tmp = document.createElement('div');
					tmp.innerHTML = res.data.html;
					while (tmp.firstChild) {
						var child = tmp.firstChild;
						grid.appendChild(child);
						if (child.nodeType === 1) {
							if (child.classList && child.classList.contains('wpg-card')) {
								newCards.push(child);
							} else if (child.querySelectorAll) {
								child.querySelectorAll('.wpg-card').forEach(function (c) { newCards.push(c); });
							}
						}
					}

					attachCardHandlers(grid);

					// Only span NEW cards — never touch existing spans (prevents scroll jump)
					if (grid.classList.contains('wpg-grid-masonry') && newCards.length) {
						var mROW = 4;
						var mGap = 14;
						try {
							var cg2 = parseFloat(window.getComputedStyle(grid).columnGap);
							if (!isNaN(cg2) && cg2 > 0) { mGap = cg2; }
						} catch (e) {}

						function applyNewSpans() {
							requestAnimationFrame(function () {
								newCards.forEach(function (card) {
									var h = card.getBoundingClientRect().height;
									if (!h) { return; }
									card.style.gridRowEnd = 'span ' + Math.ceil((h + mGap) / mROW);
								});
							});
						}

						var newImgs = [];
						newCards.forEach(function (c) {
							c.querySelectorAll('img').forEach(function (i) { newImgs.push(i); });
						});

						if (!newImgs.length) {
							applyNewSpans();
						} else {
							var doneCount = 0;
							newImgs.forEach(function (img) {
								function onDone() { doneCount++; if (doneCount === newImgs.length) { applyNewSpans(); } }
								if (img.complete) { onDone(); } else {
									img.addEventListener('load',  onDone);
									img.addEventListener('error', onDone);
								}
							});
						}
					}

					if (!res.data.has_more) {
						exhausted = true;
						observer.disconnect();
						sentinel.style.display = 'none';
					}
				});
			});
		}, { rootMargin: '600px' });

		observer.observe(sentinel);
	});

	// ── Generate 4K button ────────────────────────────────────────────────────

	function initGenerate4kButtons(ctx) {
		var container = ctx || document;
		container.querySelectorAll('.wpg-generate-4k-btn').forEach(function (btn) {
			if (btn.dataset.wpgHandled) { return; }
			btn.dataset.wpgHandled = '1';

			btn.addEventListener('click', function () {
				if (btn.disabled) { return; }
				btn.disabled = true;
				btn.classList.add('wpg-generating');
				btn.innerHTML =
					'<span class="wpg-btn-spinner"></span>' +
					'<span> Generating 4K…</span>';

				postAjax('wpg_generate_4k', { post_id: btn.getAttribute('data-id') }, function (res) {
					if (res.success) {
						var dl = document.createElement('a');
						dl.className   = 'wpg-dl-btn wpg-dl-primary';
						dl.href        = res.data.url;
						dl.setAttribute('download', '');
						dl.setAttribute('rel', 'nofollow');
						dl.setAttribute('data-id',   btn.getAttribute('data-id'));
						dl.setAttribute('data-type', '4k');
						dl.innerHTML =
							'<svg width="18" height="18" viewBox="0 0 24 24" fill="none">' +
							'<path d="M12 3v13m0 0l-4-4m4 4l4-4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>' +
							'<path d="M5 21h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>' +
							' Download in 4K (' + (res.data.res_label || '3840×2160') + ')';
						btn.parentNode.replaceChild(dl, btn);
					} else {
						btn.disabled = false;
						btn.classList.remove('wpg-generating');
						if (res.data && res.data.code === 'already_optimal') {
							var note = document.createElement('span');
							note.className = 'wpg-4k-optimal-note';
							note.innerHTML =
								'<svg width="16" height="16" viewBox="0 0 24 24" fill="none">' +
								'<path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
								' Original is already optimally sized';
							btn.parentNode.replaceChild(note, btn);
						} else {
							btn.innerHTML =
								'<svg width="18" height="18" viewBox="0 0 24 24" fill="none">' +
								'<path d="M15 4l5 5L8 21l-5-1 -1-5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
								'<path d="M17 8l-2-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>' +
								' Generate & Download 4K';
						}
					}
				});
			});
		});
	}

	// Init on page load
	initGenerate4kButtons(document);

	// ── View Count (fire-and-forget on card click) ─────────────────────────────

	function attachCardHandlers(ctx) {
		var container = ctx || document;

		// View count
		container.querySelectorAll('.wpg-card[data-id]').forEach(function (card) {
			if (card.dataset.wpgHandled) { return; }
			card.dataset.wpgHandled = '1';

			card.addEventListener('click', function (e) {
				// Don't fire if clicking the download button
				if (e.target.closest('.wpg-card-dl')) { return; }
				var id = card.getAttribute('data-id');
				if (id) {
					// Fire and forget — don't await
					postAjax('wpg_increment_views', { post_id: id }, function () {});
				}
			});
		});

		// Download tracking
		container.querySelectorAll('.wpg-dl-btn[data-id], .wpg-card-dl[data-id]').forEach(function (link) {
			if (link.dataset.wpgHandled) { return; }
			link.dataset.wpgHandled = '1';

			link.addEventListener('click', function () {
				var id   = link.getAttribute('data-id');
				var type = link.getAttribute('data-type') || '4k';
				if (id) {
					postAjax('wpg_track_download', { post_id: id, type: type }, function () {});
				}
			});
		});
	}

	// Attach on initial load
	attachCardHandlers(document);

	// ── CSS Grid Masonry (JS span calculator) ────────────────────────────────
	// card.php outputs width/height on <img> so browser reserves the correct
	// space before image loads → spans are accurate on first call.

	function calcMasonrySpans(grid) {
		var ROW_H = 4; // must match grid-auto-rows: 4px in CSS
		var gap   = 14;
		try {
			var cg = parseFloat(window.getComputedStyle(grid).columnGap);
			if (!isNaN(cg) && cg > 0) { gap = cg; }
		} catch (e) {}

		// Save scroll position — clearing spans momentarily collapses the grid
		// which can cause the browser to jump the scroll position.
		var savedY = window.scrollY !== undefined ? window.scrollY : window.pageYOffset;

		// Clear spans → browser relays natural height → remeasure
		grid.querySelectorAll('.wpg-card').forEach(function (card) {
			card.style.gridRowEnd = '';
		});
		// eslint-disable-next-line no-unused-expressions
		grid.offsetHeight; // force reflow

		grid.querySelectorAll('.wpg-card').forEach(function (card) {
			var h = card.getBoundingClientRect().height;
			if (!h) { return; }
			// gap added so next card in same column starts after a gap
			card.style.gridRowEnd = 'span ' + Math.ceil((h + gap) / ROW_H);
		});

		// Restore scroll position if the collapse caused a jump
		var newY = window.scrollY !== undefined ? window.scrollY : window.pageYOffset;
		if (newY !== savedY) { window.scrollTo(0, savedY); }
	}

	// Debounced rAF scheduler — prevents multiple back-to-back recalcs
	// (e.g. ResizeObserver firing many times during an append operation)
	var masonryCalcTimers = new (window.WeakMap || function WeakMapPolyfill() {
		var keys = [], vals = [];
		this.get = function (k) { return vals[keys.indexOf(k)]; };
		this.set = function (k, v) { var i = keys.indexOf(k); if (i > -1) { vals[i] = v; } else { keys.push(k); vals.push(v); } };
	})();

	function scheduleMasonryCalc(grid) {
		var t = masonryCalcTimers.get(grid);
		if (t) { cancelAnimationFrame(t); }
		masonryCalcTimers.set(grid, requestAnimationFrame(function () {
			masonryCalcTimers.set(grid, null);
			calcMasonrySpans(grid);
		}));
	}

	var masonryGrids = document.querySelectorAll('.wpg-grid-masonry');

	masonryGrids.forEach(function (grid) {
		// Initial calc (images already have reserved space via width/height attrs)
		scheduleMasonryCalc(grid);

		// Recalc once any lazy image actually loads (fills in for browsers
		// that don't reserve space from width/height in all situations)
		grid.querySelectorAll('img').forEach(function (img) {
			if (!img.complete) {
				img.addEventListener('load',  function () { scheduleMasonryCalc(grid); });
				img.addEventListener('error', function () { scheduleMasonryCalc(grid); });
			}
		});
	});

	// ResizeObserver — recalc on container width change (responsive)
	if (masonryGrids.length && window.ResizeObserver) {
		var ro = new ResizeObserver(function (entries) {
			entries.forEach(function (e) { scheduleMasonryCalc(e.target); });
		});
		masonryGrids.forEach(function (grid) { ro.observe(grid); });
	}

	// ── Filmstrip Arrows ──────────────────────────────────────────────────────

	document.querySelectorAll('.wpg-grid-filmstrip').forEach(function (strip) {
		var wrap = strip.closest('.wpg-grid-wrap');
		if (!wrap) { return; }

		var prev = document.createElement('button');
		prev.className    = 'wpg-filmstrip-arrow wpg-filmstrip-arrow--prev';
		prev.setAttribute('aria-label', 'Previous');
		prev.innerHTML    = '&#8249;';

		var next = document.createElement('button');
		next.className    = 'wpg-filmstrip-arrow wpg-filmstrip-arrow--next';
		next.setAttribute('aria-label', 'Next');
		next.innerHTML    = '&#8250;';

		wrap.appendChild(prev);
		wrap.appendChild(next);

		var scrollAmount = 300;

		prev.addEventListener('click', function () {
			strip.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
		});

		next.addEventListener('click', function () {
			strip.scrollBy({ left: scrollAmount, behavior: 'smooth' });
		});
	});

	// ── Resolution tabs (detail page) ────────────────────────────────────────

	document.querySelectorAll('.wpg-res-tab-btn').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var section = btn.closest('.wpg-resolutions-section');
			if (!section) { return; }

			// Update active tab
			section.querySelectorAll('.wpg-res-tab-btn').forEach(function (b) {
				b.classList.remove('active');
				var chev = b.querySelector('.wpg-res-chevron');
				if (chev) { chev.remove(); }
			});
			btn.classList.add('active');

			// Add chevron to active tab
			var chevron = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
			chevron.setAttribute('class', 'wpg-res-chevron');
			chevron.setAttribute('width', '10');
			chevron.setAttribute('height', '10');
			chevron.setAttribute('viewBox', '0 0 24 24');
			chevron.setAttribute('fill', 'none');
			var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
			path.setAttribute('d', 'M6 9l6 6 6-6');
			path.setAttribute('stroke', 'currentColor');
			path.setAttribute('stroke-width', '2.5');
			path.setAttribute('stroke-linecap', 'round');
			path.setAttribute('stroke-linejoin', 'round');
			chevron.appendChild(path);
			btn.appendChild(chevron);

			// Show panel
			var panelId = btn.getAttribute('data-panel');
			section.querySelectorAll('.wpg-res-panel').forEach(function (p) {
				p.classList.toggle('active', p.id === panelId);
			});
		});
	});

	// ── Hash-based tab activation on page load ─────────────────────────────────
	// Only activates a tab in the first/primary filter module on the page.

	(function () {
		var hash = window.location.hash;
		if (!hash || !hash.startsWith('#category-')) { return; }
		var catSlug  = hash.replace('#category-', '');
		var firstMod = document.querySelector('.wpg-category-filter-module, .wpg-category-filter-wrap');
		if (!firstMod) { return; }
		var matchBtn = firstMod.querySelector('.wpg-tab-btn[data-cat="' + catSlug + '"]');
		if (matchBtn) {
			matchBtn.click();
		}
	})();

	// ── [wpg_filter] module — RECENT / POPULAR / FEATURED / RANDOM ────────────

	function initFilterModule(moduleEl) {
		var tabs     = moduleEl.querySelectorAll('.wpg-filter-tab');
		var gridWrap = moduleEl.querySelector('.wpg-filter-target');
		if (!tabs.length || !gridWrap) { return; }

		var cols    = moduleEl.getAttribute('data-columns') || 4;
		var count   = moduleEl.getAttribute('data-count')   || perPage;
		var style   = moduleEl.getAttribute('data-style')   || 'uniform';

		// State
		var curOrderby  = 'date';
		var curFeatured = 'false';
		var curPage     = 1;
		var isLoading   = false;
		var isExhausted = false;
		var obsHandle   = null;

		// Create scroll sentinel (loading spinner) + end banner
		var fmSentinel = document.createElement('div');
		fmSentinel.className = 'wpg-fm-sentinel';
		moduleEl.appendChild(fmSentinel);

		var fmEnd = document.createElement('div');
		fmEnd.className = 'wpg-fm-end';
		moduleEl.appendChild(fmEnd);

		function getInnerGrid() {
			return gridWrap.querySelector(
				'.wpg-grid-uniform,.wpg-grid-masonry,.wpg-grid-portrait,' +
				'.wpg-grid-natural,.wpg-grid-spotlight,.wpg-grid-filmstrip'
			);
		}

		// Full recalc (tab switch — all content replaced, safe to clear all spans)
		function reflowMasonry() {
			var mGrid = gridWrap.querySelector('.wpg-grid-masonry');
			if (!mGrid || typeof calcMasonrySpans !== 'function') { return; }
			var imgs = mGrid.querySelectorAll('img');
			if (!imgs.length) { calcMasonrySpans(mGrid); return; }
			var cnt = 0;
			imgs.forEach(function (img) {
				function onL() { cnt++; if (cnt === imgs.length) { calcMasonrySpans(mGrid); } }
				if (img.complete) { onL(); } else {
					img.addEventListener('load',  onL);
					img.addEventListener('error', onL);
				}
			});
		}

		// Append-only recalc — only spans for NEW cards, never touches existing ones.
		// Avoids clearing all spans which causes the page-jump on scroll-load.
		function reflowMasonryNew(newCards) {
			var mGrid = gridWrap.querySelector('.wpg-grid-masonry');
			if (!mGrid || !newCards.length) { return; }

			var ROW_H = 4;
			var gap   = 14;
			try {
				var cg = parseFloat(window.getComputedStyle(mGrid).columnGap);
				if (!isNaN(cg) && cg > 0) { gap = cg; }
			} catch (e) {}

			function calcNew() {
				newCards.forEach(function (card) {
					var h = card.getBoundingClientRect().height;
					if (!h) { return; }
					card.style.gridRowEnd = 'span ' + Math.ceil((h + gap) / ROW_H);
				});
			}

			// Wait for images inside new cards to load before measuring
			var imgs = [];
			newCards.forEach(function (card) {
				Array.prototype.forEach.call(card.querySelectorAll('img'), function (i) { imgs.push(i); });
			});

			if (!imgs.length) { calcNew(); return; }

			var done = 0;
			imgs.forEach(function (img) {
				function onL() { done++; if (done === imgs.length) { calcNew(); } }
				if (img.complete) { onL(); } else {
					img.addEventListener('load',  onL);
					img.addEventListener('error', onL);
				}
			});
		}

		function markExhausted() {
			isExhausted = true;
			fmSentinel.classList.remove('active');
			fmEnd.innerHTML =
				'<svg width="16" height="16" viewBox="0 0 24 24" fill="none">' +
				'<path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>' +
				'</svg><span>All wallpapers loaded</span>';
			fmEnd.classList.add('visible');
			if (obsHandle) { obsHandle.disconnect(); obsHandle = null; }
		}

		function markHasMore() {
			isExhausted = false;
			fmEnd.classList.remove('visible');
			fmSentinel.classList.add('active');
			if (!obsHandle && window.IntersectionObserver) {
				obsHandle = new IntersectionObserver(function (entries) {
					entries.forEach(function (e) {
						if (!e.isIntersecting || isLoading || isExhausted) { return; }
						isLoading = true;
						curPage++;
						doFetch(true);
					});
				}, { rootMargin: '300px' });
				obsHandle.observe(fmSentinel);
			}
		}

		function animateCards(cards) {
			cards.forEach(function (card, i) {
				card.classList.remove('wpg-anim-in');
				void card.offsetWidth; // force reflow to restart animation
				card.style.animationDelay = Math.min(i * 0.038, 0.46) + 's';
				card.classList.add('wpg-anim-in');
			});
		}

		function doFetch(append) {
			postAjax('wpg_filter_category', {
				category: '',
				page:     curPage,
				columns:  cols,
				count:    count,
				orderby:  curOrderby,
				featured: curFeatured,
				style:    style,
			}, function (res) {
				isLoading = false;
				gridWrap.style.opacity = '1';
				if (!res.success) { return; }

				var innerGrid = getInnerGrid();
				var newCards;

				if (append && innerGrid) {
					var tmp = document.createElement('div');
					tmp.innerHTML = res.data.html;
					newCards = Array.prototype.slice.call(tmp.querySelectorAll('.wpg-card'));
					while (tmp.firstChild) { innerGrid.appendChild(tmp.firstChild); }
					animateCards(newCards);
					reflowMasonryNew(newCards); // only new cards — no layout shift
				} else if (innerGrid) {
					innerGrid.innerHTML = res.data.html;
					animateCards(Array.prototype.slice.call(innerGrid.querySelectorAll('.wpg-card')));
					reflowMasonry(); // full recalc safe — all content replaced
				} else {
					gridWrap.innerHTML = res.data.html;
					reflowMasonry();
				}
				attachCardHandlers(gridWrap);
				initGenerate4kButtons(gridWrap);

				if (res.data.has_more) { markHasMore(); } else { markExhausted(); }
			});
		}

		function setActive(btn) {
			tabs.forEach(function (b) {
				b.classList.remove('active');
				b.setAttribute('aria-selected', 'false');
			});
			btn.classList.add('active');
			btn.setAttribute('aria-selected', 'true');
		}

		// Tab click
		tabs.forEach(function (btn) {
			btn.addEventListener('click', function () {
				if (isLoading) { return; }
				var newOrderby  = btn.getAttribute('data-orderby')  || 'date';
				var newFeatured = btn.getAttribute('data-featured') || 'false';
				if (newOrderby === curOrderby && newFeatured === curFeatured) { return; }

				setActive(btn);
				curOrderby  = newOrderby;
				curFeatured = newFeatured;
				curPage     = 1;
				isLoading   = true;
				isExhausted = false;
				gridWrap.style.opacity = '0.4';
				fmEnd.classList.remove('visible');
				fmSentinel.classList.remove('active');
				if (obsHandle) { obsHandle.disconnect(); obsHandle = null; }
				doFetch(false);
			});
		});

		// Init: check if server-rendered grid has more pages
		// grid.php outputs .wpg-infinite-sentinel only when max_num_pages > 1
		if (gridWrap.querySelector('.wpg-infinite-sentinel')) {
			markHasMore();
		} else {
			markExhausted();
		}
	}

	document.querySelectorAll('.wpg-filter-module').forEach(initFilterModule);

	// ── Category pills bar — drag-to-scroll (desktop) ─────────────────────────
	document.querySelectorAll('.wpg-cat-pills-bar').forEach(function (bar) {
		var isDown = false;
		var startX, scrollLeft;

		bar.addEventListener('mousedown', function (e) {
			isDown     = true;
			startX     = e.pageX - bar.offsetLeft;
			scrollLeft = bar.scrollLeft;
			bar.style.userSelect = 'none';
		});

		document.addEventListener('mouseup', function () {
			isDown = false;
			bar.style.userSelect = '';
		});

		bar.addEventListener('mousemove', function (e) {
			if (!isDown) { return; }
			e.preventDefault();
			var x    = e.pageX - bar.offsetLeft;
			var walk = (x - startX) * 1.2;
			bar.scrollLeft = scrollLeft - walk;
		});

		bar.addEventListener('mouseleave', function () {
			isDown = false;
			bar.style.userSelect = '';
		});
	});

	// ── Category carousel — drag-to-scroll (desktop) ──────────────────────────
	function initDragScroll(el) {
		var isDown = false;
		var startX, scrollLeft, hasDragged;

		el.addEventListener('mousedown', function (e) {
			isDown     = true;
			hasDragged = false;
			startX     = e.pageX - el.offsetLeft;
			scrollLeft = el.scrollLeft;
			el.style.userSelect = 'none';
		});

		document.addEventListener('mouseup', function () {
			isDown = false;
			el.style.userSelect = '';
		});

		el.addEventListener('mousemove', function (e) {
			if (!isDown) { return; }
			e.preventDefault();
			hasDragged = true;
			var x    = e.pageX - el.offsetLeft;
			var walk = (x - startX) * 1.4;
			el.scrollLeft = scrollLeft - walk;
		});

		el.addEventListener('mouseleave', function () {
			isDown = false;
			el.style.userSelect = '';
		});

		// Prevent click-through on child links when dragging
		el.addEventListener('click', function (e) {
			if (hasDragged) { e.preventDefault(); }
		}, true);
	}

	document.querySelectorAll('.wpg-cat-carousel').forEach(initDragScroll);

	// ── Sidebar ────────────────────────────────────────────────────────────────
	(function () {
		var toggles = document.querySelectorAll('.wpg-sb-toggle');
		var panel   = document.querySelector('.wpg-sb-panel');
		var overlay = document.querySelector('.wpg-sb-overlay');
		var close   = document.querySelector('.wpg-sb-close');

		if (!toggles.length || !panel) { return; }

		function openSidebar() {
			panel.classList.add('active');
			overlay.classList.add('active');
			document.body.classList.add('wpg-sb-open');
			toggles.forEach(function (t) { t.setAttribute('aria-expanded', 'true'); });
		}

		function closeSidebar() {
			panel.classList.remove('active');
			overlay.classList.remove('active');
			document.body.classList.remove('wpg-sb-open');
			toggles.forEach(function (t) { t.setAttribute('aria-expanded', 'false'); });
		}

		toggles.forEach(function (t) {
			t.addEventListener('click', function () {
				panel.classList.contains('active') ? closeSidebar() : openSidebar();
			});
		});

		if (close)   { close.addEventListener('click', closeSidebar); }
		if (overlay) { overlay.addEventListener('click', closeSidebar); }

		// Close on Escape
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') { closeSidebar(); }
		});
	}());

}());
