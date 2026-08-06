<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Invitation;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        $this->applyPendingInvitations($user);

        return $user;
    }

    /**
     * Accept any pending team invitations sent to the user's email.
     */
    private function applyPendingInvitations(User $user): void
    {
        $invitations = Invitation::query()
            ->where('email', $user->email)
            ->whereNull('accepted_at')
            ->get();

        foreach ($invitations as $invitation) {
            $team = $invitation->team;

            TeamMember::firstOrCreate(
                ['team_id' => $team->id, 'user_id' => $user->id],
                [
                    'role' => $invitation->role,
                    'invited_by' => $team->owner_user_id,
                    'accepted_at' => now(),
                ],
            );

            $invitation->update(['accepted_at' => now()]);
        }
    }
}
