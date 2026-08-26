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
						<?php foreach ( array( 'roofing', 'hvac', 'solar', 'plumbing', 'remodeling' ) as $ind ) : ?>
							<option value="<?php echo esc_attr( $ind ); ?>" <?php selected( $editing->industry ?? '', $ind ); ?>><?php echo esc_html( ucfirst( $ind ) ); ?></option>
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

	<hr>

	<h2>All Partners</h2>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th>Name</th><th>Industry</th><th>Phone</th><th>Payout</th><th>Status</th><th>Actions</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $partners ) ) : ?>
				<tr><td colspan="6">No partners yet - add your first one above.</td></tr>
			<?php endif; ?>
			<?php foreach ( $partners as $p ) : ?>
				<tr>
					<td><?php echo esc_html( $p->name ); ?></td>
					<td><?php echo esc_html( ucfirst( $p->industry ) ); ?></td>
					<td><?php echo esc_html( $p->phone ); ?></td>
					<td>$<?php echo esc_html( number_format( (float) $p->payout_amount, 2 ) ); ?> (<?php echo esc_html( $p->payout_type ); ?>)</td>
					<td><?php echo esc_html( ucfirst( $p->status ) ); ?></td>
					<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=grc-partners&edit=' . $p->id ) ); ?>">Edit</a></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
