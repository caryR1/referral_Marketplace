<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table = GRC_DB::table( 'partners' );
$partners = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY industry, name" );

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
				<th>Name</th><th>Industry</th><th>Coverage</th><th>Phone</th><th>Payout</th><th>Status</th><th>Actions</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $partners ) ) : ?>
				<tr><td colspan="7">No partners yet - add your first one above.</td></tr>
			<?php endif; ?>
			<?php foreach ( $partners as $p ) : ?>
				<?php $areas = json_decode( $p->service_areas ?? '', true ); ?>
				<tr>
					<td><?php echo esc_html( $p->name ); ?></td>
					<td><?php echo esc_html( GRC_Industries::label( $p->industry ) ); ?></td>
					<td><?php echo empty( $areas ) ? '<span style="color:#a00;">None set</span>' : esc_html( count( $areas ) . ' area' . ( count( $areas ) === 1 ? '' : 's' ) ); ?></td>
					<td><?php echo esc_html( $p->phone ); ?></td>
					<td>$<?php echo esc_html( number_format( (float) $p->payout_amount, 2 ) ); ?> (<?php echo esc_html( $p->payout_type ); ?>)</td>
					<td><?php echo esc_html( ucfirst( $p->status ) ); ?></td>
					<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=grc-partners&edit=' . $p->id ) ); ?>">Edit</a></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
