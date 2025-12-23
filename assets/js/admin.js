/**
 * AI Author for Websites - Admin JavaScript
 * 
 * @package AI_Author_For_Websites
 */

(function($) {
    'use strict';

    // Store for tags
    let postTags = [];

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        initPasswordToggle();
        initCharacterCount();
        initGeneratePost();
        initTestConnection();
        initAISuggestModal();
        initTaxonomyHandlers();
    });

    /**
     * Toggle API Key Visibility
     */
    function initPasswordToggle() {
        $(document).on('click', '.aiauthor-toggle-password', function() {
            var $wrapper = $(this).closest('.aiauthor-api-key-wrapper');
            var $input = $wrapper.find('input');
            var $icon = $(this).find('.dashicons');

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
     * Character count for textarea
     */
    function initCharacterCount() {
        var $kbText = $('#kb_text');
        var $charCount = $('#kb-char-count');

        if ($kbText.length && $charCount.length) {
            $kbText.on('input', function() {
                $charCount.text($(this).val().length);
            });
            $charCount.text($kbText.val().length);
        }
    }

    /**
     * Test API Connection
     */
    function initTestConnection() {
        $('#aiauthor-test-api').on('click', function() {
            var $btn = $(this);
            var $result = $('#aiauthor-test-result');

            $btn.prop('disabled', true).text('Testing...');
            $result.removeClass('success error').text('');

            $.ajax({
                url: aiauthorAdmin.restUrl + 'test-connection',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': aiauthorAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.addClass('success').text('✓ ' + response.message + ' (' + response.provider + ')');
                    } else {
                        $result.addClass('error').text('✗ ' + response.message);
                    }
                },
                error: function(xhr) {
                    var message = xhr.responseJSON?.message || 'Connection failed';
                    $result.addClass('error').text('✗ ' + message);
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> Test Connection');
                }
            });
        });
    }

    /**
     * Generate Post Functionality
     */
    function initGeneratePost() {
        var $generateBtn = $('#generate-post-btn');
        var $regenerateBtn = $('#regenerate-btn');
        var $saveDraftBtn = $('#save-draft-btn');
        var $copyBtn = $('#copy-content-btn');

        // Generate button click
        $generateBtn.add($regenerateBtn).on('click', function() {
            generatePost();
        });

        // Save draft button click
        $saveDraftBtn.on('click', function() {
            saveDraft();
        });

        // Copy content button click
        $copyBtn.on('click', function() {
            copyContent();
        });
    }

    /**
     * Generate blog post
     */
    function generatePost() {
        var topic = $('#post-topic').val().trim();
        var wordCount = $('#post-word-count').val() || 1000;
        var tone = $('#post-tone').val();

        if (!topic) {
            alert('Please enter a topic for your blog post.');
            $('#post-topic').focus();
            return;
        }

        // Show loading state
        $('#generate-empty, #generate-result, #generate-error').hide();
        $('#generate-loading').show();
        $('#generate-post-btn').prop('disabled', true);

        // Clear previous results
        postTags = [];
        updateTagsDisplay();
        $('#category-selector input[type="checkbox"]').prop('checked', false);

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
            success: function(response) {
                if (response.success) {
                    $('#result-title').val(response.title);
                    $('#result-content').html(response.content);
                    $('#generate-loading').hide();
                    $('#generate-result').show();
                } else {
                    showError(response.message);
                }
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Failed to generate content. Please try again.';
                showError(message);
            },
            complete: function() {
                $('#generate-post-btn').prop('disabled', false);
            }
        });
    }

    /**
     * Show error state
     */
    function showError(message) {
        $('#generate-loading').hide();
        $('#error-message').text(message);
        $('#generate-error').show();
    }

    /**
     * Save as draft
     */
    function saveDraft() {
        var title = $('#result-title').val().trim();
        var content = $('#result-content').html();
        var authorId = $('#post-author').val();

        if (!title) {
            alert('Please enter a post title.');
            $('#result-title').focus();
            return;
        }

        // Gather categories
        var categories = [];
        $('#category-selector input[type="checkbox"]:checked').each(function() {
            var val = $(this).val();
            // For existing categories, use the ID
            // For new categories, use the data-name from the parent label
            if (val.toString().indexOf('new-') === 0) {
                // This is a new category, use the name instead
                var catName = $(this).closest('.aiauthor-new-category').data('name');
                if (catName) {
                    categories.push(catName);
                }
            } else {
                categories.push(val);
            }
        });

        var $btn = $('#save-draft-btn');
        $btn.prop('disabled', true).find('.btn-text').text('Saving...');

        $.ajax({
            url: aiauthorAdmin.restUrl + 'save-draft',
            method: 'POST',
            headers: {
                'X-WP-Nonce': aiauthorAdmin.nonce
            },
            data: {
                title: title,
                content: content,
                author_id: authorId,
                categories: categories,
                tags: postTags
            },
            success: function(response) {
                if (response.success) {
                    alert('Post saved as draft!');
                    if (response.edit_url) {
                        if (confirm('Would you like to edit the post now?')) {
                            window.location.href = response.edit_url;
                        }
                    }
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Failed to save draft.';
                alert('Error: ' + message);
            },
            complete: function() {
                $btn.prop('disabled', false).find('.btn-text').text('Save as Draft');
            }
        });
    }

    /**
     * Copy content to clipboard
     */
    function copyContent() {
        var content = $('#result-content').text();
        
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(content).then(function() {
                var $btn = $('#copy-content-btn');
                var originalText = $btn.find('.btn-text').text();
                $btn.find('.btn-text').text('Copied!');
                setTimeout(function() {
                    $btn.find('.btn-text').text(originalText);
                }, 2000);
            });
        } else {
            // Fallback
            var textarea = document.createElement('textarea');
            textarea.value = content;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            alert('Content copied to clipboard!');
        }
    }

    /**
     * AI Suggestion Modal
     */
    function initAISuggestModal() {
        var $modal = $('#aiauthor-suggest-modal');
        var currentTarget = null;
        var currentType = null;

        // Open modal
        $(document).on('click', '.aiauthor-ai-suggest-btn', function() {
            currentTarget = $(this).data('target');
            currentType = $(this).data('type');
            
            // Reset modal state
            $('#aiauthor-suggest-prompt').val('');
            $('#aiauthor-modal-prompt-section').show();
            $('#aiauthor-modal-loading, #aiauthor-modal-result, #aiauthor-modal-error').hide();
            $('#aiauthor-modal-generate').show();
            $('#aiauthor-modal-regenerate, #aiauthor-modal-apply').hide();
            
            $modal.show();
        });

        // Close modal
        $('.aiauthor-modal-close, .aiauthor-modal-overlay, #aiauthor-modal-cancel').on('click', function() {
            $modal.hide();
        });

        // Generate suggestion
        $('#aiauthor-modal-generate, #aiauthor-modal-regenerate').on('click', function() {
            var customPrompt = $('#aiauthor-suggest-prompt').val().trim();
            
            $('#aiauthor-modal-prompt-section').hide();
            $('#aiauthor-modal-loading').show();
            $('#aiauthor-modal-result, #aiauthor-modal-error').hide();
            $('#aiauthor-modal-generate').hide();

            $.ajax({
                url: aiauthorAdmin.restUrl + 'ai-suggest',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': aiauthorAdmin.nonce
                },
                data: {
                    type: currentType,
                    custom_prompt: customPrompt
                },
                success: function(response) {
                    if (response.success) {
                        $('#aiauthor-modal-suggestion').val(response.suggestion);
                        $('#aiauthor-modal-result').show();
                        $('#aiauthor-modal-regenerate, #aiauthor-modal-apply').show();
                    } else {
                        $('#aiauthor-modal-error p').text(response.message);
                        $('#aiauthor-modal-error').show();
                        $('#aiauthor-modal-prompt-section').show();
                        $('#aiauthor-modal-generate').show();
                    }
                },
                error: function(xhr) {
                    var message = xhr.responseJSON?.message || 'Failed to generate suggestion.';
                    $('#aiauthor-modal-error p').text(message);
                    $('#aiauthor-modal-error').show();
                    $('#aiauthor-modal-prompt-section').show();
                    $('#aiauthor-modal-generate').show();
                },
                complete: function() {
                    $('#aiauthor-modal-loading').hide();
                }
            });
        });

        // Apply suggestion
        $('#aiauthor-modal-apply').on('click', function() {
            var suggestion = $('#aiauthor-modal-suggestion').val();
            if (currentTarget) {
                $('#' + currentTarget).val(suggestion);
            }
            $modal.hide();
        });

        // Close on escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $modal.is(':visible')) {
                $modal.hide();
            }
        });
    }

    /**
     * Taxonomy (Categories & Tags) Handlers
     */
    function initTaxonomyHandlers() {
        // Add new category
        $('#add-category-btn').on('click', function() {
            addNewCategory();
        });

        $('#new-category-input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                addNewCategory();
            }
        });

        // Add new tag
        $('#add-tag-btn').on('click', function() {
            addNewTag();
        });

        $('#new-tag-input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                addNewTag();
            }
        });

        // Remove tag
        $(document).on('click', '.remove-tag', function() {
            var tag = $(this).parent().data('tag');
            postTags = postTags.filter(function(t) { return t !== tag; });
            $(this).parent().remove();
        });

        // AI Suggest Taxonomy
        $('#suggest-taxonomy-btn').on('click', function() {
            suggestTaxonomy();
        });
    }

    /**
     * Add new category
     */
    function addNewCategory() {
        var $input = $('#new-category-input');
        var name = $input.val().trim();

        if (!name) return;

        // Check if already exists
        var exists = false;
        $('#category-selector input[type="checkbox"]').each(function() {
            if ($(this).next('span').text().toLowerCase() === name.toLowerCase()) {
                $(this).prop('checked', true);
                exists = true;
                return false;
            }
        });

        if (!exists) {
            var $label = $('<label class="aiauthor-checkbox-label aiauthor-new-category" data-name="' + name + '">' +
                '<input type="checkbox" name="post-categories[]" value="new-' + Date.now() + '" checked>' +
                '<span>' + $('<div>').text(name).html() + '</span>' +
                '</label>');
            $('#category-selector').append($label);
        }

        $input.val('');
    }

    /**
     * Add new tag
     */
    function addNewTag() {
        var $input = $('#new-tag-input');
        var tag = $input.val().trim();

        if (!tag) return;
        if (postTags.indexOf(tag) !== -1) {
            $input.val('');
            return;
        }

        postTags.push(tag);
        updateTagsDisplay();
        $input.val('');
    }

    /**
     * Update tags display
     */
    function updateTagsDisplay() {
        var $container = $('#tag-container');
        $container.empty();

        postTags.forEach(function(tag) {
            var $tag = $('<span class="aiauthor-tag" data-tag="' + tag + '">' +
                '<span class="tag-name">' + $('<div>').text(tag).html() + '</span>' +
                '<span class="remove-tag">&times;</span>' +
                '</span>');
            $container.append($tag);
        });
    }

    /**
     * Suggest taxonomy using AI
     */
    function suggestTaxonomy() {
        var title = $('#result-title').val();
        var content = $('#result-content').text();

        if (!title && !content) {
            alert('Generate content first to get AI suggestions.');
            return;
        }

        var $btn = $('#suggest-taxonomy-btn');
        $btn.prop('disabled', true).find('span:last').text('Loading...');

        $.ajax({
            url: aiauthorAdmin.restUrl + 'suggest-taxonomy',
            method: 'POST',
            headers: {
                'X-WP-Nonce': aiauthorAdmin.nonce
            },
            data: {
                title: title,
                content: content
            },
            success: function(response) {
                if (response.success && response.suggestions) {
                    // Apply category suggestions
                    if (response.suggestions.categories) {
                        response.suggestions.categories.forEach(function(catName) {
                            var found = false;
                            $('#category-selector input[type="checkbox"]').each(function() {
                                var label = $(this).parent().text().trim();
                                if (label.toLowerCase() === catName.toLowerCase()) {
                                    $(this).prop('checked', true);
                                    found = true;
                                    return false;
                                }
                            });
                            
                            // Add new category if not found
                            if (!found) {
                                var $label = $('<label class="aiauthor-checkbox-label aiauthor-new-category" data-name="' + catName + '">' +
                                    '<input type="checkbox" name="post-categories[]" value="new-' + Date.now() + '" checked>' +
                                    '<span>' + $('<div>').text(catName).html() + '</span>' +
                                    '</label>');
                                $('#category-selector').append($label);
                            }
                        });
                    }

                    // Apply tag suggestions
                    if (response.suggestions.tags) {
                        response.suggestions.tags.forEach(function(tag) {
                            if (postTags.indexOf(tag) === -1) {
                                postTags.push(tag);
                            }
                        });
                        updateTagsDisplay();
                    }
                } else {
                    alert('Could not get suggestions. Please try again.');
                }
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Failed to get suggestions.';
                alert('Error: ' + message);
            },
            complete: function() {
                $btn.prop('disabled', false).find('span:last').text('AI Suggest');
            }
        });
    }

})(jQuery);
