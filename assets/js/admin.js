/**
 * AI Author for Websites - Admin JavaScript
 *
 * @package AI_Author_For_Websites
 */

(function ($) {
    'use strict';

    /**
     * Initialize admin functionality
     */
    function init() {
        initPasswordToggle();
        initCharacterCount();
        initTestConnection();
        initGeneratePost();
        initProviderChange();
    }

    /**
     * Toggle password visibility
     */
    function initPasswordToggle() {
        $(document).on('click', '.aiauthor-toggle-password', function () {
            const $input = $(this).siblings('input');
            const $icon = $(this).find('.dashicons');

            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        });
    }

    /**
     * Character count for textareas
     */
    function initCharacterCount() {
        const $textarea = $('#kb_text');
        const $counter = $('#kb-char-count');

        if ($textarea.length && $counter.length) {
            $textarea.on('input', function () {
                $counter.text($(this).val().length);
            });
        }
    }

    /**
     * Test API connection
     */
    function initTestConnection() {
        $('#aiauthor-test-api').on('click', function () {
            const $button = $(this);
            const $result = $('#aiauthor-test-result');

            $button.prop('disabled', true);
            $result.removeClass('success error').text('Testing...');

            $.ajax({
                url: aiauthorAdmin.restUrl + 'test-connection',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': aiauthorAdmin.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $result.addClass('success').text('✓ ' + response.message + ' (' + response.provider + ')');
                    } else {
                        $result.addClass('error').text('✗ ' + response.message);
                    }
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || 'Connection failed';
                    $result.addClass('error').text('✗ ' + message);
                },
                complete: function () {
                    $button.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Generate post functionality
     */
    function initGeneratePost() {
        const $generateBtn = $('#generate-post-btn');
        const $regenerateBtn = $('#regenerate-btn');
        const $saveDraftBtn = $('#save-draft-btn');
        const $copyBtn = $('#copy-content-btn');

        // Generate post
        $generateBtn.add($regenerateBtn).on('click', function () {
            generatePost();
        });

        // Save as draft
        $saveDraftBtn.on('click', function () {
            saveDraft();
        });

        // Copy content
        $copyBtn.on('click', function () {
            copyContent();
        });
    }

    /**
     * Generate a blog post
     */
    function generatePost() {
        const topic = $('#post-topic').val().trim();
        const wordCount = $('#post-word-count').val();
        const tone = $('#post-tone').val();

        if (!topic) {
            alert('Please enter a topic for your blog post.');
            $('#post-topic').focus();
            return;
        }

        // Show loading state
        $('#generate-empty, #generate-result, #generate-error').hide();
        $('#generate-loading').show();
        $('#generate-post-btn').prop('disabled', true);

        $.ajax({
            url: aiauthorAdmin.restUrl + 'generate-post',
            method: 'POST',
            headers: {
                'X-WP-Nonce': aiauthorAdmin.nonce
            },
            data: {
                topic: topic,
                word_count: wordCount,
                tone: tone
            },
            success: function (response) {
                if (response.success) {
                    $('#result-title').val(response.title || topic);
                    $('#result-content').html(response.content);
                    $('#generate-loading').hide();
                    $('#generate-result').show();
                } else {
                    showError(response.message);
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Failed to generate post. Please try again.';
                showError(message);
            },
            complete: function () {
                $('#generate-post-btn').prop('disabled', false);
            }
        });
    }

    /**
     * Show error state
     */
    function showError(message) {
        $('#generate-loading, #generate-result, #generate-empty').hide();
        $('#error-message').text(message);
        $('#generate-error').show();
    }

    /**
     * Save generated post as draft
     */
    function saveDraft() {
        const title = $('#result-title').val().trim();
        const content = $('#result-content').html();

        if (!title || !content) {
            alert('No content to save.');
            return;
        }

        const $button = $('#save-draft-btn');
        $button.prop('disabled', true).text('Saving...');

        $.ajax({
            url: aiauthorAdmin.restUrl + 'save-draft',
            method: 'POST',
            headers: {
                'X-WP-Nonce': aiauthorAdmin.nonce
            },
            data: {
                title: title,
                content: content
            },
            success: function (response) {
                if (response.success) {
                    $button.html('<span class="dashicons dashicons-yes" style="margin-top: 3px;"></span> Saved!');
                    setTimeout(function () {
                        if (response.edit_url) {
                            window.open(response.edit_url, '_blank');
                        }
                        $button.html('<span class="dashicons dashicons-media-document" style="margin-top: 3px;"></span> Save as Draft');
                        $button.prop('disabled', false);
                    }, 1500);
                } else {
                    alert(response.message);
                    $button.html('<span class="dashicons dashicons-media-document" style="margin-top: 3px;"></span> Save as Draft');
                    $button.prop('disabled', false);
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Failed to save draft.';
                alert(message);
                $button.html('<span class="dashicons dashicons-media-document" style="margin-top: 3px;"></span> Save as Draft');
                $button.prop('disabled', false);
            }
        });
    }

    /**
     * Copy content to clipboard
     */
    function copyContent() {
        const content = $('#result-content').text();
        const $button = $('#copy-content-btn');

        navigator.clipboard.writeText(content).then(function () {
            $button.html('<span class="dashicons dashicons-yes" style="margin-top: 3px;"></span> Copied!');
            setTimeout(function () {
                $button.html('<span class="dashicons dashicons-clipboard" style="margin-top: 3px;"></span> Copy Content');
            }, 2000);
        }).catch(function () {
            // Fallback for older browsers
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(content).select();
            document.execCommand('copy');
            $temp.remove();

            $button.html('<span class="dashicons dashicons-yes" style="margin-top: 3px;"></span> Copied!');
            setTimeout(function () {
                $button.html('<span class="dashicons dashicons-clipboard" style="margin-top: 3px;"></span> Copy Content');
            }, 2000);
        });
    }

    /**
     * Handle provider change to update help text
     */
    function initProviderChange() {
        $('#provider').on('change', function () {
            const provider = $(this).val();
            const $helpText = $('#api-key-help');
            const $model = $('#model');

            // Update help text
            switch (provider) {
                case 'groq':
                    $helpText.html('Get your free API key from <a href="https://console.groq.com" target="_blank" rel="noopener">console.groq.com</a>');
                    break;
                case 'gemini':
                    $helpText.html('Get your API key from <a href="https://aistudio.google.com" target="_blank" rel="noopener">aistudio.google.com</a>');
                    break;
                case 'meta':
                    $helpText.html('Get your API key from <a href="https://llama.meta.com" target="_blank" rel="noopener">llama.meta.com</a>');
                    break;
            }

            // Show/hide relevant model options
            $model.find('optgroup').hide();
            $model.find('.groq-models, .gemini-models').show();

            // Select first visible option if current is hidden
            if (!$model.find('option:selected').is(':visible')) {
                $model.find('option:visible').first().prop('selected', true);
            }
        });
    }

    // Initialize when document is ready
    $(document).ready(init);

})(jQuery);

