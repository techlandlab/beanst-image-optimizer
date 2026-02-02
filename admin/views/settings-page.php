<div class="wrap beanst-optimizer-settings">
    <h1>BeanST Image Optimizer</h1>

    <?php 
    $stats = BeanST_Stats::get_overall_stats(); 
    $options = get_option( 'beanst_options', array() );
    $converter = new BeanST_Converter();
    $gs_available = $converter->is_ghostscript_available();
    $heic_available = $converter->is_heic_supported();
    ?>

    <div class="beanst-dashboard-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <nav class="beanst-tabs-nav" style="margin-bottom: 0;">
            <a href="#" class="beanst-tab-link beanst-active" data-target="beanst-dashboard">Dashboard</a>
            <a href="#" class="beanst-tab-link" data-target="beanst-settings">Settings</a>
            <a href="#" class="beanst-tab-link" data-target="beanst-manual">User Manual</a>
        </nav>
        
        <div style="display: flex; gap: 10px;">
            <?php if ( ! $gs_available && isset( $options['pdf_optimization'] ) && $options['pdf_optimization'] ) : ?>
                <div class="beanst-alert beanst-alert-warning" style="margin: 0; padding: 10px 15px; font-size: 13px; border-radius: 8px;">
                    <strong>⚠️ PDF:</strong> Ghostscript missing.
                </div>
            <?php endif; ?>

            <?php if ( ! $heic_available && isset( $options['heic_convert'] ) && $options['heic_convert'] ) : ?>
                <div class="beanst-alert beanst-alert-warning" style="margin: 0; padding: 10px 15px; font-size: 13px; border-radius: 8px;">
                    <strong>⚠️ HEIC:</strong> Server support missing.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tab 1: Dashboard -->
    <div id="beanst-dashboard" class="beanst-tab-content beanst-active">
        <div class="beanst-dashboard-grid">
            <div class="beanst-stat-box beanst-stat-total">
                <div class="beanst-stat-icon"></div>
                <div class="beanst-stat-label">Total Images</div>
                <div id="beanst-stat-total" class="beanst-stat-value"><?php echo esc_html( $stats['total'] ); ?></div>
            </div>
            <div class="beanst-stat-box beanst-stat-optimized">
                <div class="beanst-stat-icon"></div>
                <div class="beanst-stat-label">Optimized</div>
                <div id="beanst-stat-optimized" class="beanst-stat-value"><?php echo esc_html( $stats['optimized'] ); ?></div>
            </div>
            <div class="beanst-stat-box beanst-stat-savings">
                <div class="beanst-stat-icon"></div>
                <div class="beanst-stat-label">Space Saved</div>
                <div id="beanst-stat-savings" class="beanst-stat-value"><?php echo esc_html( $stats['savings_human'] ); ?></div>
            </div>
        </div>

        <div class="beanst-stats-card" data-delay="<?php echo esc_attr( isset( $options['bulk_delay'] ) ? $options['bulk_delay'] : 0 ); ?>">
            <h2>Bulk Optimization</h2>
            <p style="margin-bottom: 24px; color: var(--beanst-text-muted);">Compress your entire library using modern algorithms. Safe, fast, and entirely local.</p>
            
            <div style="background: rgba(34, 113, 177, 0.04); padding: 20px; border-radius: 12px; margin-bottom: 24px; border: 1px solid rgba(34, 113, 177, 0.1);">
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                    <input type="checkbox" id="beanst-force-optimize" style="margin:0; transform: scale(1.2);"> 
                    <span style="font-weight: 700; font-size: 15px; color: var(--beanst-text-main);">Force re-optimization</span>
                    <span style="font-size: 13px; color: var(--beanst-text-muted);">(Refresh previously optimized images)</span>
                </label>
            </div>

            <div class="beanst-bulk-controls">
                <button id="beanst-bulk-convert" class="button button-primary button-hero" style="min-width: 200px; padding: 0 30px; height: 46px; border-radius: 23px; font-weight: 700; text-transform: uppercase;">Start Full Optimization</button>
                <button id="beanst-bulk-pause" class="button" style="display: none; height: 46px; border-radius: 23px; padding: 0 25px;">Pause Process</button>
                <button id="beanst-bulk-resume" class="button button-primary" style="display: none; height: 46px; border-radius: 23px; padding: 0 25px;">Resume Engine</button>
            </div>
            
            <div id="beanst-bulk-progress" style="margin-top: 35px; display: none;">
                <div class="beanst-progress-bar">
                    <div class="beanst-progress-fill"></div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                    <p class="beanst-status-text" style="margin: 0; font-size: 15px;">Initializing systems...</p>
                    <p id="beanst-memory-usage" style="margin:0; font-size: 13px; font-weight: 800; color: var(--beanst-text-muted);"></p>
                </div>
                <div id="beanst-bulk-log" class="beanst-bulk-log" style="display: none;"></div>
            </div>
        </div>

        <div class="beanst-stats-card" style="margin-top: 30px; background: linear-gradient(135deg, #fff 0%, #f9f9f9 100%);">
            <h2>Server Maintenance (Clean & Safe)</h2>
            <p style="margin-bottom: 24px; color: var(--beanst-text-muted);">Remove redundant optimized files that no longer have original images. Keep your server storage clean and efficient.</p>
            
            <div id="beanst-cleanup-controls" style="display: flex; gap: 15px; align-items: center;">
                <div id="beanst-scan-results" style="display: none; font-size: 14px; font-weight: 600; color: var(--beanst-text-main);">
                    Found <span id="beanst-orphan-count">0</span> orphans (<span id="beanst-orphan-size">0 MB</span>) 
                    <button id="beanst-toggle-orphan-preview" class="button" style="margin-left: 15px; border-radius: 15px;">Preview & Manage</button>
                    <button id="beanst-delete-orphans" class="button button-link-delete" style="margin-left: 10px; color: #d63384; font-weight: 700; text-decoration: none; border: 1px solid #ffccd5; padding: 5px 15px; border-radius: 15px; background: #fff;">Clear Selected</button>
                </div>
            </div>
            
            <div id="beanst-orphan-preview-container" style="display: none; margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h4 style="margin: 0;">Select Files to Remove</h4>
                    <div style="font-size: 12px;">
                        <a href="#" id="beanst-orphan-select-all" style="text-decoration: none; font-weight: 700;">Select All</a> | 
                        <a href="#" id="beanst-orphan-select-none" style="text-decoration: none; font-weight: 700;">Deselect All</a>
                    </div>
                </div>
                <div id="beanst-orphan-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 15px; max-height: 400px; overflow-y: auto; padding: 10px; background: #f9f9f9; border-radius: 12px; border: 1px solid #eee;">
                    <!-- Thumbnails injected here -->
                </div>
            </div>

            <div id="beanst-cleanup-status" style="margin-top: 15px; display: none; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 700;"></div>
        </div>

        <?php $seo_audit = BeanST_SEO::get_seo_audit_stats(); ?>
        <div class="beanst-stats-card" style="margin-top: 30px; border-top: 4px solid var(--beanst-primary-vibrant);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">🛡️ SEO Media Health Report</h2>
                <div id="beanst-seo-score-badge" style="background: #e7f9ed; color: #1e7e34; padding: 5px 15px; border-radius: 20px; font-weight: 800; font-size: 14px;">SCORE: <span id="beanst-seo-score"><?php echo $seo_audit['score']; ?></span>/100</div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; border: 1px solid #eee;">
                    <div style="font-size: 12px; color: #666; font-weight: 700; text-transform: uppercase; margin-bottom: 10px;">Bad Filenames</div>
                    <div style="display: flex; align-items: baseline; gap: 10px;">
                        <span id="beanst-bad-names-count" style="font-size: 24px; font-weight: 800; color: #d63384;"><?php echo $seo_audit['bad_names']; ?></span>
                        <span style="font-size: 13px; color: #999;">Generic/Numeric names</span>
                    </div>
                </div>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; border: 1px solid #eee;">
                    <div style="font-size: 12px; color: #666; font-weight: 700; text-transform: uppercase; margin-bottom: 10px;">Missing Alt Tags</div>
                    <div style="display: flex; align-items: baseline; gap: 10px;">
                        <span id="beanst-missing-alt-count" style="font-size: 24px; font-weight: 800; color: #d63384;"><?php echo $seo_audit['missing_alt']; ?></span>
                        <span style="font-size: 13px; color: #999;">Empty accessibility fields</span>
                    </div>
                </div>
            </div>

            <p style="margin-top: 20px; font-size: 13px; color: var(--beanst-text-muted); border-top: 1px solid #eee; padding-top: 15px;">
                <strong>💡 Quick Fix:</strong> Visit your <a href="upload.php" style="color: var(--beanst-primary); font-weight: 700; text-decoration: none;">Media Library</a> and look for the "SEO Review" column to fix these issues individually.
            </p>
        </div>
    </div>

    <!-- Tab 2: Settings -->
    <div id="beanst-settings" class="beanst-tab-content">
        <div class="beanst-stats-card">
            <h2>Optimization Settings</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'beanst_options_group' );
                do_settings_sections( 'beanst-image-optimizer' );
                submit_button('Apply Professional Settings', 'primary button-hero');
                ?>
            </form>
        </div>
    </div>

    <!-- Tab 3: Manual -->
    <div id="beanst-manual" class="beanst-tab-content">
        <div class="beanst-manual-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px;">
                <div>
                    <h2 style="margin-top: 0;">📖 Knowledge Base & Pro Guide</h2>
                    <p style="color: var(--beanst-text-muted); font-size: 15px;">Master every tool in the BeanST Optimizer arsenal. Version 2.3 focus.</p>
                </div>
                <div style="background: var(--beanst-primary); color: #fff; padding: 5px 15px; border-radius: 20px; font-size: 11px; font-weight: 800;">BUILD: <?php echo BEANST_VERSION; ?></div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                <!-- Column 1: Core & SEO -->
                <div>
                    <h3 style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; font-size: 18px;">
                        <span style="color: var(--beanst-primary);">🚀</span> Media & SEO Mastery
                    </h3>
                    
                    <div style="margin-bottom: 25px;">
                        <h4 style="margin-bottom: 8px; color: var(--beanst-text-main);">🛡️ SEO Media Health Audit</h4>
                        <p style="font-size: 13px; line-height: 1.6; color: #555;">
                            Our proprietary engine calculates a <strong>Health Score</strong> based on two factors:
                            <br>• <strong>Filename Patterns:</strong> We detect generic names like <code>IMG_001.jpg</code> or <code>screenshot.png</code> which hurt Google Image discovery.
                            <br>• <strong>Accessibility:</strong> Missing ALT tags are flagged as they violate WCAG and SEO standards.
                        </p>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <h4 style="margin-bottom: 8px; color: var(--beanst-text-main);">✨ Visual Proof System</h4>
                        <p style="font-size: 13px; line-height: 1.6; color: #555;">
                            Visit <strong>Media > Library</strong> and switch to <strong>List View</strong>. Look for <em>"Compare Original"</em>. 
                            Our interactive slider lets you verify quality retention before you trust the process with your entire library.
                        </p>
                    </div>
                </div>

                <!-- Column 2: Maintenance & Advanced -->
                <div>
                    <h3 style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; font-size: 18px;">
                        <span style="color: var(--beanst-primary);">🧹</span> Maintenance & Power Tools
                    </h3>

                    <div style="margin-bottom: 25px;">
                        <h4 style="margin-bottom: 8px; color: var(--beanst-text-main);">🔍 Smart Orphan Cleanup</h4>
                        <p style="font-size: 13px; line-height: 1.6; color: #555;">
                            <strong>What are orphans?</strong> When you delete an image manually via FTP or if a name changes, redundant WebP/AVIF files stay on your disk forever.
                            <br><strong>The Solution:</strong> Use "Scan for Orphans", then click "Preview" to see thumbnails of these lost files. Select only what you want to delete — it's 100% safe.
                        </p>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <h4 style="margin-bottom: 8px; color: var(--beanst-text-main);">📦 Directory Janitor</h4>
                        <p style="font-size: 13px; line-height: 1.6; color: #555;">
                            Optimize images outside <code>/uploads/</code>. Enter relative paths (like <code>wp-content/themes/mytheme/assets</code>) in Settings. 
                            We will recursively scan and compress theme assets, logo files, and UI icons.
                        </p>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <h4 style="margin-bottom: 8px; color: var(--beanst-text-main);">✨ Ultra-Premium Blur-Up (LQIP)</h4>
                        <p style="font-size: 13px; line-height: 1.6; color: #555;">
                            <strong>Eliminate "jumping" content.</strong> When enabled, images load instantly as beautiful blurred placeholders and fade into full resolution. 
                            Uses 20px Base64 signatures stored in your database for zero latency.
                        </p>
                    </div>
                </div>
            </div>

            <div style="margin-top: 40px; padding: 25px; background: #f8f9fa; border-radius: 15px; border: 1px solid #eee;">
                <h4 style="margin-top: 0; margin-bottom: 15px;">⚙️ Technical Requirements & Troubleshooting</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div>
                        <strong style="font-size: 12px; color: var(--beanst-primary);">PDF ENGINE</strong><br>
                        <span style="font-size: 12px; color: #666;">Requires <strong>Ghostscript</strong>. If missing, we fallback to Imagick (slower) or skip optimization if neither exists.</span>
                    </div>
                    <div>
                        <strong style="font-size: 12px; color: var(--beanst-primary);">APPLE HEIC</strong><br>
                        <span style="font-size: 12px; color: #666;">Requires <strong>Imagick with HEIC delegate</strong>. Most modern VPS support this. Check Site Health for status.</span>
                    </div>
                    <div>
                        <strong style="font-size: 12px; color: var(--beanst-primary);">MEMORY GUARD</strong><br>
                        <span style="font-size: 12px; color: #666;">We monitor usage. If your server hits <strong>85% RAM</strong>, we pause with a blue alert. Wait 30s and resume.</span>
                    </div>
                </div>
            </div>

            <div style="margin-top: 30px; text-align: center; border-top: 1px solid #eee; padding-top: 30px;">
                <p style="font-size: 14px; font-weight: 600; color: #333;">Need custom development or support? Visit <a href="https://bean.st/" target="_blank" style="color: var(--beanst-primary); text-decoration: none;">bean.st</a></p>
            </div>
        </div>
    </div>

    <!-- Persistent Branding Footer -->
    <div style="text-align: center; margin-top: 50px; padding-bottom: 40px; border-top: 1px solid #eee; padding-top: 20px;">
        <div style="display: inline-block; padding: 8px 20px; background: #fff; border-radius: 30px; border: 1px solid #eee; font-size: 12px; color: #666;">
            <strong>BeanST Optimizer <?php echo BEANST_VERSION; ?></strong> | Crafted with ❤️ by <a href="https://bean.st/" target="_blank" style="color: var(--beanst-primary); font-weight: bold; text-decoration: none;">bean.st</a>
        </div>
    </div>
</div>
