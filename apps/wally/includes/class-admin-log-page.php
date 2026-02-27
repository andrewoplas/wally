<?php
namespace Wally;

/**
 * Admin page for viewing the AI Assistant audit log.
 */
class AdminLogPage {

	public static function register_menu() {
		add_submenu_page(
			null,
			'AI Assistant Log',
			'Audit Log',
			'manage_options',
			'wpaia-audit-log',
			[ self::class, 'render_page' ]
		);
	}

	public static function render_page() {
		$per_page = 30;
		$page     = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
		$filters  = [];

		if ( ! empty( $_GET['user_id'] ) )   { $filters['user_id']   = (int) $_GET['user_id']; }
		if ( ! empty( $_GET['tool_name'] ) ) { $filters['tool_name'] = sanitize_text_field( $_GET['tool_name'] ); }
		if ( ! empty( $_GET['status'] ) )    { $filters['status']    = sanitize_text_field( $_GET['status'] ); }
		if ( ! empty( $_GET['date_from'] ) ) { $filters['date_from'] = sanitize_text_field( $_GET['date_from'] ); }
		if ( ! empty( $_GET['date_to'] ) )   { $filters['date_to']   = sanitize_text_field( $_GET['date_to'] ); }

		$filters['per_page'] = $per_page;
		$filters['page']     = $page;

		$result      = AuditLog::get_actions( $filters );
		$items       = $result['items'];
		$total       = $result['total'];
		$total_pages = (int) ceil( $total / $per_page );

		global $wpdb;
		$table      = $wpdb->prefix . 'wally_actions';
		$tool_names = $wpdb->get_col( "SELECT DISTINCT tool_name FROM {$table} ORDER BY tool_name ASC" );

		$base_url     = admin_url( 'admin.php?page=wpaia-audit-log' );
		$settings_url = admin_url( 'admin.php?page=wally' );

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

		.wpaia-filter-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
		.wpaia-filter-select, .wpaia-filter-number, .wpaia-filter-date {
			height: 40px; padding: 0 14px; border: 1px solid #E4E4E7; border-radius: 12px;
			font-family: 'Inter', sans-serif; font-size: 13px; color: #18181B;
			background: #FFFFFF; outline: none;
		}
		.wpaia-filter-select { padding-right: 32px; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%2371717A' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; cursor: pointer; }
		.wpaia-filter-number { width: 110px; }
		.wpaia-filter-date { width: 140px; }
		.wpaia-filter-sep { font-size: 13px; color: #A1A1AA; }
		.wpaia-count-text { font-size: 13px; color: #A1A1AA; margin-left: auto; white-space: nowrap; }

		.wpaia-table-wrap { border-radius: 16px; border: 1px solid #E4E4E7; overflow: hidden; background: #FFFFFF; }
		.wpaia-table { width: 100%; border-collapse: collapse; }
		.wpaia-table thead tr { background: #FAFAFA; }
		.wpaia-table th { padding: 14px 20px; font-size: 12px; font-weight: 600; color: #71717A; text-align: left; border: none; }
		.wpaia-table td { padding: 12px 20px; font-size: 13px; border: none; vertical-align: top; }
		.wpaia-tr-border td { border-top: 1px solid #F4F4F5 !important; }

		.wpaia-badge { display: inline-block; padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 600; }
		.wpaia-badge-success { background: #DCFCE7; color: #16A34A; }
		.wpaia-badge-error   { background: #FEE2E2; color: #DC2626; }
		.wpaia-badge-warning { background: #FEF9C3; color: #CA8A04; }
		.wpaia-badge-neutral { background: #F4F4F5; color: #71717A; }

		.wpaia-btn-primary-sm { display: inline-flex; align-items: center; padding: 9px 20px; border-radius: 100px; background: #8B5CF6; color: #FFFFFF; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; border: none; cursor: pointer; transition: background .15s; }
		.wpaia-btn-primary-sm:hover { background: #7C3AED; }
		.wpaia-btn-outline-sm { display: inline-flex; align-items: center; padding: 9px 20px; border-radius: 100px; border: 1px solid #E4E4E7; background: transparent; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 500; color: #71717A; text-decoration: none; transition: background .15s; }
		.wpaia-btn-outline-sm:hover { background: #F4F4F5; }

		.wpaia-pagination { display: flex; align-items: center; justify-content: space-between; }
		.wpaia-pagination-info { font-size: 13px; color: #A1A1AA; }
		.wpaia-pagination-pages { display: flex; align-items: center; gap: 4px; }
		.wpaia-page-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #E4E4E7; font-size: 13px; color: #71717A; text-decoration: none; transition: background .15s; }
		.wpaia-page-btn:hover { background: #F4F4F5; }
		.wpaia-page-btn-active { background: #8B5CF6 !important; border-color: #8B5CF6 !important; color: #FFFFFF !important; font-weight: 600; }
		.wpaia-page-btn-disabled { opacity: 0.4; pointer-events: none; }

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

		<div class="wpaia-admin">
		<div class="wpaia-header">
			<div class="wpaia-header-left">
				<div class="wpaia-logo">
					<img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'admin/images/wp-ai-logo.png' ); ?>" alt="Wally Logo" />
				</div>
				<span class="wpaia-brand">Wally</span>
			</div>
		</div>

		<div class="wpaia-body">
			<!-- Sidebar -->
			<nav class="wpaia-sidebar">
				<a href="<?php echo esc_url( $settings_url ); ?>" class="wpaia-nav-item">
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
				<a href="<?php echo esc_url( $base_url ); ?>" class="wpaia-nav-item wpaia-nav-active">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
					Audit Log
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpaia-conversations' ) ); ?>" class="wpaia-nav-item">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
					Conversations
				</a>
			</nav>

			<main class="wpaia-main">
				<div style="display:flex;flex-direction:column;gap:6px;">
					<h1 class="wpaia-section-title">Audit Log</h1>
					<p class="wpaia-section-desc">Track all actions performed by the AI assistant, including tool executions and content changes.</p>
				</div>

				<!-- Filters -->
				<form method="get" class="wpaia-filter-row">
					<input type="hidden" name="page" value="wpaia-audit-log" />

					<select name="tool_name" class="wpaia-filter-select">
						<option value="">All Tools</option>
						<?php foreach ( $tool_names as $tn ) : ?>
							<option value="<?php echo esc_attr( $tn ); ?>" <?php selected( $filters['tool_name'] ?? '', $tn ); ?>>
								<?php echo esc_html( ucwords( str_replace( '_', ' ', $tn ) ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<select name="status" class="wpaia-filter-select">
						<option value="">All Statuses</option>
						<?php foreach ( [ 'success', 'failed', 'pending', 'cancelled' ] as $st ) : ?>
							<option value="<?php echo esc_attr( $st ); ?>" <?php selected( $filters['status'] ?? '', $st ); ?>>
								<?php echo esc_html( ucfirst( $st ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<input type="number" name="user_id" placeholder="User ID"
					       value="<?php echo esc_attr( $filters['user_id'] ?? '' ); ?>"
					       class="wpaia-filter-number" />

					<input type="date" name="date_from" value="<?php echo esc_attr( $filters['date_from'] ?? '' ); ?>" class="wpaia-filter-date" />
					<span class="wpaia-filter-sep">to</span>
					<input type="date" name="date_to" value="<?php echo esc_attr( $filters['date_to'] ?? '' ); ?>" class="wpaia-filter-date" />

					<button type="submit" class="wpaia-btn-primary-sm">Filter</button>
					<a href="<?php echo esc_url( $base_url ); ?>" class="wpaia-btn-outline-sm">Reset</a>

					<span class="wpaia-count-text"><?php echo esc_html( number_format( $total ) ); ?> entries found</span>
				</form>

				<!-- Table -->
				<div class="wpaia-table-wrap">
					<table class="wpaia-table">
						<thead>
							<tr>
								<th style="width:50px;">ID</th>
								<th style="width:150px;">Date</th>
								<th style="width:90px;">User</th>
								<th style="width:180px;">Tool</th>
								<th style="width:90px;text-align:center;">Status</th>
								<th>Input</th>
								<th>Output</th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $items ) ) : ?>
								<tr>
									<td colspan="7" style="padding:40px;text-align:center;color:#A1A1AA;font-size:14px;">No actions found.</td>
								</tr>
							<?php else : foreach ( $items as $item ) :
								$user_info = get_userdata( $item->user_id );
								$username  = $user_info ? $user_info->user_login : '#' . $item->user_id;
								$status_class = [
									'success'   => 'wpaia-badge-success',
									'failed'    => 'wpaia-badge-error',
									'pending'   => 'wpaia-badge-warning',
									'cancelled' => 'wpaia-badge-neutral',
								][ $item->status ] ?? 'wpaia-badge-neutral';
							?>
							<tr class="wpaia-tr-border">
								<td style="color:#A1A1AA;"><?php echo esc_html( $item->id ); ?></td>
								<td style="font-size:12px;color:#A1A1AA;"><?php echo esc_html( $item->created_at ); ?></td>
								<td style="color:#71717A;"><?php echo esc_html( $username ); ?></td>
								<td><code style="font-size:12px;background:#F4F4F5;padding:2px 8px;border-radius:6px;"><?php echo esc_html( $item->tool_name ); ?></code></td>
								<td style="text-align:center;">
									<span class="wpaia-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( ucfirst( $item->status ) ); ?></span>
								</td>
								<td>
									<details>
										<summary style="font-size:12px;color:#71717A;cursor:pointer;">View</summary>
										<pre class="wpaia-pre"><?php echo esc_html( json_encode( json_decode( $item->tool_input ?: '{}' ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
									</details>
								</td>
								<td>
									<details>
										<summary style="font-size:12px;color:#71717A;cursor:pointer;">View</summary>
										<pre class="wpaia-pre"><?php echo esc_html( json_encode( json_decode( $item->tool_output ?: '{}' ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
									</details>
								</td>
							</tr>
							<?php endforeach; endif; ?>
						</tbody>
					</table>
				</div>

				<!-- Pagination -->
				<?php if ( $total_pages > 1 ) :
					$pagination_args = array_filter( $filters, fn( $v ) => $v !== null && $v !== '' );
					unset( $pagination_args['per_page'], $pagination_args['page'] );
					$start_item = $total ? ( $page - 1 ) * $per_page + 1 : 0;
					$end_item   = min( $page * $per_page, $total );
				?>
				<div class="wpaia-pagination">
					<span class="wpaia-pagination-info">
						Showing <?php echo esc_html( $start_item ); ?>–<?php echo esc_html( $end_item ); ?> of <?php echo esc_html( number_format( $total ) ); ?> entries
					</span>
					<div class="wpaia-pagination-pages">
						<?php $prev = $page > 1 ? add_query_arg( array_merge( [ 'paged' => $page - 1 ], $pagination_args ), $base_url ) : ''; ?>
						<a href="<?php echo $prev ? esc_url( $prev ) : '#'; ?>" class="wpaia-page-btn <?php echo ! $prev ? 'wpaia-page-btn-disabled' : ''; ?>">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
						</a>
						<?php
						$rstart = max( 1, $page - 2 );
						$rend   = min( $total_pages, $page + 2 );
						for ( $p = $rstart; $p <= $rend; $p++ ) :
							$p_url = add_query_arg( array_merge( [ 'paged' => $p ], $pagination_args ), $base_url );
						?>
						<a href="<?php echo esc_url( $p_url ); ?>" class="wpaia-page-btn <?php echo $p === $page ? 'wpaia-page-btn-active' : ''; ?>"><?php echo esc_html( $p ); ?></a>
						<?php endfor; ?>
						<?php $next = $page < $total_pages ? add_query_arg( array_merge( [ 'paged' => $page + 1 ], $pagination_args ), $base_url ) : ''; ?>
						<a href="<?php echo $next ? esc_url( $next ) : '#'; ?>" class="wpaia-page-btn <?php echo ! $next ? 'wpaia-page-btn-disabled' : ''; ?>">
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
}
