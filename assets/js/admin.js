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
        initToastContainer();
        initPasswordToggle();
        initCharacterCount();
        initGeneratePost();
        initTestConnection();
        initAISuggestModal();
        initTaxonomyHandlers();
        initPublishDropdown();
        initConfirmModal();
    });

    /* ==========================================================================
       Toast Notification System
       ========================================================================== */
    
    function initToastContainer() {
        if ($('#aiauthor-toast-container').length === 0) {
            $('body').append('<div id="aiauthor-toast-container" class="aiauthor-toast-container"></div>');
        }
    }

    /**
     * Show toast notification
     * @param {string} type - 'success', 'error', 'warning', 'info'
     * @param {string} title - Toast title
     * @param {string} message - Toast message
     * @param {object} options - Additional options
     */
    function showToast(type, title, message, options = {}) {
        var defaults = {
            duration: 5000,
            actions: null,
            persistent: false
        };
        var settings = $.extend({}, defaults, options);

        var icons = {
            success: '✓',
            error: '✕',
            warning: '!',
            info: 'i'
        };

        var $toast = $('<div class="aiauthor-toast ' + type + '">' +
            '<div class="aiauthor-toast-icon">' + icons[type] + '</div>' +
            '<div class="aiauthor-toast-content">' +
                '<div class="aiauthor-toast-title">' + title + '</div>' +
                '<div class="aiauthor-toast-message">' + message + '</div>' +
            '</div>' +
            '<button class="aiauthor-toast-close">&times;</button>' +
        '</div>');

        if (settings.actions) {
            var $actions = $('<div class="aiauthor-toast-actions"></div>');
            settings.actions.forEach(function(action) {
                var $btn = $('<button class="button ' + (action.primary ? 'button-primary' : '') + '">' + action.text + '</button>');
                $btn.on('click', function() {
                    if (action.callback) action.callback();
                    removeToast($toast);
                });
                $actions.append($btn);
            });
            $toast.find('.aiauthor-toast-content').append($actions);
        }

        $toast.find('.aiauthor-toast-close').on('click', function() {
            removeToast($toast);
        });

        $('#aiauthor-toast-container').append($toast);

        if (!settings.persistent && settings.duration > 0) {
            setTimeout(function() {
                removeToast($toast);
            }, settings.duration);
        }

        return $toast;
    }

    function removeToast($toast) {
        $toast.addClass('hiding');
        setTimeout(function() {
            $toast.remove();
        }, 300);
    }

    /* ==========================================================================
       Confirmation Modal
       ========================================================================== */
    
    function initConfirmModal() {
        if ($('#aiauthor-confirm-modal').length === 0) {
            var modalHtml = '<div id="aiauthor-confirm-modal" class="aiauthor-modal aiauthor-confirm-modal" style="display: none;">' +
                '<div class="aiauthor-modal-overlay"></div>' +
                '<div class="aiauthor-modal-content">' +
                    '<div class="aiauthor-modal-body">' +
                        '<div class="aiauthor-confirm-icon info"><span class="dashicons dashicons-info"></span></div>' +
                        '<div class="aiauthor-confirm-title"></div>' +
                        '<div class="aiauthor-confirm-message"></div>' +
                    '</div>' +
                    '<div class="aiauthor-modal-footer">' +
                        '<button class="button" id="aiauthor-confirm-cancel">Cancel</button>' +
                        '<button class="button button-primary" id="aiauthor-confirm-ok">OK</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
            $('body').append(modalHtml);
        }

        $('#aiauthor-confirm-cancel, #aiauthor-confirm-modal .aiauthor-modal-overlay').on('click', function() {
            closeConfirmModal(false);
        });
    }

    var confirmCallback = null;

    /**
     * Show confirmation modal
     * @param {object} options - Modal options
     * @returns {Promise}
     */
    function showConfirm(options) {
        return new Promise(function(resolve) {
            var defaults = {
                type: 'info',
                title: 'Confirm',
                message: 'Are you sure?',
                confirmText: 'OK',
                cancelText: 'Cancel',
                showCancel: true
            };
            var settings = $.extend({}, defaults, options);

            var $modal = $('#aiauthor-confirm-modal');
            var iconClass = 'dashicons-info';
            
            switch(settings.type) {
                case 'success': iconClass = 'dashicons-yes-alt'; break;
                case 'warning': iconClass = 'dashicons-warning'; break;
                case 'danger': iconClass = 'dashicons-dismiss'; break;
            }

            $modal.find('.aiauthor-confirm-icon')
                .removeClass('success warning danger info')
                .addClass(settings.type)
                .find('.dashicons')
                .attr('class', 'dashicons ' + iconClass);
            
            $modal.find('.aiauthor-confirm-title').text(settings.title);
            $modal.find('.aiauthor-confirm-message').text(settings.message);
            $modal.find('#aiauthor-confirm-ok').text(settings.confirmText);
            $modal.find('#aiauthor-confirm-cancel').text(settings.cancelText).toggle(settings.showCancel);

            confirmCallback = resolve;

            $modal.find('#aiauthor-confirm-ok').off('click').on('click', function() {
                closeConfirmModal(true);
            });

            $modal.show();
        });
    }

    function closeConfirmModal(result) {
        $('#aiauthor-confirm-modal').hide();
        if (confirmCallback) {
            confirmCallback(result);
            confirmCallback = null;
        }
    }

    /* ==========================================================================
       Password Toggle
       ========================================================================== */
    
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

    /* ==========================================================================
       Character Count
       ========================================================================== */
    
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

    /* ==========================================================================
       Test API Connection
       ========================================================================== */
    
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
                        showToast('success', 'Connection Successful', 'Your API key is working correctly.');
                    } else {
                        $result.addClass('error').text('✗ ' + response.message);
                        showToast('error', 'Connection Failed', response.message);
                    }
                },
                error: function(xhr) {
                    var message = xhr.responseJSON?.message || 'Connection failed';
                    $result.addClass('error').text('✗ ' + message);
                    showToast('error', 'Connection Failed', message);
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> Test Connection');
                }
            });
        });
    }

    /* ==========================================================================
       Generate Post
       ========================================================================== */
    
    function initGeneratePost() {
        var $generateBtn = $('#generate-post-btn');
        var $regenerateBtn = $('#regenerate-btn');
        var $copyBtn = $('#copy-content-btn');

        $generateBtn.add($regenerateBtn).on('click', function() {
            generatePost();
        });

        $copyBtn.on('click', function() {
            copyContent();
        });
    }

    function generatePost() {
        var topic = $('#post-topic').val().trim();
        var wordCount = $('#post-word-count').val() || 1000;
        var tone = $('#post-tone').val();

        if (!topic) {
            showToast('warning', 'Topic Required', 'Please enter a topic for your blog post.');
            $('#post-topic').focus();
            return;
        }

        $('#generate-empty, #generate-result, #generate-error').hide();
        $('#generate-loading').show();
        $('#generate-post-btn').prop('disabled', true);

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
                    showToast('success', 'Content Generated', 'Your blog post has been generated successfully.');
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

    function showError(message) {
        $('#generate-loading').hide();
        $('#error-message').text(message);
        $('#generate-error').show();
        showToast('error', 'Generation Failed', message);
    }

    /**
     * Reset the generate form to initial state
     */
    function resetGenerateForm() {
        // Clear topic input
        $('#post-topic').val('');
        
        // Reset word count to default
        $('#post-word-count').val(1000);
        
        // Reset tone to first option
        $('#post-tone').val('professional');
        
        // Clear generated content
        $('#result-title').val('');
        $('#result-content').empty();
        
        // Clear categories
        $('#category-selector input[type="checkbox"]').prop('checked', false);
        $('.aiauthor-new-category').remove();
        $('#new-category-input').val('');
        
        // Clear tags
        postTags = [];
        updateTagsDisplay();
        $('#new-tag-input').val('');
        
        // Show empty state, hide result
        $('#generate-result').hide();
        $('#generate-empty').show();
        $('#generate-error').hide();
        $('#generate-loading').hide();
    }

    /* ==========================================================================
       Publish Dropdown
       ========================================================================== */
    
    function initPublishDropdown() {
        // Toggle dropdown
        $(document).on('click', '#publish-dropdown-btn', function(e) {
            e.stopPropagation();
            $('.aiauthor-publish-menu').toggleClass('show');
        });

        // Close dropdown when clicking outside
        $(document).on('click', function() {
            $('.aiauthor-publish-menu').removeClass('show');
        });

        // Save as Draft
        $(document).on('click', '#save-draft-btn, #menu-save-draft', function() {
            $('.aiauthor-publish-menu').removeClass('show');
            savePost('draft');
        });

        // Publish Now
        $(document).on('click', '#menu-publish-now', function() {
            $('.aiauthor-publish-menu').removeClass('show');
            showConfirm({
                type: 'info',
                title: 'Publish Post',
                message: 'Are you sure you want to publish this post immediately?',
                confirmText: 'Publish Now',
                cancelText: 'Cancel'
            }).then(function(confirmed) {
                if (confirmed) {
                    savePost('publish');
                }
            });
        });

        // Schedule for later
        $(document).on('click', '#menu-schedule', function() {
            $('.aiauthor-publish-menu').removeClass('show');
            showScheduleModal();
        });
    }

    /**
     * Save post with specified status
     * @param {string} status - 'draft', 'publish', or 'future'
     * @param {string} scheduleDate - Optional schedule date for future posts
     */
    function savePost(status, scheduleDate) {
        var title = $('#result-title').val().trim();
        var content = $('#result-content').html();
        var authorId = $('#post-author').val();

        if (!title) {
            showToast('warning', 'Title Required', 'Please enter a post title.');
            $('#result-title').focus();
            return;
        }

        // Gather categories
        var categories = [];
        $('#category-selector input[type="checkbox"]:checked').each(function() {
            var val = $(this).val();
            if (val.toString().indexOf('new-') === 0) {
                var catName = $(this).closest('.aiauthor-new-category').data('name');
                if (catName) {
                    categories.push(catName);
                }
            } else {
                categories.push(val);
            }
        });

        var $btn = $('#save-draft-btn');
        var originalText = $btn.find('.btn-text').text();
        $btn.prop('disabled', true).find('.btn-text').text('Saving...');

        var data = {
            title: title,
            content: content,
            author_id: authorId,
            categories: categories,
            tags: postTags,
            status: status
        };

        if (scheduleDate) {
            data.schedule_date = scheduleDate;
        }

        $.ajax({
            url: aiauthorAdmin.restUrl + 'save-draft',
            method: 'POST',
            headers: {
                'X-WP-Nonce': aiauthorAdmin.nonce
            },
            data: data,
            success: function(response) {
                if (response.success) {
                    var toastTitle, toastMessage;
                    var shouldReset = false;
                    
                    switch(status) {
                        case 'publish':
                            toastTitle = 'Post Published!';
                            toastMessage = 'Your post has been published successfully.';
                            shouldReset = true;
                            break;
                        case 'future':
                            toastTitle = 'Post Scheduled!';
                            toastMessage = 'Your post has been scheduled for publication.';
                            shouldReset = true;
                            break;
                        default:
                            toastTitle = 'Draft Saved!';
                            toastMessage = 'Your post has been saved as a draft.';
                    }

                    showToast('success', toastTitle, toastMessage, {
                        duration: 8000,
                        actions: [
                            {
                                text: 'Edit Post',
                                primary: true,
                                callback: function() {
                                    if (response.edit_url) {
                                        window.location.href = response.edit_url;
                                    }
                                }
                            },
                            {
                                text: 'View Post',
                                callback: function() {
                                    if (response.view_url) {
                                        window.open(response.view_url, '_blank');
                                    }
                                }
                            },
                            {
                                text: 'Create New',
                                callback: function() {
                                    resetGenerateForm();
                                }
                            }
                        ]
                    });

                    // Reset form after publish or schedule to prevent duplicates
                    if (shouldReset) {
                        resetGenerateForm();
                    }
                } else {
                    showToast('error', 'Save Failed', response.message);
                }
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Failed to save post.';
                showToast('error', 'Save Failed', message);
            },
            complete: function() {
                $btn.prop('disabled', false).find('.btn-text').text(originalText);
            }
        });
    }

    /**
     * Show schedule modal
     */
    function showScheduleModal() {
        // Get tomorrow's date as default
        var tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        var dateStr = tomorrow.toISOString().split('T')[0];
        var timeStr = '09:00';

        var modalHtml = '<div id="aiauthor-schedule-modal" class="aiauthor-modal" style="display: block;">' +
            '<div class="aiauthor-modal-overlay"></div>' +
            '<div class="aiauthor-modal-content" style="max-width: 400px;">' +
                '<div class="aiauthor-modal-header">' +
                    '<h3>Schedule Post</h3>' +
                    '<button type="button" class="aiauthor-modal-close">&times;</button>' +
                '</div>' +
                '<div class="aiauthor-modal-body">' +
                    '<p>Choose when you want this post to be published:</p>' +
                    '<div class="aiauthor-schedule-inputs">' +
                        '<input type="date" id="schedule-date" value="' + dateStr + '" min="' + dateStr + '">' +
                        '<input type="time" id="schedule-time" value="' + timeStr + '">' +
                    '</div>' +
                '</div>' +
                '<div class="aiauthor-modal-footer">' +
                    '<button class="button" id="schedule-cancel">Cancel</button>' +
                    '<button class="button button-primary" id="schedule-confirm">' +
                        '<span class="dashicons dashicons-calendar-alt" style="margin-top: 3px;"></span> Schedule' +
                    '</button>' +
                '</div>' +
            '</div>' +
        '</div>';

        $('body').append(modalHtml);

        $('#schedule-cancel, #aiauthor-schedule-modal .aiauthor-modal-overlay, #aiauthor-schedule-modal .aiauthor-modal-close').on('click', function() {
            $('#aiauthor-schedule-modal').remove();
        });

        $('#schedule-confirm').on('click', function() {
            var date = $('#schedule-date').val();
            var time = $('#schedule-time').val();
            
            if (!date || !time) {
                showToast('warning', 'Invalid Date', 'Please select both date and time.');
                return;
            }

            var scheduleDateTime = date + ' ' + time + ':00';
            $('#aiauthor-schedule-modal').remove();
            savePost('future', scheduleDateTime);
        });
    }

    /* ==========================================================================
       Copy Content
       ========================================================================== */
    
    function copyContent() {
        var content = $('#result-content').text();
        
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(content).then(function() {
                showToast('success', 'Copied!', 'Content has been copied to clipboard.');
            });
        } else {
            var textarea = document.createElement('textarea');
            textarea.value = content;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showToast('success', 'Copied!', 'Content has been copied to clipboard.');
        }
    }

    /* ==========================================================================
       AI Suggestion Modal
       ========================================================================== */
    
    function initAISuggestModal() {
        var $modal = $('#aiauthor-suggest-modal');
        var currentTarget = null;
        var currentType = null;

        $(document).on('click', '.aiauthor-ai-suggest-btn', function() {
            currentTarget = $(this).data('target');
            currentType = $(this).data('type');
            
            $('#aiauthor-suggest-prompt').val('');
            $('#aiauthor-modal-prompt-section').show();
            $('#aiauthor-modal-loading, #aiauthor-modal-result, #aiauthor-modal-error').hide();
            $('#aiauthor-modal-generate').show();
            $('#aiauthor-modal-regenerate, #aiauthor-modal-apply').hide();
            
            $modal.show();
        });

        $('.aiauthor-modal-close, .aiauthor-modal-overlay, #aiauthor-modal-cancel').on('click', function() {
            $modal.hide();
        });

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

        $('#aiauthor-modal-apply').on('click', function() {
            var suggestion = $('#aiauthor-modal-suggestion').val();
            if (currentTarget) {
                $('#' + currentTarget).val(suggestion);
            }
            $modal.hide();
            showToast('success', 'Applied', 'AI suggestion has been applied.');
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $modal.is(':visible')) {
                $modal.hide();
            }
        });
    }

    /* ==========================================================================
       Taxonomy Handlers
       ========================================================================== */
    
    function initTaxonomyHandlers() {
        $('#add-category-btn').on('click', function() {
            addNewCategory();
        });

        $('#new-category-input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                addNewCategory();
            }
        });

        $('#add-tag-btn').on('click', function() {
            addNewTag();
        });

        $('#new-tag-input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                addNewTag();
            }
        });

        $(document).on('click', '.remove-tag', function() {
            var tag = $(this).parent().data('tag');
            postTags = postTags.filter(function(t) { return t !== tag; });
            $(this).parent().remove();
        });

        $('#suggest-taxonomy-btn').on('click', function() {
            suggestTaxonomy();
        });
    }

    function addNewCategory() {
        var $input = $('#new-category-input');
        var name = $input.val().trim();

        if (!name) return;

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
            showToast('success', 'Category Added', '"' + name + '" has been added.');
        }

        $input.val('');
    }

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

    function suggestTaxonomy() {
        var title = $('#result-title').val();
        var content = $('#result-content').text();

        if (!title && !content) {
            showToast('warning', 'No Content', 'Generate content first to get AI suggestions.');
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
                            
                            if (!found) {
                                var $label = $('<label class="aiauthor-checkbox-label aiauthor-new-category" data-name="' + catName + '">' +
                                    '<input type="checkbox" name="post-categories[]" value="new-' + Date.now() + '" checked>' +
                                    '<span>' + $('<div>').text(catName).html() + '</span>' +
                                    '</label>');
                                $('#category-selector').append($label);
                            }
                        });
                    }

                    if (response.suggestions.tags) {
                        response.suggestions.tags.forEach(function(tag) {
                            if (postTags.indexOf(tag) === -1) {
                                postTags.push(tag);
                            }
                        });
                        updateTagsDisplay();
                    }

                    showToast('success', 'Suggestions Applied', 'Categories and tags have been suggested.');
                } else {
                    showToast('error', 'Suggestion Failed', 'Could not get suggestions. Please try again.');
                }
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Failed to get suggestions.';
                showToast('error', 'Suggestion Failed', message);
            },
            complete: function() {
                $btn.prop('disabled', false).find('span:last').text('AI Suggest');
            }
        });
    }

})(jQuery);
