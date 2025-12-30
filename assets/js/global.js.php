<?php
/**
 * JMVC JavaScript Helpers
 *
 * Provides client-side utilities for AJAX communication with JMVC controllers.
 *
 * @package JMVC
 * @version 2.0.0
 */

header("Content-type: text/javascript; charset=utf-8");

if (!defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
}
?>
/**
 * JMVC JavaScript Helpers
 *
 * @namespace JMVC
 * @version 2.0.0
 */
const JMVC = (function() {
    'use strict';

    /**
     * Configuration object with URLs and security tokens
     * @private
     */
    const config = {
        siteUrl: '<?= esc_js(site_url('/')) ?>',
        restUrl: '<?= esc_js(rest_url('jmvc/v1/')) ?>',
        nonce: '<?= esc_js(wp_create_nonce('jmvc_ajax_nonce')) ?>',
        environments: ['pub', 'admin', 'resource']
    };

    /**
     * Validates environment parameter
     * @private
     * @param {string} env - Environment name
     * @returns {boolean}
     */
    const isValidEnv = (env) => {
        return config.environments.includes(env);
    };

    return {
        /**
         * Exposed configuration (read-only access)
         * @type {Object}
         */
        get config() {
            return { ...config };
        },

        /**
         * Build a site URL with the given path
         * @param {string} [path=''] - Path to append to site URL
         * @returns {string} Full URL
         * @example
         * JMVC.siteUrl('about-us') // https://example.com/about-us
         */
        siteUrl(path = '') {
            return config.siteUrl + String(path).replace(/^\//, '');
        },

        /**
         * Build a controller URL for legacy AJAX endpoints
         * @param {string} env - Environment: 'pub', 'admin', or 'resource'
         * @param {string} controller - Controller name
         * @param {string} action - Action/method name
         * @param {...(string|number)} params - Additional URL parameters
         * @returns {string|null} Controller URL or null if invalid
         * @example
         * JMVC.controllerUrl('pub', 'Task', 'show', 42)
         * // https://example.com/controller/pub/Task/show/42
         */
        controllerUrl(env, controller, action, ...params) {
            if (!isValidEnv(env)) {
                console.error(`[JMVC] Invalid environment: ${env}. Must be one of: ${config.environments.join(', ')}`);
                return null;
            }

            if (!controller || !action) {
                console.error('[JMVC] Controller and action are required');
                return null;
            }

            const parts = [env, controller, action, ...params].map(String);
            return this.siteUrl('controller/' + parts.join('/'));
        },

        /**
         * Build a REST API URL
         * @param {string} env - Environment: 'pub', 'admin', or 'resource'
         * @param {string} controller - Controller name
         * @param {string} action - Action/method name
         * @param {...(string|number)} params - Additional URL parameters
         * @returns {string|null} REST API URL or null if invalid
         * @example
         * JMVC.restApiUrl('pub', 'Task', 'index')
         * // https://example.com/wp-json/jmvc/v1/pub/Task/index
         */
        restApiUrl(env, controller, action, ...params) {
            if (!isValidEnv(env)) {
                console.error(`[JMVC] Invalid environment: ${env}. Must be one of: ${config.environments.join(', ')}`);
                return null;
            }

            if (!controller || !action) {
                console.error('[JMVC] Controller and action are required');
                return null;
            }

            const parts = [env, controller, action, ...params].map(String);
            return config.restUrl + parts.join('/');
        },

        /**
         * Get the current CSRF nonce token
         * @returns {string} Nonce token
         * @example
         * const nonce = JMVC.getNonce();
         */
        getNonce() {
            return config.nonce;
        },

        /**
         * Refresh the nonce token (for long-lived pages)
         * @returns {Promise<string>} New nonce token
         * @example
         * const newNonce = await JMVC.refreshNonce();
         */
        async refreshNonce() {
            try {
                const response = await window.fetch(config.restUrl + 'nonce', {
                    method: 'GET',
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                config.nonce = data.nonce;
                return config.nonce;
            } catch (error) {
                console.error('[JMVC] Failed to refresh nonce:', error);
                throw error;
            }
        },

        /**
         * Fetch wrapper with automatic nonce handling
         * @param {string} url - URL to fetch
         * @param {Object} [options={}] - Fetch options
         * @returns {Promise<Response>} Fetch response
         * @example
         * const response = await JMVC.fetch(JMVC.restApiUrl('pub', 'Task', 'index'));
         */
        async fetch(url, options = {}) {
            const defaults = {
                credentials: 'same-origin',
                headers: {
                    'X-JMVC-Nonce': config.nonce,
                    'X-WP-Nonce': config.nonce
                }
            };

            // Merge headers properly
            const mergedHeaders = {
                ...defaults.headers,
                ...(options.headers || {})
            };

            const mergedOptions = {
                ...defaults,
                ...options,
                headers: mergedHeaders
            };

            return window.fetch(url, mergedOptions);
        },

        /**
         * Perform a GET request with automatic nonce
         * @param {string} url - URL to fetch
         * @returns {Promise<Response>} Fetch response
         * @example
         * const response = await JMVC.get(JMVC.restApiUrl('pub', 'Task', 'index'));
         * const data = await response.json();
         */
        async get(url) {
            return this.fetch(url, { method: 'GET' });
        },

        /**
         * Perform a POST request with automatic nonce and JSON body
         * @param {string} url - URL to post to
         * @param {Object} [data={}] - Data to send as JSON
         * @returns {Promise<Response>} Fetch response
         * @example
         * const response = await JMVC.post(JMVC.restApiUrl('pub', 'Task', 'create'), {
         *     title: 'New Task',
         *     list_id: 1
         * });
         */
        async post(url, data = {}) {
            return this.fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
        },

        /**
         * Perform a POST request with form data
         * @param {string} url - URL to post to
         * @param {Object|FormData} data - Data to send as form
         * @returns {Promise<Response>} Fetch response
         * @example
         * const response = await JMVC.postForm(JMVC.controllerUrl('pub', 'Task', 'create'), {
         *     title: 'New Task'
         * });
         */
        async postForm(url, data = {}) {
            let body;

            if (data instanceof FormData) {
                body = data;
                // Add nonce to FormData
                body.append('_jmvc_nonce', config.nonce);
            } else {
                const formData = new URLSearchParams();
                formData.append('_jmvc_nonce', config.nonce);
                Object.entries(data).forEach(([key, value]) => {
                    formData.append(key, value);
                });
                body = formData;
            }

            return this.fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body
            });
        },

        /**
         * Parse query string parameter from current URL
         * @param {string} name - Parameter name
         * @returns {string} Parameter value or empty string
         * @example
         * // URL: https://example.com/?page=2&sort=date
         * JMVC.qs('page') // '2'
         * JMVC.qs('missing') // ''
         */
        qs(name) {
            const params = new URLSearchParams(window.location.search);
            return params.get(name) || '';
        }
    };
})();
