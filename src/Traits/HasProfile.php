<?php

declare(strict_types=1);

namespace LaravelFunLab\Traits;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use LaravelFunLab\Models\Profile;

/**
 * HasProfile Trait
 *
 * Apply this trait to any Eloquent model that uses the Awardable trait
 * to enable profile management with opt-in/opt-out logic.
 *
 * Note: This trait is automatically included when using the Awardable trait.
 * You only need to use this trait directly if you want profile functionality
 * without the full Awardable trait.
 *
 * @property-read Profile|null $profile
 */
trait HasProfile
{
    /**
     * Get the engagement profile for this model.
     *
     * @return MorphOne<Profile, $this>
     */
    public function profile(): MorphOne
    {
        return $this->morphOne(Profile::class, 'awardable');
    }

    /**
     * Get or create the engagement profile for this model.
     */
    public function getProfile(): Profile
    {
        return $this->profile()->firstOrCreate(
            [],
            [
                'is_opted_in' => true,
                'total_xp' => 0,
                'achievement_count' => 0,
                'prize_count' => 0,
            ]
        );
    }

    /**
     * Check if this model has a profile.
     */
    public function hasProfile(): bool
    {
        return $this->profile()->exists();
    }

    /**
     * Check if this model is opted in to gamification features.
     */
    public function isOptedIn(): bool
    {
        $profile = $this->profile;

        if ($profile === null) {
            // Default to opted in if no profile exists
            return true;
        }

        return $profile->isOptedIn();
    }

    /**
     * Check if this model is opted out of gamification features.
     */
    public function isOptedOut(): bool
    {
        return ! $this->isOptedIn();
    }

    /**
     * Opt in to gamification features.
     */
    public function optIn(): bool
    {
        $profile = $this->getProfile();
        $profile->optIn();

        // Reload the relationship to ensure it's fresh
        $this->load('profile');

        return true;
    }

    /**
     * Opt out of gamification features.
     */
    public function optOut(): bool
    {
        $profile = $this->profile;

        if ($profile === null) {
            // Create profile with opt-out status directly
            $profile = $this->profile()->create([
                'is_opted_in' => false,
                'total_xp' => 0,
                'achievement_count' => 0,
                'prize_count' => 0,
            ]);
        } else {
            $profile->optOut();
        }

        // Reload the relationship to ensure it's fresh
        $this->load('profile');

        return true;
    }
}
