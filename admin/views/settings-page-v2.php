<?php
// Secure access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get Data
$stats          = BeanST_Stats::get_overall_stats();
$options        = get_option( 'beanst_options', array() );
$converter      = new BeanST_Converter();
$heic_available = $converter->is_heic_supported();
$seo_audit      = BeanST_SEO::get_seo_audit_stats();

// Calculate Progress
$total_images = $stats['total'];
$optimized    = $stats['optimized'];
$percent      = $total_images > 0 ? round( ( $optimized / $total_images ) * 100 ) : 0;
$remaining    = $total_images - $optimized;

// Hero Title Logic
if ( $percent === 100 && $total_images > 0 ) {
	$hero_title = __( '🎉 Great Work! All Images Optimized', 'beanst-image-optimizer' );
	$hero_class = 'beanst-hero-success';
} elseif ( $percent > 50 ) {
	$hero_title = __( '🚀 Making Great Progress!', 'beanst-image-optimizer' );
	$hero_class = 'beanst-hero-progress';
} else {
	$hero_title = __( '⚡ Ready to Optimize Your Library', 'beanst-image-optimizer' );
	$hero_class = 'beanst-hero-start';
}
?>

<div class="wrap beanst-optimizer-settings">
	<h1>BeanST Image Optimizer <span class="beanst-version">v<?php echo BEANST_VERSION; ?></span></h1>

	<!-- HERO SECTION -->
	<div class="beanst-hero-section <?php echo $hero_class; ?>">
		<div class="beanst-hero-content">
			<h2 class="beanst-hero-title"><?php echo esc_html( $hero_title ); ?></h2>
			
			<div class="beanst-hero-stats">
				<div class="beanst-stat-item">
					<span class="beanst-stat-number"><?php echo number_format_i18n( $total_images ); ?></span>
					<span class="beanst-stat-label"><?php esc_html_e( 'Total Images', 'beanst-image-optimizer' ); ?></span>
				</div>
				<div class="beanst-stat-item">
					<span class="beanst-stat-number beanst-success"><?php echo number_format_i18n( $optimized ); ?></span>
					<span class="beanst-stat-label"><?php esc_html_e( 'Optimized', 'beanst-image-optimizer' ); ?></span>
				</div>
				<div class="beanst-stat-item">
					<span class="beanst-stat-number beanst-highlight"><?php echo esc_html( $stats['savings'] ); ?></span>
					<span class="beanst-stat-label"><?php esc_html_e( 'Disk Space Saved', 'beanst-image-optimizer' ); ?></span>
				</div>
			</div>

			<div class="beanst-progress-wrapper">
				<div class="beanst-progress-bar-hero">
					<div class="beanst-progress-fill-hero" style="width: <?php echo esc_attr( $percent ); ?>%"></div>
				</div>
				<div class="beanst-progress-label">
					<span><?php echo esc_html( $percent ); ?>% <?php esc_html_e( 'Complete', 'beanst-image-optimizer' ); ?></span>
					<span><?php echo esc_html( $remaining ); ?> <?php esc_html_e( 'remaining', 'beanst-image-optimizer' ); ?></span>
				</div>
			</div>

			<?php if ( $remaining > 0 ) : ?>
				<button id="beanst-hero-optimize" class="button button-primary beanst-hero-cta">
					<?php printf( esc_html__( 'Optimize %d Remaining Images &rarr;', 'beanst-image-optimizer' ), esc_html( $remaining ) ); ?>
				</button>
			<?php else : ?>
				<div class="beanst-success-message">
					<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Library is fully optimized!', 'beanst-image-optimizer' ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- AUTO-OPTIMIZE TOGGLE CARD -->
	<div class="beanst-section">
		<h3 class="beanst-section-title"><?php esc_html_e( 'Quick Actions', 'beanst-image-optimizer' ); ?></h3>
		<div class="beanst-action-cards">
			
			<!-- Card 1: Auto Optimize -->
			<div class="beanst-action-card">
				<div class="beanst-action-icon">⚡</div>
				<h3><?php esc_html_e( 'Auto-Optimize', 'beanst-image-optimizer' ); ?></h3>
				<p><?php esc_html_e( 'Automatically convert new images on upload.', 'beanst-image-optimizer' ); ?></p>
				<label class="beanst-toggle">
					<input type="checkbox" id="beanst-auto-convert-toggle" 
						<?php checked( isset($options['auto_convert']) ? $options['auto_convert'] : 0, 1 ); ?>>
					<span class="beanst-toggle-slider"></span>
					<span class="beanst-toggle-label"><?php esc_html_e( 'Disabled', 'beanst-image-optimizer' ); ?></span>
				</label>
			</div>

			<!-- Card 2: Cleanup -->
			<div class="beanst-action-card">
				<div class="beanst-action-icon">🧹</div>
				<h3><?php esc_html_e( 'Clean Up Files', 'beanst-image-optimizer' ); ?></h3>
				<p><?php esc_html_e( 'Remove unused optimized files to free up space.', 'beanst-image-optimizer' ); ?></p>
				<button id="beanst-scan-orphans-btn" class="button button-secondary"><?php esc_html_e( 'Scan for Unused Files', 'beanst-image-optimizer' ); ?></button>
			</div>

			<!-- Card 3: Settings -->
			<div class="beanst-action-card">
				<div class="beanst-action-icon">⚙️</div>
				<h3><?php esc_html_e( 'Settings', 'beanst-image-optimizer' ); ?></h3>
				<p><?php esc_html_e( 'Configure output formats and quality.', 'beanst-image-optimizer' ); ?></p>
				<a href="#beanst-settings-section" class="button button-secondary beanst-scroll-to"><?php esc_html_e( 'Configure Settings', 'beanst-image-optimizer' ); ?></a>
			</div>

		</div>
	</div>
    
    <!-- CLEANUP RESULTS (Hidden by default) -->
    <div id="beanst-cleanup-results" style="display:none; margin-bottom: 30px;">
        <div class="beanst-stats-card">
             <h3><?php esc_html_e( '🧹 Scan Results', 'beanst-image-optimizer' ); ?></h3>
             <p class="beanst-cleanup-summary">
                <?php printf( esc_html__( 'Found %s unused files taking up %s.', 'beanst-image-optimizer' ), '<span id="beanst-orphan-count" class="beanst-highlight">0</span>', '<span id="beanst-orphan-size" class="beanst-highlight">0 MB</span>' ); ?>
             </p>
             <div id="beanst-orphan-grid" style="max-height: 200px; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; margin: 15px 0;">
                <!-- Populated via JS -->
             </div>
             <button id="beanst-delete-orphans" class="button button-primary beanst-destructive"><?php esc_html_e( 'Delete Selected Files', 'beanst-image-optimizer' ); ?></button>
             <div id="beanst-cleanup-status" style="margin-top: 10px;"></div>
        </div>
    </div>

	<!-- BULK OPTIMIZATION SECTION -->
	<div id="beanst-bulk-section" class="beanst-section">
		<h3 class="beanst-section-title"><?php esc_html_e( 'Bulk Optimization', 'beanst-image-optimizer' ); ?></h3>
		
		<div class="beanst-stats-card">
			<p class="beanst-card-description">
				<?php esc_html_e( 'Process your entire media library in the background. Does not affect site performance.', 'beanst-image-optimizer' ); ?>
			</p>

			<div class="beanst-bulk-controls">
				<button id="beanst-bulk-convert" class="button button-primary button-hero"><?php esc_html_e( 'Start Optimization', 'beanst-image-optimizer' ); ?></button>
				<button id="beanst-bulk-pause" class="button button-secondary" style="display:none;"><?php esc_html_e( 'Pause', 'beanst-image-optimizer' ); ?></button>
				<button id="beanst-bulk-resume" class="button button-secondary" style="display:none;"><?php esc_html_e( 'Resume', 'beanst-image-optimizer' ); ?></button>
				
				<div class="beanst-force-option">
					<label class="beanst-checkbox-label">
						<input type="checkbox" id="beanst-force-optimize">
						<span><?php esc_html_e( 'Re-optimize all images (including already optimized ones)', 'beanst-image-optimizer' ); ?></span>
						<span class="beanst-help-tip" data-tip="<?php esc_attr_e( 'Use this if you changed quality settings and want to re-process everything.', 'beanst-image-optimizer' ); ?>">?</span>
					</label>
				</div>
			</div>

			<div id="beanst-bulk-progress" style="display:none;">
				<div class="beanst-progress-bar">
					<div class="beanst-progress-fill" style="width: 0%"></div>
				</div>
				<p class="beanst-status-text"><?php esc_html_e( 'Starting engine...', 'beanst-image-optimizer' ); ?></p>
			</div>
			
			<div id="beanst-bulk-log" class="beanst-console-log" style="display:none;"></div>
		</div>
	</div>

	<!-- SEO & ACCESSIBILITY SECTION -->
	<div class="beanst-section">
		<div class="beanst-seo-header">
			<h3 class="beanst-section-title"><?php esc_html_e( 'SEO & Accessibility', 'beanst-image-optimizer' ); ?></h3>
			<span id="beanst-seo-score-badge" class="beanst-seo-score-badge"><?php esc_html_e( 'Score:', 'beanst-image-optimizer' ); ?> <?php echo esc_html( $seo_audit['score'] ); ?>/100</span>
		</div>

		<div class="beanst-stats-card">
			<div class="beanst-seo-metrics">
				<div class="beanst-seo-metric">
					<span class="beanst-seo-metric-value" id="beanst-bad-names-count"><?php echo esc_html( $seo_audit['bad_names_count'] ); ?></span>
					<span class="beanst-seo-metric-label"><?php esc_html_e( 'Generic Filenames', 'beanst-image-optimizer' ); ?></span>
				</div>
				<div class="beanst-seo-metric">
					<span class="beanst-seo-metric-value" id="beanst-missing-alt-count"><?php echo esc_html( $seo_audit['missing_alt_count'] ); ?></span>
					<span class="beanst-seo-metric-label"><?php esc_html_e( 'Missing Alt Text', 'beanst-image-optimizer' ); ?></span>
				</div>
			</div>
			
			<p class="beanst-seo-help">
				💡 <strong><?php esc_html_e( 'Quick Fix:', 'beanst-image-optimizer' ); ?></strong> <?php printf( wp_kses_post( __( 'Visit your <a href="%s">Media Library</a> and look for the "SEO Review" column to fix these issues.', 'beanst-image-optimizer' ) ), esc_url( admin_url('upload.php?mode=list') ) ); ?>
			</p>
		</div>
	</div>

	<!-- SETTINGS SECTION -->
	<div id="beanst-settings-section" class="beanst-section">
		<h3 class="beanst-section-title"><?php esc_html_e( 'Configuration', 'beanst-image-optimizer' ); ?></h3>
		<form method="post" action="options.php" class="beanst-settings-form">
			<?php
			settings_fields( 'beanst_options_group' );
			do_settings_sections( 'beanst-image-optimizer' );
			submit_button( __( 'Save Changes', 'beanst-image-optimizer' ) );
			?>
		</form>
	</div>

	<!-- HELP & SUPPORT -->
	<div class="beanst-section">
		<h3 class="beanst-section-title"><?php esc_html_e( 'Help & Support', 'beanst-image-optimizer' ); ?></h3>
		<div class="beanst-help-grid">
			<div class="beanst-help-card">
				<span class="dashicons dashicons-book"></span>
				<h3><?php esc_html_e( 'Documentation', 'beanst-image-optimizer' ); ?></h3>
				<p><?php esc_html_e( 'Read the setup guide.', 'beanst-image-optimizer' ); ?></p>
				<a href="#"><?php esc_html_e( 'Read Docs', 'beanst-image-optimizer' ); ?></a>
			</div>
			<div class="beanst-help-card">
				<span class="dashicons dashicons-format-chat"></span>
				<h3><?php esc_html_e( 'Support', 'beanst-image-optimizer' ); ?></h3>
				<p><?php esc_html_e( 'Need help? Ask on the forums.', 'beanst-image-optimizer' ); ?></p>
				<a href="https://wordpress.org/support/plugin/beanst-image-optimizer/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get Support', 'beanst-image-optimizer' ); ?></a>
			</div>
			<div class="beanst-help-card">
				<span class="dashicons dashicons-star-filled"></span>
				<h3><?php esc_html_e( 'Rate Us', 'beanst-image-optimizer' ); ?></h3>
				<p><?php esc_html_e( 'Love it? Review us.', 'beanst-image-optimizer' ); ?></p>
				<a href="https://wordpress.org/support/plugin/beanst-image-optimizer/reviews/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Leave Review', 'beanst-image-optimizer' ); ?></a>
			</div>
		</div>
	</div>

	<div class="beanst-footer">
		BeanST Image Optimizer v<?php echo BEANST_VERSION; ?>
	</div>
</div>
