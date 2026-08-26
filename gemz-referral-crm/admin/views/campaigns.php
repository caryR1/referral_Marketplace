<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$campaigns_table = GRC_DB::table( 'campaigns' );
$partners_table  = GRC_DB::table( 'partners' );

$partners = $wpdb->get_results( "SELECT id, name, industry FROM {$partners_table} WHERE status = 'active' ORDER BY name" );

$campaigns = $wpdb->get_results( "
	SELECT c.*, p.name AS partner_name
	FROM {$campaigns_table} c
	LEFT JOIN {$partners_table} p ON p.id = c.partner_id
	ORDER BY c.created_at DESC
" );

$editing = null;
if ( ! empty( $_GET['edit'] ) ) {
	$editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$campaigns_table} WHERE id = %d", absint( $_GET['edit'] ) ) );
}

// Pages that already have the shortcode somewhere, to make picking a landing page easier.
// (Falls back to "any published page" if nothing has the shortcode yet.)
$pages = get_posts( array(
	'post_type'      => 'page',
	'post_status'    => 'publish',
	'numberposts'    => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
) );
?>
<div class="wrap">
	<h1><?php echo $editing ? 'Edit Campaign' : 'Campaigns'; ?></h1>

	<?php if ( ! empty( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success"><p>Campaign saved.</p></div>
	<?php endif; ?>

	<?php if ( empty( $partners ) ) : ?>
		<div class="notice notice-warning">
			<p>You need at least one active partner before creating a campaign. <a href="<?php echo esc_url( admin_url( 'admin.php?page=grc-partners' ) ); ?>">Add a partner first</a>.</p>
		</div>
	<?php endif; ?>

	<h2><?php echo $editing ? 'Edit' : 'Add New'; ?> Campaign</h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'grc_save_campaign' ); ?>
		<input type="hidden" name="action" value="grc_save_campaign">
		<input type="hidden" name="campaign_id" value="<?php echo esc_attr( $editing->id ?? '' ); ?>">
		<table class="form-table">
			<tr>
				<th><label for="name">Campaign Name</label></th>
				<td><input type="text" id="name" name="name" class="regular-text" required value="<?php echo esc_attr( $editing->name ?? '' ); ?>" placeholder="e.g. Treasure Coast Storm - Roofing"></td>
			</tr>
			<tr>
				<th><label for="partner_id">Fulfillment Partner</label></th>
				<td>
					<select id="partner_id" name="partner_id" required>
						<option value="">— Select Partner —</option>
						<?php foreach ( $partners as $p ) : ?>
							<option value="<?php echo esc_attr( $p->id ); ?>" data-industry="<?php echo esc_attr( $p->industry ); ?>" <?php selected( $editing->partner_id ?? '', $p->id ); ?>><?php echo esc_html( $p->name . ' (' . GRC_Industries::label( $p->industry ) . ')' ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description">Note: partner_id will need to be entered manually into the <code>[gemz_appointment_form]</code> shortcode when you build the landing page - see the Partner ID shown in the campaign list below.</p>
				</td>
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
				<th><label for="landing_page_id">Landing Page</label></th>
				<td>
					<select id="landing_page_id" name="landing_page_id">
						<option value="">— Select Page —</option>
						<?php foreach ( $pages as $page ) : ?>
							<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $editing->landing_page_id ?? '', $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description">The Gutenberg page containing the <code>[gemz_appointment_form]</code> shortcode. Ready-to-send links redirect here.</p>
				</td>
			</tr>
			<tr>
				<th><label for="tracking_slug">Tracking Slug</label></th>
				<td>
					<code><?php echo esc_html( home_url( '/go/' ) ); ?></code><input type="text" id="tracking_slug" name="tracking_slug" value="<?php echo esc_attr( $editing->tracking_slug ?? '' ); ?>" required placeholder="roofing-tc-storm">
					<p class="description">Letters, numbers, and hyphens only. Must be unique. This is what makes your ready-to-send link short and clean.</p>
				</td>
			</tr>
			<tr>
				<th><label for="status">Status</label></th>
				<td>
					<select id="status" name="status">
						<?php foreach ( array( 'active', 'paused' ) as $status ) : ?>
							<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $editing->status ?? 'active', $status ); ?>><?php echo esc_html( ucfirst( $status ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>
		<?php submit_button( $editing ? 'Update Campaign' : 'Add Campaign' ); ?>
	</form>

	<hr>

	<h2>All Campaigns</h2>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th>Name</th><th>Partner</th><th>Ready-to-Send Link</th><th>Partner ID (for shortcode)</th><th>Status</th><th>Actions</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $campaigns ) ) : ?>
				<tr><td colspan="6">No campaigns yet - add your first one above.</td></tr>
			<?php endif; ?>
			<?php foreach ( $campaigns as $c ) :
				$link = home_url( '/go/' . $c->tracking_slug );
			?>
				<tr>
					<td><?php echo esc_html( $c->name ); ?></td>
					<td><?php echo esc_html( $c->partner_name ?: '—' ); ?></td>
					<td><input type="text" readonly value="<?php echo esc_attr( $link . '?ref=AG-XXXXXX' ); ?>" style="width:100%;" onclick="this.select();"></td>
					<td><code><?php echo esc_html( $c->partner_id ); ?></code></td>
					<td><?php echo esc_html( ucfirst( $c->status ) ); ?></td>
					<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=grc-campaigns&edit=' . $c->id ) ); ?>">Edit</a></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p class="description">Swap <code>AG-XXXXXX</code> in a link for a specific agent's real referral code (see the Agents screen) before sending it out.</p>
</div>
