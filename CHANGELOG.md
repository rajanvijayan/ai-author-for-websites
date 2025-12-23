# Changelog

All notable changes to the AI Author for Websites plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2024-12-23

### Added
- Yoast SEO integration for AI-generated SEO metadata
  - Auto-generate focus keyphrase, meta description, and SEO title
  - Settings page with enable/disable toggle and configuration options
  - Test generation feature to preview SEO suggestions
  - Activity logging for debugging
- Rank Math SEO integration for AI-generated SEO metadata
  - Auto-generate focus keyword, secondary keywords, meta description, and SEO title
  - Settings page with enable/disable toggle and configuration options
  - Support for secondary keywords (Rank Math Pro feature)
  - Test generation feature to preview SEO suggestions
  - Activity logging for debugging
- SEO section in Generate Post page
  - Focus keyword input field
  - SEO title field with character counter (60 char limit)
  - Meta description field with character counter (155 char limit)
  - Live Google SERP preview
  - One-click "Generate SEO" button for AI-powered suggestions
- REST API endpoints for SEO data generation and application
- SEO data is automatically saved when publishing posts
- Detection of active SEO plugins (Yoast SEO or Rank Math)

### Changed
- Updated REST API to accept and apply SEO metadata when saving posts

## [1.0.0] - 2024-12-23

### Added
- Initial release of AI Author for Websites plugin
- AI-powered blog post generation with Groq, Gemini, and Meta Llama support
- Knowledge Base management for training AI with custom content
  - Add URLs from your website
  - Upload documents (PDF, DOC, TXT)
  - Add custom text entries
- Admin settings page for API configuration
- REST API endpoints for content generation
- Auto-scheduling feature for automated post creation
- Pixabay integration for featured images
- Twitter/X integration for social sharing
- Facebook integration for social sharing
- GitHub-based automatic update system
- WordPress Coding Standards compliance

### Security
- Nonce verification for REST API requests
- Sanitization of user inputs
- Capability checks for admin operations

[Unreleased]: https://github.com/rajanvijayan/ai-author-for-websites/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/rajanvijayan/ai-author-for-websites/releases/tag/v1.0.0

