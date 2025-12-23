# AI Author for Websites - Brand Guidelines

This document defines the brand guidelines for AI Author for Websites plugin. All code, UI, and documentation should follow these guidelines for consistency.

## Brand Identity

### Plugin Name
- **Full Name:** AI Author for Websites
- **Short Name:** AI Author
- **Slug:** `ai-author-for-websites`
- **Text Domain:** `ai-author-for-websites`
- **Prefix:** `aiauthor_` (for functions, options, hooks)
- **Class Prefix:** `AIAuthor\` (namespace)
- **CSS Prefix:** `aiauthor-`

### Tagline
> AI-powered blog post generator. Train the AI with your knowledge base and create high-quality content.

## Color Palette

### Primary Colors
| Name | Hex | CSS Variable | Usage |
|------|-----|--------------|-------|
| Primary Blue | `#0073aa` | `--aiauthor-primary` | Primary actions, links, accents |
| Primary Dark | `#005a87` | `--aiauthor-primary-dark` | Hover states, emphasis |

### Semantic Colors
| Name | Hex | CSS Variable | Usage |
|------|-----|--------------|-------|
| Success Green | `#23a455` | `--aiauthor-success` | Success states, enabled toggles |
| Warning Yellow | `#f1c40f` | `--aiauthor-warning` | Warnings, AI suggestions |
| Danger Red | `#dc3232` | `--aiauthor-danger` | Errors, destructive actions |
| Info Blue | `#3498db` | `--aiauthor-info` | Information, notifications |

### Neutral Colors
| Name | Hex | CSS Variable | Usage |
|------|-----|--------------|-------|
| Text | `#1e1e1e` | `--aiauthor-text` | Primary text |
| Text Light | `#50575e` | `--aiauthor-text-light` | Secondary text, descriptions |
| Border | `#dcdcde` | `--aiauthor-border` | Borders, dividers |
| Background | `#f6f7f7` | `--aiauthor-bg` | Page background, subtle sections |
| Card Background | `#ffffff` | `--aiauthor-card-bg` | Cards, modals |

## Typography

### Font Stack
```css
font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
```

This follows the WordPress admin default font stack for consistency.

### Font Sizes
| Element | Size | Weight |
|---------|------|--------|
| Page Title (h1) | 28px | 600 |
| Card Title (h2) | 18px | 600 |
| Section Title (h3) | 15px | 600 |
| Body Text | 14px | 400 |
| Small Text | 13px | 400 |
| Micro Text | 12px | 400 |
| Labels | 14px | 600 |

## Spacing

### Base Unit
- Base spacing unit: `4px`
- Use multiples: `8px`, `12px`, `16px`, `20px`, `24px`, `32px`

### Component Spacing
| Element | Padding |
|---------|---------|
| Card | 24px |
| Button | 8px 16px |
| Button Hero | 12px 28px |
| Form Row | margin-bottom: 16px |
| Section Gap | 20px |

## Border Radius

| Element | Radius | CSS Variable |
|---------|--------|--------------|
| Cards | 8px | `--aiauthor-radius` |
| Buttons | 4px | - |
| Inputs | 4px | - |
| Tags/Badges | 12px-20px | - |
| Toggle Switch | 24px | - |

## Shadows

```css
--aiauthor-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
```

Use subtle shadows for elevation. Avoid heavy drop shadows.

## Components

### Buttons

**Primary Button:**
```css
.button.button-primary {
    background: var(--aiauthor-primary);
    border-color: var(--aiauthor-primary);
    color: #fff;
}
```

**Buttons with Icons:**
- Always use `aiauthor-btn-with-icon` class
- Icon and text should be vertically centered
- Gap between icon and text: 8px
- Icon size: 16px (18px for hero buttons)

```html
<button class="button button-primary aiauthor-btn-with-icon">
    <span class="dashicons dashicons-edit"></span>
    <span class="btn-text">Button Text</span>
</button>
```

### Cards

All content sections should use the card pattern:
```html
<div class="aiauthor-card">
    <h2>
        <span class="dashicons dashicons-admin-settings"></span>
        Card Title
    </h2>
    <!-- Card content -->
</div>
```

### Toggle Switches

Use for boolean settings:
```html
<label class="aiauthor-switch">
    <input type="checkbox" name="setting" value="1">
    <span class="slider"></span>
</label>
```

### Badges

```html
<span class="aiauthor-badge">Default</span>
<span class="aiauthor-badge aiauthor-badge-active">Active</span>
<span class="aiauthor-badge aiauthor-badge-builtin">Built-in</span>
```

## Icons

Use WordPress Dashicons for all icons. Common icons used:

| Purpose | Dashicon |
|---------|----------|
| Settings | `dashicons-admin-settings` |
| Generate | `dashicons-admin-generic` |
| Edit | `dashicons-edit` |
| Save | `dashicons-saved` |
| Knowledge | `dashicons-book-alt` |
| AI/Magic | `dashicons-lightbulb` |
| Calendar | `dashicons-calendar-alt` |
| Integrations | `dashicons-admin-plugins` |
| Success | `dashicons-yes-alt` |
| Error | `dashicons-warning` |
| Info | `dashicons-info` |
| Close | `dashicons-no-alt` |

## Naming Conventions

### PHP

```php
// Functions: snake_case with prefix
function aiauthor_get_settings() {}

// Classes: PascalCase with namespace
namespace AIAuthor\Core;
class Plugin {}

// Options: snake_case with prefix
get_option( 'aiauthor_settings' );

// Hooks: snake_case with prefix
do_action( 'aiauthor_after_generate' );
add_filter( 'aiauthor_prompt_template', $callback );

// Constants: SCREAMING_SNAKE_CASE with prefix
define( 'AIAUTHOR_VERSION', '1.0.0' );
```

### CSS

```css
/* Classes: kebab-case with prefix */
.aiauthor-card {}
.aiauthor-btn-with-icon {}
.aiauthor-form-row {}

/* Modifiers: use double dash */
.aiauthor-badge--active {}
.aiauthor-card--highlighted {}

/* States: use is- prefix */
.aiauthor-integration-card.is-enabled {}
```

### JavaScript

```javascript
// Variables/Functions: camelCase
const aiauthorAdmin = {};
function generatePost() {}

// Constants: SCREAMING_SNAKE_CASE
const MAX_WORD_COUNT = 5000;

// jQuery selectors: match CSS class names
$('#aiauthor-generate-btn');
$('.aiauthor-card');
```

## Accessibility

1. **Color Contrast:** Maintain WCAG AA contrast ratios
   - Normal text: 4.5:1 minimum
   - Large text: 3:1 minimum

2. **Focus States:** All interactive elements must have visible focus states

3. **Labels:** Form inputs must have associated labels

4. **ARIA:** Use ARIA attributes when semantic HTML is insufficient

5. **Keyboard Navigation:** All functionality accessible via keyboard

## Responsive Design

### Breakpoints
| Name | Width | Usage |
|------|-------|-------|
| Mobile | ≤ 782px | WordPress mobile breakpoint |
| Tablet | 783px - 1200px | Medium screens |
| Desktop | > 1200px | Full layout |

### Grid Behavior
- Two-column layouts collapse to single column below 1200px
- Cards stack vertically on mobile
- Navigation tabs wrap on smaller screens

## Voice & Tone

### UI Copy
- Clear and concise
- Action-oriented
- Friendly but professional
- Avoid jargon when possible

### Examples

**Good:**
- "Generate Post" (not "Initiate Content Generation Process")
- "API Key Required" (not "Authentication Token Missing")
- "Something went wrong" (not "An unexpected error has occurred")

**Descriptions:**
- Keep descriptions to 1-2 sentences
- Use helper text for complex settings
- Provide actionable guidance

## File Structure

```
ai-author-for-websites/
├── ai-author-for-websites.php    # Main plugin file
├── includes/
│   ├── Admin/                    # Admin-related classes
│   ├── API/                      # REST API classes
│   ├── Core/                     # Core plugin classes
│   ├── Knowledge/                # Knowledge base classes
│   ├── Integrations/             # Integrations framework
│   └── Views/                    # PHP view templates
├── assets/
│   ├── css/
│   │   └── admin.css            # Admin styles
│   └── js/
│       └── admin.js             # Admin scripts
├── tests/                        # PHPUnit tests
├── vendor/                       # Composer dependencies
└── languages/                    # Translation files
```

## Changelog

- **v1.0.0** - Initial brand guidelines

