<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table = GRC_DB::table( 'partners' );
$leads_table = GRC_DB::table( 'leads' );
$partners = $wpdb->get_results( "
	SELECT p.*, (SELECT COUNT(*) FROM {$leads_table} WHERE partner_id = p.id) AS leads_sent
	FROM {$table} p
	ORDER BY FIELD(p.outreach_status, 'new', 'contacted', 'approved', 'rejected'), p.created_at DESC
" );

$latest_batch_id = $wpdb->get_var( "SELECT research_batch_id FROM {$table} WHERE research_batch_id IS NOT NULL ORDER BY created_at DESC LIMIT 1" );

$editing = null;
if ( ! empty( $_GET['edit'] ) ) {
	$editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $_GET['edit'] ) ) );
}
?>
<div class="wrap">
	<h1><?php echo $editing ? 'Edit Partner' : 'Fulfillment Partners'; ?></h1>

	<?php if ( ! empty( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success"><p>Partner saved.</p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['outreach_updated'] ) ) : ?>
		<div class="notice notice-success"><p>Partner outreach status updated.</p></div>
	<?php endif; ?>

	<h2><?php echo $editing ? 'Edit' : 'Add New'; ?> Partner</h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'grc_save_partner' ); ?>
		<input type="hidden" name="action" value="grc_save_partner">
		<input type="hidden" name="partner_id" value="<?php echo esc_attr( $editing->id ?? '' ); ?>">
		<table class="form-table">
			<tr>
				<th><label for="name">Company Name</label></th>
				<td><input type="text" id="name" name="name" class="regular-text" required value="<?php echo esc_attr( $editing->name ?? '' ); ?>"></td>
			</tr>
			<tr>
				<th><label for="industry">Industry</label></th>
				<td>
					<select id="industry" name="industry">
						<?php foreach ( GRC_Industries::all() as $ind => $label ) : ?>
							<option value="<?php echo esc_attr( $ind ); ?>" <?php selected( $editing->industry ?? '', $ind ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="contact_name">Contact Name</label></th>
				<td><input type="text" id="contact_name" name="contact_name" class="regular-text" value="<?php echo esc_attr( $editing->contact_name ?? '' ); ?>"></td>
			</tr>
			<tr>
				<th><label for="phone">Phone</label></th>
				<td><input type="text" id="phone" name="phone" value="<?php echo esc_attr( $editing->phone ?? '' ); ?>"></td>
			</tr>
			<tr>
				<th><label for="email">Email</label></th>
				<td><input type="email" id="email" name="email" class="regular-text" value="<?php echo esc_attr( $editing->email ?? '' ); ?>"></td>
			</tr>
			<tr>
				<th><label for="website">Website</label></th>
				<td><input type="url" id="website" name="website" class="regular-text" value="<?php echo esc_attr( $editing->website ?? '' ); ?>"></td>
			</tr>
			<tr>
				<th><label for="payout_amount">Payout Amount</label></th>
				<td><input type="number" step="0.01" id="payout_amount" name="payout_amount" value="<?php echo esc_attr( $editing->payout_amount ?? '' ); ?>"></td>
			</tr>
			<tr>
				<th><label for="payout_type">Payout Type</label></th>
				<td>
					<select id="payout_type" name="payout_type">
						<?php foreach ( array( 'flat', 'percentage', 'tiered' ) as $type ) : ?>
							<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $editing->payout_type ?? 'flat', $type ); ?>><?php echo esc_html( ucfirst( $type ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="customer_cashback_amount">Customer Cash-Back Amount</label></th>
				<td>
					<input type="number" step="0.01" id="customer_cashback_amount" name="customer_cashback_amount" value="<?php echo esc_attr( $editing->customer_cashback_amount ?? '' ); ?>">
					<p class="description">Flat $ paid directly to the homeowner once their lead with this partner is marked completed. Separate from Payout Amount above, which funds agent commissions. Leave at 0 if this partner doesn't fund a homeowner reward.</p>
				</td>
			</tr>
			<tr>
				<th><label for="payout_notes">Payout Notes</label></th>
				<td><textarea id="payout_notes" name="payout_notes" class="large-text" rows="3"><?php echo esc_textarea( $editing->payout_notes ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th>Service Areas</th>
				<td>
					<table id="grc-service-areas" class="widefat" style="max-width:600px;">
						<thead>
							<tr><th>State</th><th>City (optional)</th><th>Zip (optional)</th><th></th></tr>
						</thead>
						<tbody></tbody>
					</table>
					<p><button type="button" class="button" id="grc-add-service-area">Add Coverage Area</button></p>
					<p class="description">Add a state alone to cover the whole state, or narrow it with a city or zip. A partner needs at least one row to ever show up in the industry browser or coverage search.</p>
					<input type="hidden" id="service_areas" name="service_areas" value="<?php echo esc_attr( $editing->service_areas ?? '[]' ); ?>">
				</td>
			</tr>
			<tr>
				<th><label for="status">Status</label></th>
				<td>
					<select id="status" name="status">
						<?php foreach ( array( 'active', 'paused', 'dropped' ) as $status ) : ?>
							<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $editing->status ?? 'active', $status ); ?>><?php echo esc_html( ucfirst( $status ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>
		<?php submit_button( $editing ? 'Update Partner' : 'Add Partner' ); ?>
	</form>

	<script>
	( function () {
		var hidden = document.getElementById( 'service_areas' );
		var body   = document.querySelector( '#grc-service-areas tbody' );

		function escapeAttr( value ) {
			return String( value || '' ).replace( /&/g, '&amp;' ).replace( /"/g, '&quot;' ).replace( /</g, '&lt;' );
		}

		function addRow( area ) {
			area = area || {};
			var tr = document.createElement( 'tr' );
			tr.innerHTML =
				'<td><input type="text" class="grc-area-state" maxlength="2" style="text-transform:uppercase;width:4em;" value="' + escapeAttr( area.state ) + '"></td>' +
				'<td><input type="text" class="grc-area-city" value="' + escapeAttr( area.city ) + '"></td>' +
				'<td><input type="text" class="grc-area-zip" maxlength="10" value="' + escapeAttr( area.zip ) + '"></td>' +
				'<td><button type="button" class="button-link grc-remove-area">Remove</button></td>';
			body.appendChild( tr );
		}

		document.getElementById( 'grc-add-service-area' ).addEventListener( 'click', function () {
			addRow();
		} );

		body.addEventListener( 'click', function ( e ) {
			if ( e.target.classList.contains( 'grc-remove-area' ) ) {
				e.target.closest( 'tr' ).remove();
			}
		} );

		document.querySelector( 'form' ).addEventListener( 'submit', function () {
			var areas = [];
			body.querySelectorAll( 'tr' ).forEach( function ( tr ) {
				var state = tr.querySelector( '.grc-area-state' ).value.trim();
				var city  = tr.querySelector( '.grc-area-city' ).value.trim();
				var zip   = tr.querySelector( '.grc-area-zip' ).value.trim();
				if ( state || city || zip ) {
					areas.push( { state: state, city: city, zip: zip } );
				}
			} );
			hidden.value = JSON.stringify( areas );
		} );

		try {
			var initial = JSON.parse( hidden.value || '[]' );
			initial.forEach( addRow );
		} catch ( e ) {}
	} )();
	</script>

	<hr>

	<h2>All Partners</h2>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th>Name</th><th>Industry</th><th>Coverage</th><th>Payout / Cash-Back</th><th>Leads Sent</th><th>Live Status</th><th>Outreach</th><th>Source</th><th>Pipeline Action</th><th></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $partners ) ) : ?>
				<tr><td colspan="10">No partners yet - add your first one above.</td></tr>
			<?php endif; ?>
			<?php foreach ( $partners as $p ) :
				$areas = json_decode( $p->service_areas ?? '', true );
				$outreach = $p->outreach_status ?: 'new';
				$is_new_batch = $p->research_batch_id && $latest_batch_id && $p->research_batch_id === $latest_batch_id;
				$business_days = ( 'new' === $outreach ) ? GRC_Admin::business_days_since( $p->created_at ) : 0;
				$calendar_days = ( 'new' === $outreach ) ? floor( ( current_time( 'timestamp' ) - strtotime( $p->created_at ) ) / DAY_IN_SECONDS ) : 0;
			?>
				<tr>
					<td>
						<?php echo esc_html( $p->name ); ?>
						<?php if ( $is_new_batch && 'new' === $outreach ) : ?>
							<span style="background:#2271b1; color:#fff; font-size:10px; padding:1px 6px; border-radius:10px; margin-left:4px;">NEW</span>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( GRC_Industries::label( $p->industry ) ); ?></td>
					<td><?php echo empty( $areas ) ? '<span style="color:#a00;">None set</span>' : esc_html( count( $areas ) . ' area' . ( count( $areas ) === 1 ? '' : 's' ) ); ?></td>
					<td>
						$<?php echo esc_html( number_format( (float) $p->payout_amount, 2 ) ); ?> agent
						<?php if ( (float) ( $p->customer_cashback_amount ?? 0 ) > 0 ) : ?>
							/ $<?php echo esc_html( number_format( (float) $p->customer_cashback_amount, 2 ) ); ?> cash-back
						<?php endif; ?>
					</td>
					<td>
						<?php echo esc_html( $p->leads_sent ); ?>
						<?php if ( $p->leads_sent >= 4 ) : ?>
							<br><span style="color:#996800; font-size:11px;">&#9888; Discuss better terms</span>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( ucfirst( $p->status ) ); ?></td>
					<td>
						<?php
						$badge_colors = array( 'new' => '#646970', 'contacted' => '#2271b1', 'approved' => '#00a32a', 'rejected' => '#d63638' );
						$color = $badge_colors[ $outreach ] ?? '#646970';
						?>
						<span style="color:<?php echo esc_attr( $color ); ?>; font-weight:600;"><?php echo esc_html( ucfirst( $outreach ) ); ?></span>
						<?php if ( 'new' === $outreach && $calendar_days >= 7 ) : ?>
							<br><span style="color:#d63638; font-weight:600; font-size:11px;">&#9873; Red-flagged (<?php echo esc_html( $calendar_days ); ?>d)</span>
						<?php elseif ( 'new' === $outreach && $business_days >= 3 ) : ?>
							<br><span style="color:#996800; font-weight:600; font-size:11px;">Overdue (<?php echo esc_html( $business_days ); ?> business days)</span>
						<?php endif; ?>
						<?php if ( 'approved' === $outreach && ! empty( $p->unusual_terms ) ) : ?>
							<br><span style="font-size:11px; color:#646970;">Terms: <?php echo esc_html( $p->unusual_terms ); ?></span>
						<?php endif; ?>
						<?php if ( 'rejected' === $outreach && ! empty( $p->rejection_reason ) ) : ?>
							<br><span style="font-size:11px; color:#646970;">Reason: <?php echo esc_html( $p->rejection_reason ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $p->source_url ) : ?>
							<a href="<?php echo esc_url( $p->source_url ); ?>" target="_blank" rel="noopener noreferrer">Source &#8599;</a>
						<?php else : ?>
							&mdash;
						<?php endif; ?>
					</td>
					<td>
						<?php if ( in_array( $outreach, array( 'new', 'contacted' ), true ) ) : ?>
							<?php if ( 'new' === $outreach ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:6px;">
									<?php wp_nonce_field( 'grc_mark_partner_contacted' ); ?>
									<input type="hidden" name="action" value="grc_mark_partner_contacted">
									<input type="hidden" name="partner_id" value="<?php echo esc_attr( $p->id ); ?>">
									<button type="submit" class="button">Mark Contacted</button>
								</form>
							<?php endif; ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:6px; display:flex; gap:4px;">
								<?php wp_nonce_field( 'grc_approve_partner' ); ?>
								<input type="hidden" name="action" value="grc_approve_partner">
								<input type="hidden" name="partner_id" value="<?php echo esc_attr( $p->id ); ?>">
								<input type="text" name="unusual_terms" placeholder="Unusual terms (optional)" style="width:140px;">
								<button type="submit" class="button button-primary">Approve</button>
							</form>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex; gap:4px;" onsubmit="return confirm('Reject this partner?');">
								<?php wp_nonce_field( 'grc_reject_partner' ); ?>
								<input type="hidden" name="action" value="grc_reject_partner">
								<input type="hidden" name="partner_id" value="<?php echo esc_attr( $p->id ); ?>">
								<input type="text" name="rejection_reason" placeholder="Rejection reason" required style="width:140px;">
								<button type="submit" class="button" style="color:#a00; border-color:#a00;">Reject</button>
							</form>
						<?php else : ?>
							&mdash;
						<?php endif; ?>
					</td>
					<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=grc-partners&edit=' . $p->id ) ); ?>">Edit</a></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
