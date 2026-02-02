jQuery(document).ready(function ($) {
    console.log('BeanST Optimizer Admin JS Loaded v1.1.2');

    // Tab Switching - Scoped and Namespaced
    $(document).on('click', '.beanst-tab-link', function (e) {
        e.preventDefault();
        const target = $(this).attr('data-target');

        console.log('Switching to tab:', target);

        $('.beanst-tab-link').removeClass('beanst-active');
        $(this).addClass('beanst-active');

        $('.beanst-tab-content').removeClass('beanst-active');
        $('#' + target).addClass('beanst-active');
    });

    const $bulkBtn = $('#beanst-bulk-convert');
    const $pauseBtn = $('#beanst-bulk-pause');
    const $resumeBtn = $('#beanst-bulk-resume');
    const $progressArea = $('#beanst-bulk-progress');
    const $progressBar = $progressArea.find('.beanst-progress-fill');
    const $statusText = $progressArea.find('.beanst-status-text');
    const $memText = $('#beanst-memory-usage');
    const $logArea = $('#beanst-bulk-log');

    let totalImages = 0;
    let processedImages = 0;
    let imageIds = [];
    let orphanFiles = []; // Store found orphans
    let isPaused = false;
    let isRunning = false;

    $bulkBtn.on('click', function (e) {
        e.preventDefault();
        if (confirm(beanst_ajax.i18n.confirm_optimize_all)) {
            startBulkProcess();
        }
    });

    $pauseBtn.on('click', function (e) {
        e.preventDefault();
        isPaused = true;
        $pauseBtn.hide();
        $resumeBtn.show();
        $statusText.text(beanst_ajax.i18n.paused_status.replace('%1$s', processedImages).replace('%2$s', totalImages));
    });

    $resumeBtn.on('click', function (e) {
        e.preventDefault();
        isPaused = false;
        $resumeBtn.hide();
        $pauseBtn.show();
        $statusText.text(beanst_ajax.i18n.resuming);
        processNextBatch();
    });

    function startBulkProcess() {
        isRunning = true;
        isPaused = false;
        $bulkBtn.hide();
        $pauseBtn.show();
        $progressArea.show();
        $logArea.show().empty();
        $statusText.text(beanst_ajax.i18n.initializing);

        $.post(beanst_ajax.ajax_url, {
            action: 'beanst_get_stats',
            nonce: beanst_ajax.nonce
        }, function (response) {
            if (response.success) {
                imageIds = response.data.ids;
                totalImages = response.data.total;
                processedImages = 0;

                $('#beanst-stat-total').text(totalImages);
                $('#beanst-stat-optimized').text(response.data.optimized);
                $('#beanst-stat-savings').text(response.data.savings);

                $statusText.text(beanst_ajax.i18n.found_images.replace('%s', totalImages));
                $progressBar.addClass('beanst-animating');
                processNextBatch();
            } else {
                alert(beanst_ajax.i18n.error_fetching);
                resetBulkUI();
            }
        });
    }

    function processNextBatch() {
        if (isPaused || !isRunning) return;

        if (imageIds.length === 0) {
            finishProcess();
            return;
        }

        const nextId = imageIds.shift();
        const force = $('#beanst-force-optimize').is(':checked');
        const delay = parseInt($('.beanst-stats-card').data('delay')) || 0;

        $.post(beanst_ajax.ajax_url, {
            action: 'beanst_process_batch',
            nonce: beanst_ajax.nonce,
            id: nextId,
            force: force
        }, function (response) {
            processedImages++;
            updateProgress();

            if (response.success) {
                const filename = response.data.filename || beanst_ajax.i18n.unknown_file;
                addLogEntry(filename, 'beanst-success');
            } else {
                addLogEntry('Error: ' + response.data, 'beanst-error');
            }

            if (response.data && response.data.memory) {
                $memText.text('MEM: ' + response.data.memory);
            }

            if (delay > 0) {
                setTimeout(processNextBatch, delay);
            } else {
                processNextBatch();
            }
        }).fail(function () {
            processedImages++;
            updateProgress();
            addLogEntry('Critical failure skipping ID: ' + nextId, 'beanst-error');
            processNextBatch();
        });
    }

    function addLogEntry(message, type) {
        const time = new Date().toLocaleTimeString([], { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });

        // Secure creation
        const $entry = $('<div>').addClass('beanst-log-entry ' + type);
        const $timeSpan = $('<span>').text(time + ' ');
        const $msgSpan = $('<span>').text(message);

        $entry.append($timeSpan, $msgSpan);
        $logArea.prepend($entry);

        if ($logArea.children().length > 10) {
            $logArea.children().last().remove();
        }
    }

    function updateProgress() {
        const percentage = Math.round((processedImages / totalImages) * 100);
        $progressBar.css('width', percentage + '%');
        $statusText.html('Optimizing... <strong>' + processedImages + '</strong> / ' + totalImages + ' (' + percentage + '%)');
    }

    function finishProcess() {
        isRunning = false;
        $progressBar.removeClass('beanst-animating').css('width', '100%');
        $statusText.text(beanst_ajax.i18n.optimization_complete);
        $pauseBtn.hide();
        $bulkBtn.show().text(beanst_ajax.i18n.optimize_library_again);

        // Final refresh of stats to show new numbers
        refreshDashboardStats();
    }

    function resetBulkUI() {
        isRunning = false;
        $pauseBtn.hide();
        $resumeBtn.hide();
        $bulkBtn.show();
    }

    // SEO Apply - Keeping IDs and data-attrs specific
    $(document).on('click', '.beanst-apply-seo', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const id = $btn.data('id');
        const $container = $btn.closest('.beanst-seo-suggestion');

        $btn.prop('disabled', true).text(beanst_ajax.i18n.applying);

        $.post(beanst_ajax.ajax_url, {
            action: 'beanst_apply_seo',
            nonce: beanst_ajax.nonce,
            id: id
        }, function (response) {
            if (response.success) {
                $container.html('<span style="color: #46b450; font-weight: bold;">✓ SEO Optimized</span>');
            } else {
                alert('Error: ' + response.data);
                $btn.prop('disabled', false).text(beanst_ajax.i18n.apply_seo);
            }
        });
    });

    // Visual Comparison Slider
    $(document).on('click', '.beanst-compare-link', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        const $link = $(this);
        const originalText = $link.text();

        $link.text(beanst_ajax.i18n.loading).css('pointer-events', 'none');

        $.post(beanst_ajax.ajax_url, {
            action: 'beanst_get_comparison_data',
            nonce: beanst_ajax.nonce,
            id: id
        }, function (response) {
            $link.text(originalText).css('pointer-events', 'auto');

            if (response.success) {
                openComparisonModal(response.data);
            } else {
                alert('Error: ' + response.data);
            }
        });
    });

    // --- CLEANUP Maintenance ---
    $(document).on('click', '#beanst-scan-orphans', function (e) {
        const $btn = $(this);
        $btn.addClass('updating').prop('disabled', true).text(beanst_ajax.i18n.scanning_uploads);
        $('#beanst-scan-results, #beanst-cleanup-status, #beanst-orphan-preview-container').hide();

        $.ajax({
            url: beanst_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'beanst_scan_orphans',
                nonce: beanst_ajax.nonce
            },
            success: function (response) {
                $btn.removeClass('updating').prop('disabled', false).text(beanst_ajax.i18n.scan_orphans);
                if (response.success) {
                    orphanFiles = response.data.files;
                    $('#beanst-orphan-count').text(response.data.count);
                    $('#beanst-orphan-size').text(response.data.size);
                    $('#beanst-scan-results').fadeIn();
                    renderOrphanGrid();
                } else {
                    $('#beanst-cleanup-status')
                        .css({ background: '#f8d7da', color: '#721c24', border: '1px solid #f5c6cb' })
                        .text(beanst_ajax.i18n.error_scanning + response.data)
                        .fadeIn();
                }
            },
            error: function () {
                $btn.removeClass('updating').prop('disabled', false).text(beanst_ajax.i18n.scan_orphans);
                $('#beanst-cleanup-status')
                    .css({ background: '#f8d7da', color: '#721c24', border: '1px solid #f5c6cb' })
                    .text(beanst_ajax.i18n.unknown_error)
                    .fadeIn();
            }
        });
    });

    $(document).on('click', '#beanst-toggle-orphan-preview', function (e) {
        e.preventDefault();
        $('#beanst-orphan-preview-container').slideToggle();
    });

    $(document).on('click', '#beanst-orphan-select-all', function (e) {
        e.preventDefault();
        $('.beanst-orphan-check').prop('checked', true);
    });

    $(document).on('click', '#beanst-orphan-select-none', function (e) {
        e.preventDefault();
        $('.beanst-orphan-check').prop('checked', false);
    });

    function renderOrphanGrid() {
        const $grid = $('#beanst-orphan-grid');
        $grid.empty();

        if (orphanFiles.length === 0) {
            $grid.append('<p style="grid-column: 1/-1; padding: 20px; text-align: center; color: #999;">' + beanst_ajax.i18n.no_orphans + '</p>');
            return;
        }

        orphanFiles.forEach((file, index) => {
            const isImage = /\.(webp|avif|jpg|jpeg|png)$/i.test(file.name);
            const thumb = isImage ? `<img src="${file.url}" style="width: 100%; height: 80px; object-fit: cover; border-radius: 6px;">` : `<div style="height: 80px; display: flex; align-items: center; justify-content: center; background: #eee; border-radius: 6px; font-weight: bold; font-size: 10px;">PDF</div>`;

            const itemHtml = `
                <div class="beanst-orphan-item" style="position: relative; background: #fff; padding: 10px; border-radius: 8px; border: 1px solid #ddd; text-align: center;">
                    <input type="checkbox" class="beanst-orphan-check" value="${file.path}" checked style="position: absolute; top: 5px; right: 5px; z-index: 5;">
                    ${thumb}
                    <div style="font-size: 9px; margin-top: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #666;" title="${file.name}">${file.name}</div>
                    <div style="font-size: 8px; color: #999;">${file.size}</div>
                </div>
            `;
            $grid.append(itemHtml);
        });
    }

    $(document).on('click', '#beanst-delete-orphans', function (e) {
        const selectedFiles = $('.beanst-orphan-check:checked').map(function () { return $(this).val(); }).get();

        if (selectedFiles.length === 0) {
            alert(beanst_ajax.i18n.select_one_delete);
            return;
        }

        if (!confirm(beanst_ajax.i18n.confirm_delete.replace('%s', selectedFiles.length))) {
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).text(beanst_ajax.i18n.deleting);
        $('#beanst-cleanup-status').hide();

        $.ajax({
            url: beanst_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'beanst_delete_orphans',
                nonce: beanst_ajax.nonce,
                files: selectedFiles
            },
            success: function (response) {
                if (response.success) {
                    $('#beanst-orphan-preview-container').hide();
                    $('#beanst-scan-results').hide();
                    $('#beanst-cleanup-status')
                        .css({ background: '#e7f9ed', color: '#1e7e34', border: '1px solid #c3e6cb' })
                        .text(beanst_ajax.i18n.success_cleaned.replace('%1$s', response.data.count).replace('%2$s', response.data.freed))
                        .fadeIn();

                    refreshDashboardStats();
                } else {
                    $('#beanst-cleanup-status')
                        .css({ background: '#f8d7da', color: '#721c24', border: '1px solid #f5c6cb' })
                        .text(beanst_ajax.i18n.error_deleting + response.data)
                        .fadeIn();
                }
                $btn.prop('disabled', false).text(beanst_ajax.i18n.clear_selected);
            },
            error: function () {
                $btn.prop('disabled', false).text(beanst_ajax.i18n.clear_selected);
                $('#beanst-cleanup-status')
                    .css({ background: '#f8d7da', color: '#721c24', border: '1px solid #f5c6cb' })
                    .text(beanst_ajax.i18n.unknown_error_delete || beanst_ajax.i18n.unknown_error)
                    .fadeIn();
            }
        });
    });

    function refreshDashboardStats() {
        $.post(beanst_ajax.ajax_url, {
            action: 'beanst_get_stats',
            nonce: beanst_ajax.nonce
        }, function (response) {
            if (response.success) {
                $('#beanst-stat-total').text(response.data.total);
                $('#beanst-stat-optimized').text(response.data.optimized);
                $('#beanst-stat-savings').text(response.data.savings);

                // SEO Audit Refresh
                if (response.data.seo_score !== undefined) {
                    $('#beanst-seo-score').text(response.data.seo_score);
                    $('#beanst-bad-names-count').text(response.data.bad_names);
                    $('#beanst-missing-alt-count').text(response.data.missing_alt);

                    // Dynamic Color for Score
                    const score = response.data.seo_score;
                    const $badge = $('#beanst-seo-score-badge');
                    if (score < 50) {
                        $badge.css({ background: '#fff0f0', color: '#d63384' });
                    } else if (score < 85) {
                        $badge.css({ background: '#fff9e6', color: '#856404' });
                    } else {
                        $badge.css({ background: '#e7f9ed', color: '#1e7e34' });
                    }
                }
            } else {
                console.error('Failed to refresh dashboard stats:', response.data);
            }
        });
    }

    function openComparisonModal(data) {
        // Safe DOM creation
        const $modal = $('<div class="beanst-modal-overlay"></div>');
        const $content = $('<div class="beanst-modal-content"></div>');
        const $header = $('<div class="beanst-modal-header"><h3>Visual Proof: Original vs Optimized</h3><button class="beanst-modal-close">&times;</button></div>');

        const $container = $('<div class="beanst-comparison-container"></div>');
        $container.append('<div class="beanst-comparison-label beanst-label-before">Original</div>');
        $container.append('<div class="beanst-comparison-label beanst-label-after">Optimized</div>');

        // Securely setting attributes
        const $imgBeforeEl = $('<img>', { src: data.original, alt: 'Original', class: 'beanst-image-before' });
        const $imgAfterEl = $('<img>', { src: data.optimized, alt: 'Optimized', class: 'beanst-image-after' });

        $container.append($imgBeforeEl, $imgAfterEl);
        $container.append('<div class="beanst-comparison-handle"></div>');
        $container.append('<input type="range" min="0" max="100" value="50" class="beanst-comparison-slider">');

        $content.append($header, $container);
        $modal.append($content);
        $modal.appendTo('body');

        // Use a small timeout to allow for DOM rendering before adding active class for animation
        setTimeout(() => $modal.addClass('beanst-active'), 10);

        const $slider = $modal.find('.beanst-comparison-slider');
        const $imgAfter = $modal.find('.beanst-image-after');
        const $handle = $modal.find('.beanst-comparison-handle');

        $slider.on('input', function () {
            const val = $(this).val();
            $imgAfter.css('clip-path', `inset(0 0 0 ${val}%)`);
            $handle.css('left', `${val}%`);
        });

        // Close logic
        const closeModal = () => {
            $modal.removeClass('beanst-active');
            setTimeout(() => $modal.remove(), 300);
        };

        $modal.find('.beanst-modal-close').on('click', closeModal);
        $modal.on('click', function (e) {
            if ($(e.target).hasClass('beanst-modal-overlay')) closeModal();
        });
    }

    // ========================================
    // V2 UX IMPROVEMENTS - NEW INTERACTIONS
    // ========================================

    // Auto-Convert Toggle Switch
    $('#beanst-auto-convert-toggle').on('change', function () {
        const isEnabled = $(this).is(':checked');
        const $label = $(this).siblings('.beanst-toggle-label');

        $label.text(isEnabled ? beanst_ajax.i18n.enabled : beanst_ajax.i18n.disabled);

        // Save setting via AJAX
        $.post(beanst_ajax.ajax_url, {
            action: 'beanst_update_option',
            nonce: beanst_ajax.nonce,
            option: 'auto_convert',
            value: isEnabled ? '1' : '0'
        }, function (response) {
            if (response.success) {
                // Show brief success feedback
                $label.css('color', '#46b450');
                setTimeout(() => $label.css('color', ''), 1000);
            }
        });
    });

    // Hero CTA Button - Scroll to bulk section
    $('#beanst-hero-optimize').on('click', function (e) {
        e.preventDefault();

        // Smooth scroll to bulk section
        $('html, body').animate({
            scrollTop: $('#beanst-bulk-section').offset().top - 50
        }, 600);

        // Auto-click bulk button after scroll
        setTimeout(() => {
            $('#beanst-bulk-convert').trigger('click');
        }, 700);
    });

    // Smooth scroll for internal links
    $('.beanst-scroll-to').on('click', function (e) {
        e.preventDefault();
        const target = $(this).attr('href');

        if (target && target.startsWith('#')) {
            $('html, body').animate({
                scrollTop: $(target).offset().top - 50
            }, 600);
        }
    });

    // Scan Orphans Button (new ID for V2)
    $('#beanst-scan-orphans-btn').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const $results = $('#beanst-cleanup-results');

        $btn.prop('disabled', true).text('Scanning...');
        $results.hide();

        $.post(beanst_ajax.ajax_url, {
            action: 'beanst_scan_orphans',
            nonce: beanst_ajax.nonce
        }, function (response) {
            $btn.prop('disabled', false).text('Scan for Unused Files');

            if (response.success) {
                orphanFiles = response.data.files;
                $('#beanst-orphan-count').text(response.data.count);
                $('#beanst-orphan-size').text(response.data.size);
                $results.fadeIn();
            } else {
                alert('Error scanning: ' + response.data);
            }
        });
    });

    // Update toggle label on page load
    if ($('#beanst-auto-convert-toggle').is(':checked')) {
        $('#beanst-auto-convert-toggle').siblings('.beanst-toggle-label').text(beanst_ajax.i18n.enabled);
    } else {
        $('#beanst-auto-convert-toggle').siblings('.beanst-toggle-label').text(beanst_ajax.i18n.disabled);
    }
});
