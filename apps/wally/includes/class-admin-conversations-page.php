<?php
namespace Wally;

/**
 * Admin page for browsing and reviewing conversation transcripts.
 */
class AdminConversationsPage {

	public static function register_menu() {
		add_submenu_page(
			null,
			'AI Assistant Conversations',
			'Conversations',
			'manage_options',
			'wpaia-conversations',
			[ self::class, 'render_page' ]
		);
	}

	public static function render_page() {
		$conv_id = ! empty( $_GET['conv_id'] ) ? (int) $_GET['conv_id'] : 0;

		if ( $conv_id && ! empty( $_GET['export'] ) && $_GET['export'] === 'json' ) {
			self::export_json( $conv_id );
			return;
		}

		if ( $conv_id ) {
			self::render_transcript( $conv_id );
			return;
		}

		self::render_list();
	}

	// ─── Conversation List ────────────────────────────────────────────

	private static function render_list() {
		global $wpdb;

		$per_page   = 10;
		$page       = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
		$offset     = ( $page - 1 ) * $per_page;
		$base_url   = admin_url( 'admin.php?page=wpaia-conversations' );
		$settings_url = admin_url( 'admin.php?page=wally' );
		$conv_table = $wpdb->prefix . 'wally_conversations';
		$msg_table  = $wpdb->prefix . 'wally_messages';

		$filter_user   = ! empty( $_GET['user_filter'] ) ? sanitize_text_field( $_GET['user_filter'] ) : '';
		$where_clauses = [];
		$where_values  = [];

		if ( $filter_user ) {
			$like = '%' . $wpdb->esc_like( $filter_user ) . '%';
			// Resolve matching user IDs outside the main query to avoid
			// table-name interpolation inside $wpdb->prepare().
			$user_ids = $wpdb->get_col( $wpdb->prepare(
				"SELECT ID FROM {$wpdb->users} WHERE user_login LIKE %s OR user_email LIKE %s",
				$like,
				$like
			) );
			$where_clauses[] = 'c.title LIKE %s';
			$where_values[]  = $like;
			if ( $user_ids ) {
				$placeholders    = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
				$where_clauses[] = "c.user_id IN ({$placeholders})";
				$where_values    = array_merge( $where_values, array_map( 'intval', $user_ids ) );
			}
		}

		$where_sql = $where_clauses ? 'WHERE ' . implode( ' OR ', $where_clauses ) : '';

		if ( $where_values ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$conv_table} c {$where_sql}",
				...$where_values
			) );
		} else {
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$conv_table} c" );
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*,
				        COUNT(m.id)          AS message_count,
				        SUM(m.token_count)   AS total_tokens
				 FROM {$conv_table} c
				 LEFT JOIN {$msg_table} m ON m.conversation_id = c.id
				 {$where_sql}
				 GROUP BY c.id
				 ORDER BY c.updated_at DESC
				 LIMIT %d OFFSET %d",
				...array_merge( $where_values, [ $per_page, $offset ] )
			)
		);

		$total_pages = (int) ceil( $total / $per_page );
		$start_item  = $total ? $offset + 1 : 0;
		$end_item    = min( $offset + $per_page, $total );

		self::render_page_styles();
		self::render_page_header( $settings_url, 'conversations' );
		?>

		<div class="wpaia-body">
			<?php self::render_sidebar( $settings_url, 'conversations' ); ?>

			<main class="wpaia-main">
				<!-- Title Area -->
				<div class="wpaia-section-header">
					<h1 class="wpaia-section-title">Conversations</h1>
					<p class="wpaia-section-desc">Full transcripts of all user conversations. Use these to review interactions, spot issues, and improve the assistant.</p>
				</div>

				<!-- Filter Row -->
				<form method="get" class="wpaia-filter-row">
					<input type="hidden" name="page" value="wpaia-conversations" />
					<div class="wpaia-filter-input-wrap">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
						<input type="text" name="user_filter" placeholder="Filter by title, username, or email"
						       value="<?php echo esc_attr( $filter_user ?: '' ); ?>"
						       class="wpaia-filter-input" />
					</div>
					<button type="submit" class="wpaia-btn-primary-sm">Filter</button>
					<a href="<?php echo esc_url( $base_url ); ?>" class="wpaia-btn-outline-sm">Reset</a>
					<div style="flex:1;"></div>
					<span class="wpaia-count-text">
						<?php echo esc_html( number_format( $total ) ); ?> conversation<?php echo $total !== 1 ? 's' : ''; ?> found
					</span>
				</form>

				<!-- Table -->
				<div class="wpaia-table-wrap">
					<table class="wpaia-table">
						<thead>
							<tr>
								<th style="width:50px;">ID</th>
								<th>Title</th>
								<th style="width:90px;">User</th>
								<th style="width:80px;text-align:center;">Messages</th>
								<th style="width:90px;text-align:center;">Tokens</th>
								<th style="width:150px;">Last Active</th>
								<th style="width:90px;text-align:center;">View</th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $rows ) ) : ?>
								<tr>
									<td colspan="7" style="padding:40px 20px;text-align:center;color:#A1A1AA;font-size:14px;">
										No conversations found.
									</td>
								</tr>
							<?php else : foreach ( $rows as $row ) :
								$user_info = get_userdata( $row->user_id );
								$username  = $user_info ? $user_info->user_login : '#' . $row->user_id;
								$view_url  = add_query_arg( 'conv_id', $row->id, $base_url );
								$date      = date( 'M j, Y · H:i', strtotime( $row->updated_at ) );
							?>
							<tr class="wpaia-tr-border">
								<td style="font-size:13px;font-weight:500;color:#18181B;"><?php echo esc_html( $row->id ); ?></td>
								<td style="font-size:13px;color:#18181B;"><?php echo esc_html( $row->title ?: '(untitled)' ); ?></td>
								<td style="font-size:13px;color:#71717A;"><?php echo esc_html( $username ); ?></td>
								<td style="font-size:13px;color:#71717A;text-align:center;"><?php echo esc_html( number_format( (int) $row->message_count ) ); ?></td>
								<td style="font-size:13px;text-align:center;color:<?php echo $row->total_tokens ? '#71717A' : '#A1A1AA'; ?>;">
									<?php echo esc_html( $row->total_tokens ? number_format( (int) $row->total_tokens ) : '—' ); ?>
								</td>
								<td style="font-size:12px;color:#A1A1AA;"><?php echo esc_html( $date ); ?></td>
								<td style="text-align:center;">
									<a href="<?php echo esc_url( $view_url ); ?>" class="wpaia-transcript-btn">Transcript</a>
								</td>
							</tr>
							<?php endforeach; endif; ?>
						</tbody>
					</table>
				</div>

				<!-- Pagination -->
				<?php if ( $total > 0 ) : ?>
				<div class="wpaia-pagination">
					<span class="wpaia-pagination-info">
						Showing <?php echo esc_html( $start_item ); ?>–<?php echo esc_html( $end_item ); ?> of <?php echo esc_html( number_format( $total ) ); ?> conversations
					</span>
					<div class="wpaia-pagination-pages">
						<!-- Prev -->
						<?php $prev_url = $page > 1 ? add_query_arg( [ 'paged' => $page - 1 ] + ( $filter_user ? [ 'user_filter' => $filter_user ] : [] ), $base_url ) : ''; ?>
						<a href="<?php echo $prev_url ? esc_url( $prev_url ) : '#'; ?>"
						   class="wpaia-page-btn <?php echo ! $prev_url ? 'wpaia-page-btn-disabled' : ''; ?>">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
						</a>

						<?php
						$range_start = max( 1, $page - 2 );
						$range_end   = min( $total_pages, $page + 2 );
						for ( $p = $range_start; $p <= $range_end; $p++ ) :
							$p_url = add_query_arg( [ 'paged' => $p ] + ( $filter_user ? [ 'user_filter' => $filter_user ] : [] ), $base_url );
						?>
						<a href="<?php echo esc_url( $p_url ); ?>"
						   class="wpaia-page-btn <?php echo $p === $page ? 'wpaia-page-btn-active' : ''; ?>">
							<?php echo esc_html( $p ); ?>
						</a>
						<?php endfor; ?>

						<!-- Next -->
						<?php $next_url = $page < $total_pages ? add_query_arg( [ 'paged' => $page + 1 ] + ( $filter_user ? [ 'user_filter' => $filter_user ] : [] ), $base_url ) : ''; ?>
						<a href="<?php echo $next_url ? esc_url( $next_url ) : '#'; ?>"
						   class="wpaia-page-btn <?php echo ! $next_url ? 'wpaia-page-btn-disabled' : ''; ?>">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
						</a>
					</div>
				</div>
				<?php endif; ?>

			</main>
		</div>
		</div><!-- .wpaia-admin -->
		<?php
	}

	// ─── Transcript View ──────────────────────────────────────────────

	private static function render_transcript( int $conv_id ) {
		global $wpdb;

		$conv_table   = $wpdb->prefix . 'wally_conversations';
		$msg_table    = $wpdb->prefix . 'wally_messages';
		$act_table    = $wpdb->prefix . 'wally_actions';
		$base_url     = admin_url( 'admin.php?page=wpaia-conversations' );
		$settings_url = admin_url( 'admin.php?page=wally' );

		$conv = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$conv_table} WHERE id = %d", $conv_id ) );

		if ( ! $conv ) {
			self::render_page_styles();
			self::render_page_header( $settings_url, 'conversations' );
			echo '<div class="wpaia-body">';
			self::render_sidebar( $settings_url, 'conversations' );
			echo '<main class="wpaia-main"><p style="color:#71717A;">Conversation not found. <a href="' . esc_url( $base_url ) . '">← Back to list</a></p></main></div></div>';
			return;
		}

		$messages = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$msg_table} WHERE conversation_id = %d ORDER BY created_at ASC", $conv_id
		) );

		$actions = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$act_table} WHERE conversation_id = %d ORDER BY created_at ASC", $conv_id
		) );

		$user_info    = get_userdata( $conv->user_id );
		$username     = $user_info ? $user_info->display_name . ' (' . $user_info->user_login . ')' : '#' . $conv->user_id;
		$total_tokens = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(token_count) FROM {$msg_table} WHERE conversation_id = %d", $conv_id
		) );

		$json_url   = add_query_arg( [ 'conv_id' => $conv_id, 'export' => 'json' ], $base_url );
		$plain_text = self::build_plain_text( $conv, $messages, $actions, $username );

		self::render_page_styles();
		self::render_page_header( $settings_url, 'conversations' );
		?>

		<div class="wpaia-body">
			<?php self::render_sidebar( $settings_url, 'conversations' ); ?>

			<main class="wpaia-main">

				<!-- Back + title -->
				<div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
					<a href="<?php echo esc_url( $base_url ); ?>" class="wpaia-btn-outline">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
						Back
					</a>
					<div>
						<h1 class="wpaia-section-title" style="font-size:22px;">Conversation #<?php echo esc_html( $conv_id ); ?></h1>
						<p class="wpaia-section-desc" style="font-size:13px;"><?php echo esc_html( $conv->title ?: '(untitled)' ); ?></p>
					</div>
					<div style="margin-left:auto;display:flex;gap:8px;">
						<a href="<?php echo esc_url( $json_url ); ?>" class="wpaia-btn-outline">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
							Export JSON
						</a>
						<button id="wpaia-copy-btn" type="button" class="wpaia-btn-outline" onclick="wpaiacopytranscript()">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
							Copy as Text
						</button>
					</div>
				</div>

				<textarea id="wpaia-plain-transcript" style="position:absolute;left:-9999px;" readonly><?php echo esc_textarea( $plain_text ); ?></textarea>

				<!-- Meta card -->
				<div class="wpaia-card" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
					<?php
					$meta = [
						'User'         => $username,
						'Messages'     => count( $messages ),
						'Total Tokens' => $total_tokens ? number_format( $total_tokens ) : '—',
						'Started'      => date( 'M j, Y · H:i', strtotime( $conv->created_at ) ),
						'Last Active'  => date( 'M j, Y · H:i', strtotime( $conv->updated_at ) ),
					];
					if ( ! empty( $actions ) ) $meta['Tool Actions'] = count( $actions );
					foreach ( $meta as $k => $v ) :
					?>
					<div style="display:flex;flex-direction:column;gap:3px;">
						<span style="font-size:11px;font-weight:600;color:#A1A1AA;text-transform:uppercase;letter-spacing:.5px;"><?php echo esc_html( $k ); ?></span>
						<span style="font-size:14px;color:#18181B;"><?php echo esc_html( $v ); ?></span>
					</div>
					<?php endforeach; ?>
				</div>

				<!-- Messages -->
				<div style="display:flex;flex-direction:column;gap:10px;">
					<?php if ( empty( $messages ) ) : ?>
						<p style="color:#A1A1AA;font-size:14px;font-style:italic;">No messages in this conversation.</p>
					<?php else : foreach ( $messages as $msg ) :
						$is_user = $msg->role === 'user';
					?>
					<div class="wpaia-msg-card <?php echo $is_user ? 'wpaia-msg-user' : 'wpaia-msg-assistant'; ?>">
						<div class="wpaia-msg-header">
							<span class="wpaia-msg-role <?php echo $is_user ? 'wpaia-role-user' : 'wpaia-role-assistant'; ?>">
								<?php echo $is_user ? 'User' : 'Assistant'; ?>
							</span>
							<span class="wpaia-msg-meta">
								<?php echo esc_html( date( 'M j, Y · H:i', strtotime( $msg->created_at ) ) ); ?>
								<?php if ( $msg->token_count ) : ?>
									&middot; <?php echo esc_html( number_format( (int) $msg->token_count ) ); ?> tokens
								<?php endif; ?>
							</span>
						</div>
						<div class="wpaia-msg-content"><?php echo esc_html( $msg->content ); ?></div>
					</div>
					<?php endforeach; endif; ?>
				</div>

				<!-- Tool Actions -->
				<?php if ( ! empty( $actions ) ) : ?>
				<div>
					<h2 class="wpaia-section-title" style="font-size:18px;margin-bottom:16px;">Tool Actions</h2>
					<div class="wpaia-table-wrap">
						<table class="wpaia-table">
							<thead>
								<tr>
									<th style="width:150px;">Time</th>
									<th style="width:180px;">Tool</th>
									<th style="width:90px;text-align:center;">Status</th>
									<th>Input / Output</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $actions as $action ) :
									$status_class = [
										'success'   => 'wpaia-badge-success',
										'failed'    => 'wpaia-badge-error',
										'pending'   => 'wpaia-badge-warning',
										'cancelled' => 'wpaia-badge-neutral',
									][ $action->status ] ?? 'wpaia-badge-neutral';
								?>
								<tr class="wpaia-tr-border">
									<td style="font-size:12px;color:#A1A1AA;"><?php echo esc_html( $action->created_at ); ?></td>
									<td><code style="font-size:12px;background:#F4F4F5;padding:2px 8px;border-radius:6px;"><?php echo esc_html( $action->tool_name ); ?></code></td>
									<td style="text-align:center;">
										<span class="wpaia-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( ucfirst( $action->status ) ); ?></span>
									</td>
									<td>
										<details style="cursor:pointer;">
											<summary style="font-size:12px;color:#71717A;cursor:pointer;">Input</summary>
											<pre class="wpaia-pre"><?php echo esc_html( json_encode( json_decode( $action->tool_input ?: '{}' ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
										</details>
										<details style="cursor:pointer;margin-top:4px;">
											<summary style="font-size:12px;color:#71717A;cursor:pointer;">Output</summary>
											<pre class="wpaia-pre"><?php echo esc_html( json_encode( json_decode( $action->tool_output ?: '{}' ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
										</details>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php endif; ?>

			</main>
		</div>
		</div><!-- .wpaia-admin -->

		<script>
		function wpaiacopytranscript() {
			var btn = document.getElementById('wpaia-copy-btn');
			var text = document.getElementById('wpaia-plain-transcript').value;
			navigator.clipboard.writeText(text).then(function() {
				btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Copied!';
				setTimeout(function() { btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Copy as Text'; }, 2000);
			}).catch(function() {
				var ta = document.getElementById('wpaia-plain-transcript');
				ta.style.position = 'static'; ta.select(); document.execCommand('copy'); ta.style.position = 'absolute';
				btn.textContent = '✓ Copied!';
				setTimeout(function() { btn.textContent = '⎘ Copy as Text'; }, 2000);
			});
		}
		</script>
		<?php
	}

	// ─── Shared UI Helpers ────────────────────────────────────────────

	private static function render_page_styles() {
		?>
		<style>
		#wpcontent { padding-left: 0 !important; }
		#wpbody-content { padding-bottom: 0; }
		.wpaia-admin *, .wpaia-admin *::before, .wpaia-admin *::after { box-sizing: border-box; }
		.wpaia-admin { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; color: #18181B; min-height: 100vh; background: #FFFFFF; }

		.wpaia-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 32px; border-bottom: 1px solid #E4E4E7; background: #FFFFFF; position: sticky; top: 32px; z-index: 100; }
		.wpaia-header-left { display: flex; align-items: center; gap: 12px; }
		.wpaia-logo { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden; }
		.wpaia-logo img { width: 100%; height: 100%; object-fit: cover; }
		.wpaia-brand { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; font-size: 16px; font-weight: 700; color: #18181B; }

		.wpaia-body { display: flex; min-height: calc(100vh - 65px); }

		.wpaia-sidebar { width: 240px; flex-shrink: 0; padding: 32px 16px 32px 24px; display: flex; flex-direction: column; gap: 4px; position: sticky; top: 97px; align-self: flex-start; }
		.wpaia-nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 16px; border-radius: 12px; font-size: 14px; font-weight: 400; color: #71717A; text-decoration: none; transition: background .15s, color .15s; }
		.wpaia-nav-item svg { color: #A1A1AA; flex-shrink: 0; }
		.wpaia-nav-item:hover { background: #F4F4F5; color: #18181B; }
		.wpaia-nav-active { background: #F4F4F5 !important; color: #18181B !important; font-weight: 600 !important; }
		.wpaia-nav-active svg { color: #8B5CF6 !important; }

		.wpaia-main { flex: 1; min-width: 0; padding: 40px 60px; display: flex; flex-direction: column; gap: 28px; }

		.wpaia-section-title { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; font-size: 28px; font-weight: 700; color: #18181B; margin: 0; line-height: 1.1; }
		.wpaia-section-desc { font-size: 15px; color: #71717A; margin: 0; line-height: 1.5; }
		.wpaia-section-header { display: flex; flex-direction: column; gap: 6px; }

		.wpaia-filter-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
		.wpaia-filter-input-wrap { display: flex; align-items: center; gap: 8px; padding: 10px 16px; background: #FFFFFF; border: 1px solid #E4E4E7; border-radius: 12px; }
		.wpaia-filter-input-wrap svg { color: #A1A1AA; flex-shrink: 0; }
		.wpaia-filter-input { border: none !important; outline: none !important; box-shadow: none !important; background: transparent; font-family: 'Inter', sans-serif; font-size: 13px; color: #18181B; width: 200px; padding: 0; }
		.wpaia-filter-input::placeholder { color: #A1A1AA; }
		.wpaia-count-text { font-size: 13px; color: #A1A1AA; white-space: nowrap; }

		.wpaia-table-wrap { border-radius: 16px; border: 1px solid #E4E4E7; overflow: hidden; background: #FFFFFF; }
		.wpaia-table { width: 100%; border-collapse: collapse; }
		.wpaia-table thead tr { background: #FAFAFA; }
		.wpaia-table th { padding: 14px 20px; font-size: 12px; font-weight: 600; color: #71717A; text-align: left; border: none; }
		.wpaia-table td { padding: 14px 20px; font-size: 13px; border: none; }
		.wpaia-tr-border td { border-top: 1px solid #F4F4F5 !important; }

		.wpaia-transcript-btn { display: inline-flex; align-items: center; padding: 5px 12px; border-radius: 8px; border: 1px solid #8B5CF6; font-size: 11px; font-weight: 500; color: #8B5CF6; text-decoration: none; transition: background .15s; }
		.wpaia-transcript-btn:hover { background: rgba(139,92,246,.06); color: #7C3AED; }

		.wpaia-pagination { display: flex; align-items: center; justify-content: space-between; }
		.wpaia-pagination-info { font-size: 13px; color: #A1A1AA; }
		.wpaia-pagination-pages { display: flex; align-items: center; gap: 4px; }
		.wpaia-page-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #E4E4E7; font-size: 13px; color: #71717A; text-decoration: none; transition: background .15s, color .15s; }
		.wpaia-page-btn:hover { background: #F4F4F5; color: #18181B; }
		.wpaia-page-btn-active { background: #8B5CF6 !important; border-color: #8B5CF6 !important; color: #FFFFFF !important; font-weight: 600; }
		.wpaia-page-btn-disabled { opacity: 0.4; pointer-events: none; }

		.wpaia-btn-primary { display: inline-flex; align-items: center; justify-content: center; padding: 10px 24px; border-radius: 100px; background: #8B5CF6; color: #FFFFFF; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; border: none; cursor: pointer; transition: background .15s; text-decoration: none; }
		.wpaia-btn-primary:hover { background: #7C3AED; color: #FFFFFF; }
		.wpaia-btn-primary-sm { display: inline-flex; align-items: center; justify-content: center; padding: 9px 20px; border-radius: 100px; background: #8B5CF6; color: #FFFFFF; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; border: none; cursor: pointer; transition: background .15s; }
		.wpaia-btn-primary-sm:hover { background: #7C3AED; }
		.wpaia-btn-outline { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 100px; border: 1px solid #E4E4E7; background: transparent; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 500; color: #71717A; cursor: pointer; transition: background .15s, color .15s; text-decoration: none; }
		.wpaia-btn-outline:hover { background: #F4F4F5; color: #18181B; border-color: #D4D4D8; }
		.wpaia-btn-outline-sm { display: inline-flex; align-items: center; padding: 9px 20px; border-radius: 100px; border: 1px solid #E4E4E7; background: transparent; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 500; color: #71717A; cursor: pointer; text-decoration: none; transition: background .15s; }
		.wpaia-btn-outline-sm:hover { background: #F4F4F5; color: #18181B; }

		.wpaia-card { background: #F4F4F5; border-radius: 24px; padding: 28px; }
		.wpaia-badge { display: inline-block; padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 600; }
		.wpaia-badge-success { background: #DCFCE7; color: #16A34A; }
		.wpaia-badge-error   { background: #FEE2E2; color: #DC2626; }
		.wpaia-badge-warning { background: #FEF9C3; color: #CA8A04; }
		.wpaia-badge-neutral { background: #F4F4F5; color: #71717A; }

		.wpaia-msg-card { border-radius: 12px; padding: 16px; }
		.wpaia-msg-user { background: #F4F4F5; }
		.wpaia-msg-assistant { background: #FFFFFF; border: 1px solid #E4E4E7; }
		.wpaia-msg-header { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
		.wpaia-msg-role { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
		.wpaia-role-user { color: #18181B; }
		.wpaia-role-assistant { color: #7C3AED; }
		.wpaia-msg-meta { font-size: 11px; color: #A1A1AA; }
		.wpaia-msg-content { font-size: 13px; line-height: 1.6; color: #18181B; white-space: pre-wrap; word-break: break-word; }

		.wpaia-pre { max-height: 150px; overflow: auto; font-size: 11px; background: #F4F4F5; padding: 8px; border-radius: 6px; margin: 6px 0 0; }

		@media (max-width: 960px) {
			.wpaia-sidebar { display: none; }
			.wpaia-main { padding: 24px 20px; }
			.wpaia-filter-row { gap: 8px; }
			.wpaia-pagination { flex-direction: column; align-items: flex-start; gap: 12px; }
		}
		@media (max-width: 900px) {
			.wpaia-table thead { display: none; }
			.wpaia-table tr { display: block; }
			.wpaia-table td { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 10px 16px; text-align: left !important; }
			.wpaia-table td[data-label]::before { content: attr(data-label); font-size: 11px; font-weight: 600; color: #A1A1AA; text-transform: uppercase; letter-spacing: .5px; white-space: nowrap; flex-shrink: 0; padding-top: 1px; min-width: 72px; }
			.wpaia-table tbody tr.wpaia-tr-border { border-top: 8px solid #F4F4F5; }
			.wpaia-table tbody tr.wpaia-tr-border td { border-top: 1px solid #F4F4F5 !important; }
			.wpaia-table tbody tr.wpaia-tr-border td:first-child { border-top: none !important; }
		}
		</style>
		<?php
	}

	private static function render_page_header( string $settings_url, string $active_page = '' ) {
		?>
		<div class="wpaia-admin">
		<div class="wpaia-header">
			<div class="wpaia-header-left">
				<div class="wpaia-logo">
					<img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'admin/images/wp-ai-logo.png' ); ?>" alt="Wally Logo" />
				</div>
				<span class="wpaia-brand">Wally</span>
			</div>
		</div>
		<?php
	}

	private static function render_sidebar( string $settings_url, string $active = '' ) {
		$conv_url  = admin_url( 'admin.php?page=wpaia-conversations' );
		$audit_url = admin_url( 'admin.php?page=wpaia-audit-log' );
		?>
		<nav class="wpaia-sidebar">
			<a href="<?php echo esc_url( $settings_url . '#section-general' ); ?>" class="wpaia-nav-item <?php echo $active === 'general' ? 'wpaia-nav-active' : ''; ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
				General
			</a>
			<a href="<?php echo esc_url( $settings_url . '#section-permissions' ); ?>" class="wpaia-nav-item">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
				Permissions
			</a>
			<a href="<?php echo esc_url( $settings_url . '#section-site-profile' ); ?>" class="wpaia-nav-item">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
				Site Profile
			</a>
			<a href="<?php echo esc_url( $audit_url ); ?>" class="wpaia-nav-item <?php echo $active === 'audit' ? 'wpaia-nav-active' : ''; ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
				Audit Log
			</a>
			<a href="<?php echo esc_url( $conv_url ); ?>" class="wpaia-nav-item <?php echo $active === 'conversations' ? 'wpaia-nav-active' : ''; ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
				Conversations
			</a>
		</nav>
		<?php
	}

	// ─── JSON Export ──────────────────────────────────────────────────

	private static function export_json( int $conv_id ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions.' );
		}

		global $wpdb;

		$conv_table = $wpdb->prefix . 'wally_conversations';
		$msg_table  = $wpdb->prefix . 'wally_messages';
		$act_table  = $wpdb->prefix . 'wally_actions';

		$conv = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$conv_table} WHERE id = %d", $conv_id ), ARRAY_A );

		if ( ! $conv ) {
			wp_die( 'Conversation not found.' );
		}

		$messages = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$msg_table} WHERE conversation_id = %d ORDER BY created_at ASC", $conv_id
		), ARRAY_A );

		$actions = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$act_table} WHERE conversation_id = %d ORDER BY created_at ASC", $conv_id
		), ARRAY_A );

		foreach ( $actions as &$action ) {
			$action['tool_input']  = json_decode( $action['tool_input'] ?? '{}', true );
			$action['tool_output'] = json_decode( $action['tool_output'] ?? '{}', true );
		}
		unset( $action );

		$user_info = get_userdata( (int) $conv['user_id'] );

		$export = [
			'export_date'  => gmdate( 'c' ),
			'site_url'     => get_site_url(),
			'conversation' => $conv,
			'user'         => $user_info ? [
				'id'    => $user_info->ID,
				'login' => $user_info->user_login,
			] : null,
			'messages'     => $messages,
			'tool_actions' => $actions,
		];

		$filename = 'conversation-' . $conv_id . '-' . gmdate( 'Ymd-His' ) . '.json';

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		exit;
	}

	// ─── Plain-Text Builder ───────────────────────────────────────────

	private static function build_plain_text( object $conv, array $messages, array $actions, string $username ): string {
		$lines = [];

		$lines[] = '=== Conversation #' . $conv->id . ' ===';
		$lines[] = 'User:       ' . $username;
		$lines[] = 'Title:      ' . ( $conv->title ?: '(untitled)' );
		$lines[] = 'Started:    ' . $conv->created_at;
		$lines[] = 'Last active:' . $conv->updated_at;
		$lines[] = '';
		$lines[] = str_repeat( '-', 60 );
		$lines[] = '';

		foreach ( $messages as $msg ) {
			$label   = strtoupper( $msg->role );
			$lines[] = "[{$label}] ({$msg->created_at})";
			$lines[] = $msg->content;
			$lines[] = '';
		}

		if ( ! empty( $actions ) ) {
			$lines[] = str_repeat( '-', 60 );
			$lines[] = 'TOOL ACTIONS';
			$lines[] = str_repeat( '-', 60 );
			$lines[] = '';
			foreach ( $actions as $action ) {
				$lines[] = "[{$action->tool_name}] status={$action->status}  ({$action->created_at})";
				$lines[] = 'Input:  ' . ( $action->tool_input ?? '{}' );
				$lines[] = 'Output: ' . ( $action->tool_output ?? '{}' );
				$lines[] = '';
			}
		}

		return implode( "\n", $lines );
	}
}
