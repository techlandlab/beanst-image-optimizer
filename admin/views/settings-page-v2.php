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
	$hero_title = '🎉 Great Work! All Images Optimized';
	$hero_class = 'beanst-hero-success';
} elseif ( $percent > 50 ) {
	$hero_title = '🚀 Making Great Progress!';
	$hero_class = 'beanst-hero-progress';
} else {
	$hero_title = '⚡ Ready to Optimize Your Library';
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
					<span class="beanst-stat-label">Total Images</span>
				</div>
				<div class="beanst-stat-item">
					<span class="beanst-stat-number beanst-success"><?php echo number_format_i18n( $optimized ); ?></span>
					<span class="beanst-stat-label">Optimized</span>
				</div>
				<div class="beanst-stat-item">
					<span class="beanst-stat-number beanst-highlight"><?php echo esc_html( $stats['savings'] ); ?></span>
					<span class="beanst-stat-label">Disk Space Saved</span>
				</div>
			</div>

			<div class="beanst-progress-wrapper">
				<div class="beanst-progress-bar-hero">
					<div class="beanst-progress-fill-hero" style="width: <?php echo esc_attr( $percent ); ?>%"></div>
				</div>
				<div class="beanst-progress-label">
					<span><?php echo esc_html( $percent ); ?>% Complete</span>
					<span><?php echo esc_html( $remaining ); ?> remaining</span>
				</div>
			</div>

			<?php if ( $remaining > 0 ) : ?>
				<button id="beanst-hero-optimize" class="button button-primary beanst-hero-cta">
					Optimize <?php echo esc_html( $remaining ); ?> Remaining Images &rarr;
				</button>
			<?php else : ?>
				<div class="beanst-success-message">
					<span class="dashicons dashicons-yes-alt"></span> Library is fully optimized!
				</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- AUTO-OPTIMIZE TOGGLE CARD -->
	<div class="beanst-section">
		<h3 class="beanst-section-title">Quick Actions</h3>
		<div class="beanst-action-cards">
			
			<!-- Card 1: Auto Optimize -->
			<div class="beanst-action-card">
				<div class="beanst-action-icon">⚡</div>
				<h3>Auto-Optimize</h3>
				<p>Automatically convert new images on upload.</p>
				<label class="beanst-toggle">
					<input type="checkbox" id="beanst-auto-convert-toggle" 
						<?php checked( isset($options['auto_convert']) ? $options['auto_convert'] : 0, 1 ); ?>>
					<span class="beanst-toggle-slider"></span>
					<span class="beanst-toggle-label">Disabled</span>
				</label>
			</div>

			<!-- Card 2: Cleanup -->
			<div class="beanst-action-card">
				<div class="beanst-action-icon">🧹</div>
				<h3>Clean Up Files</h3>
				<p>Remove unused optimized files to free up space.</p>
				<button id="beanst-scan-orphans-btn" class="button button-secondary">Scan for Unused Files</button>
			</div>

			<!-- Card 3: Settings -->
			<div class="beanst-action-card">
				<div class="beanst-action-icon">⚙️</div>
				<h3>Settings</h3>
				<p>Configure output formats and quality.</p>
				<a href="#beanst-settings-section" class="button button-secondary beanst-scroll-to">Configure Settings</a>
			</div>

		</div>
	</div>
    
    <!-- CLEANUP RESULTS (Hidden by default) -->
    <div id="beanst-cleanup-results" style="display:none; margin-bottom: 30px;">
        <div class="beanst-stats-card">
             <h3>🧹 Scan Results</h3>
             <p class="beanst-cleanup-summary">
                Found <span id="beanst-orphan-count" class="beanst-highlight">0</span> unused files 
                taking up <span id="beanst-orphan-size" class="beanst-highlight">0 MB</span>.
             </p>
             <div id="beanst-orphan-grid" style="max-height: 200px; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; margin: 15px 0;">
                <!-- Populated via JS -->
             </div>
             <button id="beanst-delete-orphans" class="button button-primary beanst-destructive">Delete Selected Files</button>
             <div id="beanst-cleanup-status" style="margin-top: 10px;"></div>
        </div>
    </div>

	<!-- BULK OPTIMIZATION SECTION -->
	<div id="beanst-bulk-section" class="beanst-section">
		<h3 class="beanst-section-title">Bulk Optimization</h3>
		
		<div class="beanst-stats-card">
			<p class="beanst-card-description">
				Process your entire media library in the background. Does not affect site performance.
			</p>

			<div class="beanst-bulk-controls">
				<button id="beanst-bulk-convert" class="button button-primary button-hero">Start Optimization</button>
				<button id="beanst-bulk-pause" class="button button-secondary" style="display:none;">Pause</button>
				<button id="beanst-bulk-resume" class="button button-secondary" style="display:none;">Resume</button>
				
				<div class="beanst-force-option">
					<label class="beanst-checkbox-label">
						<input type="checkbox" id="beanst-force-optimize">
						<span>Re-optimize all images (including already optimized ones)</span>
						<span class="beanst-help-tip" data-tip="Use this if you changed quality settings and want to re-process everything.">?</span>
					</label>
				</div>
			</div>

			<div id="beanst-bulk-progress" style="display:none;">
				<div class="beanst-progress-bar">
					<div class="beanst-progress-fill" style="width: 0%"></div>
				</div>
				<p class="beanst-status-text">Starting engine...</p>
			</div>
			
			<div id="beanst-bulk-log" class="beanst-console-log" style="display:none;"></div>
		</div>
	</div>

	<!-- SEO & ACCESSIBILITY SECTION -->
	<div class="beanst-section">
		<div class="beanst-seo-header">
			<h3 class="beanst-section-title">SEO & Accessibility</h3>
			<span id="beanst-seo-score-badge" class="beanst-seo-score-badge">Score: <?php echo esc_html( $seo_audit['score'] ); ?>/100</span>
		</div>

		<div class="beanst-stats-card">
			<div class="beanst-seo-metrics">
				<div class="beanst-seo-metric">
					<span class="beanst-seo-metric-value" id="beanst-bad-names-count"><?php echo esc_html( $seo_audit['bad_names_count'] ); ?></span>
					<span class="beanst-seo-metric-label">Generic Filenames</span>
				</div>
				<div class="beanst-seo-metric">
					<span class="beanst-seo-metric-value" id="beanst-missing-alt-count"><?php echo esc_html( $seo_audit['missing_alt_count'] ); ?></span>
					<span class="beanst-seo-metric-label">Missing Alt Text</span>
				</div>
			</div>
			
			<p class="beanst-seo-help">
				💡 <strong>Quick Fix:</strong> Visit your <a href="<?php echo esc_url( admin_url('upload.php?mode=list') ); ?>">Media Library</a> and look for the "SEO Review" column to fix these issues.
			</p>
		</div>
	</div>

	<!-- SETTINGS SECTION -->
	<div id="beanst-settings-section" class="beanst-section">
		<h3 class="beanst-section-title">Configuration</h3>
		<form method="post" action="options.php" class="beanst-settings-form">
			<?php
			settings_fields( 'beanst_options_group' );
			do_settings_sections( 'beanst-image-optimizer' );
			submit_button( 'Save Changes' );
			?>
		</form>
	</div>

	<!-- HELP & SUPPORT -->
	<div class="beanst-section">
		<h3 class="beanst-section-title">Help & Support</h3>
		<div class="beanst-help-grid">
			<div class="beanst-help-card">
				<span class="dashicons dashicons-book"></span>
				<h3>Documentation</h3>
				<p>Read the setup guide.</p>
				<a href="#">Read Docs</a>
			</div>
			<div class="beanst-help-card">
				<span class="dashicons dashicons-format-chat"></span>
				<h3>Support</h3>
				<p>Need help? Ask on the forums.</p>
				<a href="https://wordpress.org/support/plugin/beanst-image-optimizer/" target="_blank">Get Support</a>
			</div>
			<div class="beanst-help-card">
				<span class="dashicons dashicons-star-filled"></span>
				<h3>Rate Us</h3>
				<p>Love it? Review us.</p>
				<a href="https://wordpress.org/support/plugin/beanst-image-optimizer/reviews/" target="_blank">Leave Review</a>
			</div>
		</div>
	</div>

	<div class="beanst-footer">
		BeanST Image Optimizer v<?php echo BEANST_VERSION; ?>
	</div>
</div>
