/**
 * JMVC Admin JavaScript
 */

(function($) {
    'use strict';

    const JMVC = {
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.testRewriteRules();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Initialize JMVC button
            $(document).on('click', '.jmvc-initialize-btn', this.handleInitialize.bind(this));

            // Install dependencies button
            $(document).on('click', '.jmvc-install-deps-btn', this.handleInstallDeps.bind(this));

            // Test rewrite rules button
            $(document).on('click', '.jmvc-test-rewrite-btn', this.testRewriteRules.bind(this));

            // Save config form
            $(document).on('submit', '#jmvc-config-form', this.handleSaveConfig.bind(this));
        },

        /**
         * Show notice
         */
        showNotice: function(message, type) {
            const $notices = $('#jmvc-notices');
            const $notice = $('<div class="jmvc-notice ' + type + '">' + message + '</div>');

            $notices.append($notice);

            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Handle initialize button click
         */
        handleInitialize: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);

            if ($btn.hasClass('loading')) {
                return;
            }

            $btn.addClass('loading').text(jmvcAdmin.strings.initializing);

            $.ajax({
                url: jmvcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'jmvc_initialize',
                    nonce: jmvcAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        JMVC.showNotice(response.data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        JMVC.showNotice(response.data.message, 'error');
                        $btn.removeClass('loading').text('Initialize JMVC');
                    }
                },
                error: function() {
                    JMVC.showNotice(jmvcAdmin.strings.error, 'error');
                    $btn.removeClass('loading').text('Initialize JMVC');
                }
            });
        },

        /**
         * Handle install dependencies button click
         */
        handleInstallDeps: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);

            if ($btn.hasClass('loading')) {
                return;
            }

            $btn.addClass('loading').text(jmvcAdmin.strings.installing);

            $.ajax({
                url: jmvcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'jmvc_install_deps',
                    nonce: jmvcAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        JMVC.showNotice(response.data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        JMVC.showNotice(response.data.message, 'error');
                        $btn.removeClass('loading').text('Install Dependencies');

                        // Show manual instructions if provided
                        if (response.data.instructions) {
                            $('#jmvc-composer-instructions')
                                .show()
                                .find('.jmvc-instructions-content')
                                .html('<pre><code>' + response.data.instructions + '</code></pre>');
                        }
                    }
                },
                error: function() {
                    JMVC.showNotice(jmvcAdmin.strings.error, 'error');
                    $btn.removeClass('loading').text('Install Dependencies');
                }
            });
        },

        /**
         * Test rewrite rules
         */
        testRewriteRules: function() {
            const $card = $('#jmvc-rewrite-card');
            const $status = $card.find('.jmvc-rewrite-status');
            const $btn = $card.find('.jmvc-test-rewrite-btn');
            const $instructions = $('#jmvc-rewrite-instructions');

            $status.html(
                '<span class="dashicons dashicons-update jmvc-spinner"></span>' +
                '<span>' + jmvcAdmin.strings.testing + '</span>'
            );
            $btn.hide();
            $instructions.hide();

            $.ajax({
                url: jmvcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'jmvc_test_rewrite',
                    nonce: jmvcAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.status) {
                        // Rewrite rules working
                        $card.removeClass('status-warning status-error').addClass('status-ok');
                        $status.html(
                            '<span class="dashicons dashicons-yes-alt"></span>' +
                            '<span>Working</span>'
                        );
                    } else {
                        // Rewrite rules not working
                        $card.removeClass('status-ok').addClass('status-warning');
                        $status.html(
                            '<span class="dashicons dashicons-warning"></span>' +
                            '<span>Not Configured</span>'
                        );
                        $btn.show();

                        // Show instructions
                        if (response.data.instructions) {
                            $instructions
                                .show()
                                .find('.jmvc-instructions-content')
                                .html(response.data.instructions);
                        }
                    }
                },
                error: function() {
                    $card.removeClass('status-ok').addClass('status-error');
                    $status.html(
                        '<span class="dashicons dashicons-dismiss"></span>' +
                        '<span>Test Failed</span>'
                    );
                    $btn.show();
                }
            });
        },

        /**
         * Handle save config form submission
         */
        handleSaveConfig: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $btn = $form.find('.jmvc-save-config-btn');
            const $status = $form.find('.jmvc-save-status');

            if ($btn.hasClass('loading')) {
                return;
            }

            $btn.addClass('loading');
            $status.removeClass('success error').text(jmvcAdmin.strings.saving);

            $.ajax({
                url: jmvcAdmin.ajaxUrl,
                type: 'POST',
                data: $form.serialize() + '&action=jmvc_save_config&nonce=' + jmvcAdmin.nonce,
                success: function(response) {
                    $btn.removeClass('loading');

                    if (response.success) {
                        $status.addClass('success').text(jmvcAdmin.strings.success);
                    } else {
                        $status.addClass('error').text(response.data.message);
                    }

                    setTimeout(function() {
                        $status.removeClass('success error').text('');
                    }, 3000);
                },
                error: function() {
                    $btn.removeClass('loading');
                    $status.addClass('error').text(jmvcAdmin.strings.error);
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        JMVC.init();
    });

})(jQuery);
