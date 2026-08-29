<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table = GRC_DB::table( 'agent_segments' );
$segments = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY name" );
?>
<div class="wrap">
	<h1>Agent Segments</h1>
	<p class="description">Tag agents into segments for future targeted outreach. This is tagging infrastructure only - no messaging/campaign logic is built on top of it yet.</p>

	<?php if ( ! empty( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success"><p>Segment saved.</p></div>
	<?php endif; ?>

	<h2>Add New Segment</h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'grc_save_agent_segment' ); ?>
		<input type="hidden" name="action" value="grc_save_agent_segment">
		<table class="form-table">
			<tr>
				<th><label for="name">Segment Name</label></th>
				<td><input type="text" id="name" name="name" class="regular-text" required></td>
			</tr>
			<tr>
				<th><label for="description">Description</label></th>
				<td><textarea id="description" name="description" class="large-text" rows="2"></textarea></td>
			</tr>
		</table>
		<?php submit_button( 'Add Segment' ); ?>
	</form>

	<hr>

	<h2>All Segments</h2>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr><th>Name</th><th>Description</th><th>Agents</th></tr>
		</thead>
		<tbody>
			<?php if ( empty( $segments ) ) : ?>
				<tr><td colspan="3">No segments yet - add one above.</td></tr>
			<?php endif; ?>
			<?php
			$agents_table = GRC_DB::table( 'agents' );
			foreach ( $segments as $s ) :
				$agent_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$agents_table} WHERE segment_id = %d", $s->id ) );
			?>
				<tr>
					<td><?php echo esc_html( $s->name ); ?></td>
					<td><?php echo esc_html( $s->description ); ?></td>
					<td><?php echo esc_html( $agent_count ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
