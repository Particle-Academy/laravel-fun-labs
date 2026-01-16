# Changelog

All notable changes to Laravel Fun Lab will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.3.0] - 2025-01-XX

### Fixed

- **MetricLevelGroup Logic Alignment**
  - Fixed structural misalignment between MetricLevel and MetricLevelGroup patterns
  - MetricLevelGroup now uses ProfileMetricGroup to store level progression state, matching ProfileMetric pattern
  - Group level progression is now stored persistently instead of calculated dynamically

### Added

- **ProfileMetricGroup Model**
  - New model to track level progression for MetricLevelGroups per Profile
  - Stores `current_level` for each profile/group combination
  - Mirrors ProfileMetric structure for consistency
  - Migration: `2024_01_01_000017_create_lfl_profile_metric_groups_table.php`

- **MetricLevelGroupService Enhancements**
  - `checkProgression()` now accepts ProfileMetricGroup and updates stored state
  - `getCurrentLevel()` uses stored ProfileMetricGroup.current_level with fallback
  - Added `getOrCreateProfileMetricGroup()` helper method
  - Automatic group progression checking when XP is awarded to metrics in groups

- **GamedMetricService Integration**
  - Automatically checks group progression after awarding XP to any metric
  - Finds all groups containing the metric and updates their progression

- **Admin UI Updates**
  - MetricLevelGroups view now shows ProfileMetricGroup statistics
  - Displays count of profiles tracking each group

- **Tests**
  - Comprehensive test suite for ProfileMetricGroup model
  - Tests for stored level progression and automatic group checking
  - 5 new test cases in MetricLevelGroupTest.php

### Changed

- **MetricLevelGroupLevel Model**
  - Added `scopeForGroup()` method to match MetricLevel::scopeForMetric() pattern

- **MetricLevelGroup Model**
  - Added `profileMetricGroups()` relationship

## [Unreleased]

### Added

- **Installation Workflow** (Story #6)
  - Created `lfl:install` artisan command for streamlined package installation
  - Interactive table prefix configuration with Laravel Prompts
  - Config publishing with automatic prefix customization
  - Migration running with user confirmation prompt
  - `--ui` flag for optional UI component scaffolding
  - `--force` flag to overwrite existing configuration files
  - `--skip-migrations` flag to skip database migrations
  - `--prefix` option for non-interactive table prefix setting
  - Beautiful welcome banner and success message with quick start guide
  - Non-interactive mode detection for CI/CD environments and testing
  - 13 feature tests covering all command functionality

- **Config System** (Story #5)
  - Enhanced `config/lfl.php` with default point values configuration
  - Added `defaults.points` for global default point amount (default: 10)
  - Added `defaults.multipliers` for streak_bonus (1.5x) and first_time_bonus (2.0x)
  - Added `default_amount` to each award type in `award_types` config
  - Implemented feature flag helper methods via LFL facade:
    - `LFL::isFeatureEnabled($feature)` - Check if specific feature is enabled
    - `LFL::getEnabledFeatures()` - Get all enabled features
    - `LFL::getTablePrefix()` - Get configured table prefix
    - `LFL::getDefaultPoints()` - Get default points amount
    - `LFL::getMultiplier($name)` - Get multiplier value from config
    - `LFL::isEventLoggingEnabled()` - Check event logging status
    - `LFL::isEventDispatchEnabled()` - Check event dispatch status
    - `LFL::isApiEnabled()` / `LFL::getApiPrefix()` - API configuration helpers
    - `LFL::isUiEnabled()` - UI layer status check
  - AwardBuilder now uses config defaults when no amount is explicitly specified
  - 20 comprehensive unit tests for configuration system
  - All acceptance criteria met: publishable config, table prefix, feature flags, default values, event toggles, API prefix

- **Event Pipeline** (Story #4)
  - Created type-specific events: `PointsAwarded`, `AchievementUnlocked`, `PrizeAwarded`, `BadgeAwarded`
  - All events implement `LflEvent` contract with consistent interface
  - Events contain full context: recipient, award/grant, amount, reason, source, metadata
  - `PointsAwarded` includes `previousTotal` and `newTotal` for tracking
  - `AchievementUnlocked` includes achievement model and grant record
  - Created `EventLog` model for analytics and auditing
  - EventLog captures: event_type, award_type, awardable, amount, reason, source, context JSON
  - Strategic indexes on event_type, award_type, source, occurred_at for query performance
  - Scopes: `ofEventType()`, `ofAwardType()`, `forAwardable()`, `fromSource()`, `recent()`, `between()`
  - `EventLogSubscriber` auto-logs events when `lfl.events.log_to_database` is enabled
  - Both generic (`AwardGranted`) and specific events dispatched for flexibility
  - 25 tests covering all event types and EventLog functionality

- **Dynamic Achievement Setup** (Story #3)
  - Implemented `LFL::setup()` method for runtime achievement definition
  - Named parameters API: `an` (slug), `for` (awardable type), `name`, `description`, `icon`, `metadata`, `active`, `order`
  - Upsert logic: creates new achievements or updates existing ones by slug
  - Automatic slug generation from achievement name with `Str::slug()`
  - Human-readable name generation from slug with `Str::headline()`
  - Awardable type normalization: short class names (e.g., 'User') resolved to FQCN when class exists
  - Flexible JSON metadata storage for custom attributes
  - 29 feature tests covering creation, upsert, metadata, type handling, and edge cases

- **Award Engine Workflow** (Story #2)
  - Implemented unified award API: `LFL::award(type)->to($recipient)->for('reason')->from('source')->amount(n)->grant()`
  - Created `AwardBuilder` fluent builder for expressive award operations
  - Created `AwardResult` value object encapsulating success/failure status, errors, and metadata
  - Created `AwardType` enum with `Points`, `Achievement`, `Prize`, `Badge` types
  - Shorthand methods: `awardPoints()`, `grantAchievement()`, `awardPrize()`, `awardBadge()`
  - Event dispatching: `AwardGranted` and `AwardFailed` events with configurable toggle
  - Points accumulation with previous/new total tracking in result meta
  - Achievement validation: active status, duplicate prevention, slug resolution
  - Recipient validation: ensures model uses `Awardable` trait
  - 28 feature tests covering all award operations, validation, and events

- **Core Models & Awardables** (Story #1)
  - Created `Awardable` trait for Eloquent models with relationships and helper methods
  - Created `Award` model for point grants with polymorphic relationships
  - Created `Achievement` model for achievement definitions with metadata
  - Created `AchievementGrant` model for tracking awarded achievements
  - Created 3 migrations with configurable table prefix support (`lfl_awards`, `lfl_achievements`, `lfl_achievement_grants`)
  - Added scopes: `ofType()`, `forAwardableType()`, `fromSource()`, `active()`, `ordered()`
  - Helper methods: `getTotalPoints()`, `hasAchievement()`, `getAchievements()`, `getAwardCount()`, `getRecentAwards()`, `getRecentAchievements()`
  - 35 unit tests covering all models and trait functionality

- **Package Development Setup** (Story #15)
  - Created package directory structure at `packages/laravel-fun-lab/`
  - Set up PSR-4 autoloading for `LaravelFunLab\` namespace
  - Created `LFLServiceProvider` with publishable config, migrations, routes, and views
  - Created `LFL` facade resolving to `AwardEngine` service
  - Created `config/lfl.php` with table_prefix, feature flags, API/UI configuration
  - Created `AwardEngine` service with placeholder methods: `award()`, `setup()`, `profile()`, `leaderboard()`
  - Created `routes/api.php` stub for future API routes

