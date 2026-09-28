<?php

declare(strict_types=1);

namespace App\Livewire\Backend;

use App\Models\User;
use Livewire\Component;

class UserSearch extends Component
{
    public string $search = '';

    public ?int $selectedUserId = null;

    public ?array $selectedUser = null;

    public string $fieldName = 'uploaded_by';

    public int $minimumSearchLength = 2;

    public function mount(?int $selectedUserId = null, string $fieldName = 'uploaded_by', int $minimumSearchLength = 2): void
    {
        $this->fieldName = $fieldName;
        $this->minimumSearchLength = $minimumSearchLength;

        if ($selectedUserId) {
            $this->selectedUserId = $selectedUserId;
            $user = User::find($selectedUserId);
            if ($user) {
                $this->selectedUser = [
                    'id' => $user->id,
                    'name' => $user->last_name.', '.$user->first_name,
                    'email' => $user->email,
                ];
            }
        }
    }

    public function render()
    {
        $results = collect();
        $search = trim($this->search);
        $isUserId = ctype_digit($search);

        if ($isUserId || mb_strlen($search) >= $this->minimumSearchLength) {
            $results = User::where(function ($query) use ($search, $isUserId): void {
                $query->where('last_name', 'ilike', '%'.$search.'%')
                    ->orWhere('first_name', 'ilike', '%'.$search.'%')
                    ->orWhere('email', 'ilike', '%'.$search.'%');

                if ($isUserId) {
                    $query->orWhere('id', (int) $search);
                }
            })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(20)
                ->get();
        }

        return view('livewire.backend.user-search', [
            'results' => $results,
            'showResults' => $isUserId || mb_strlen($search) >= $this->minimumSearchLength,
        ]);
    }

    public function selectUser(int $userId): void
    {
        $user = User::find($userId);
        if ($user) {
            $this->selectedUserId = $user->id;
            $this->selectedUser = [
                'id' => $user->id,
                'name' => $user->last_name.', '.$user->first_name,
                'email' => $user->email,
            ];
            $this->search = '';
        }
    }

    public function clearUser(): void
    {
        $this->selectedUserId = null;
        $this->selectedUser = null;
        $this->search = '';
    }
}
