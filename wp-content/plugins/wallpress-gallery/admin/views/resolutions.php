<?php
/**
 * Admin View: Resolutions Manager
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$all_res = wpg_get_resolutions();
$devices = [
	'desktop' => [ 'label' => __( 'Desktop',  'wallpress-gallery' ), 'icon' => '🖥️' ],
	'mobile'  => [ 'label' => __( 'Mobile',   'wallpress-gallery' ), 'icon' => '📱' ],
	'tablet'  => [ 'label' => __( 'Tablet',   'wallpress-gallery' ), 'icon' => '⬜' ],
	'iphone'  => [ 'label' => __( 'iPhone',   'wallpress-gallery' ), 'icon' => '📱' ],
	'ipad'    => [ 'label' => __( 'iPad',     'wallpress-gallery' ), 'icon' => '⬛' ],
];
?>
<div class="wpg-admin-wrap">

	<div class="wpg-admin-header">
		<h1 class="wpg-admin-title"><?php esc_html_e( 'Resolutions', 'wallpress-gallery' ); ?></h1>
		<div class="wpg-admin-header-actions">
			<button id="wpg-reset-res-btn" class="wpg-btn wpg-btn-secondary">
				<?php esc_html_e( 'Reset to Defaults', 'wallpress-gallery' ); ?>
			</button>
		</div>
	</div>

	<div style="display:grid;grid-template-columns:340px 1fr;gap:24px;align-items:start;">

		<!-- ADD FORM -->
		<div class="wpg-card-panel" style="position:sticky;top:40px;">
			<h2 class="wpg-section-title"><?php esc_html_e( 'Add Resolution', 'wallpress-gallery' ); ?></h2>

			<div class="wpg-field-group">
				<label><?php esc_html_e( 'Device Type', 'wallpress-gallery' ); ?> <span style="color:#f66">*</span></label>
				<select id="wpg-res-device" class="wpg-select">
					<?php foreach ( $devices as $key => $d ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $d['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="wpg-field-group">
				<label><?php esc_html_e( 'Resolution', 'wallpress-gallery' ); ?> <span style="color:#f66">*</span></label>
				<input type="text" id="wpg-res-res" class="wpg-input" placeholder="e.g. 1920×1080" />
			</div>

			<div class="wpg-field-group">
				<label><?php esc_html_e( 'Label / Description', 'wallpress-gallery' ); ?></label>
				<input type="text" id="wpg-res-label" class="wpg-input" placeholder="e.g. Full HD 1080p" />
			</div>

			<div class="wpg-field-group">
				<label><?php esc_html_e( 'Compatible Resolutions', 'wallpress-gallery' ); ?></label>
				<input type="text" id="wpg-res-compat" class="wpg-input" placeholder="e.g. 1280×720, 1366×768" />
			</div>

			<button id="wpg-res-add-btn" class="wpg-btn wpg-btn-primary" style="width:100%;margin-top:8px;">
				<?php esc_html_e( 'Add Resolution', 'wallpress-gallery' ); ?>
			</button>

			<p id="wpg-res-msg" style="margin-top:12px;font-size:13px;color:var(--wpg-admin-accent);display:none;"></p>
		</div>

		<!-- RESOLUTION TABLES -->
		<div>
			<!-- Device Tabs -->
			<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px;" id="wpg-res-tabs">
				<?php foreach ( $devices as $key => $d ) : ?>
				<button class="wpg-btn <?php echo $key === 'desktop' ? 'wpg-btn-primary' : 'wpg-btn-secondary'; ?> wpg-res-tab"
					data-device="<?php echo esc_attr( $key ); ?>"
					style="font-size:12px;padding:6px 14px;">
					<?php echo esc_html( $d['label'] ); ?>
					<span class="wpg-res-count" style="background:rgba(255,255,255,0.12);border-radius:999px;padding:1px 7px;font-size:11px;margin-left:4px;">
						<?php echo count( $all_res[ $key ] ?? [] ); ?>
					</span>
				</button>
				<?php endforeach; ?>
			</div>

			<!-- Tables per device -->
			<?php foreach ( $devices as $key => $d ) :
				$rows = $all_res[ $key ] ?? [];
			?>
			<div class="wpg-res-device-panel" data-device="<?php echo esc_attr( $key ); ?>"
				style="<?php echo $key !== 'desktop' ? 'display:none;' : ''; ?>">
				<div class="wpg-card-panel" style="padding:0;overflow:hidden;">
					<table class="wpg-admin-table" id="wpg-res-table-<?php echo esc_attr( $key ); ?>">
						<thead>
							<tr>
								<th style="width:130px;"><?php esc_html_e( 'Resolution', 'wallpress-gallery' ); ?></th>
								<th><?php esc_html_e( 'Label', 'wallpress-gallery' ); ?></th>
								<th><?php esc_html_e( 'Compatible', 'wallpress-gallery' ); ?></th>
								<th style="width:70px;"></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $rows as $i => $r ) : ?>
							<tr data-device="<?php echo esc_attr( $key ); ?>" data-index="<?php echo esc_attr( $i ); ?>">
								<td style="font-weight:700;color:var(--wpg-admin-accent);font-family:monospace;font-size:13px;">
									<?php echo esc_html( $r['res'] ); ?>
								</td>
								<td style="color:var(--wpg-admin-text);font-size:13px;">
									<?php echo esc_html( $r['label'] ); ?>
								</td>
								<td style="color:var(--wpg-admin-muted);font-size:12px;">
									<?php echo esc_html( $r['compat'] ); ?>
								</td>
								<td>
									<button class="wpg-btn wpg-btn-danger wpg-res-del-btn"
										style="font-size:11px;padding:3px 10px;">
										<?php esc_html_e( 'Del', 'wallpress-gallery' ); ?>
									</button>
								</td>
							</tr>
							<?php endforeach; ?>
							<?php if ( empty( $rows ) ) : ?>
							<tr class="wpg-res-empty-row">
								<td colspan="4" style="text-align:center;color:var(--wpg-admin-muted);padding:24px;">
									<?php esc_html_e( 'No resolutions added yet.', 'wallpress-gallery' ); ?>
								</td>
							</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

	</div><!-- grid -->

</div><!-- .wpg-admin-wrap -->

<script>
(function(){
	'use strict';
	var ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
	var nonce   = '<?php echo esc_js( wp_create_nonce( 'wpg_nonce' ) ); ?>';

	function postAjax(action, data, cb) {
		var fd = new FormData();
		fd.append('action', action);
		fd.append('nonce', nonce);
		Object.keys(data).forEach(function(k){ fd.append(k, data[k]); });
		fetch(ajaxurl, { method: 'POST', body: fd })
			.then(function(r){ return r.json(); })
			.then(cb);
	}

	// Device tabs
	document.querySelectorAll('.wpg-res-tab').forEach(function(btn){
		btn.addEventListener('click', function(){
			document.querySelectorAll('.wpg-res-tab').forEach(function(b){
				b.classList.remove('wpg-btn-primary');
				b.classList.add('wpg-btn-secondary');
			});
			btn.classList.add('wpg-btn-primary');
			btn.classList.remove('wpg-btn-secondary');
			var device = btn.getAttribute('data-device');
			document.querySelectorAll('.wpg-res-device-panel').forEach(function(p){
				p.style.display = p.getAttribute('data-device') === device ? '' : 'none';
			});
		});
	});

	// Add resolution
	document.getElementById('wpg-res-add-btn').addEventListener('click', function(){
		var device = document.getElementById('wpg-res-device').value;
		var res    = document.getElementById('wpg-res-res').value.trim();
		var label  = document.getElementById('wpg-res-label').value.trim();
		var compat = document.getElementById('wpg-res-compat').value.trim();
		var msg    = document.getElementById('wpg-res-msg');

		if (!res) { msg.style.display='block'; msg.style.color='#f66'; msg.textContent='Resolution is required.'; return; }

		postAjax('wpg_save_resolutions', { device: device, res: res, label: label, compat: compat }, function(r){
			if (r.success) {
				msg.style.display='block'; msg.style.color='var(--wpg-admin-accent)'; msg.textContent='Added!';
				// Append row to table
				var tbody = document.querySelector('#wpg-res-table-' + device + ' tbody');
				var emptyRow = tbody.querySelector('.wpg-res-empty-row');
				if (emptyRow) { emptyRow.remove(); }
				var newIndex = tbody.querySelectorAll('tr').length;
				var tr = document.createElement('tr');
				tr.setAttribute('data-device', device);
				tr.setAttribute('data-index', newIndex);
				tr.innerHTML =
					'<td style="font-weight:700;color:var(--wpg-admin-accent);font-family:monospace;font-size:13px;">' + escHtml(res) + '</td>' +
					'<td style="color:var(--wpg-admin-text);font-size:13px;">' + escHtml(label) + '</td>' +
					'<td style="color:var(--wpg-admin-muted);font-size:12px;">' + escHtml(compat) + '</td>' +
					'<td><button class="wpg-btn wpg-btn-danger wpg-res-del-btn" style="font-size:11px;padding:3px 10px;">Del</button></td>';
				tbody.appendChild(tr);
				attachDel(tr.querySelector('.wpg-res-del-btn'));
				// Update count badge
				updateCount(device);
				// Clear fields
				document.getElementById('wpg-res-res').value = '';
				document.getElementById('wpg-res-label').value = '';
				document.getElementById('wpg-res-compat').value = '';
			} else {
				msg.style.display='block'; msg.style.color='#f66'; msg.textContent = r.data || 'Error.';
			}
		});
	});

	// Delete
	function attachDel(btn) {
		btn.addEventListener('click', function(){
			var tr     = btn.closest('tr');
			var device = tr.getAttribute('data-device');
			var index  = parseInt(tr.getAttribute('data-index'), 10);
			if (!confirm('Delete this resolution?')) { return; }
			postAjax('wpg_delete_resolution', { device: device, index: index }, function(r){
				if (r.success) {
					tr.remove();
					// Re-index remaining rows
					document.querySelectorAll('#wpg-res-table-' + device + ' tbody tr').forEach(function(row, i){
						row.setAttribute('data-index', i);
					});
					updateCount(device);
				}
			});
		});
	}

	document.querySelectorAll('.wpg-res-del-btn').forEach(attachDel);

	// Reset to defaults
	document.getElementById('wpg-reset-res-btn').addEventListener('click', function(){
		if (!confirm('<?php echo esc_js( __( 'Reset all resolutions to defaults? This will overwrite your changes.', 'wallpress-gallery' ) ); ?>')) { return; }
		postAjax('wpg_reset_resolutions', {}, function(r){
			if (r.success) { window.location.reload(); }
		});
	});

	function updateCount(device) {
		var count = document.querySelectorAll('#wpg-res-table-' + device + ' tbody tr:not(.wpg-res-empty-row)').length;
		var tab   = document.querySelector('.wpg-res-tab[data-device="' + device + '"] .wpg-res-count');
		if (tab) { tab.textContent = count; }
	}

	function escHtml(str) {
		return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
	}
})();
</script>
