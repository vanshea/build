/**
 * SG AI Studio - AI Studio Prompt Block
 * 
 * A Gutenberg block for generating content using AI Studio.
 */

(function(wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var Button = wp.components.Button;
    var ExternalLink = wp.components.ExternalLink;
    var createElement = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    
    // Fix for i18n
    var __ = wp.i18n.__ || function(text) { return text; };
    
    /**
     * Register the block
     */
    registerBlockType('sg-ai-studio/ai-studio-prompt', {
        title: 'AI Content Generator',
        description: 'Generate content using AI Studio',
        category: 'text',
        icon: 'editor-paste-text',
        keywords: ['ai', 'aistudio', 'content', 'generator'],
        apiVersion: 3,
        attributes: {
            prompt: {
                type: 'string',
                default: ''
            },
            isGenerating: {
                type: 'boolean',
                default: false
            }
        },
        
        /**
         * Edit function for the block
         */
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var prompt = attributes.prompt;
            var isGenerating = attributes.isGenerating;
            
            // Get API key status from global variable
            var hasApiKey = typeof sgAiStudioBlock !== 'undefined' && sgAiStudioBlock.hasApiKey;
            
            /**
             * Generate content from AI Studio
             */
            function generateContent() {
                if (!prompt) {
                    return;
                }
                
                // Set the generating state
                setAttributes({ isGenerating: true });
                
                // Get the current post ID
                var postId = wp.data.select('core/editor').getCurrentPostId();

                // Call AI Studio via WordPress REST API
                wp.apiFetch({
                    path: '/sg-ai-studio/generate-content',
                    method: 'POST',
                    data: {
                        prompt: prompt,
                        nonce: sgAiStudioBlock.nonce,
                        post_id: postId
                    }
                })
                .then(function(response) {
                    if (response.success && (response.data || response.images)) {
                        // Insert content into the editor
                        insertContentIntoEditor(response.data, response.images);
                    } else {
                        // Store error in local variable for React to use in rendering
                        props.error = response.message || 'Error generating content.';
                        // Force re-render
                        setAttributes({ isGenerating: false });
                    }
                })
                .catch(function(error) {
                    console.error('Error generating content:', error);
                    // Store error in local variable for React to use in rendering
                    props.error = 'Error connecting to the AI service.';
                    // Force re-render
                    setAttributes({ isGenerating: false });
                })
                .finally(function() {
                    setAttributes({ isGenerating: false });
                });
            }
            
            /**
             * Insert content into the editor
             */
            function insertContentIntoEditor(content, images) {
                if (!content && !images) {
                    return;
                }

                var blocks = [];

                // Process text content if available
                if (content) {
                    // Handle potential escaped HTML entities
                    content = content.replace(/&lt;/g, '<').replace(/&gt;/g, '>');

                    // Check if content has an H1 heading to use as post title
                    // Support both <h1> tags and potential escaped versions
                    var titleRegex = /<h1[^>]*>(.*?)<\/h1>/i;
                    var titleMatch = content.match(titleRegex);

                    // Extract the title from H1 if it exists
                    if (titleMatch && titleMatch[1]) {
                        var title = titleMatch[1].trim();
                        // Strip any HTML tags from the title
                        var tempDiv = document.createElement('div');
                        tempDiv.innerHTML = title;
                        title = tempDiv.textContent || tempDiv.innerText || title;

                        // Set as post title
                        wp.data.dispatch('core/editor').editPost({ title: title });

                        // Remove the H1 from content before processing
                        content = content.replace(titleRegex, '').trim();
                    }

                    // Check if content contains Gutenberg block comments
                    var hasBlockComments = content.indexOf('<!-- wp:') !== -1;

                    if (hasBlockComments) {
                        // Parse Gutenberg block markup
                        blocks = wp.blocks.parse(content);
                    } else {
                        // Use rawHandler to convert plain HTML to blocks
                        blocks = wp.blocks.rawHandler({
                            HTML: content,
                            mode: 'BLOCKS'
                        });
                    }

                    // If parsing failed, fallback to paragraph
                    if (!blocks || blocks.length === 0) {
                        blocks = [wp.blocks.createBlock('core/paragraph', {
                            content: content
                        })];
                    }
                }

                // Add image blocks if images are provided separately (not already in content)
                // Check if images are already embedded in the parsed blocks
                if (images && images.length > 0) {
                    // Recursive function to check for image blocks at any nesting level
                    function hasImageBlocksRecursive(blockList) {
                        for (var j = 0; j < blockList.length; j++) {
                            var block = blockList[j];
                            // Check for image, cover, gallery, or media-text blocks
                            if (block.name === 'core/image' ||
                                block.name === 'core/cover' ||
                                block.name === 'core/gallery' ||
                                block.name === 'core/media-text') {
                                return true;
                            }
                            // Check inner blocks recursively
                            if (block.innerBlocks && block.innerBlocks.length > 0) {
                                if (hasImageBlocksRecursive(block.innerBlocks)) {
                                    return true;
                                }
                            }
                        }
                        return false;
                    }

                    var hasImageBlocks = hasImageBlocksRecursive(blocks);

                    // Only add image blocks if they weren't already included in the parsed content
                    if (!hasImageBlocks) {
                        for (var i = 0; i < images.length; i++) {
                            var imageBlock = wp.blocks.createBlock('core/image', {
                                id: images[i].id,
                                url: images[i].url,
                                sizeSlug: 'large'
                            });
                            blocks.push(imageBlock);
                        }
                    }
                }

                // Replace temporary/placeholder image URLs with actual WordPress URLs if images array is provided
                if (images && images.length > 0) {
                    var imageIndex = 0;

                    // Helper function to check if URL needs replacement
                    function needsUrlReplacement(url) {
                        if (!url) return false;
                        // Check for common placeholder patterns first
                        if (url.indexOf('/internal/res_') !== -1) return true;
                        if (url.indexOf('localhost/internal') !== -1) return true;
                        if (url.indexOf('placeholder') !== -1) return true;
                        if (url.indexOf('temp') !== -1) return true;
                        // Check if it's an external URL (not from WordPress uploads)
                        if (url.indexOf('http') === 0 && url.indexOf(window.location.hostname) === -1) return true;
                        return false;
                    }

                    // Recursive function to update image URLs in blocks
                    function replaceImageUrls(blockList) {
                        for (var k = 0; k < blockList.length; k++) {
                            var block = blockList[k];

                            // Update media-text blocks - remove nested image blocks and use mediaUrl instead
                            if (block.name === 'core/media-text' && block.attributes) {
                                var needsMediaUpdate = false;
                                var contentBlocks = []; // To store non-image inner blocks

                                // Check if mediaUrl needs replacement
                                if (block.attributes.mediaUrl && needsUrlReplacement(block.attributes.mediaUrl)) {
                                    needsMediaUpdate = true;
                                }
                                // Check for mediaLink attribute (non-standard but sometimes used)
                                else if (block.attributes.mediaLink && needsUrlReplacement(block.attributes.mediaLink)) {
                                    needsMediaUpdate = true;
                                }
                                // Check for non-standard image_url attribute
                                else if (block.attributes.image_url && needsUrlReplacement(block.attributes.image_url)) {
                                    needsMediaUpdate = true;
                                }
                                // Check if mediaType is image but no mediaUrl is set yet
                                else if (block.attributes.mediaType === 'image' && !block.attributes.mediaUrl) {
                                    needsMediaUpdate = true;
                                }

                                // Look for nested image block to extract URL and separate content blocks
                                if (block.innerBlocks && block.innerBlocks.length > 0) {
                                    for (var m = 0; m < block.innerBlocks.length; m++) {
                                        var innerBlock = block.innerBlocks[m];
                                        // If it's an image block, extract its URL and don't keep it
                                        if (innerBlock.name === 'core/image' && innerBlock.attributes && innerBlock.attributes.url) {
                                            if (needsUrlReplacement(innerBlock.attributes.url)) {
                                                needsMediaUpdate = true;
                                            }
                                            // Don't add image block to contentBlocks - we'll use mediaUrl instead
                                        } else {
                                            // Keep other inner blocks (paragraphs, headings, etc.)
                                            contentBlocks.push(innerBlock);
                                        }
                                    }
                                }

                                // Create fresh block instead of modifying existing one
                                if (needsMediaUpdate && imageIndex < images.length) {

                                    // Create new attributes object with correct values
                                    var newAttributes = {};
                                    for (var attrKey in block.attributes) {
                                        if (block.attributes.hasOwnProperty(attrKey)) {
                                            newAttributes[attrKey] = block.attributes[attrKey];
                                        }
                                    }

                                    // Set correct media attributes
                                    newAttributes.mediaUrl = images[imageIndex].url;
                                    newAttributes.mediaId = images[imageIndex].id;
                                    newAttributes.mediaType = 'image';

                                    // Clean up non-standard attributes
                                    delete newAttributes.image_url;
                                    delete newAttributes.image_id;
                                    delete newAttributes.mediaLink;

                                    // Create fresh block with cleaned content blocks
                                    var newBlock = wp.blocks.createBlock('core/media-text', newAttributes, contentBlocks);

                                    // Replace in array
                                    blockList[k] = newBlock;

                                    imageIndex++;
                                } else if (needsMediaUpdate) {
                                } else {
                                    // No media update needed, but still process inner blocks
                                    if (block.innerBlocks && block.innerBlocks.length > 0) {
                                        replaceImageUrls(block.innerBlocks);
                                    }
                                }
                            }
                            // Update standalone image blocks
                            else if (block.name === 'core/image' && block.attributes && block.attributes.url) {
                                if (needsUrlReplacement(block.attributes.url)) {
                                    if (imageIndex < images.length) {
                                        block.attributes.url = images[imageIndex].url;
                                        block.attributes.id = images[imageIndex].id;
                                        imageIndex++;
                                    }
                                }
                            }
                            // Update cover blocks
                            else if (block.name === 'core/cover' && block.attributes && block.attributes.url) {
                                if (needsUrlReplacement(block.attributes.url)) {
                                    if (imageIndex < images.length) {
                                        block.attributes.url = images[imageIndex].url;
                                        block.attributes.id = images[imageIndex].id;
                                        imageIndex++;
                                    }
                                }

                                // Process inner blocks for cover
                                if (block.innerBlocks && block.innerBlocks.length > 0) {
                                    replaceImageUrls(block.innerBlocks);
                                }
                            }
                            // Recursively update inner blocks for other block types
                            else if (block.innerBlocks && block.innerBlocks.length > 0) {
                                replaceImageUrls(block.innerBlocks);
                            }
                        }
                    }

                    replaceImageUrls(blocks);
                }

                // Get the current block's position info
                var selectedBlockId = wp.data.select('core/block-editor').getSelectedBlockClientId();

                if (selectedBlockId) {
                    // Get the index and parent of the current block
                    var blockIndex = wp.data.select('core/block-editor').getBlockIndex(selectedBlockId);
                    var rootClientId = wp.data.select('core/block-editor').getBlockRootClientId(selectedBlockId);

                    // Insert the new blocks at the same position
                    wp.data.dispatch('core/block-editor').insertBlocks(blocks, blockIndex, rootClientId);

                    // Remove the AI Studio Prompt block (which is now at blockIndex + blocks.length)
                    wp.data.dispatch('core/block-editor').removeBlock(selectedBlockId);
                } else {
                    // Fallback: insert at the end
                    wp.data.dispatch('core/block-editor').insertBlocks(blocks);
                }

                wp.data.dispatch('core/editor').savePost();
            }
            
            /**
             * Render the block editor UI
             */
            return createElement(
                'div',
                {
                    className: 'wp-block-sg-ai-studio-prompt',
                    style: {
                        padding: '16px',
                        position: 'relative',
                        background: '#fff',
                        border: '1px solid #e0e0e0',
                        borderRadius: '4px'
                    }
                },
                [
                    // Loading spinner overlay when generating
                    isGenerating && createElement('div', {
                        style: {
                            position: 'absolute',
                            top: '0',
                            left: '0',
                            right: '0',
                            bottom: '0',
                            background: 'rgba(255, 255, 255, 0.8)',
                            backdropFilter: 'blur(2px)',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            zIndex: 1000,
                            pointerEvents: 'all',
                            borderRadius: '4px'
                        }
                    },
                        createElement('div', {
                            style: {
                                width: '40px',
                                height: '40px',
                                border: '4px solid #e0e0e0',
                                borderTop: '4px solid #6366f1',
                                borderRadius: '50%',
                                animation: 'spin 1s linear infinite'
                            }
                        })
                    ),

                    // API Key notice
                    !hasApiKey && createElement(
                        'div',
                        {
                            className: 'sg-ai-studio-settings-notice',
                            style: {
                                padding: '12px',
                                background: '#fff3cd',
                                border: '1px solid #ffc107',
                                borderRadius: '4px',
                                marginBottom: '16px',
                                display: 'flex',
                                alignItems: 'center',
                                gap: '8px'
                            }
                        },
                        [
                            createElement('span', { style: { flex: 1 } }, 'Please configure your AI Studio API key in the plugin settings.'),
                            createElement(ExternalLink, { href: sgAiStudioBlock.settingsUrl }, 'Settings')
                        ]
                    ),
                    
                    // Prompt container with styled text section
                    createElement(
                        'div',
                        { className: 'sg-ai-studio-text-section', style: { marginBottom: '16px' } },
                        [
                            createElement('label', {
                                className: 'components-base-control__label',
                                style: { display: 'block', marginBottom: '8px', fontWeight: '600' }
                            }, 'Generate Content with AI'),
                            createElement('textarea', {
                                className: 'sg-ai-studio-text-input',
                                value: prompt,
                                onChange: function(e) {
                                    setAttributes({ prompt: e.target.value });
                                    // Clear any previous error when prompt changes
                                    props.error = null;
                                },
                                placeholder: 'Describe the content you want to generate...',
                                disabled: isGenerating || !hasApiKey,
                                rows: 6,
                                style: {
                                    width: '100%',
                                    padding: '12px',
                                    background: isGenerating ? '#f9f9f9' : '#f0f7ff',
                                    border: isGenerating ? '2px solid #6164ff' : '1px solid var(--color-primary-main, #4343f0)',
                                    borderRadius: '4px',
                                    fontSize: '14px',
                                    lineHeight: '1.6',
                                    fontFamily: 'inherit',
                                    resize: 'vertical',
                                    minHeight: '120px',
                                    boxSizing: 'border-box',
                                    outline: 'none'
                                }
                            })
                        ]
                    ),
                    
                    // Error message (if any)
                    props.error && createElement(
                        'div',
                        {
                            className: 'sg-ai-studio-error',
                            style: {
                                padding: '12px',
                                background: '#f8d7da',
                                border: '1px solid #f5c2c7',
                                borderRadius: '4px',
                                color: '#842029',
                                marginBottom: '16px',
                                fontSize: '14px'
                            }
                        },
                        props.error
                    ),
                    
                    // Button container
                    createElement(
                        'div',
                        { className: 'sg-ai-studio-button-container', style: { marginBottom: '16px' } },
                        [
                            createElement(
                                Button,
                                {
                                    className: 'sg-button-base sg-button-base--color-primary sg-button-base--type-filled sg-button sg-button--medium',
                                    isPrimary: true,
                                    onClick: generateContent,
                                    disabled: isGenerating || !prompt || !hasApiKey,
                                    style: {
                                        width: '100%',
                                        justifyContent: 'center',
                                        background: 'var(--color-primary-main, #4343f0)',
                                        color: 'var(--color-primary-contrast, #ffffff)',
                                        border: 'none',
                                        borderRadius: '50px',
                                        fontFamily: 'Poppins, -apple-system, BlinkMacSystemFont, sans-serif',
                                        fontSize: 'var(--typography-size-medium, 1.4rem)',
                                        fontWeight: 'var(--typography-weight-medium, 500)',
                                        padding: 'var(--space-x-small, 8px) var(--space-small, 12px)',
                                        height: 'auto',
                                        minHeight: '40px'
                                    }
                                },
                                isGenerating ? 'Generating...' : 'Generate'
                            ),
                            createElement(
                                'p',
                                {
                                    className: 'sg-ai-studio-info',
                                    style: {
                                        fontSize: '12px',
                                        color: '#757575',
                                        textAlign: 'center',
                                        margin: '8px 0 0 0'
                                    }
                                },
                                'Generated content will be added to your post.'
                            )
                        ]
                    )
                ].filter(Boolean) // Filter out any falsy elements (like when !hasApiKey is false)
            );
        },
        
        /**
         * Save function for the block
         */
        save: function() {
            // This block is dynamic and doesn't save content
            return null;
        }
    });
})(window.wp);