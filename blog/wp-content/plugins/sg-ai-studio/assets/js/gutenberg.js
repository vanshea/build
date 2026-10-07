/**
 * Gutenberg Block Toolbar Button & Sidebar
 * Adds "Ask SG AI Studio" button to blocks with text content and opens a sidebar panel
 *
 * @package SG_AI_Studio
 */

(function (wp) {
	'use strict';

	// Check if wp and required modules are available
	// Note: wp.editor is preferred (WP 6.6+), wp.editPost is fallback for older versions
	if (!wp || !wp.compose || !wp.element || !wp.blockEditor || !wp.components || !wp.hooks || !wp.plugins || (!wp.editor && !wp.editPost) || !wp.data) {
		console.error('SG AI Studio: Required WordPress modules not available');
		return;
	}

	var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
	var Fragment = wp.element.Fragment;
	var createElement = wp.element.createElement;
	var Component = wp.element.Component;
	var BlockControls = wp.blockEditor.BlockControls;
	var ToolbarGroup = wp.components.ToolbarGroup;
	var ToolbarButton = wp.components.ToolbarButton;
	var SelectControl = wp.components.SelectControl;
	var TextareaControl = wp.components.TextareaControl;
	var Button = wp.components.Button;
	var Notice = wp.components.Notice;
	var addFilter = wp.hooks.addFilter;
	var registerPlugin = wp.plugins.registerPlugin;
	// Use wp.editor instead of wp.editPost (deprecated since WP 6.6)
	var PluginSidebar = wp.editor ? wp.editor.PluginSidebar : wp.editPost.PluginSidebar;
	var PluginSidebarMoreMenuItem = wp.editor ? wp.editor.PluginSidebarMoreMenuItem : wp.editPost.PluginSidebarMoreMenuItem;
	var dispatch = wp.data.dispatch;
	var select = wp.data.select;
	var __ = wp.i18n.__ || function(text) { return text; };

	/**
	 * Get the current post ID from the editor
	 *
	 * @return {number} The current post ID or 0 if not available
	 */
	function getCurrentPostId() {
		try {
			var editor = select('core/editor');
			if (editor && editor.getCurrentPostId) {
				var postId = editor.getCurrentPostId();
				return postId ? parseInt(postId, 10) : 0;
			}
		} catch (error) {
			console.error('Error getting post ID:', error);
		}
		return 0;
	}

	/**
	 * Sanitize text content to prevent XSS attacks
	 * Converts plain text to safe HTML with only <br> tags for newlines
	 *
	 * @param {string} text - Plain text to sanitize
	 * @return {string} Sanitized HTML content
	 */
	function sanitizeTextToHTML(text) {
		if (!text) {
			return '';
		}
		// Create a temporary DOM element to escape HTML entities
		var temp = document.createElement('div');
		temp.textContent = text;
		var sanitized = temp.innerHTML;
		// Replace newlines with <br> tags after sanitization
		return sanitized.replace(/\n/g, '<br>');
	}

	// Custom SVG icon for SG AI Studio
	var sgAiStudioIcon = createElement('svg', {
		xmlns: 'http://www.w3.org/2000/svg',
		viewBox: '0 0 36 36',
	},
		createElement('path', {
			fill: 'currentColor',
			d: 'M33.459,34.389c-.332,0-.655-.145-.876-.41l-6.187-7.408H6.946c-.631,0-1.141-.511-1.141-1.141v-8.406c0-.63.511-1.141,1.141-1.141s1.141.511,1.141,1.141v7.265h18.844c.338,0,.659.15.876.41l4.511,5.402V8.188h-12.109c-.631,0-1.141-.511-1.141-1.141s.511-1.141,1.141-1.141h13.25c.631,0,1.141.511,1.141,1.141v26.2c0,.481-.301.909-.753,1.073-.126.046-.258.069-.389.069Z'
		}),
		createElement('path', {
			fill: 'currentColor',
			d: 'M20.397,11.449c-1.523-.376-3.08-.873-4.157-1.951-1.077-1.077-1.574-2.634-1.95-4.156-.061-.247-.283-.421-.538-.421s-.476.174-.538.421c-.376,1.522-.872,3.079-1.95,4.156-1.077,1.077-2.634,1.574-4.156,1.95-.247.061-.421.283-.421.538s.174.476.421.538c1.523.377,3.08.874,4.156,1.95s1.573,2.634,1.951,4.157c.061.247.283.42.538.42h0c.255,0,.476-.174.537-.421.375-1.521.871-3.077,1.95-4.156s2.635-1.575,4.156-1.95c.247-.061.421-.283.421-.538s-.174-.476-.421-.538Z'
		}),
		createElement('path', {
			fill: 'currentColor',
			d: 'M8.691,4.653c-.762-.188-1.54-.437-2.079-.976-.538-.538-.787-1.317-.975-2.079-.031-.124-.141-.21-.269-.21s-.238.087-.269.211c-.188.761-.436,1.54-.975,2.078-.539.538-1.317.787-2.079.975-.124.031-.21.141-.21.269s.087.238.21.269c.762.189,1.54.437,2.078.975s.787,1.317.976,2.079c.031.123.141.21.269.21h0c.127,0,.238-.087.269-.211.187-.761.435-1.539.975-2.078s1.318-.788,2.078-.975c.124-.03.211-.141.211-.269s-.087-.238-.21-.269Z'
		})
	);

	/**
	 * Check if a block supports text content
	 *
	 * @param {string} blockName - The block name
	 * @return {boolean} Whether the block has text content
	 */
	function blockHasTextContent(blockName) {
		var textBlocks = [
			'core/paragraph',
			'core/heading',
			'core/list',
			'core/list-item',
			'core/quote',
			//'core/code',
			'core/preformatted',
			'core/verse',
			'core/pullquote',
			//'core/table',
			//'core/columns',
			//'core/column',
			//'core/group',
			//'core/cover',
		];

		return textBlocks.indexOf(blockName) !== -1;
	}

	/**
	 * Check if a block is a media/image block
	 *
	 * @param {string} blockName - The block name
	 * @return {boolean} Whether the block is a media block
	 */
	function blockIsMediaBlock(blockName) {
		var mediaBlocks = [
			'core/image',
			'core/gallery',
			'core/cover',
			'core/media-text'
		];

		return mediaBlocks.indexOf(blockName) !== -1;
	}

	/**
	 * Get text content from block attributes
	 *
	 * @param {Object} props - Block props
	 * @return {string} Text content
	 */
	function getBlockTextContent(props) {
		var attributes = props.attributes;

		// Handle different block types
		if (attributes.content) {
			return attributes.content;
		}

		if (attributes.values) {
			return attributes.values;
		}

		if (attributes.value) {
			return attributes.value;
		}

		if (attributes.citation) {
			return attributes.citation;
		}

		// For container blocks, return empty string
		return '';
	}

	/**
	 * Handle button click - Opens the sidebar and populates text
	 *
	 * @param {Object} props - Block props
	 */
	function handleAskSGAIStudio(props) {
		var textContent = getBlockTextContent(props);
		var plainText;

		// Strip HTML tags to get plain text
		plainText = textContent.replace(/<[^>]*>/g, '');

		// Store the selected text globally for the sidebar to access
		window.sgAiStudioSelectedText = plainText || '';
		window.sgAiStudioSidebarMode = 'text';
		window.sgAiStudioCurrentTextBlockId = props.clientId;
		window.sgAiStudioTextAction = 'custom'; // custom prompt mode

		// Add highlighting class to the block
		addBlockHighlight(props.clientId);

		// Dispatch event to update the sidebar
		window.dispatchEvent(new Event('sgAiStudioTextSelected'));

		// Open the sidebar
		dispatch('core/edit-post').openGeneralSidebar('sg-ai-studio-sidebar/sg-ai-studio-sidebar');
	}

	/**
	 * Handle image generation button click - Opens the image generation sidebar
	 *
	 * @param {Object} props - Block props
	 */
	function handleGenerateImage(props) {
		// Set mode to image generation
		window.sgAiStudioSidebarMode = 'image';
		window.sgAiStudioCurrentBlockId = props.clientId;

		// Add highlighting class to the block
		addBlockHighlight(props.clientId);

		// Dispatch event to update the sidebar
		window.dispatchEvent(new Event('sgAiStudioModeChanged'));

		// Open the sidebar
		dispatch('core/edit-post').openGeneralSidebar('sg-ai-studio-sidebar/sg-ai-studio-sidebar');
	}

	/**
	 * Add highlight animation to a block
	 *
	 * @param {string} blockId - The block client ID
	 */
	function addBlockHighlight(blockId) {
		// Update block attributes to add the highlight class
		var block = select('core/block-editor').getBlock(blockId);
		if (block) {
			var currentClassName = block.attributes.className || '';
			// Only add if not already present
			if (currentClassName.indexOf('sg-ai-highlighted-block') === -1) {
				var newClassName = currentClassName ? currentClassName + ' sg-ai-highlighted-block' : 'sg-ai-highlighted-block';
				dispatch('core/block-editor').updateBlockAttributes(blockId, {
					className: newClassName
				});
			}
		}
	}

	/**
	 * Remove highlight animation from a block
	 *
	 * @param {string} blockId - The block client ID
	 */
	function removeBlockHighlight(blockId) {
		var block = select('core/block-editor').getBlock(blockId);
		if (block && block.attributes.className) {
			var newClassName = block.attributes.className.replace(/\s*sg-ai-highlighted-block\s*/g, '').trim();
			dispatch('core/block-editor').updateBlockAttributes(blockId, {
				className: newClassName || undefined
			});
		}
	}

	/**
	 * Add custom button to block toolbar
	 */
	var withAskSGAIStudioButton = createHigherOrderComponent(function(BlockEdit) {
		return function(props) {
			var name = props.name;
			var isSelected = props.isSelected;
			var attributes = props.attributes;
			var mainButton, toolbarGroup, blockControls;

			// Use useSelect to reactively check sidebar state - only re-renders when the value changes
			var useSelect = wp.data.useSelect;
			var isSidebarOpen = useSelect(function(select) {
				var activeGeneralSidebar = select('core/edit-post').getActiveGeneralSidebarName();
				return activeGeneralSidebar === 'sg-ai-studio-sidebar/sg-ai-studio-sidebar';
			}, []);

			// Check if block is a media block
			if (blockIsMediaBlock(name)) {

				// Create "AI Studio" button for media blocks in its own toolbar group for visual separation
				mainButton = createElement(ToolbarButton, {
					text: sgAiStudioGutenberg.buttonLabel,
					icon: sgAiStudioIcon,
					showTooltip: true,
					onClick: function() {
						handleGenerateImage(props);
					},
					className: 'sg-ai-studio-button',
					isPressed: isSidebarOpen && window.sgAiStudioSidebarMode === 'image'
				});

				// Wrap in separate ToolbarGroup to create visual separator before it
				toolbarGroup = createElement(ToolbarGroup, { className: 'sg-ai-studio-toolbar-group' }, mainButton);
				blockControls = isSelected ? createElement(BlockControls, { group: 'block' }, toolbarGroup) : null;

				return createElement(
					Fragment,
					{},
					createElement(BlockEdit, props),
					blockControls
				);
			}

			// Only add button to blocks with text content
			if (!blockHasTextContent(name)) {
				return createElement(BlockEdit, props);
			}

			// Create main "AI Studio" button
			var mainButton = createElement(ToolbarButton, {
				text: sgAiStudioGutenberg.buttonLabel,
				icon: sgAiStudioIcon,
				showTooltip: true,
				onClick: function() {
					handleAskSGAIStudio(props);
				},
				className: 'sg-ai-studio-button',
				isPressed: isSidebarOpen && window.sgAiStudioSidebarMode === 'text'
			});

			// Create toolbar group with main button only
			var toolbarGroup = createElement(ToolbarGroup, {}, mainButton);

			// Create block controls
			var blockControls = isSelected ? createElement(BlockControls, {}, toolbarGroup) : null;

			// Return fragment with BlockEdit and controls
			return createElement(
				Fragment,
				{},
				createElement(BlockEdit, props),
				blockControls
			);
		};
	}, 'withAskSGAIStudioButton');

	// Apply the filter to add the button to all blocks
	addFilter(
		'editor.BlockEdit',
		'sg-ai-studio/with-ask-button',
		withAskSGAIStudioButton
	);

	/**
	 * Add "Generate" button to media placeholder upload interface
	 * This adds a custom button alongside Upload, Media Library, and Insert from URL
	 */
	var withMediaPlaceholderGenerate = createHigherOrderComponent(function(BlockEdit) {
		return function(props) {
			var name = props.name;
			var attributes = props.attributes;

			// Only add to media blocks that don't have media yet
			// Check for different attribute names: url, id, mediaId (for media-text)
			var hasMedia = attributes.url || attributes.id || attributes.mediaId;

			if (blockIsMediaBlock(name) && !hasMedia) {
				// Use useEffect to inject button after component mounts
				var useEffect = wp.element.useEffect;

				useEffect(function() {
					var isMounted = true;
					var timeoutId = null;

					// Wait for MediaPlaceholder to render
					timeoutId = setTimeout(function() {
						// Check if component is still mounted before DOM manipulation
						if (!isMounted) {
							return;
						}

						var placeholder = document.querySelector('.block-editor-media-placeholder');
						if (placeholder && !placeholder.querySelector('.sg-ai-generate-button')) {
							var fieldset = placeholder.querySelector('.components-placeholder__fieldset');
							if (fieldset) {
								// Find the "Insert from URL" button container
								var urlContainer = fieldset.querySelector('.block-editor-media-placeholder__url-input-container');

								if (urlContainer) {
									// Verify parent node still exists before manipulation
									if (!urlContainer.parentNode) {
										return;
									}

									// Create Generate button - using WordPress button classes to match other buttons
									var generateBtn = document.createElement('button');
									generateBtn.type = 'button';
									generateBtn.className = 'components-button is-next-40px-default-size is-secondary sg-ai-generate-button';
									generateBtn.textContent = __('Generate With AI Studio', 'sg-ai-studio');
									generateBtn.onclick = function(e) {
										e.preventDefault();
										e.stopPropagation();
										handleGenerateImage(props);
									};

									// Insert button after the "Insert from URL" button - check parent exists
									if (urlContainer.parentNode) {
										urlContainer.parentNode.insertBefore(generateBtn, urlContainer.nextSibling);
									}
								}
							}
						}
					}, 100);

					// Cleanup function
					return function() {
						isMounted = false;
						if (timeoutId) {
							clearTimeout(timeoutId);
						}
						var button = document.querySelector('.sg-ai-generate-button');
						if (button && button.parentNode) {
							button.parentNode.removeChild(button);
						}
					};
				}, [attributes.url, attributes.id, attributes.mediaId]);
			}

			return createElement(BlockEdit, props);
		};
	}, 'withMediaPlaceholderGenerate');

	// Apply the filter to add generate button to media placeholder
	addFilter(
		'editor.BlockEdit',
		'sg-ai-studio/with-media-generate',
		withMediaPlaceholderGenerate
	);

	/**
	 * Add "AI Studio" menu item to block toolbar
	 * Uses DOM manipulation to inject into the native WordPress Replace dropdown
	 */
	var withMediaReplaceAI = createHigherOrderComponent(function(BlockEdit) {
		return function(props) {
			var name = props.name;
			var isSelected = props.isSelected;
			var attributes = props.attributes;
			var useEffect = wp.element.useEffect;

			// Only add to media blocks
			if (!blockIsMediaBlock(name) || !isSelected) {
				return createElement(BlockEdit, props);
			}

			// Use effect to inject menu item into Replace dropdown
			useEffect(function() {
				// Check if block has media
				var menuText = __('Edit with AI Studio', 'sg-ai-studio');

				// Wait for the Replace dropdown menu to be available
				var checkAttempts = 0;
				var MAX_ATTEMPTS = 50; // 5 seconds max (50 * 100ms)

				var checkInterval = setInterval(function() {
					checkAttempts++;

					// Stop after max attempts to prevent infinite interval
					if (checkAttempts >= MAX_ATTEMPTS) {
						clearInterval(checkInterval);
						return;
					}

					// Look for the MediaReplaceFlow menu container
					var menu = document.querySelector('.block-editor-media-replace-flow__media-upload-menu');

					if (menu && !menu.querySelector('.sg-ai-studio-menu-item')) {
						// Create our custom menu item matching WordPress structure
						var menuItem = document.createElement('button');
						menuItem.type = 'button';
						menuItem.setAttribute('role', 'menuitem');
						menuItem.className = 'components-button components-menu-item__button is-next-40px-default-size sg-ai-studio-menu-item';
						menuItem.setAttribute('tabindex', '-1');

						// Add text span
						var textSpan = document.createElement('span');
						textSpan.className = 'components-menu-item__item';
						textSpan.textContent = menuText;
						menuItem.appendChild(textSpan);

						// Add click handler with proper event stopping
						menuItem.addEventListener('click', function(e) {
							e.preventDefault();
							e.stopPropagation();
							handleGenerateImage(props);
							// Close the popover
							var popover = menu.closest('.components-popover');
							if (popover) {
								var closeButton = popover.querySelector('.components-popover__close');
								if (closeButton) {
									closeButton.click();
								}
							}
						});

						// Prevent event bubbling on mouseenter/mouseleave
						menuItem.addEventListener('mouseenter', function(e) {
							e.stopPropagation();
						});

						menuItem.addEventListener('mouseleave', function(e) {
							e.stopPropagation();
						});

						// Create a separator before our menu item
						var separator = document.createElement('div');
						separator.className = 'components-menu-group sg-ai-studio-separator';
						separator.style.margin = '8px 0';
						separator.style.borderTop = '1px solid #ddd';

						// Insert separator and menu item at the end of the menu
						menu.appendChild(separator);
						menu.appendChild(menuItem);

						clearInterval(checkInterval);
					}
				}, 100);

				// Cleanup
				return function() {
					clearInterval(checkInterval);
					var separator = document.querySelector('.sg-ai-studio-separator');
					var menuItem = document.querySelector('.sg-ai-studio-menu-item');
					if (separator) {
						separator.remove();
					}
					if (menuItem) {
						menuItem.remove();
					}
				};
			}, [attributes.url, attributes.id, attributes.mediaId, isSelected]);

			return createElement(BlockEdit, props);
		};
	}, 'withMediaReplaceAI');

	// Apply the filter to add AI option to media replace dropdown
	addFilter(
		'editor.BlockEdit',
		'sg-ai-studio/with-media-replace-ai',
		withMediaReplaceAI,
		100 // Higher priority to run after other filters
	);

	/**
	 * Unified SG AI Studio Sidebar Component
	 * Displays different content based on the selected block type (text or media)
	 */
	var SGAIStudioSidebar = function() {
		var useState = wp.element.useState;
		var useEffect = wp.element.useEffect;
		var useRef = wp.element.useRef;

		// Refs for input fields
		var customPromptRef = useRef(null);
		var imageDescriptionRef = useRef(null);

		// State for mode (text or image)
		var modeArray = useState(window.sgAiStudioSidebarMode || 'text');
		var mode = modeArray[0];
		var setMode = modeArray[1];

		// Text mode states
		var textStateArray = useState(window.sgAiStudioSelectedText || '');
		var promptText = textStateArray[0];
		var setPromptText = textStateArray[1];

		var customPromptArray = useState('');
		var customPrompt = customPromptArray[0];
		var setCustomPrompt = customPromptArray[1];

		var isProcessingTextArray = useState(false);
		var isProcessingText = isProcessingTextArray[0];
		var setIsProcessingText = isProcessingTextArray[1];

		var aiResultArray = useState(null);
		var aiResult = aiResultArray[0];
		var setAiResult = aiResultArray[1];

		var currentTextBlockIdArray = useState(null);
		var currentTextBlockId = currentTextBlockIdArray[0];
		var setCurrentTextBlockId = currentTextBlockIdArray[1];

		var textActionModeArray = useState('custom'); // 'custom' or 'predefined'
		var textActionMode = textActionModeArray[0];
		var setTextActionMode = textActionModeArray[1];

		var currentActionPromptArray = useState('');
		var currentActionPrompt = currentActionPromptArray[0];
		var setCurrentActionPrompt = currentActionPromptArray[1];

		// Image mode states
		var imageDescArray = useState('');
		var imageDescription = imageDescArray[0];
		var setImageDescription = imageDescArray[1];

		var aspectRatioArray = useState('square');
		var aspectRatio = aspectRatioArray[0];
		var setAspectRatio = aspectRatioArray[1];

		var isGeneratingArray = useState(false);
		var isGenerating = isGeneratingArray[0];
		var setIsGenerating = isGeneratingArray[1];

		var generatedImageArray = useState(null);
		var generatedImage = generatedImageArray[0];
		var setGeneratedImage = generatedImageArray[1];

		var currentBlockIdArray = useState(null);
		var currentBlockId = currentBlockIdArray[0];
		var setCurrentBlockId = currentBlockIdArray[1];

		// Block selection state for dynamic updates
		var hasBlockSelectedArray = useState(true);
		var hasBlockSelected = hasBlockSelectedArray[0];
		var setHasBlockSelected = hasBlockSelectedArray[1];

		var isBlockSupportedArray = useState(true);
		var isBlockSupported = isBlockSupportedArray[0];
		var setIsBlockSupported = isBlockSupportedArray[1];

		// Focus trigger - incremented to force re-focus
		var focusTriggerArray = useState(0);
		var focusTrigger = focusTriggerArray[0];
		var setFocusTrigger = focusTriggerArray[1];

		// Track block IDs that are currently being generated (to prevent sidebar interactions)
		var generatingBlockIdsArray = useState([]);
		var generatingBlockIds = generatingBlockIdsArray[0];
		var setGeneratingBlockIds = generatingBlockIdsArray[1];

		// Update when events are triggered
		useEffect(function() {
			var handleTextUpdate = function() {
				if (window.sgAiStudioSelectedText !== undefined) {
					setPromptText(window.sgAiStudioSelectedText);
				}
				if (window.sgAiStudioSidebarMode !== undefined) {
					setMode(window.sgAiStudioSidebarMode);
				}
				if (window.sgAiStudioCurrentTextBlockId !== undefined) {
					setCurrentTextBlockId(window.sgAiStudioCurrentTextBlockId);
				}
				if (window.sgAiStudioTextAction !== undefined) {
					setTextActionMode(window.sgAiStudioTextAction);
				}
				// Reset states when opening custom mode
				setAiResult(null);
				setIsProcessingText(false);
				setCustomPrompt('');
				// Trigger focus
				setFocusTrigger(function(prev) { return prev + 1; });
			};

			var handleModeChange = function() {
				if (window.sgAiStudioSidebarMode !== undefined) {
					setMode(window.sgAiStudioSidebarMode);
				}
				if (window.sgAiStudioCurrentBlockId !== undefined) {
					setCurrentBlockId(window.sgAiStudioCurrentBlockId);
				}
				// Trigger focus
				setFocusTrigger(function(prev) { return prev + 1; });
			};

			var handlePredefinedAction = function() {
				if (window.sgAiStudioSelectedText !== undefined) {
					setPromptText(window.sgAiStudioSelectedText);
				}
				if (window.sgAiStudioSidebarMode !== undefined) {
					setMode(window.sgAiStudioSidebarMode);
				}
				if (window.sgAiStudioCurrentTextBlockId !== undefined) {
					setCurrentTextBlockId(window.sgAiStudioCurrentTextBlockId);
				}
				if (window.sgAiStudioTextAction !== undefined) {
					setTextActionMode(window.sgAiStudioTextAction);
				}
				if (window.sgAiStudioActionPrompt !== undefined) {
					setCurrentActionPrompt(window.sgAiStudioActionPrompt);
				}

				// Start processing immediately for predefined actions
				setIsProcessingText(true);
				setAiResult(null);

				// Call backend API
				wp.apiFetch({
					path: '/sg-ai-studio/gutenberg/edit-text',
					method: 'POST',
					data: {
						text: window.sgAiStudioSelectedText,
						prompt: window.sgAiStudioActionPrompt,
						action_type: window.sgAiStudioActionType || 'custom',
						nonce: sgAiStudioGutenberg.nonce
					}
				}).then(function(response) {
					if (response.success && response.data) {
						setAiResult(response.data.edited_text);
						setIsProcessingText(false);
					} else {
						console.error('AI text editing failed:', response);
						setIsProcessingText(false);
					}
				}).catch(function(error) {
					console.error('Error calling text edit API:', error);
					setIsProcessingText(false);
				});
			};

			// Listen for events
			window.addEventListener('sgAiStudioTextSelected', handleTextUpdate);
			window.addEventListener('sgAiStudioModeChanged', handleModeChange);
			window.addEventListener('sgAiStudioPredefinedAction', handlePredefinedAction);

			return function() {
				window.removeEventListener('sgAiStudioTextSelected', handleTextUpdate);
				window.removeEventListener('sgAiStudioModeChanged', handleModeChange);
				window.removeEventListener('sgAiStudioPredefinedAction', handlePredefinedAction);
			};
		}, []);

		// Monitor block selection changes for dynamic sidebar updates
		useEffect(function() {
			var lastBlockId = null;

			var unsubscribe = wp.data.subscribe(function() {
				var selectedBlock = select('core/block-editor').getSelectedBlock();
				var currentBlockId = selectedBlock ? selectedBlock.clientId : null;

				// Only update if the selected block actually changed
				if (currentBlockId === lastBlockId) {
					return;
				}

				lastBlockId = currentBlockId;

				if (!selectedBlock) {
					setHasBlockSelected(false);
					setIsBlockSupported(false);
					return;
				}

				setHasBlockSelected(true);

				// Check if our sidebar is currently open
				var activeGeneralSidebar = select('core/edit-post').getActiveGeneralSidebarName();
				var isSidebarOpen = activeGeneralSidebar === 'sg-ai-studio-sidebar/sg-ai-studio-sidebar';

				// Determine block type and appropriate mode
				var isImageBlock = blockIsMediaBlock(selectedBlock.name);
				var isTextBlock = blockHasTextContent(selectedBlock.name);

				if (isImageBlock) {
					// Image block selected - switch to image mode
					setIsBlockSupported(true);

					if (isSidebarOpen) {
						// Remove highlight from previous image block
						if (window.sgAiStudioCurrentBlockId && window.sgAiStudioCurrentBlockId !== currentBlockId) {
							removeBlockHighlight(window.sgAiStudioCurrentBlockId);
						}
						// Remove highlight from previous text block (cross-mode cleanup)
						if (window.sgAiStudioCurrentTextBlockId) {
							removeBlockHighlight(window.sgAiStudioCurrentTextBlockId);
							window.sgAiStudioCurrentTextBlockId = null;
						}

						// Set mode and block ID
						window.sgAiStudioSidebarMode = 'image';
						window.sgAiStudioCurrentBlockId = currentBlockId;

						// Add highlight to new block
						addBlockHighlight(currentBlockId);

						// Dispatch event to update the sidebar
						window.dispatchEvent(new Event('sgAiStudioModeChanged'));

						// Refresh sidebar
						dispatch('core/edit-post').openGeneralSidebar('sg-ai-studio-sidebar/sg-ai-studio-sidebar');
					}
				} else if (isTextBlock) {
					// Text block selected - switch to text mode
					setIsBlockSupported(true);

					if (isSidebarOpen) {
						// Check if this block is currently being generated (locked)
						var isGeneratingBlock = generatingBlockIds.indexOf(currentBlockId) !== -1;

						if (isGeneratingBlock) {
							// Block is being generated - don't allow editing
							return;
						}

						// Remove highlight from previous text block
						if (window.sgAiStudioCurrentTextBlockId && window.sgAiStudioCurrentTextBlockId !== currentBlockId) {
							removeBlockHighlight(window.sgAiStudioCurrentTextBlockId);
						}
						// Remove highlight from previous image block (cross-mode cleanup)
						if (window.sgAiStudioCurrentBlockId) {
							removeBlockHighlight(window.sgAiStudioCurrentBlockId);
							window.sgAiStudioCurrentBlockId = null;
						}

						// Get text content
						var block = select('core/block-editor').getBlock(currentBlockId);
						var textContent = '';
						if (block && block.attributes && block.attributes.content) {
							textContent = block.attributes.content.replace(/<[^>]*>/g, '');
						}

						// Set mode and text
						window.sgAiStudioSelectedText = textContent || '';
						window.sgAiStudioSidebarMode = 'text';
						window.sgAiStudioCurrentTextBlockId = currentBlockId;
						window.sgAiStudioTextAction = 'custom';

						// Add highlight to new block
						addBlockHighlight(currentBlockId);

						// Dispatch event to update the sidebar
						window.dispatchEvent(new Event('sgAiStudioTextSelected'));

						// Refresh sidebar
						dispatch('core/edit-post').openGeneralSidebar('sg-ai-studio-sidebar/sg-ai-studio-sidebar');
					}
				} else {
					// Unsupported block - remove highlight if sidebar is open
					setIsBlockSupported(false);
					if (isSidebarOpen) {
						if (window.sgAiStudioCurrentBlockId) {
							removeBlockHighlight(window.sgAiStudioCurrentBlockId);
						}
						if (window.sgAiStudioCurrentTextBlockId) {
							removeBlockHighlight(window.sgAiStudioCurrentTextBlockId);
						}
					}
				}
			});

			return function() {
				if (unsubscribe) {
					unsubscribe();
				}
			};
		}, [mode]);

		// Auto-focus input fields when mode changes or sidebar opens
		useEffect(function() {
			// Small delay to ensure the DOM has rendered
			var timeoutId = setTimeout(function() {
				if (mode === 'text' && customPromptRef.current && !isProcessingText && !aiResult) {
					customPromptRef.current.focus();
				} else if (mode === 'image' && imageDescriptionRef.current && !isGenerating && !generatedImage) {
					imageDescriptionRef.current.focus();
				}
			}, 100);

			return function() {
				clearTimeout(timeoutId);
			};
		}, [mode, isProcessingText, aiResult, isGenerating, generatedImage, focusTrigger]);

		// Monitor sidebar state and clean up unused placeholder blocks
		useEffect(function() {
			var unsubscribe = wp.data.subscribe(function() {
				var activeGeneralSidebar = select('core/edit-post').getActiveGeneralSidebarName();
				var isSidebarOpen = activeGeneralSidebar === 'sg-ai-studio-sidebar/sg-ai-studio-sidebar';

				// If sidebar is closed and we have a tracked placeholder block
				if (!isSidebarOpen && window.sgAiStudioPlaceholderBlockId) {
					var placeholderBlockId = window.sgAiStudioPlaceholderBlockId;
					var block = select('core/block-editor').getBlock(placeholderBlockId);

					if (block) {
						var content = block.attributes.content || '';
						var isEmptyOrLoading = content === '' ||
							content.indexOf('sg-ai-loading-spinner') !== -1 ||
							content.indexOf('Generating with AI Studio') !== -1;

						// Only remove if the block is still empty or in loading state
						if (isEmptyOrLoading) {
							dispatch('core/block-editor').removeBlocks([placeholderBlockId]);
						}
					}

					// Clear tracking variables
					window.sgAiStudioPlaceholderBlockId = null;
					window.sgAiStudioPlaceholderCreatedTime = null;
				}
			});

			return function() {
				if (unsubscribe) {
					unsubscribe();
				}
			};
		}, []);

		// Aspect ratio options
		var aspectRatioOptions = [
			{ label: __('Square (1:1)', 'sg-ai-studio'), value: 'square' },
			{ label: __('Landscape (16:9)', 'sg-ai-studio'), value: 'landscape' },
			{ label: __('Portrait (9:16)', 'sg-ai-studio'), value: 'portrait' },
			{ label: __('Wide (21:9)', 'sg-ai-studio'), value: 'wide' }
		];

		// Determine sidebar title and icon based on mode
		var sidebarTitle = __('AI Studio', 'sg-ai-studio');
		var sidebarIcon = sgAiStudioIcon;

		return createElement(
			Fragment,
			{},
			// Sidebar menu item
			createElement(PluginSidebarMoreMenuItem, {
				target: 'sg-ai-studio-sidebar',
				icon: sidebarIcon
			}, sidebarTitle),
			// Sidebar content
			createElement(PluginSidebar, {
				name: 'sg-ai-studio-sidebar',
				title: sidebarTitle,
				icon: sidebarIcon
			},
				createElement(Fragment, {},
					// Show connection notice if not connected, hide all features
					!sgAiStudioGutenberg.is_connected ? createElement(Notice, {
						status: 'warning',
						isDismissible: false,
						style: { margin: '16px' }
					},
						createElement('p', { style: { margin: 0 } },
							__('Please configure your AI Studio API key in the plugin settings. ', 'sg-ai-studio'),
							createElement('a', {
								href: sgAiStudioGutenberg.settingsUrl,
								target: '_blank',
								style: { textDecoration: 'underline' }
							}, __('Go to Settings →', 'sg-ai-studio'))
						)
					)
				:
					// Conditionally render based on mode (only when connected)
					mode === 'image' ?
						// IMAGE GENERATION MODE
						createElement('div', {
							className: 'sg-ai-studio-sidebar-content',
							style: { padding: '16px', position: 'relative', minHeight: '100vh' }
						},
							// Full sidebar spinner overlay when generating image
							isGenerating ? createElement('div', {
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
									pointerEvents: 'all'
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
							) : null,
							// Show message if no block is selected or block is unsupported
							(!hasBlockSelected || !isBlockSupported) ?
								createElement('div', {
									style: {
										padding: '24px',
										textAlign: 'center',
										color: '#757575',
										fontSize: '14px'
									}
								}, __('Selected block is not supported yet.', 'sg-ai-studio'))
							:
							// Show result state if image is generated
							generatedImage ? createElement('div', {
							className: 'sg-ai-studio-image-result'
						},
							// Generated image preview
							createElement('div', {
								className: 'sg-ai-studio-image-preview'
							},
								createElement('img', {
									src: generatedImage.url,
									alt: imageDescription,
									style: { width: '100%', height: 'auto', borderRadius: '8px' }
								})
							),
							// Result action buttons
							createElement('div', {
								className: 'sg-ai-studio-result-actions',
								style: { marginTop: '16px', display: 'flex', gap: '8px', flexDirection: 'column' }
							},
								// Use this image / Replace button (wrapped in div for consistency)
								createElement('div', {},
									(function() {
										// Get the block to check if it has an existing image
										var block = select('core/block-editor').getBlock(currentBlockId);
										var hasExistingImage = false;

										if (block) {
											// Check different block types for existing images
											if (block.name === 'core/image' && block.attributes.url) {
												hasExistingImage = true;
											} else if (block.name === 'core/cover' && block.attributes.url) {
												hasExistingImage = true;
											} else if (block.name === 'core/media-text' && block.attributes.mediaUrl) {
												hasExistingImage = true;
											} else if (block.name === 'core/gallery' && block.attributes.images && block.attributes.images.length > 0) {
												hasExistingImage = true;
											}
										}

										return createElement(Button, {
											icon: hasExistingImage ? createElement('svg', { xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 24 24', width: '24', height: '24', fill: 'currentColor' },
												createElement('path', { d: 'M12 6v3l4-4-4-4v3c-4.42 0-8 3.58-8 8 0 1.57.46 3.03 1.24 4.26L6.7 14.8c-.45-.83-.7-1.79-.7-2.8 0-3.31 2.69-6 6-6zm6.76 1.74L17.3 9.2c.44.84.7 1.79.7 2.8 0 3.31-2.69 6-6 6v-3l-4 4 4 4v-3c4.42 0 8-3.58 8-8 0-1.57-.46-3.03-1.24-4.26z' })
											) : undefined,
											variant: 'primary',
											className: 'sg-button-base sg-button-base--color-primary sg-button sg-button--medium',
											onClick: function() {
												// Get the block
												var block = select('core/block-editor').getBlock(currentBlockId);
												if (!block) {
													console.error('Block not found:', currentBlockId);
													return;
												}

										// Ensure ID is an integer
										var imageId = parseInt(generatedImage.id, 10);

										// Update block attributes with the generated image
										var newAttributes = {
											id: imageId,
											url: generatedImage.url,
											alt: imageDescription
										};

										// For cover blocks, use different attributes (no alt attribute)
										if (block.name === 'core/cover') {
											newAttributes = {
												url: generatedImage.url,
												id: imageId,
												backgroundType: 'image'
											};
										}

										// For media-text blocks, use mediaId and mediaUrl
										if (block.name === 'core/media-text') {
											newAttributes = {
												mediaId: imageId,
												mediaUrl: generatedImage.url,
												mediaAlt: imageDescription,
												mediaType: 'image'
											};
										}

										// For gallery blocks, need to create media object
										if (block.name === 'core/gallery') {
											var galleryImage = {
												id: imageId,
												url: generatedImage.url,
												alt: imageDescription,
												caption: ''
											};
											// Add to existing images or create new array
											var existingImages = block.attributes.images || [];
											newAttributes = {
												images: existingImages.concat([galleryImage])
											};
										}

										// Update the block
										dispatch('core/block-editor').updateBlockAttributes(
											currentBlockId,
											newAttributes
										);

										// Reset state
										setGeneratedImage(null);
										setImageDescription('');
										setAspectRatio('square');

											// Close sidebar (optional)
											// dispatch('core/edit-post').closeGeneralSidebar();
										}
									}, hasExistingImage ? __('Replace', 'sg-ai-studio') : __('Use this image', 'sg-ai-studio'));
								})()
								),
								// Try again and Discard buttons in a row
								createElement('div', {
									className: 'sg-ai-studio-button-row',
									style: { display: 'flex', gap: '8px' }
								},
									// Try again button
									createElement(Button, {
										variant: 'secondary',
										className: 'sg-button-base sg-button-base--color-secondary sg-button-base--type-contained sg-button sg-button--small',
										onClick: function() {
											// Delete the generated image from media library
											if (generatedImage && generatedImage.id) {
												wp.apiFetch({
													path: '/sg-ai-studio/gutenberg/delete-image',
													method: 'POST',
													data: {
														image_id: generatedImage.id
													}
												}).catch(function(error) {
													console.error('Error deleting image:', error);
												});
											}

											// Reset to input state (back to previous menu)
											setGeneratedImage(null);
										}
									}, __('Try again', 'sg-ai-studio')),
									// Discard button
									createElement(Button, {
										isDestructive: true,
										variant: 'secondary',
										className: 'sg-button-base sg-button-base--color-secondary sg-button-base--type-contained sg-button sg-button--small',
										onClick: function() {
											// Delete the generated image from media library
											if (generatedImage && generatedImage.id) {
												wp.apiFetch({
													path: '/sg-ai-studio/gutenberg/delete-image',
													method: 'POST',
													data: {
														image_id: generatedImage.id
													}
												}).catch(function(error) {
													console.error('Error deleting image:', error);
												});
											}

											// Reset to input state
											setGeneratedImage(null);
											setImageDescription('');
											setAspectRatio('square');
										}
									}, __('Discard', 'sg-ai-studio'))
								)
							)
						) : (
							// Input state (when no image is generated)
							createElement(Fragment, {},
								// Image description field - styled box
								createElement('div', {
									className: 'sg-ai-studio-text-section',
									style: { marginBottom: '16px' }
								},
									createElement('label', {
										className: 'components-base-control__label',
										style: { display: 'block', marginBottom: '8px', fontWeight: '600' }
									}, __('Image Description', 'sg-ai-studio')),
									createElement('textarea', {
										ref: imageDescriptionRef,
										className: 'sg-ai-studio-text-input',
										value: imageDescription,
										onChange: function(e) {
											setImageDescription(e.target.value);
										},
										readOnly: isGenerating,
										rows: 6,
										placeholder: __('Describe the image you want to generate in detail...', 'sg-ai-studio'),
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
								),
								// Aspect ratio selector - styled box
								createElement('div', {
									className: 'sg-ai-studio-text-section',
									style: { marginBottom: '16px' }
								},
									createElement(SelectControl, {
										label: __('Aspect Ratio', 'sg-ai-studio'),
										value: aspectRatio,
										options: aspectRatioOptions,
										onChange: function(value) {
											setAspectRatio(value);
										},
										disabled: isGenerating,
										__next40pxDefaultSize: true,
										__nextHasNoMarginBottom: true
									})
								),
								// Generate image button
								createElement(Button, {
									isPrimary: true,
									className: 'sg-button-base sg-button-base--color-primary sg-button-base--type-outlined sg-button sg-button--medium',
									disabled: !imageDescription || !imageDescription.trim() || isGenerating,
									onClick: function() {
										// Start generating
										setIsGenerating(true);

										// Call backend API for image generation
										wp.apiFetch({
											path: '/sg-ai-studio/gutenberg/generate-image',
											method: 'POST',
											data: {
												description: imageDescription,
												aspect_ratio: aspectRatio,
												post_id: getCurrentPostId(),
												nonce: sgAiStudioGutenberg.nonce
											}
										}).then(function(response) {
											if (response.success && response.data) {
												setGeneratedImage({
													id: response.data.id,
													url: response.data.url,
													alt: imageDescription
												});
												setIsGenerating(false);
											} else {
												setIsGenerating(false);
											}
										}).catch(function() {
											setIsGenerating(false);
										});
									}
								}, isGenerating ? __('Generating image...', 'sg-ai-studio') : __('Generate image', 'sg-ai-studio'))
							)
						)
						)
				:
					// TEXT CONTENT MODE
					createElement('div', {
						className: 'sg-ai-studio-sidebar-content',
						style: { padding: '16px', position: 'relative', minHeight: '100vh' }
					},
						// Full sidebar spinner overlay when processing text
						isProcessingText ? createElement('div', {
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
								pointerEvents: 'all'
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
						) : null,
						// Show message if no block is selected or block is unsupported
						(!hasBlockSelected || !isBlockSupported) ?
							createElement('div', {
								style: {
									padding: '24px',
									textAlign: 'center',
									color: '#757575',
									fontSize: '14px'
								}
							}, __('No supported block selected.', 'sg-ai-studio'))
						:
						// Show result comparison if AI result exists (predefined actions)
						aiResult ? createElement('div', {
							className: 'sg-ai-studio-text-result'
						},
							// Original text
							createElement('div', {
								className: 'sg-ai-studio-text-section',
								style: { marginBottom: '16px' }
							},
								createElement('label', {
									className: 'components-base-control__label',
									style: { display: 'block', marginBottom: '8px', fontWeight: '600' }
								}, __('Original Text', 'sg-ai-studio')),
								createElement('div', {
									className: 'sg-ai-studio-text-display',
									style: {
										padding: '12px',
										background: '#f9f9f9',
										border: '1px solid #ddd',
										borderRadius: '4px',
										fontSize: '14px',
										lineHeight: '1.6',
										whiteSpace: 'pre-wrap'
									}
								}, promptText)
							),
							// AI-generated text
							createElement('div', {
								className: 'sg-ai-studio-text-section',
								style: { marginBottom: '16px' }
							},
								createElement('label', {
									className: 'components-base-control__label',
									style: { display: 'block', marginBottom: '8px', fontWeight: '600' }
								}, __('AI-Generated Version', 'sg-ai-studio')),
								createElement('div', {
									className: 'sg-ai-studio-text-display',
									style: {
										padding: '12px',
										background: '#f0f7ff',
										border: '1px solid var(--color-primary-main, #4343f0)',
										borderRadius: '4px',
										fontSize: '14px',
										lineHeight: '1.6',
										whiteSpace: 'pre-wrap'
									}
								}, aiResult)
							),
							// Action buttons
							createElement('div', {
								className: 'sg-ai-studio-result-actions',
								style: { display: 'flex', flexDirection: 'column', gap: '8px' }
							},
								// Replace button
								createElement(Button, {
									icon: createElement('svg', { xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 24 24', width: '24', height: '24', fill: 'currentColor' },
										createElement('path', { d: 'M12 6v3l4-4-4-4v3c-4.42 0-8 3.58-8 8 0 1.57.46 3.03 1.24 4.26L6.7 14.8c-.45-.83-.7-1.79-.7-2.8 0-3.31 2.69-6 6-6zm6.76 1.74L17.3 9.2c.44.84.7 1.79.7 2.8 0 3.31-2.69 6-6 6v-3l-4 4 4 4v-3c4.42 0 8-3.58 8-8 0-1.57-.46-3.03-1.24-4.26z' })
									),
									variant: 'primary',
									className: 'sg-button-base sg-button-base--color-primary sg-button sg-button--medium',
									onClick: function() {
										// Get the block
										var block = select('core/block-editor').getBlock(currentTextBlockId);
										if (!block) {
											console.error('Block not found:', currentTextBlockId);
											return;
										}

										// Update block content with AI result
										// Sanitize and convert plain text to HTML
										var htmlContent = sanitizeTextToHTML(aiResult);

										dispatch('core/block-editor').updateBlockAttributes(
											currentTextBlockId,
											{ content: htmlContent }
										);

										// Reset state to initial (as if user reselected the block)
										setAiResult(null);
										setCustomPrompt('');
										setTextActionMode('custom');
										setCurrentActionPrompt('');

										// Update with new text content
										var updatedBlock = select('core/block-editor').getBlock(currentTextBlockId);
										if (updatedBlock && updatedBlock.attributes && updatedBlock.attributes.content) {
											var newText = updatedBlock.attributes.content.replace(/<[^>]*>/g, '');
											setPromptText(newText);
										}
									}
								}, __('Replace', 'sg-ai-studio')),
								// Try again and Discard buttons in a row
								createElement('div', {
									className: 'sg-ai-studio-button-row',
									style: { display: 'flex', gap: '8px' }
								},
									// Try again button
									createElement(Button, {
										variant: 'secondary',
										className: 'sg-button-base sg-button-base--color-secondary sg-button-base--type-contained sg-button sg-button--small',
										onClick: function() {
											// Regenerate
											setIsProcessingText(true);
											setAiResult(null);

											// Call backend API again with same parameters
											wp.apiFetch({
												path: '/sg-ai-studio/gutenberg/edit-text',
												method: 'POST',
												data: {
													text: promptText,
													prompt: currentActionPrompt || customPrompt,
													action_type: textActionMode === 'predefined' ? window.sgAiStudioActionType : 'custom'
												}
											}).then(function(response) {
												if (response.success && response.data) {
													setAiResult(response.data.edited_text);
													setIsProcessingText(false);
												} else {
													console.error('AI text editing failed:', response);
													setIsProcessingText(false);
												}
											}).catch(function(error) {
												console.error('Error calling text edit API:', error);
												setIsProcessingText(false);
											});
										}
									}, __('Try again', 'sg-ai-studio')),
									// Discard button
									createElement(Button, {
										isDestructive: true,
										variant: 'secondary',
										className: 'sg-button-base sg-button-base--color-secondary sg-button-base--type-contained sg-button sg-button--small',
										onClick: function() {
											// Reset to initial state (as if user reselected the block)
											setAiResult(null);
											setCustomPrompt('');
											setTextActionMode('custom');
											setCurrentActionPrompt('');

											// Reload the original block text
											var block = select('core/block-editor').getBlock(currentTextBlockId);
											if (block && block.attributes && block.attributes.content) {
												var textContent = block.attributes.content.replace(/<[^>]*>/g, '');
												setPromptText(textContent);
											}
										}
									}, __('Discard', 'sg-ai-studio'))
								)
							)
						) : (
							// Custom prompt mode (input state)
							createElement(Fragment, {},
								// Check if we're in generation mode (no text selected)
								(function() {
									var isGenerationMode = !promptText || !promptText.trim();

									return createElement(Fragment, {},
										// Selected text display (only show in edit mode) - styled box
										!isGenerationMode ? createElement('div', {
											className: 'sg-ai-studio-text-section',
											style: { marginBottom: '16px' }
										},
											createElement('label', {
												className: 'components-base-control__label',
												style: { display: 'block', marginBottom: '8px', fontWeight: '600' }
											}, __('Original Text', 'sg-ai-studio')),
											createElement('div', {
												className: 'sg-ai-studio-text-display',
												style: {
													padding: '12px',
													background: '#f9f9f9',
													border: '1px solid #ddd',
													borderRadius: '4px',
													fontSize: '14px',
													lineHeight: '1.6',
													whiteSpace: 'pre-wrap',
													minHeight: '120px',
													maxHeight: '300px',
													overflowY: 'auto'
												}
											}, promptText || __('Click "Ask SG AI Studio" button on any text block to populate this field...', 'sg-ai-studio'))
										) : null,
										// Predefined action buttons container (only show when text is not empty)
										(promptText && promptText.trim()) ? createElement('div', {
											className: 'sg-ai-studio-actions'
										},
									// Improve button
									createElement(Button, {
										icon: createElement('svg', { xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 24 24', width: '24', height: '24', fill: 'currentColor' },
											createElement('path', { d: 'M7.5 5.6L5 7l1.4-2.5L5 2l2.5 1.4L10 2 8.6 4.5 10 7 7.5 5.6zm12 9.8L22 14l-1.4 2.5L22 19l-2.5-1.4L17 19l1.4-2.5L17 14l2.5 1.4zM22 2l-1.4 2.5L22 7l-2.5-1.4L17 7l1.4-2.5L17 2l2.5 1.4L22 2zm-7.63 5.29a.996.996 0 0 0-1.41 0L1.29 18.96c-.39.39-.39 1.02 0 1.41l2.34 2.34c.39.39 1.02.39 1.41 0L16.7 11.05a.996.996 0 0 0 0-1.41l-2.33-2.35zm-1.03 5.49l-2.12-2.12 2.44-2.44 2.12 2.12-2.44 2.44z' })
										),
										variant: 'secondary',
										className: 'sg-button-base sg-button-base--color-secondary sg-button-base--type-contained sg-button sg-button--small',
										disabled: !promptText || !promptText.trim() || isProcessingText,
										onClick: function() {
											setCurrentActionPrompt('Improve this text');
											setTextActionMode('predefined');
											setIsProcessingText(true);
											setAiResult(null);

											// Call backend API
											wp.apiFetch({
												path: '/sg-ai-studio/gutenberg/edit-text',
												method: 'POST',
												data: {
													text: promptText,
													prompt: 'Improve this text',
													action_type: 'improve',
													nonce: sgAiStudioGutenberg.nonce
												}
											}).then(function(response) {
												if (response.success && response.data) {
													setAiResult(response.data.edited_text);
													setIsProcessingText(false);
												} else {
													console.error('AI text editing failed:', response);
													setIsProcessingText(false);
												}
											}).catch(function(error) {
												console.error('Error calling text edit API:', error);
												setIsProcessingText(false);
											});
										}
									}, __('Improve', 'sg-ai-studio')),
									// Fix spelling & grammar button
									createElement(Button, {
										icon: createElement('svg', { xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 24 24', width: '24', height: '24', fill: 'currentColor' },
											createElement('path', { d: 'M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z' })
										),
										variant: 'secondary',
										className: 'sg-button-base sg-button-base--color-secondary sg-button-base--type-contained sg-button sg-button--small',
										disabled: !promptText || !promptText.trim() || isProcessingText,
										onClick: function() {
											setCurrentActionPrompt('Fix spelling and grammar');
											setTextActionMode('predefined');
											setIsProcessingText(true);
											setAiResult(null);

											wp.apiFetch({
												path: '/sg-ai-studio/gutenberg/edit-text',
												method: 'POST',
												data: {
													text: promptText,
													prompt: 'Fix spelling and grammar',
													action_type: 'fix-grammar',
													nonce: sgAiStudioGutenberg.nonce
												}
											}).then(function(response) {
												if (response.success && response.data) {
													setAiResult(response.data.edited_text);
													setIsProcessingText(false);
												} else {
													console.error('AI text editing failed:', response);
													setIsProcessingText(false);
												}
											}).catch(function(error) {
												console.error('Error calling text edit API:', error);
												setIsProcessingText(false);
											});
										}
									}, __('Fix grammar', 'sg-ai-studio')),
									// Make shorter button
									createElement(Button, {
											icon: createElement('svg', { xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 24 24', width: '24', height: '24', fill: 'currentColor' },
												createElement('text', { x: '12', y: '17', fontSize: '18', fill: 'currentColor', fontFamily: 'Poppins, -apple-system, BlinkMacSystemFont, sans-serif' }, '-')
											),
											variant: 'secondary',
											className: 'sg-button-base sg-button-base--color-secondary sg-button-base--type-contained sg-button sg-button--small',
											disabled: !promptText || !promptText.trim() || isProcessingText,
											onClick: function() {
												setCurrentActionPrompt('Make this text shorter');
												setTextActionMode('predefined');
												setIsProcessingText(true);
												setAiResult(null);

												wp.apiFetch({
													path: '/sg-ai-studio/gutenberg/edit-text',
													method: 'POST',
													data: {
														text: promptText,
														prompt: 'Make this text shorter',
														action_type: 'make-shorter',
														nonce: sgAiStudioGutenberg.nonce
													}
												}).then(function(response) {
													if (response.success && response.data) {
														setAiResult(response.data.edited_text);
														setIsProcessingText(false);
													} else {
														console.error('AI text editing failed:', response);
														setIsProcessingText(false);
													}
												}).catch(function(error) {
													console.error('Error calling text edit API:', error);
													setIsProcessingText(false);
												});
											}
										}, __('Make Shorter', 'sg-ai-studio')),
										// Make longer button
										createElement(Button, {
											icon: createElement('svg', { xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 24 24', width: '24', height: '24', fill: 'currentColor' },
												createElement('text', { x: '12', y: '17', fontSize: '18', fill: 'currentColor', fontFamily: 'Poppins, -apple-system, BlinkMacSystemFont, sans-serif' }, '+')
											),
											variant: 'secondary',
											className: 'sg-button-base sg-button-base--color-secondary sg-button-base--type-contained sg-button sg-button--small',
											disabled: !promptText || !promptText.trim() || isProcessingText,
											onClick: function() {
												setCurrentActionPrompt('Make this text longer');
												setTextActionMode('predefined');
												setIsProcessingText(true);
												setAiResult(null);

												wp.apiFetch({
													path: '/sg-ai-studio/gutenberg/edit-text',
													method: 'POST',
													data: {
														text: promptText,
														prompt: 'Make this text longer',
														action_type: 'make-longer',
														nonce: sgAiStudioGutenberg.nonce
													}
												}).then(function(response) {
													if (response.success && response.data) {
														setAiResult(response.data.edited_text);
														setIsProcessingText(false);
													} else {
														console.error('AI text editing failed:', response);
														setIsProcessingText(false);
													}
												}).catch(function(error) {
													console.error('Error calling text edit API:', error);
													setIsProcessingText(false);
												});
											}
										}, __('Make Longer', 'sg-ai-studio'))
								) : null,
										// OR divider (only show when text is not empty)
										(promptText && promptText.trim()) ? createElement('div', {
											className: 'sg-ai-studio-or-divider',
											style: {
												position: 'relative',
												margin: '24px 0',
												textAlign: 'center'
											}
										},
											createElement('div', {
												style: {
													position: 'absolute',
													top: '50%',
													left: 0,
													right: 0,
													height: '1px',
													backgroundColor: '#ddd',
													zIndex: 1
												}
											}),
											createElement('span', {
												style: {
													position: 'relative',
													display: 'inline-block',
													padding: '0 12px',
													backgroundColor: '#fff',
													color: '#757575',
													fontSize: '12px',
													fontWeight: '600',
													letterSpacing: '0.5px',
													zIndex: 2
												}
											}, 'OR')
										) : null,
										// Custom instruction input field and button (always visible)
										createElement('div', {
											className: 'sg-ai-studio-text-section',
											style: { marginBottom: '16px' }
										},
											createElement('label', {
												className: 'components-base-control__label',
												style: { display: 'block', marginBottom: '8px', fontWeight: '600' }
											}, isGenerationMode ? __('What would you like to generate?', 'sg-ai-studio') : __('Describe Your Request', 'sg-ai-studio')),
											createElement(TextareaControl, {
												value: customPrompt,
												onChange: function(value) {
													setCustomPrompt(value);
												},
												placeholder: isGenerationMode
													? __('Describe what content you want to generate...', 'sg-ai-studio')
													: __('Explain in plain words what do you want to change.', 'sg-ai-studio'),
												rows: 4,
												style: {
													width: '100%',
													minHeight: '100px',
													fontSize: '14px'
												},
												disabled: isProcessingText,
												__nextHasNoMarginBottom: true,
												onKeyDown: function(e) {
													// Submit on Cmd+Enter or Ctrl+Enter
													if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) {
														e.preventDefault();
														if (customPrompt.trim() && !isProcessingText) {
															// Trigger the button click
															var btn = document.querySelector('.sg-ai-studio-custom-prompt-button');
															if (btn) btn.click();
														}
													}
												}
											})
										),
										// Submit button for custom prompt
										createElement(Button, {
											className: 'sg-button-base sg-button-base--color-primary sg-button-base--type-filled sg-button sg-button--medium sg-ai-studio-custom-prompt-button',
											isPrimary: true,
											disabled: !customPrompt.trim() || isProcessingText,
											onClick: function() {
												// Set action mode
												setCurrentActionPrompt(customPrompt);
												setTextActionMode('custom');
												setIsProcessingText(true);
												setAiResult(null);

												if (isGenerationMode) {
													// Generate new block content
													wp.apiFetch({
														path: '/sg-ai-studio/gutenberg/generate-block',
														method: 'POST',
														data: {
															prompt: customPrompt,
															post_id: getCurrentPostId(),
															nonce: sgAiStudioGutenberg.nonce
														}
													}).then(function(response) {
														if (response.success && response.data && response.data.block_markup) {
															// Insert the generated blocks
															var blockMarkup = response.data.block_markup;
															// Parse and insert blocks
															var blocks = wp.blocks.parse(blockMarkup);
															var selectedBlockId = select('core/block-editor').getSelectedBlockClientId();

															if (selectedBlockId) {
																// Get the block's position to insert after it
																var blockIndex = select('core/block-editor').getBlockIndex(selectedBlockId);
																var rootClientId = select('core/block-editor').getBlockRootClientId(selectedBlockId);

																// Insert the new blocks at the same position
																dispatch('core/block-editor').insertBlocks(blocks, blockIndex, rootClientId);

																// Remove the selected block (empty placeholder)
																dispatch('core/block-editor').removeBlock(selectedBlockId);
															} else {
																dispatch('core/block-editor').insertBlocks(blocks);
															}

															setIsProcessingText(false);
															setCustomPrompt('');

															// Close sidebar
															dispatch('core/edit-post').closeGeneralSidebar();
														} else {
															console.error('Block generation failed:', response);
															setIsProcessingText(false);
														}
													}).catch(function(error) {
														console.error('Error calling block generation API:', error);
														setIsProcessingText(false);
													});
												} else {
													// Edit existing text with custom prompt
													wp.apiFetch({
														path: '/sg-ai-studio/gutenberg/edit-text',
														method: 'POST',
														data: {
															text: promptText,
															prompt: customPrompt,
															action_type: 'custom',
															nonce: sgAiStudioGutenberg.nonce
														}
													}).then(function(response) {
														if (response.success && response.data) {
															setAiResult(response.data.edited_text);
															setIsProcessingText(false);
														} else {
															console.error('AI text editing failed:', response);
															setIsProcessingText(false);
														}
													}).catch(function(error) {
														console.error('Error calling text edit API:', error);
														setIsProcessingText(false);
													});
												}
											}
										}, isGenerationMode ? __('Generate Block', 'sg-ai-studio') : __('Change With AI', 'sg-ai-studio'))
									);
								})()
							)
						)
					)
					)
				)
		);
	};

	// Register the unified plugin sidebar
	registerPlugin('sg-ai-studio-sidebar', {
		render: SGAIStudioSidebar,
		icon: sgAiStudioIcon
	});

	/**
	 * Add "Generate with AI" button next to block inserter in toolbar
	 */
	var AIGenerateButton = function() {
		var useEffect = wp.element.useEffect;
		var useSelect = wp.data.useSelect;
		var useState = wp.element.useState;

		// Track if we've added the button
		var buttonAddedArray = useState(false);
		var buttonAdded = buttonAddedArray[0];
		var setButtonAdded = buttonAddedArray[1];

		var selectedBlockInfo = useSelect(function(select) {
			var selectedBlockId = select('core/block-editor').getSelectedBlockClientId();
			if (!selectedBlockId) {
				return { isEmpty: false, blockId: null };
			}

			var block = select('core/block-editor').getBlock(selectedBlockId);
			if (!block) {
				return { isEmpty: false, blockId: null };
			}

			// Check if block is empty (paragraph or heading with no content)
			var isEmpty = (block.name === 'core/paragraph' || block.name === 'core/heading') &&
						  (!block.attributes.content || block.attributes.content.trim() === '');

			return {
				blockId: selectedBlockId,
				isEmpty: isEmpty
			};
		}, []);

		useEffect(function() {
			// Find the block inserter button and add our button next to it
			var checkAttempts = 0;
			var MAX_CHECK_ATTEMPTS = 50; // 5 seconds max (50 * 100ms)

			var checkInterval = setInterval(function() {
				checkAttempts++;

				// Stop after max attempts to prevent infinite interval
				if (checkAttempts >= MAX_CHECK_ATTEMPTS) {
					clearInterval(checkInterval);
					return;
				}

				var inserterButton = document.querySelector('.block-editor-inserter__toggle');

				if (inserterButton && !document.querySelector('.sg-ai-generate-toolbar-button')) {
					// Clone the inserter button structure to get exact same styling
					var generateButton = inserterButton.cloneNode(false);
					generateButton.className = 'components-button block-editor-inserter__toggle is-next-40px-default-size has-icon sg-ai-generate-toolbar-button';
					generateButton.setAttribute('aria-label', __('Generate with AI', 'sg-ai-studio'));
					generateButton.removeAttribute('aria-haspopup');
					generateButton.removeAttribute('aria-expanded');

					// Add icon
					var iconSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
					iconSvg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
					iconSvg.setAttribute('viewBox', '0 0 36 36');
					iconSvg.setAttribute('width', '24');
					iconSvg.setAttribute('height', '24');
					iconSvg.setAttribute('aria-hidden', 'true');
					iconSvg.setAttribute('focusable', 'false');

					var path1 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
					path1.setAttribute('fill', 'currentColor');
					path1.setAttribute('d', 'M33.459,34.389c-.332,0-.655-.145-.876-.41l-6.187-7.408H6.946c-.631,0-1.141-.511-1.141-1.141v-8.406c0-.63.511-1.141,1.141-1.141s1.141.511,1.141,1.141v7.265h18.844c.338,0,.659.15.876.41l4.511,5.402V8.188h-12.109c-.631,0-1.141-.511-1.141-1.141s.511-1.141,1.141-1.141h13.25c.631,0,1.141.511,1.141,1.141v26.2c0,.481-.301.909-.753,1.073-.126.046-.258.069-.389.069Z');

					var path2 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
					path2.setAttribute('fill', 'currentColor');
					path2.setAttribute('d', 'M20.397,11.449c-1.523-.376-3.08-.873-4.157-1.951-1.077-1.077-1.574-2.634-1.95-4.156-.061-.247-.283-.421-.538-.421s-.476.174-.538.421c-.376,1.522-.872,3.079-1.95,4.156-1.077,1.077-2.634,1.574-4.156,1.95-.247.061-.421.283-.421.538s.174.476.421.538c1.523.377,3.08.874,4.156,1.95s1.573,2.634,1.951,4.157c.061.247.283.42.538.42h0c.255,0,.476-.174.537-.421.375-1.521.871-3.077,1.95-4.156s2.635-1.575,4.156-1.95c.247-.061.421-.283.421-.538s-.174-.476-.421-.538Z');

					var path3 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
					path3.setAttribute('fill', 'currentColor');
					path3.setAttribute('d', 'M8.691,4.653c-.762-.188-1.54-.437-2.079-.976-.538-.538-.787-1.317-.975-2.079-.031-.124-.141-.21-.269-.21s-.238.087-.269.211c-.188.761-.436,1.54-.975,2.078-.539.538-1.317.787-2.079.975-.124.031-.21.141-.21.269s.087.238.21.269c.762.189,1.54.437,2.078.975s.787,1.317.976,2.079c.031.123.141.21.269.21h0c.127,0,.238-.087.269-.211.187-.761.435-1.539.975-2.078s1.318-.788,2.078-.975c.124-.03.211-.141.211-.269s-.087-.238-.21-.269Z');

					iconSvg.appendChild(path1);
					iconSvg.appendChild(path2);
					iconSvg.appendChild(path3);

					generateButton.appendChild(iconSvg);

					// Add click handler
					generateButton.onclick = function(e) {
						e.preventDefault();
						e.stopPropagation();

						// Get the currently selected block
						var selectedBlock = select('core/block-editor').getSelectedBlock();

						// Only proceed if we have an empty block selected
						if (!selectedBlock || !selectedBlock.clientId) {
							return;
						}

						// Verify the block is actually empty
						var isEmpty = (selectedBlock.name === 'core/paragraph' || selectedBlock.name === 'core/heading') &&
									  (!selectedBlock.attributes.content || selectedBlock.attributes.content.trim() === '');

						if (!isEmpty) {
							return;
						}

						// Use the existing empty block as the placeholder
						var blockId = selectedBlock.clientId;

						// Track this as a toolbar-created placeholder
						window.sgAiStudioPlaceholderBlockId = blockId;
						window.sgAiStudioPlaceholderCreatedTime = Date.now();

						// Set the mode for generation
						window.sgAiStudioSidebarMode = 'text';
						window.sgAiStudioSelectedText = '';
						window.sgAiStudioCurrentTextBlockId = blockId;
						window.sgAiStudioTextAction = 'custom';

						// Dispatch event to update the sidebar
						window.dispatchEvent(new Event('sgAiStudioTextSelected'));

						// Open the sidebar
						dispatch('core/edit-post').openGeneralSidebar('sg-ai-studio-sidebar/sg-ai-studio-sidebar');
					};

					// Insert button directly after the inserter button
					inserterButton.parentNode.insertBefore(generateButton, inserterButton.nextSibling);

					setButtonAdded(true);
					clearInterval(checkInterval);
				}
			}, 100);

			// Update button visibility based on empty block selection
			var updateAttempts = 0;
			var MAX_UPDATE_ATTEMPTS = 500; // 50 seconds max (500 * 100ms) - longer for visibility updates

			var updateInterval = setInterval(function() {
				updateAttempts++;

				// Stop after max attempts to prevent infinite interval
				if (updateAttempts >= MAX_UPDATE_ATTEMPTS) {
					clearInterval(updateInterval);
					return;
				}

				var button = document.querySelector('.sg-ai-generate-toolbar-button');
				if (button) {
					if (selectedBlockInfo.isEmpty) {
						button.style.display = '';
						button.classList.add('is-highlighted');
					} else {
						button.style.display = 'none';
						button.classList.remove('is-highlighted');
					}
				}
			}, 100);

			return function() {
				clearInterval(checkInterval);
				clearInterval(updateInterval);
			};
		}, [selectedBlockInfo.isEmpty, selectedBlockInfo.blockId]);

		return null;
	};

	// Register the AI Generate Button plugin
	registerPlugin('sg-ai-generate-button', {
		render: AIGenerateButton
	});

})(window.wp);
